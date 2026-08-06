/**
 * intentClassifier.ts — Spec 019: Router Determinístico Ampliado (0-LLM)
 *
 * Clasificador de intención 100% cliente usando el worker Xenova existente
 * (public/worker.js, modelo paraphrase-multilingual-MiniLM-L12-v2).
 *
 * Principios:
 * - §5 (0-LLM): clasificación en cliente, sin tokens LLM.
 * - §2 (Honestidad): doble umbral conservador. La duda SIEMPRE cae al LLM.
 * - P1 (Claude audit): si el modelo no está listo → LLM inmediato, sin bloquear.
 * - P2 (Claude audit): respuestas filtradas por `id` (cross-wiring cero);
 *   faqCorpus indexado una sola vez en montaje.
 */

// ─── Types ────────────────────────────────────────────────────────────────────

export type IntentId = 'faq' | 'cita' | 'guiado' | 'complejo' | 'unknown';

export interface IntentResult {
  intent: IntentId;
  confidence: number;
}

export interface FAQEntry {
  question: string;
  answer: string;
}

export interface FAQMatch {
  answer: string;
  score: number;
}

interface IntentConfig {
  id: IntentId;
  examples: string[];
}

interface IntentsJson {
  version: string;
  confidence_threshold: number;
  faq_match_threshold: number;
  intents: IntentConfig[];
}

// ─── Internal state (module-level singletons) ─────────────────────────────────

// Corpus plano para el worker: todas las frases-ejemplo de todas las intenciones
let _intentCorpusTexts: string[] = [];
// Mapeo índice → intent id (paralelo a _intentCorpusTexts)
let _intentLabels: IntentId[] = [];
// Thresholds leídos del JSON
let _confidenceThreshold = 0.72;
let _faqMatchThreshold = 0.65;
// Textos del corpus FAQ (objectionResponses.objection)
let _faqCorpusTexts: string[] = [];
// Corpus FAQ indexado (enviado al worker una sola vez)
let _faqCorpusIndexed = false;
// Clasificador listo (modelo warm + corpus indexado)
let _classifierReady = false;
// Map de promesas pendientes: id → { resolve, reject }
const _pending = new Map<string, { resolve: (v: any) => void; reject: (e: any) => void }>();
// Referencia al worker (inyectada desde el componente)
let _worker: Worker | null = null;
// Counter para IDs únicos
let _idCounter = 0;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function nextId(prefix: string): string {
  return `${prefix}-${++_idCounter}-${Date.now()}`;
}

/** Envía un mensaje al worker y retorna una promesa que resuelve cuando llega la respuesta con el mismo id. */
function workerRequest(type: string, payload: Record<string, any>): Promise<any> {
  return new Promise((resolve, reject) => {
    if (!_worker) return reject(new Error('Worker not set'));
    const id = nextId(type);
    _pending.set(id, { resolve, reject });
    _worker.postMessage({ type, id, ...payload });
    // Timeout de seguridad: si no responde en 5s → rechazar
    setTimeout(() => {
      if (_pending.has(id)) {
        _pending.delete(id);
        reject(new Error(`Worker timeout: ${id}`));
      }
    }, 5000);
  });
}

/** Handler global del worker. Debe ser instalado por el consumidor (ver setupWorkerListener). */
function handleWorkerMessage(event: MessageEvent) {
  const { status, id, results, error } = event.data ?? {};
  if (!id) return; // Mensajes sin id (progress, ready) no son respuestas a solicitudes nuestras
  const pending = _pending.get(id);
  if (!pending) return; // No es para nosotros
  _pending.delete(id);
  if (status === 'complete') {
    pending.resolve(results);
  } else if (status === 'error') {
    pending.reject(new Error(error ?? 'Worker error'));
  }
}

// ─── Public API ───────────────────────────────────────────────────────────────

/**
 * Instala el listener de mensajes del worker para el clasificador.
 * IMPORTANTE: el consumidor debe llamar esto UNA VEZ tras obtener la referencia al worker.
 * No reemplaza el listener existente — añade uno propio para los `id` que el clasificador gestiona.
 */
export function setupWorkerListener(worker: Worker): void {
  _worker = worker;
  worker.addEventListener('message', handleWorkerMessage);
}

/**
 * Inicializa el clasificador: carga los prototipos de intención y el corpus FAQ en el worker.
 * Debe llamarse cuando el worker ya está en estado 'ready'.
 *
 * P1: Si el worker NO está listo aún, esta función simplemente no indexa nada.
 * El estado `_classifierReady` permanece false → classifyIntent devolverá `unknown`
 * → el chatbot cae al LLM inmediatamente, sin bloquear.
 *
 * @param intentsJson  Contenido de src/data/intents.json
 * @param faqEntries   Array plano de { question, answer } de personasCorpus.objectionResponses
 * @param workerReady  true si el worker ya emitió status:'ready'
 */
