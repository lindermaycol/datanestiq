/**
 * eval_router.mjs — Evaluación END-TO-END del Router 0-LLM (Spec 019)
 *
 * Simula el pipeline real de producción:
 *   classifyIntent → (cita|guiado → 0llm-determinístico) | (resto → matchFAQ → si match: 0llm-faq, si no: llm)
 *
 * Modelo EXACTO de producción: Xenova/paraphrase-multilingual-MiniLM-L12-v2
 */
import { pipeline, cos_sim } from '@xenova/transformers';
import fs from 'fs';
import path from 'path';

// 1. Cargar datos
const intentsData = JSON.parse(fs.readFileSync(path.resolve('./src/data/intents.json'), 'utf8'));
const personasData = JSON.parse(fs.readFileSync(path.resolve('./src/data/personas.json'), 'utf8'));
const faqData     = JSON.parse(fs.readFileSync(path.resolve('./src/data/faq.json'), 'utf8'));

// Corpus FAQ combinado idéntico a Chatbot.jsx (excluye answer:null)
const faqCorpus = [
  ...personasData.roles.flatMap(r =>
    (r.objectionResponses || []).map(or => ({ question: or.objection, answer: or.response }))
  ),
  ...faqData.faqs
    .filter(f => f.answer !== null)
    .flatMap(f => [
      { question: f.question, answer: f.answer },
      ...(f.variants || []).map(v => ({ question: v, answer: f.answer }))
    ])
];

const intentCorpus = intentsData.intents;
const CONF_THRESHOLD     = intentsData.confidence_threshold;  // 0.65
const FAQ_MATCH_THRESHOLD = intentsData.faq_match_threshold;  // 0.89 (para recalibrar)

// 2. Modelo
const MODEL_NAME = 'Xenova/paraphrase-multilingual-MiniLM-L12-v2';
console.log(`Modelo: ${MODEL_NAME}`);
const extractor = await pipeline('feature-extraction', MODEL_NAME, { quantized: true });
const emb = async t => Array.from((await extractor(t, { pooling: 'mean', normalize: true })).data);

// 3. Pre-embeber corpus
const intentEmbs = await Promise.all(intentCorpus.map(async i => ({
  id: i.id,
  examples: await Promise.all(i.examples.map(ex => emb(ex)))
})));
const faqEmbs = await Promise.all(faqCorpus.map(async f => ({
  question: f.question, answer: f.answer, embedding: await emb(f.question)
})));

// 4. Funciones que replican EXACTAMENTE intentClassifier.ts
function classifyIntent(qEmb) {
  const scores = intentEmbs.map(intent => {
    const sims = intent.examples.map(exEmb => cos_sim(qEmb, exEmb));
    sims.sort((a, b) => b - a);
    const top3 = sims.slice(0, 3);
    return { id: intent.id, score: top3.reduce((a, b) => a + b, 0) / top3.length };
  });
  scores.sort((a, b) => b.score - a.score);
  const best = scores[0];
  return best.score >= CONF_THRESHOLD ? best.id : 'unknown';
}

function matchFAQ(qEmb, threshold) {
  let best = null, bestScore = -Infinity;
  for (const f of faqEmbs) {
    const s = cos_sim(qEmb, f.embedding);
    if (s > bestScore) { bestScore = s; best = f; }
  }
  return bestScore >= threshold ? { answer: best.answer, score: bestScore } : null;
}

// 5. Pipeline end-to-end (replica Chatbot.jsx)
function routeE2E(intent, faqMatch) {
  if (intent === 'cita')   return '0llm-cita';
  if (intent === 'guiado') return '0llm-guiado';
  if (faqMatch)            return '0llm-faq';
  return 'llm';
}

