import { pipeline, cos_sim } from '@xenova/transformers';
import fs from 'fs';

const personasData = JSON.parse(fs.readFileSync('./src/data/personas.json', 'utf8'));
const faqData = JSON.parse(fs.readFileSync('./src/data/faq.json', 'utf8'));

const faqCorpus = [
  ...personasData.roles.flatMap(r =>
    (r.objectionResponses || []).map(or => ({ question: or.objection, answer: or.response }))
  ),
  ...faqData.faqs.filter(f => f.answer !== null)
    .flatMap(f => [{ question: f.question, answer: f.answer }, ...(f.variants||[]).map(v => ({ question: v, answer: f.answer }))])
];

const extractor = await pipeline('feature-extraction', 'Xenova/paraphrase-multilingual-MiniLM-L12-v2', { quantized: true });
const emb = async t => Array.from((await extractor(t, { pooling: 'mean', normalize: true })).data);

const faqEmbs = await Promise.all(faqCorpus.map(async f => ({ question: f.question, embedding: await emb(f.question) })));

const queries = [
  { text: '¿mis datos van a la nube?', isNeg: false },
  { text: 'Ya tenemos contrato con Microsoft o Google', isNeg: false },
  { text: '¿qué diferencia los hace mejores que otras consultoras?', isNeg: false },
  { text: '¿tienen referencias o clientes conocidos?', isNeg: false },
  { text: '¿qué garantías ofrecen en el proyecto?', isNeg: false },
  { text: '¿el ROI se ve rápido?', isNeg: false },
  { text: '¿trabajan con SAP?', isNeg: false },
  { text: 'necesito un feature store para mi equipo de ML', isNeg: true },
  { text: '¿venden laptops?', isNeg: true },
  { text: '¿qué hora es?', isNeg: true },
];

console.log('Neg | Score  | TopCorpus | Query');
for (const { text, isNeg } of queries) {
  const qEmb = await emb(text);
  let best = null, bestScore = -Infinity;
  for (const f of faqEmbs) {
    const s = cos_sim(qEmb, f.embedding);
    if (s > bestScore) { bestScore = s; best = f.question; }
  }
  const marker = isNeg ? '[NEG]' : '     ';
  console.log(`${marker} | ${bestScore.toFixed(3)} | "${best}" | "${text}"`);
}