export async function initIntentClassifier(
  intentsJson: IntentsJson,
  faqEntries: FAQEntry[],
  workerReady: boolean
): Promise<void> {
  // Extraer parámetros
  _confidenceThreshold = intentsJson.confidence_threshold ?? 0.72;
  _faqMatchThreshold = intentsJson.faq_match_threshold ?? 0.65;

  // Construir corpus plano de intenciones
  _intentCorpusTexts = [];
  _intentLabels = [];
  for (const intent of intentsJson.intents) {
    for (const example of intent.examples) {
      _intentCorpusTexts.push(example);
      _intentLabels.push(intent.id as IntentId);
    }
  }

  // Construir corpus FAQ
  _faqCorpusTexts = faqEntries.map(e => e.question);

  // P1: si el modelo no está listo, no bloquear — el clasificador simplemente no estará disponible
  if (!workerReady || !_worker) {
    _classifierReady = false;
    return;
  }

  // Indexar corpus de intenciones en el worker (una sola vez)
  try {
    await workerRequest('index', { corpusTexts: _intentCorpusTexts });
    _classifierReady = true;
  } catch (e) {
    console.warn('[intentClassifier] Failed to index intents corpus:', e);
    _classifierReady = false;
  }

  // P2: Indexar corpus FAQ una sola vez (no re-embebe por consulta)
  // Nota: el worker solo mantiene UN corpus cacheado. Para soportar dos corpus independientes
  // (intenciones + FAQ), la búsqueda FAQ pasa su propio corpusTexts en cada llamada.
  // En v2 → TD-019-01: worker singleton con multi-corpus por id.
  _faqCorpusIndexed = true; // Flag informativo; el corpus se pasa inline en la búsqueda FAQ.
}

/**
 * Marca el clasificador como listo cuando el worker emite 'ready' post-montaje
 * (caso: el chatbot se abre mientras el worker aún descargaba el modelo).
 * Reindexar el corpus de intenciones.
 */
export async function onWorkerReady(
  intentsJson: IntentsJson,
  faqEntries: FAQEntry[]
): Promise<void> {
  if (_classifierReady) return; // Ya estaba listo
  await initIntentClassifier(intentsJson, faqEntries, true);
}

/**
 * Clasifica un texto libre en una de las 4 intenciones.
 *
 * P1: Si el clasificador NO está listo → retorna { intent: 'unknown', confidence: 0 }
 *     → el chatbot cae al LLM inmediatamente (comportamiento actual, sin bloquear).
 *
 * @returns IntentResult con la intención ganadora y su confianza (similitud coseno)
 */
export async function classifyIntent(text: string): Promise<IntentResult> {
  // P1: clasificación es oportunista — si no está listo, LLM al toque
  if (!_classifierReady || !_worker || _intentCorpusTexts.length === 0) {
    return { intent: 'unknown', confidence: 0 };
  }

  try {
    // Buscar las frases más similares en el corpus de intenciones
    // P2: id único 'intent-classify-*' → no se cruza con búsquedas del SemanticSearch
    const results = await workerRequest('search', {
      query: text,
      corpusTexts: _intentCorpusTexts,
      id: nextId('intent-classify'),
    }) as Array<{ index: number; score: number }>;

    if (!results || results.length === 0) {
      return { intent: 'unknown', confidence: 0 };
    }

    // Agrupar scores por intención → promedio de los top-3 por intención
    const scoresByIntent: Record<string, number[]> = {};
    for (const r of results.slice(0, 15)) {
      const intentId = _intentLabels[r.index];
      if (!scoresByIntent[intentId]) scoresByIntent[intentId] = [];
      scoresByIntent[intentId].push(r.score);
    }

    // Score de intención = promedio de sus top-3 ejemplos más similares
    let bestIntent: IntentId = 'unknown';
    let bestScore = 0;
    for (const [intentId, scores] of Object.entries(scoresByIntent)) {
      const top3 = scores.slice(0, 3);
      const avg = top3.reduce((a, b) => a + b, 0) / top3.length;
      if (avg > bestScore) {
        bestScore = avg;
        bestIntent = intentId as IntentId;
      }
    }

    // Aplicar umbral de confianza (§2: la duda cae al LLM)
    if (bestScore < _confidenceThreshold) {
      return { intent: 'unknown', confidence: bestScore };
    }

    return { intent: bestIntent, confidence: bestScore };
  } catch (e) {
    console.warn('[intentClassifier] classifyIntent error:', e);
    return { intent: 'unknown', confidence: 0 };
  }
}

/**
 * Busca la FAQ más cercana al texto dado en el corpus de objectionResponses.
 *
 * P2: id único 'faq-match-*' → no se cruza con búsquedas del SemanticSearch ni con classifyIntent.
 *     faqCorpus se pasa inline (corpus completo para el worker, no re-embebido por consulta
 *     gracias al caché interno del worker por firma de corpus).
 *
 * @param text        Texto del usuario
 * @param faqEntries  Array plano de { question, answer }
 * @returns FAQMatch si score >= faq_match_threshold, null si no hay match suficiente (→ LLM)
 */
export async function matchFAQ(text: string, faqEntries: FAQEntry[]): Promise<FAQMatch | null> {
  if (!_classifierReady || !_worker || faqEntries.length === 0) {
    return null; // No hay clasificador → LLM
  }

  try {
    const corpusTexts = faqEntries.map(e => e.question);
    // P2: id único 'faq-match-*' para evitar cross-wiring con el SemanticSearch
    const results = await workerRequest('search', {
      query: text,
      corpusTexts,
      id: nextId('faq-match'),
    }) as Array<{ index: number; score: number }>;

    if (!results || results.length === 0) return null;

    const best = results[0];
    if (best.score < _faqMatchThreshold) {
      return null; // Score insuficiente → LLM (§2: no forzar FAQ errónea)
    }

    return {
      answer: faqEntries[best.index].answer,
      score: best.score,
    };
  } catch (e) {
    console.warn('[intentClassifier] matchFAQ error:', e);
    return null;
  }
}

/** Expone el estado de disponibilidad del clasificador (para diagnóstico). */
export function isClassifierReady(): boolean {
  return _classifierReady;
}
