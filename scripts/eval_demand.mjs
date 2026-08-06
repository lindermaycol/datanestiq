import { pipeline, cos_sim } from '@xenova/transformers';
import fs from 'fs';

const taxonomyCorpus = JSON.parse(fs.readFileSync('./src/data/taxonomyCorpus.json', 'utf8'));

const TAXONOMY_ENTRIES = taxonomyCorpus.flatMap(service => [
  { text: service.name, id: service.id },
  { text: service.hero?.headline || '', id: service.id },
  { text: service.hero?.subheadline || '', id: service.id },
  { text: service.seo?.description || '', id: service.id }
]).filter(e => e.text.trim().length > 0);

const MODEL = 'Xenova/paraphrase-multilingual-MiniLM-L12-v2';
const extractor = await pipeline('feature-extraction', MODEL, { quantized: true });
const emb = async t => Array.from((await extractor(t, { pooling: 'mean', normalize: true })).data);

const corpusEmbs = await Promise.all(TAXONOMY_ENTRIES.map(async entry => ({
  id: entry.id,
  text: entry.text,
  embedding: await emb(entry.text)
})));

const testCases = [
  { query: 'necesito dashboards para mi junta directiva', expectedOffered: true, expectedService: 'business-intelligence' },
  { query: 'quiero entrenar modelos de machine learning y llm', expectedOffered: true, expectedService: 'ai-data-science' },
  { query: 'crear un data lake y pipelines con python y aws', expectedOffered: true, expectedService: 'data-engineering' },
  { query: '¿venden repuestos de autos?', expectedOffered: false },
  { query: 'quiero pedir una pizza de pepperoni', expectedOffered: false },
  { query: 'necesito comprar repuestos de laptops', expectedOffered: false },
];

console.log('--- EVAL CLASIFICACIÓN DE DEMANDA (0-LLM) ---');
let passed = 0;
for (const tc of testCases) {
  const qEmb = await emb(tc.query);
  let best = null, bestScore = -Infinity;
  for (const entry of corpusEmbs) {
    const s = cos_sim(qEmb, entry.embedding);
    if (s > bestScore) {
      bestScore = s;
      best = entry;
    }
  }

  const offered = bestScore >= 0.40;
  const matchOk = offered === tc.expectedOffered && (!offered || best.id === tc.expectedService);
  
  if (matchOk) passed++;
  const marker = matchOk ? '✅' : '❌';
  console.log(`${marker} "${tc.query}" -> matched: "${best?.id}" | score: ${bestScore.toFixed(3)} | offered: ${offered} (expected offered: ${tc.expectedOffered})`);
}

console.log(`\nResultado: ${passed}/${testCases.length} pasados`);
process.exit(passed === testCases.length ? 0 : 1);
