import fs from 'fs';
import path from 'path';

const BLOG_DIR = path.resolve('src/content/blog');
const OUTPUT_FILE = path.resolve('src/data/blogCorpus.json');

function parseFrontmatter(fileContent) {
    const frontmatterRegex = /^---\s*[\r\n]+([\s\S]*?)[\r\n]+---\s*[\r\n]+([\s\S]*)$/;
    const match = fileContent.match(frontmatterRegex);

    if (!match) {
        return { data: {}, content: fileContent };
    }

    const rawYaml = match[1];
    const content = match[2];
    const data = {};

    let currentArrayKey = null;

    rawYaml.split(/\r?\n/).forEach(line => {
        const trimmed = line.strip ? line.strip() : line.trim();
        if (!trimmed || trimmed.startsWith('#')) return;

        if (trimmed.startsWith('- ') && currentArrayKey) {
            const item = trimmed.substring(2).replace(/^['"]|['"]$/g, '').trim();
            if (!Array.isArray(data[currentArrayKey])) {
                data[currentArrayKey] = [];
            }
            data[currentArrayKey].push(item);
            return;
        }

        const colonIdx = trimmed.indexOf(':');
        if (colonIdx !== -1) {
            const key = trimmed.substring(0, colonIdx).trim();
            const val = trimmed.substring(colonIdx + 1).trim().replace(/^['"]|['"]$/g, '');

            if (val === '' || val.startsWith('[')) {
                if (val.startsWith('[')) {
                    try {
                        data[key] = JSON.parse(val.replace(/'/g, '"'));
                    } catch (e) {
                        data[key] = [];
                    }
                } else {
                    currentArrayKey = key;
                    data[key] = [];
                }
            } else {
                currentArrayKey = null;
                data[key] = val;
            }
        }
    });

    return { data, content };
}

function buildBlogCorpus() {
    console.log('📦 Generando SSOT del corpus del blog desde src/content/blog/...');

    if (!fs.existsSync(BLOG_DIR)) {
        console.error(`❌ El directorio ${BLOG_DIR} no existe.`);
        process.exit(1);
    }

    const files = fs.readdirSync(BLOG_DIR).filter(f => f.endsWith('.md') || f.endsWith('.mdx'));
    const corpus = [];

    for (const file of files) {
        const filePath = path.join(BLOG_DIR, file);
        const fileContent = fs.readFileSync(filePath, 'utf-8');
        const { data, content } = parseFrontmatter(fileContent);

        const slug = file.replace(/\.mdx?$/, '');
        const title = data.title || slug;
        const excerpt = data.description || data.excerpt || '';
        const tags = Array.isArray(data.tags) ? data.tags : [];
        const publishDate = data.publishDate ? new Date(data.publishDate).toISOString() : new Date().toISOString();

        // Extraer los primeros 300 caracteres del cuerpo limpiando Markdown/HTML
        const cleanBody = content
            .replace(/```[\s\S]*?```/g, '') // Eliminar bloques de código
            .replace(/#+\s+/g, '')          // Eliminar encabezados
            .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1') // Reemplazar links por texto
            .replace(/[*_~`]/g, '')        // Eliminar formato
            .replace(/\s+/g, ' ')          // Normalizar espacios
            .trim();

        const body_head = cleanBody.substring(0, 300);

        corpus.push({
            slug,
            title,
            excerpt,
            tags,
            url: `/blog/${slug}`,
            publishDate,
            body_head,
            text: `${title} ${excerpt} ${tags.join(' ')} ${body_head}`.trim()
        });
    }

    fs.writeFileSync(OUTPUT_FILE, JSON.stringify(corpus, null, 2), 'utf-8');
    console.log(`✅ [SUCCESS] Generado ${OUTPUT_FILE} con ${corpus.length} publicaciones del blog.`);
}

buildBlogCorpus();
