import { pipeline, cos_sim } from '@xenova/transformers';
import fs from 'fs';
import path from 'path';

// 1. Cargar corpus
const intentsData = JSON.parse(fs.readFileSync(path.resolve('./src/data/intents.json'), 'utf8'));
const personasData = JSON.parse(fs.readFileSync(path.resolve('./src/data/personas.json'), 'utf8'));
const faqData = JSON.parse(fs.readFileSync(path.resolve('./src/data/faq.json'), 'utf8'));

// Construir corpus combinado de FAQ, igual que en Chatbot.jsx
const faqCorpus = [
  ...personasData.roles.flatMap(r =>
    (r.objectionResponses || []).map(or => ({ question: or.objection, answer: or.response }))
  ),
  ...faqData.faqs
    .filter(f => f.answer !== null) // Excluir nulls
    .flatMap(f => [
      { question: f.question, answer: f.answer },
      ...(f.variants || []).map(v => ({ question: v, answer: f.answer }))
    ])
];

const intentCorpus = intentsData.intents;

// 2. Modelo EXACTO de producción
const MODEL_NAME = 'Xenova/paraphrase-multilingual-MiniLM-L12-v2';
console.log(`Cargando modelo: ${MODEL_NAME}...`);

let extractor;
try {
    extractor = await pipeline('feature-extraction', MODEL_NAME, {
        quantized: true // Node/ONNX optimizations
    });
} catch (e) {
    console.error("Error cargando pipeline:", e);
    process.exit(1);
}

// Embeber función
async function getEmbedding(text) {
    const output = await extractor(text, { pooling: 'mean', normalize: true });
    return Array.from(output.data);
}

// Dataset de prueba etiquetado
const TEST_CASES = [
  { text: "¿cuánto cuesta?", expected: "faq" },
  { text: "precio", expected: "faq" },
  { text: "tarifas", expected: "faq" },
  { text: "¿cuál es el horario de atención?", expected: "faq" },
  { text: "¿cómo los contacto?", expected: "faq" },
  { text: "¿trabajan fines de semana?", expected: "faq" },
  { text: "¿cuánto tiempo tarda un proyecto de IA?", expected: "faq" },
  { text: "¿qué garantía tienen?", expected: "faq" },
  
  { text: "agéndame", expected: "cita" },
  { text: "llámenme", expected: "cita" },
  { text: "quiero hablar", expected: "cita" },
  { text: "quiero agendar una reunión", expected: "cita" },
  
  { text: "¿qué hacen?", expected: "guiado" },
  { text: "¿qué servicios tienen?", expected: "guiado" },
  { text: "ayúdame a elegir", expected: "guiado" },
  { text: "¿por dónde empiezo?", expected: "guiado" },
  
  { text: "necesito un feature store", expected: "complejo" },
  { text: "migración aws rds", expected: "complejo" },
  { text: "tenemos 50TB de logs sin estructurar", expected: "complejo" },
  
  // Fuera de catálogo (Negativos de FAQ -> deben ir a complejo o no llegar a FAQ)
  { text: "¿venden laptops?", expected: "complejo" },
  { text: "¿me ayudan con mi tesis?", expected: "complejo" },
  { text: "¿qué hora es?", expected: "complejo" },
  { text: "quiero una pizza", expected: "complejo" }
];

console.log(`Evaluando ${TEST_CASES.length} casos...`);

// Pre-embeber corpus de FAQ
const faqEmbeddings = await Promise.all(faqCorpus.map(async f => ({
  question: f.question,
  embedding: await getEmbedding(f.question)
})));

// Pre-embeber corpus de Intents
const intentEmbeddings = await Promise.all(intentCorpus.map(async i => ({
  id: i.id,
  examples: await Promise.all(i.examples.map(ex => getEmbedding(ex)))
})));

const metrics = {
  faq: { tp: 0, fp: 0, fn: 0, tn: 0 },
  cita: { tp: 0, fp: 0, fn: 0, tn: 0 },
  guiado: { tp: 0, fp: 0, fn: 0, tn: 0 },
  complejo: { tp: 0, fp: 0, fn: 0, tn: 0 }
};

const confidence_threshold = intentsData.confidence_threshold; // 0.65
const faq_match_threshold = intentsData.faq_match_threshold; // 0.65

console.log(`\n=== Evaluando con Umbrales actuales: Confidence=${confidence_threshold}, FAQ_Match=${faq_match_threshold} ===\n`);

let fpFaq = [];

for (const tc of TEST_CASES) {
  const qEmb = await getEmbedding(tc.text);
  
  // 1. Simular classifyIntent (Promedio top-3)
  const intentScores = intentEmbeddings.map(intent => {
      const sims = intent.examples.map(exEmb => cos_sim(qEmb, exEmb));
      sims.sort((a, b) => b - a);
      const top3 = sims.slice(0, 3);
      const avgTop3 = top3.reduce((a, b) => a + b, 0) / top3.length;
      return { id: intent.id, score: avgTop3 };
  });
  intentScores.sort((a, b) => b.score - a.score);
  const bestIntent = intentScores[0];
  let classifiedIntent = bestIntent.score >= confidence_threshold ? bestIntent.id : "complejo"; // unknown defaults to LLM/complejo

  // 2. Simular matchFAQ (Máximo global)
  let faqScore = 0;
  if (classifiedIntent === 'faq') {
      const faqSims = faqEmbeddings.map(f => cos_sim(qEmb, f.embedding));
      faqScore = Math.max(...faqSims);
      if (faqScore < faq_match_threshold) {
          classifiedIntent = "complejo"; // falló el umbral FAQ
      }
  }

  console.log(`Test: "${tc.text}" | Expected: ${tc.expected} | BestIntent: ${bestIntent.id} (Score: ${bestIntent.score.toFixed(3)}) | FAQ Match: ${faqScore.toFixed(3)} | Final: ${classifiedIntent}`);

  // Medir
  for (const intent of Object.keys(metrics)) {
      if (classifiedIntent === intent && tc.expected === intent) metrics[intent].tp++;
      if (classifiedIntent === intent && tc.expected !== intent) {
          metrics[intent].fp++;
          if (intent === 'faq') fpFaq.push({text: tc.text, score: faqScore});
      }
      if (classifiedIntent !== intent && tc.expected === intent) metrics[intent].fn++;
      if (classifiedIntent !== intent && tc.expected !== intent) metrics[intent].tn++;
  }
}

console.log("=== Resultados ===");
for (const [intent, m] of Object.entries(metrics)) {
  const precision = m.tp + m.fp > 0 ? (m.tp / (m.tp + m.fp)).toFixed(2) : 0;
  const recall = m.tp + m.fn > 0 ? (m.tp / (m.tp + m.fn)).toFixed(2) : 0;
  console.log(`${intent.toUpperCase().padEnd(10)} | Precision: ${precision} | Recall: ${recall} | TP: ${m.tp}, FP: ${m.fp}, FN: ${m.fn}`);
}

if (fpFaq.length > 0) {
    console.log("\n⚠️ Falsos Positivos en FAQ (Peligro de respuesta inventada/errónea):");
    fpFaq.forEach(f => console.log(`- "${f.text}" (score: ${f.score.toFixed(3)})`));
} else {
    console.log("\n✅ Cero falsos positivos en FAQ.");
}