// 6. Dataset de prueba END-TO-END
//    expected_route: qué debe devolver routeE2E en producción
//    Usamos PARÁFRASIS realistas, no copias verbatim del corpus
const TEST_CASES = [
  // Objeciones de la taxonomía (deben ser 0llm-faq si hay match, o llm si no están representadas)
  { text: '¿mis datos van a la nube?',                              expected: '0llm-faq' },
  { text: 'Nuestros datos son demasiado sensibles para la nube',   expected: '0llm-faq' },
  { text: 'Ya tenemos contrato con Microsoft o Google',             expected: '0llm-faq' },
  { text: '¿qué diferencia los hace mejores que otras consultoras?', expected: 'llm' },
  { text: '¿tienen referencias o clientes conocidos?',              expected: 'llm' },
  { text: '¿qué garantías ofrecen en el proyecto?',                expected: 'llm' },
  { text: '¿el ROI se ve rápido?',                                  expected: 'llm' },
  { text: '¿trabajan con SAP?',                                     expected: 'llm' },
  // Cita (deben ser 0llm-cita)
  { text: 'agéndame',                                               expected: '0llm-cita' },
  { text: 'quiero agendar una reunión',                             expected: '0llm-cita' },
  { text: 'llámenme',                                               expected: '0llm-cita' },
  // Guiado (deben ser 0llm-guiado)
  { text: '¿qué hacen?',                                            expected: '0llm-guiado' },
  { text: '¿por dónde empiezo?',                                    expected: '0llm-guiado' },
  // FAQ operativa pendiente → LLM (answer:null → excluido del corpus → no hay match → LLM)
  { text: '¿cuánto cuesta?',                                        expected: 'llm' },
  { text: '¿cuál es el horario de atención?',                       expected: 'llm' },
  // Negativos fuera de catálogo → LLM (§2: no inventar)
  { text: 'necesito un feature store para mi equipo de ML',         expected: 'llm' },
  { text: '¿venden laptops?',                                        expected: 'llm' },
  { text: '¿me ayudan con mi tesis?',                               expected: 'llm' },
  { text: '¿qué hora es?',                                          expected: 'llm' },
  { text: 'quiero una pizza',                                        expected: 'llm' },
];

console.log(`\nUmbrales: confidence=${CONF_THRESHOLD}, faq_match=${FAQ_MATCH_THRESHOLD}`);
console.log(`Corpus FAQ: ${faqEmbs.length} entradas (excluidas nulls)`);
console.log(`Casos de prueba: ${TEST_CASES.length}\n`);
console.log('─'.repeat(120));

const metrics = { '0llm-faq': {tp:0,fp:0,fn:0}, '0llm-cita': {tp:0,fp:0,fn:0}, '0llm-guiado': {tp:0,fp:0,fn:0}, llm: {tp:0,fp:0,fn:0} };
const fpFaq = [];

for (const tc of TEST_CASES) {
  const qEmb   = await emb(tc.text);
  const intent  = classifyIntent(qEmb);
  const faqM    = matchFAQ(qEmb, FAQ_MATCH_THRESHOLD);
  const route   = routeE2E(intent, faqM);
  const ok      = route === tc.expected ? '✅' : '❌';

  console.log(`${ok} "${tc.text}"`);
  console.log(`   intent=${intent} | faqScore=${faqM ? faqM.score.toFixed(3) : 'none'} | route=${route} | expected=${tc.expected}`);

  for (const r of Object.keys(metrics)) {
    if (route === r && tc.expected === r) metrics[r].tp++;
    if (route === r && tc.expected !== r) {
      metrics[r].fp++;
      if (r === '0llm-faq') fpFaq.push({ text: tc.text, score: faqM?.score, expected: tc.expected });
    }
    if (route !== r && tc.expected === r) metrics[r].fn++;
  }
}

console.log('\n' + '═'.repeat(120));
console.log('RESULTADOS END-TO-END');
console.log('─'.repeat(120));
let totalTP = 0, totalTests = TEST_CASES.length;
for (const [r, m] of Object.entries(metrics)) {
  const precision = m.tp + m.fp > 0 ? (m.tp / (m.tp + m.fp)).toFixed(2) : 'N/A';
  const recall    = m.tp + m.fn > 0 ? (m.tp / (m.tp + m.fn)).toFixed(2) : 'N/A';
  console.log(`${r.padEnd(14)} | Precision: ${precision} | Recall: ${recall} | TP:${m.tp} FP:${m.fp} FN:${m.fn}`);
  totalTP += m.tp;
}
console.log(`\nTasa resolución 0-LLM real: ${TEST_CASES.filter(t => t.expected !== 'llm').length - metrics.llm.fn}/${TEST_CASES.filter(t => t.expected !== 'llm').length}`);
console.log(`Accuracy global: ${(totalTP / totalTests * 100).toFixed(1)}% (${totalTP}/${totalTests})`);

if (fpFaq.length > 0) {
  console.log('\n⚠️  FALSOS POSITIVOS en 0llm-faq (respuesta enlatada cuando NO debía):');
  fpFaq.forEach(f => console.log(`   "${f.text}" → faqScore=${f.score?.toFixed(3)} (esperado: ${f.expected})`));
} else {
  console.log('\n✅ Cero falsos positivos en FAQ (§2 seguro)');
}
console.log('─'.repeat(120));
