import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.resolve(__dirname, '..');

// --- 1. CONFIG & ENV ---
function loadEnv() {
  const envPath = path.join(ROOT_DIR, '.env');
  if (fs.existsSync(envPath)) {
    const lines = fs.readFileSync(envPath, 'utf-8').split('\n');
    for (const line of lines) {
      if (line.trim().startsWith('#') || !line.trim()) continue;
      const [key, ...rest] = line.split('=');
      if (key && rest.length > 0) {
        process.env[key.trim()] = rest.join('=').trim().replace(/^['"]|['"]$/g, '');
      }
    }
  }
}
loadEnv();

const args = process.argv.slice(2);
const DRY_RUN = args.includes('--dry-run');
const IS_SEED = args.includes('--seed') || args.includes('--all');
const targetArg = args.find(a => a.startsWith('--target='));
const TARGET = targetArg ? targetArg.split('=')[1] : null;
const sinceArg = args.find(a => a.startsWith('--since='));
const SINCE_REF = sinceArg ? sinceArg.split('=')[1] : 'HEAD~1..HEAD';
const briefArg = args.find(a => a.startsWith('--brief='));
const BRIEF = briefArg ? briefArg.split('=')[1] : '';
const slugArg = args.find(a => a.startsWith('--slug='));
const SLUG_OVR = slugArg ? slugArg.split('=')[1] : '';

const isAllTarget = TARGET === 'all' || args.includes('--all');

if (!isAllTarget && (!TARGET || !['wiki', 'agents', 'skills', 'blog', 'page'].includes(TARGET))) {
  console.error("Uso: node scripts/docs-generator.mjs --target=<wiki|agents|skills|blog|page|all> [opciones]");
  process.exit(1);
}

// --- 2. PROVIDERS ---
const PROVIDERS = {
  groq: { url: 'https://api.groq.com/openai/v1/chat/completions', key: process.env.GROQ_API_KEY, model: process.env.GROQ_MODEL || 'llama-3.3-70b-versatile', weight: parseInt(process.env.LLM_WEIGHT_GROQ || '1', 10) },
  dashscope: { url: process.env.DASHSCOPE_URL || 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions', key: process.env.DASHSCOPE_API_KEY, model: process.env.DASHSCOPE_MODEL || 'qwen-plus', weight: parseInt(process.env.LLM_WEIGHT_DASHSCOPE || '1', 10) },
  gemini: { url: process.env.GEMINI_URL || 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions', key: process.env.GEMINI_API_KEY, model: process.env.GEMINI_MODEL || 'gemini-2.5-flash', weight: parseInt(process.env.LLM_WEIGHT_GEMINI || '1', 10) }
};

let providerStats = { groq: 0, dashscope: 0, gemini: 0 };

function buildPool() {
  const pool = [];
  for (const [name, p] of Object.entries(PROVIDERS)) {
    if (!p.key || p.weight <= 0) continue;
    for (let i = 0; i < p.weight; i++) pool.push(name);
  }
  return pool;
}
let poolIndex = Math.floor(Math.random() * (buildPool().length || 1));
function getProviderOrder() {
  const pool = buildPool();
  if (pool.length === 0) return [];
  const primary = pool[poolIndex % pool.length];
  poolIndex++;
  const enabledUnique = [...new Set(pool)];
  return [primary, ...enabledUnique.filter(n => n !== primary)];
}

// --- 3. SECURITY & FILES ---
const SECURITY_EXCLUDES = ['.env', 'secure_leads', 'wp-config.php', '.jsonl', 'wp-admin', 'wp-includes', 'remote_extract.py', 'wp-content'];

function isSafeFile(filePath) {
  const lowerPath = filePath.toLowerCase();
  for (const excl of SECURITY_EXCLUDES) {
    if (lowerPath.includes(excl) || filePath === excl) return false;
  }
  return true;
}

function getTrackedFiles(pattern = '.') {
  try {
    const output = execSync(`git ls-files ${pattern}`, { cwd: ROOT_DIR, encoding: 'utf-8' });
    return output.split('\n').filter(Boolean).filter(isSafeFile);
  } catch(e) {
    return [];
  }
}

function getChangedFiles() {
  if (IS_SEED) return ['SEED_MODE'];
  try {
    const output = execSync(`git diff --name-only ${SINCE_REF}`, { cwd: ROOT_DIR, encoding: 'utf-8' });
    const changed = output.split('\n').filter(Boolean);
    const tracked = new Set(getTrackedFiles());
    const WATCH_PATHS = process.env.WATCH_PATHS ? process.env.WATCH_PATHS.split(',') : ['specs/', 'src/content/', 'scripts/'];
    return changed.filter(f => WATCH_PATHS.some(wp => f.startsWith(wp))).filter(f => tracked.has(f) && isSafeFile(f));
  } catch (err) {
    console.warn(`[WARN] git diff falló (${err.message}). Usa --seed si es el primer commit.`);
    return [];
  }
}

// --- 4. LLM CALL ---
const sleep = (ms) => new Promise(r => setTimeout(r, ms));

async function callOpenAICompatible(url, key, model, messages, jsonFormat = true) {
  const payload = {
    model: model,
    messages: messages,
    temperature: 0.2,
    max_tokens: 4096
  };
  if (jsonFormat) {
    payload.response_format = { type: "json_object" };
  }
  
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${key}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  const responseData = await response.text();
  let parsed = null;
  try {
    parsed = JSON.parse(responseData);
  } catch(e) {
    parsed = responseData; 
  }

  return { status: response.status, data: parsed };
}

async function balanceAndCall(messages, docName, jsonFormat = true) {
  if (DRY_RUN) {
    console.log(`[DRY-RUN] [${docName}] Simulación de llamada LLM.`);
    if (jsonFormat) {
       return {
         title: "Dry Run Doc " + docName,
         description: "Simulated doc for dry run.",
         author: "AI Documenter",
         lastUpdated: new Date().toISOString().split('T')[0],
         tags: ["dry-run"],
         seoScore: 100,
         content: `Simulated content for ${docName}\n\n%%IGNORE_BLOCK_0%%`,
         pubDate: new Date().toISOString(),
         draft: true,
         confidence: 0.9
       };
    }
    return `Contenido simulado para ${docName}`;
  }

  const order = getProviderOrder();
  if (order.length === 0) {
    console.error("[ERROR] No hay API keys configuradas y no es --dry-run.");
    process.exit(1);
  }

  let attempt = 0;
  let maxRetries = 2;
  
  while (attempt <= maxRetries) {
    let all429 = true;
    for (let i = 0; i < order.length; i++) {
      const name = order[i];
      const p = PROVIDERS[name];
      const role = i === 0 ? 'primario' : 'failover';
      console.log(`[INFO] [${docName}] Intentando ${role}: ${name.toUpperCase()} (${p.model})`);

      const res = await callOpenAICompatible(p.url, p.key, p.model, messages, jsonFormat);
      
      if (res.status >= 200 && res.status < 300) {
        let content = res.data.choices?.[0]?.message?.content || "";
        if (jsonFormat) {
          try {
            content = content.replace(/^```json\n?/, '').replace(/\n?```$/, '').trim();
            const parsed = JSON.parse(content);
            providerStats[name]++;
            return parsed;
          } catch(e) {
            console.warn(`[WARN] JSON inválido devuelto por ${name}`);
            all429 = false;
            continue;
          }
        } else {
           providerStats[name]++;
           return content;
        }
      }
      
      if (res.status === 429) {
         console.warn(`[WARN] [${docName}] 429 Too Many Requests en ${name.toUpperCase()}.`);
      } else {
         all429 = false;
         console.warn(`[WARN] [${docName}] Error HTTP ${res.status} en ${name.toUpperCase()}.`);
      }
    }
    
    if (all429) {
      attempt++;
      if (attempt <= maxRetries) {
         const delay = Math.pow(2, attempt) * 2000;
         console.warn(`[WARN] Todos los proveedores dieron 429. Backoff de ${delay}ms...`);
         await sleep(delay);
      }
    } else {
      break;
    }
  }

  console.error(`[ERROR] [${docName}] Todos los intentos fallaron.`);
  return null;
}

// --- 5. HELPERS ---
function stripFrontmatter(text) {
  if (!text) return text;
  return text.replace(/^\s*---\r?\n[\s\S]*?\r?\n---\r?\n?/, '');
}
function extractIgnoreBlocks(text) {
  const regex = /<!--\s*OPENWIKI:IGNORE:START\s*-->[\s\S]*?<!--\s*OPENWIKI:IGNORE:END\s*-->/g;
  const blocks = [];
  let match, counter = 0, newText = text;
  while ((match = regex.exec(text)) !== null) {
    const placeholder = `%%IGNORE_BLOCK_${counter}%%`;
    blocks.push({ placeholder, content: match[0] });
    newText = newText.replace(match[0], placeholder);
    counter++;
  }
  return { newText, blocks };
}
function restoreIgnoreBlocks(text, blocks) {
  let restored = text || '';
  for (const block of blocks) {
    restored = restored.replace(block.placeholder, block.content);
  }
  return restored;
}

// --- 6. TARGET GENERATORS ---

async function runWiki() {
  const files = getChangedFiles();
  if (files.length === 0) {
    console.log("[INFO] No hay cambios relevantes detectados en wiki.");
    return;
  }
  
  const SYSTEM_PROMPT = `
Eres OpenWiki, el motor de "Documentación Viva" de Datanestiq.
Tu trabajo es generar o actualizar documentación técnica en formato Markdown para la colección Astro 'wiki'.
Responde ÚNICAMENTE con JSON: { "title": "...", "description": "...", "author": "AI Documenter", "lastUpdated": "YYYY-MM-DD", "tags": [], "seoScore": 100, "content": "markdown", "confidence": 0.95 }
Fundamenta tu redacción en el código/cambios provistos. Respeta placeholders %%IGNORE_BLOCK_x%%.`;

  for (const f of files) {
    let slug = f.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase() + '.md';
    slug = slug.replace(/-+/g, '-').replace(/^-|-$/g, '');
    let sourceContent = '';
    
    if (IS_SEED && f === 'SEED_MODE') {
      slug = 'arquitectura.md';
      sourceContent = "ARBOL DEL REPOSITORIO (SEED):\n" + getTrackedFiles().filter(f => !f.startsWith('node_modules') && !f.startsWith('.git') && f.split('/').length < 4).join('\n');
    } else {
      try {
        sourceContent = fs.readFileSync(path.join(ROOT_DIR, f), 'utf-8');
        if (sourceContent.length > 10000) sourceContent = sourceContent.substring(0, 10000) + "\n...[TRUNCADO]";
      } catch(e) { sourceContent = `[Archivo no leíble: ${f}]`; }
    }

    const outPath = path.join(ROOT_DIR, 'src/content/wiki', slug);
    let targetDocContent = '', extractedBlocks = [];
    if (fs.existsSync(outPath)) {
      const existingText = fs.readFileSync(outPath, 'utf-8');
      const extraction = extractIgnoreBlocks(stripFrontmatter(existingText));
      targetDocContent = extraction.newText;
      extractedBlocks = extraction.blocks;
    }

    const messages = [
      { role: 'system', content: SYSTEM_PROMPT },
      { role: 'user', content: `ARCHIVO: ${f}\nCONTENIDO:\n${sourceContent}\n\n${targetDocContent ? 'DOC EXISTENTE:\n' + targetDocContent : 'NUEVO DOC'}` }
    ];
    
    const resultJSON = await balanceAndCall(messages, slug, true);
    if (!resultJSON) continue;
    
    resultJSON.lastUpdated = new Date().toISOString().split('T')[0];
    const cleanContent = stripFrontmatter(resultJSON.content || '');
    const finalContent = restoreIgnoreBlocks(cleanContent, extractedBlocks);

    const fileContent = `---
title: "${(resultJSON.title||'Untitled').substring(0,97).replace(/"/g, '\\"')}"
description: "${(resultJSON.description||'').substring(0,157).replace(/"/g, '\\"')}"
author: "${resultJSON.author || 'AI Documenter'}"
lastUpdated: ${resultJSON.lastUpdated}
tags: ${JSON.stringify(resultJSON.tags || ['autogenerado'])}
seoScore: ${resultJSON.seoScore || 100}
---
${finalContent}`;

    saveDoc(outPath, fileContent, slug);
  }
}

async function runAgents() {
  console.log("[INFO] Recolectando contexto para AGENTS.md...");
  const files = getTrackedFiles();
  const tree = files.filter(f => !f.startsWith('node_modules') && !f.startsWith('.git') && f.split('/').length < 4).join('\n');
  
  const specFiles = files.filter(f => f.startsWith('specs/') && f.endsWith('.md'));
  let specsOverview = '';
  for (const sf of specFiles) {
    try {
       const text = fs.readFileSync(path.join(ROOT_DIR, sf), 'utf-8');
       const lines = text.split('\n');
       const title = lines.find(l => l.startsWith('# ')) || sf;
       specsOverview += `- ${sf}: ${title.replace('# ', '')}\n`;
    } catch(e) {}
  }
  
  const SYSTEM_PROMPT = `Eres un ingeniero principal. Genera el documento de configuración AGENTS.md en markdown (sin frontmatter). Este documento es el overview para agentes IA de cómo está estructurado el repositorio de Datanestiq, sus convenciones, arquitectura y un resumen de las specs. Usa el contexto proporcionado, NO inventes nada de lo que no esté fundamentado en el contexto.`;
  const userPrompt = `ARBOL DE ARCHIVOS (parcial):\n${tree}\n\nRESUMEN DE SPECS:\n${specsOverview}\n\nRedacta el AGENTS.md detallado para orientar a nuevos agentes IA.`;
  
  const messages = [
    { role: 'system', content: SYSTEM_PROMPT },
    { role: 'user', content: userPrompt }
  ];
  
  const result = await balanceAndCall(messages, 'AGENTS.md', false);
  if (result) {
     const clean = stripFrontmatter(result);
     saveDoc(path.join(ROOT_DIR, 'AGENTS.md'), clean, 'AGENTS.md');
  }
}

async function runSkills() {
  const skillFiles = getTrackedFiles('.agents/skills/');
  if (skillFiles.length === 0) {
    console.log("[INFO] No se encontraron skills tracked.");
    return;
  }
  
  let skillsContext = '';
  for (const sf of skillFiles) {
    if (sf.endsWith('SKILL.md')) {
       skillsContext += `\n--- SKILL: ${sf} ---\n`;
       skillsContext += fs.readFileSync(path.join(ROOT_DIR, sf), 'utf-8').substring(0, 1500); 
    }
  }
  
  const SYSTEM_PROMPT = `Genera un overview consolidado de las skills de IA disponibles en Datanestiq. Devuelve JSON con el formato Wiki: { "title": "Skills Overview", "description": "...", "content": "markdown", "tags": ["skills"] }`;
  const messages = [
    { role: 'system', content: SYSTEM_PROMPT },
    { role: 'user', content: `Las skills extraidas de .agents/skills/SKILL.md son:\n${skillsContext}` }
  ];
  
  const resultJSON = await balanceAndCall(messages, 'skills-overview.md', true);
  if (resultJSON) {
     const slug = SLUG_OVR || 'skills-overview.md';
     const fileContent = `---
title: "${(resultJSON.title||'Skills').substring(0,97).replace(/"/g, '\\"')}"
description: "${(resultJSON.description||'').substring(0,157).replace(/"/g, '\\"')}"
author: "AI Documenter"
lastUpdated: ${new Date().toISOString().split('T')[0]}
tags: ${JSON.stringify(resultJSON.tags || ['skills'])}
seoScore: 100
---
${stripFrontmatter(resultJSON.content || '')}`;
     saveDoc(path.join(ROOT_DIR, 'src/content/wiki', slug), fileContent, slug);
  }
}

async function runBlog() {
  if (!BRIEF) {
    console.log("[INFO] target=blog omitido (se requiere --brief=... para generar un post nuevo).");
    return;
  }
  
  let taxonomyContext = '';
  const dataDir = path.join(ROOT_DIR, 'src/data');
  if (fs.existsSync(dataDir)) {
     const jsonFiles = fs.readdirSync(dataDir).filter(f => f.endsWith('.json'));
     for (const jf of jsonFiles) {
        const text = fs.readFileSync(path.join(dataDir, jf), 'utf-8');
        taxonomyContext += `\n[Taxonomía ${jf}]: ${text.substring(0, 2000)}`;
     }
  }
  
  const SYSTEM_PROMPT = `Eres el redactor experto de Datanestiq. Redacta un artículo de blog usando el brief y adaptándote a la taxonomía.
Responde ÚNICAMENTE en JSON con el esquema: { "title": "máx 120 chars", "description": "máx 200 chars", "pubDate": "fecha ISO 8601", "author": "Datanestiq", "tags": ["array"], "draft": true, "content": "el artículo en markdown", "seoScore": 100 }`;
  
  const messages = [
    { role: 'system', content: SYSTEM_PROMPT },
    { role: 'user', content: `BRIEF: ${BRIEF}\nTAXONOMÍA (resumida): ${taxonomyContext}` }
  ];
  
  const slug = SLUG_OVR || BRIEF.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase().substring(0, 30) + '.md';
  const finalSlug = slug.endsWith('.md') ? slug : slug + '.md';
  
  const resultJSON = await balanceAndCall(messages, finalSlug, true);
  if (resultJSON) {
     // pubDate SIN comillas (YAML lo tipa como fecha; con comillas z.date() falla) y
     // sellado a la fecha real (el modelo alucina fechas, ej. 2024).
     const fileContent = `---
title: "${(resultJSON.title||'Blog').substring(0,117).replace(/"/g, '\\"')}"
description: "${(resultJSON.description||'').substring(0,197).replace(/"/g, '\\"')}"
author: "${resultJSON.author || 'Datanestiq'}"
pubDate: ${new Date().toISOString()}
tags: ${JSON.stringify(resultJSON.tags || ['blog'])}
draft: ${resultJSON.draft !== undefined ? resultJSON.draft : true}
---
${stripFrontmatter(resultJSON.content || '')}`;
     saveDoc(path.join(ROOT_DIR, 'src/content/blog', finalSlug), fileContent, finalSlug);
  }
}

async function runPage() {
  if (!BRIEF) {
    console.error("[ERROR] --brief=... es requerido para target=page");
    process.exit(1);
  }
  
  let taxonomyContext = '';
  const dataDir = path.join(ROOT_DIR, 'src/data');
  if (fs.existsSync(dataDir)) {
     const jsonFiles = fs.readdirSync(dataDir).filter(f => f.endsWith('.json'));
     for (const jf of jsonFiles) {
        const text = fs.readFileSync(path.join(dataDir, jf), 'utf-8');
        taxonomyContext += `\n[Taxonomía ${jf}]: ${text.substring(0, 2000)}`;
     }
  }
  
  const SYSTEM_PROMPT = `Eres el redactor experto de Datanestiq. Redacta una página de sitio web completa usando el brief y adaptándote a la taxonomía.
Responde ÚNICAMENTE en JSON con el esquema: { "title": "...", "draft": true, "seo": { "title": "...", "description": "..." }, "hero": { "headline": "...", "subheadline": "...", "cta": "..." }, "sections": [{ "type": "features|benefits|faq|trust|content", "heading": "...", "body": "...", "items": ["opcional array"] }], "content": "markdown opcional" }`;
  
  const messages = [
    { role: 'system', content: SYSTEM_PROMPT },
    { role: 'user', content: `BRIEF: ${BRIEF}\nTAXONOMÍA (resumida): ${taxonomyContext}` }
  ];
  
  const slug = SLUG_OVR || BRIEF.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase().substring(0, 30) + '.md';
  const finalSlug = slug.endsWith('.md') ? slug : slug + '.md';
  
  const resultJSON = await balanceAndCall(messages, finalSlug, true);
  if (resultJSON) {
     const fileContent = `---
title: "${(resultJSON.title||'Página').replace(/"/g, '\\"')}"
draft: true
seo:
  title: "${(resultJSON.seo?.title||'').replace(/"/g, '\\"')}"
  description: "${(resultJSON.seo?.description||'').replace(/"/g, '\\"')}"
hero:
  headline: "${(resultJSON.hero?.headline||'').replace(/"/g, '\\"')}"
  subheadline: "${(resultJSON.hero?.subheadline||'').replace(/"/g, '\\"')}"
  cta: "${(resultJSON.hero?.cta||'').replace(/"/g, '\\"')}"
sections:
${(resultJSON.sections||[]).map(s => `  - type: "${s.type || 'content'}"
    heading: "${(s.heading||'').replace(/"/g, '\\"')}"
    body: "${(s.body||'').replace(/\n/g, ' ')}"
${s.items && s.items.length > 0 ? '    items:\n' + s.items.map(i => `      - "${i.replace(/"/g, '\\"')}"`).join('\n') : ''}`).join('\n')}
---
${stripFrontmatter(resultJSON.content || '')}`;
     saveDoc(path.join(ROOT_DIR, 'src/content/pages', finalSlug), fileContent, finalSlug);
  }
}

function saveDoc(outPath, fileContent, docName) {
  if (!DRY_RUN) {
    fs.mkdirSync(path.dirname(outPath), { recursive: true });
    fs.writeFileSync(outPath, fileContent, 'utf-8');
    console.log(`[SUCCESS] Archivo guardado/actualizado: ${outPath}`);
  } else {
    console.log(`[DRY-RUN] Guardaría archivo en: ${outPath}`);
    console.log(`=== FRAGMENTO ===\n${fileContent.substring(0, 300)}...\n=================`);
  }
}

async function main() {
  const currentTarget = isAllTarget ? 'all (wiki, agents, skills, blog)' : TARGET;
  console.log(`[INFO] docs-generator.mjs | Target: ${currentTarget} | Dry Run: ${DRY_RUN}`);
  
  if (isAllTarget) {
    // Patrón diamante: fan-out paralelo con Promise.allSettled -> reduce en informe atómico
    console.log("[DIAMOND PATTERN] Ejecutando los 4 targets (wiki, agents, skills, blog) en paralelo...");
    const results = await Promise.allSettled([
      runWiki().then(() => ({ target: 'wiki', status: 'OK' })),
      runAgents().then(() => ({ target: 'agents', status: 'OK' })),
      runSkills().then(() => ({ target: 'skills', status: 'OK' })),
      runBlog().then(() => ({ target: 'blog', status: 'OK' }))
    ]);

    const targetSummary = results.map((res, i) => {
      const targetName = ['wiki', 'agents', 'skills', 'blog'][i];
      if (res.status === 'fulfilled') {
        return { Target: targetName, Status: res.value.status, Error: '-' };
      } else {
        return { Target: targetName, Status: 'FAILED', Error: res.reason?.message || String(res.reason) };
      }
    });

    console.log("\n--- RESUMEN POR TARGET (DIAMOND PATTERN) ---");
    console.table(targetSummary);
  } else if (TARGET === 'wiki') await runWiki();
  else if (TARGET === 'agents') await runAgents();
  else if (TARGET === 'skills') await runSkills();
  else if (TARGET === 'blog') await runBlog();
  else if (TARGET === 'page') await runPage();
  
  if (!DRY_RUN) {
     console.table(providerStats);
  }
  console.log("[INFO] Completado.");
}

main().catch(err => {
  console.error("[FATAL] Error en motor:", err);
  process.exit(1);
});
