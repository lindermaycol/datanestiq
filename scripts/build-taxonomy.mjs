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
const validRoleIds = new Set(personas ? personas.roles.map(r => r.id) : []);
sectors.forEach(sec => {
    if (sec.relevantPersonas) {
        sec.relevantPersonas.forEach(p => {
            if (personas && !personas.roles.find(role => role.id === p)) {
                console.error(`Referential Error in Sector '${sec.id}': relevantPersona '${p}' does not exist.`);
                process.exit(1);
            }
        });
    }
    if (sec.roleEquivalents) {
        Object.keys(sec.roleEquivalents).forEach(roleId => {
            if (!validRoleIds.has(roleId)) {
                console.error(`Referential Error in Sector '${sec.id}': roleEquivalents key '${roleId}' is not a valid role in personas.json.`);
                process.exit(1);
            }
            if (typeof sec.roleEquivalents[roleId] !== 'string' || !sec.roleEquivalents[roleId].trim()) {
                console.error(`Referential Error in Sector '${sec.id}': roleEquivalents value for '${roleId}' must be a non-empty string.`);
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
writeGeneratedJson('personasCorpus.json', personas);
writeGeneratedJson('contentAngles.json', contentAngles);

// 5. Build Taxonomy Graph SSOT (Spec 025 GraphRAG)
console.log('Building Taxonomy Graph SSOT (src/data/taxonomyGraph.json)...');

const graphNodes = {};
const graphEdges = [];

const addNode = (id, type, label, url, attributes) => {
    graphNodes[id] = {
        id,
        type,
        label,
        url,
        attributes: attributes || {}
    };
};

const addEdge = (source, target, relation, weight = 1.0, metadata = {}) => {
    graphEdges.push({ source, target, relation, weight, metadata });
};

// Nodos Pilares (/soluciones/{slug})
pillars.forEach(p => {
    const nodeId = `pillar-${p.slug}`;
    addNode(nodeId, 'pillar', p.name, `/soluciones/${p.slug}`, {
        slug: p.slug,
        description: p.seo?.description || p.contrast?.problem || '',
        features: p.features || [],
        techStack: p.techStack || [],
        proofPoints: p.proofPoints || [],
        targetRoles: p.targetRoles || []
    });
});

// Nodos Sectores (/sectores/{slug})
sectors.forEach(sec => {
    const nodeId = `sector-${sec.slug}`;
    addNode(nodeId, 'sector', sec.title, `/sectores/${sec.slug}`, {
        slug: sec.slug,
        description: sec.seo?.description || '',
        problems: sec.problems || [],
        kpis: sec.kpis || [],
        roiCases: sec.roiCases || [],
        regulations: sec.regulations || [],
        roleEquivalents: sec.roleEquivalents || {}
    });
});

// Nodos Personas / Roles
if (personas && personas.roles) {
    personas.roles.forEach(role => {
        const nodeId = `persona-${role.id}`;
        addNode(nodeId, 'persona', role.title, undefined, {
            description: role.gains || role.pains?.join('. ') || '',
            goals: role.goals || [],
            pains: role.pains || [],
            objections: role.objections || [],
            objectionResponses: role.objectionResponses || [],
            decisionCriteria: role.decisionCriteria || []
        });

        // Aristas: role --pillarsOfInterest--> pillar
        (role.pillarsOfInterest || []).forEach(pSlug => {
            const targetId = `pillar-${pSlug}`;
            if (!graphNodes[targetId]) {
                console.error(`Graph Referential Error: Role '${role.id}' references unknown pillar '${pSlug}'.`);
                process.exit(1);
            }
            addEdge(nodeId, targetId, 'pillarsOfInterest');
        });

        // Aristas: role --relevantSectors--> sector
        (role.relevantSectors || []).forEach(secId => {
            const secObj = sectors.find(s => s.id === secId);
            if (!secObj) {
                console.error(`Graph Referential Error: Role '${role.id}' references unknown sector '${secId}'.`);
                process.exit(1);
            }
            addEdge(nodeId, `sector-${secObj.slug}`, 'relevantSectors');
        });
    });
}

// Nodos Industrias CIIU
industries.forEach(ind => {
    const nodeId = `industry-${ind.id}`;
    addNode(nodeId, 'industry', ind.title || ind.id, undefined, {
        ciiu: ind.ciiu
    });

    // Aristas: industry --relatedPillars--> pillar
    (ind.relatedPillars || []).forEach(pSlug => {
        const targetId = `pillar-${pSlug}`;
        if (!graphNodes[targetId]) {
            console.error(`Graph Referential Error: Industry '${ind.id}' references unknown pillar '${pSlug}'.`);
            process.exit(1);
        }
        addEdge(nodeId, targetId, 'relatedPillars');
    });
});

// Aristas de Sectores -> Personas y RoleEquivalents
sectors.forEach(sec => {
    const secNodeId = `sector-${sec.slug}`;
    (sec.relevantPersonas || []).forEach(roleId => {
        const roleNodeId = `persona-${roleId}`;
        if (!graphNodes[roleNodeId]) {
            console.error(`Graph Referential Error: Sector '${sec.id}' references unknown persona '${roleId}'.`);
            process.exit(1);
        }
        addEdge(secNodeId, roleNodeId, 'relevantPersonas');
    });

    if (sec.roleEquivalents) {
        Object.keys(sec.roleEquivalents).forEach(roleId => {
            const roleNodeId = `persona-${roleId}`;
            if (!graphNodes[roleNodeId]) {
                console.error(`Graph Referential Error: Sector '${sec.id}' roleEquivalent '${roleId}' does not exist.`);
                process.exit(1);
            }
            addEdge(secNodeId, roleNodeId, 'roleEquivalent', 1.0, { localTitle: sec.roleEquivalents[roleId] });
        });
    }
});

// Validar que no queden aristas huérfanas
graphEdges.forEach(e => {
    if (!graphNodes[e.source] || !graphNodes[e.target]) {
        console.error(`Graph Integrity Error: Broken edge ${e.source} -> ${e.target} (${e.relation}).`);
        process.exit(1);
    }
});

const taxonomyGraphSSOT = {
    version: '1.0.0',
    generatedAt: new Date().toISOString(),
    stats: {
        totalNodes: Object.keys(graphNodes).length,
        totalEdges: graphEdges.length,
        nodesByType: Object.values(graphNodes).reduce((acc, n) => {
            acc[n.type] = (acc[n.type] || 0) + 1;
            return acc;
        }, {})
    },
    nodes: graphNodes,
    edges: graphEdges
};

writeGeneratedJson('taxonomyGraph.json', taxonomyGraphSSOT);
console.log(`Generated taxonomyGraph.json: ${taxonomyGraphSSOT.stats.totalNodes} nodes, ${taxonomyGraphSSOT.stats.totalEdges} edges.`);

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
