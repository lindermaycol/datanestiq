import fs from 'fs';
import path from 'path';
import { load } from 'js-yaml';
import { fileURLToPath } from 'url';
import { pillarSchema, sectorSchema, industrySchema, personaSchema } from '../src/lib/schemas.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const contentDir = path.join(__dirname, '../src/content');
const dataDir = path.join(__dirname, '../src/data');

const loadYamlDir = (dir, schema) => {
    const fullPath = path.join(contentDir, dir);
    if (!fs.existsSync(fullPath)) return [];
    
    const files = fs.readdirSync(fullPath).filter(f => f.endsWith('.yaml'));
    const items = [];
    
    for (const file of files) {
        const content = fs.readFileSync(path.join(fullPath, file), 'utf8');
        try {
            const data = load(content);
            const parsed = schema.parse(data);
            items.push(parsed);
        } catch (err) {
            console.error(`Error validating ${dir}/${file}:`, err);
            process.exit(1);
        }
    }
    return items;
};

console.log('Building taxonomy JSONs from YAML collections...');

// 1. Read and validate YAMLs
const pillars = loadYamlDir('pillars', pillarSchema);
const sectors = loadYamlDir('sectors', sectorSchema);
const industries = loadYamlDir('industries', industrySchema);

// 2. Read and validate Personas (JSON)
const personasPath = path.join(dataDir, 'personas.json');
let personas = null;
if (fs.existsSync(personasPath)) {
    try {
        const data = JSON.parse(fs.readFileSync(personasPath, 'utf8'));
        personas = personaSchema.parse(data);
    } catch (err) {
        console.error('Error validating personas.json:', err);
        process.exit(1);
    }
}

// 3. Referential Integrity Check
const pillarSlugs = new Set(pillars.map(p => p.slug));

// Check industries
industries.forEach(ind => {
    ind.relatedPillars.forEach(p => {
        if (!pillarSlugs.has(p)) {
            console.error(`Referential Error in Industry '${ind.id}': relatedPillar '${p}' does not exist.`);
            process.exit(1);
        }
    });
});

// Check personas
if (personas) {
    personas.roles.forEach(role => {
        role.pillarsOfInterest.forEach(p => {
            if (!pillarSlugs.has(p)) {
                console.error(`Referential Error in Persona Role '${role.id}': pillarOfInterest '${p}' does not exist.`);
                process.exit(1);
            }
        });
        if (role.relevantSectors) {
            role.relevantSectors.forEach(s => {
                if (!sectors.find(sec => sec.id === s)) {
                    console.error(`Referential Error in Persona Role '${role.id}': relevantSector '${s}' does not exist.`);
                    process.exit(1);
                }
            });
        }
    });
}

// Check sectors
sectors.forEach(sec => {
    if (sec.relevantPersonas) {
        sec.relevantPersonas.forEach(p => {
            if (personas && !personas.roles.find(role => role.id === p)) {
                console.error(`Referential Error in Sector '${sec.id}': relevantPersona '${p}' does not exist.`);
                process.exit(1);
            }
        });
    }
});

// Extract Content Angles and strip from output to reduce client bundle
const contentAngles = [];

const extractAngles = (sourceArray, sourceType) => {
    sourceArray.forEach(item => {
        if (item.contentAngles) {
            item.contentAngles.forEach(angle => {
                contentAngles.push({ ...angle, sourceType, sourceId: item.id });
            });
            delete item.contentAngles;
        }
    });
};

extractAngles(pillars, 'pillar');
extractAngles(sectors, 'sector');
if (personas && personas.roles) {
    extractAngles(personas.roles, 'persona');
}

// 4. Write Generated JSONs
const writeGeneratedJson = (filename, data) => {
    const outPath = path.join(dataDir, filename);
    fs.writeFileSync(outPath, JSON.stringify(data, null, 2));
    console.log(`Generated ${filename}`);
};

writeGeneratedJson('taxonomyCorpus.json', pillars);
writeGeneratedJson('sectorsCorpus.json', sectors);
writeGeneratedJson('extendedIndustries.json', industries);
writeGeneratedJson('contentAngles.json', contentAngles);

// Generate public/api/services.json for chatbot grounding
const chatbotServices = pillars.map(p => ({
    name: p.name,
    slug: p.slug,
    url: `/soluciones/${p.slug}`,
    descripcion: p.seo?.description || '',
    en_que_consiste: p.contrast?.solution || '',
    features: p.features || []
}));
const apiDir = path.join(__dirname, '../public/api');
if (!fs.existsSync(apiDir)) {
    fs.mkdirSync(apiDir, { recursive: true });
}
fs.writeFileSync(path.join(apiDir, 'services.json'), JSON.stringify(chatbotServices, null, 2));
console.log('Generated services.json');

console.log('Taxonomy build complete. All integrity checks passed.');
