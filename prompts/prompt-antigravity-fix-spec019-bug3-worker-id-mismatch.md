# FIX — Spec 019 Bug #3: `workerRequest` id mismatch → el `search` del router siempre hace timeout (0-LLM sigue muerto)

**Reauditoría EN VIVO** en `https://app.datanestiq.com/` tras tus fixes (commit `8a29a78`). Resultado:

- ✅ **Bug #1 (chat.php 403) ARREGLADO** — "¿cuánto cuesta?" → respuesta real del LLM (no 403). Chatbot restaurado.
- ✅ **Bug #2 (index sin id) ARREGLADO** — en el bundle nuevo `Chatbot.BakCuFzt.js` ya NO hay `Worker timeout: index-*`;
  el clasificador llega a `_classifierReady = true`.
- 🔴 **Bug #3 (NUEVO, se destapó al arreglar el #2)** — el `search` del clasificador hace timeout → **el router 0-LLM
  sigue sin rutear**. "¿cuánto cuesta?" fue al LLM, no a la FAQ 0-LLM.

## Evidencia en vivo
Consola (bundle nuevo `Chatbot.BakCuFzt.js`):
```
[intentClassifier] classifyIntent error: Worker timeout: search-5-1786017619388
```
El id que hace timeout es **`search-5`** — la clave que generó `workerRequest` con `nextId(type)`. Pero el mensaje que
se envió al worker llevaba otro id (`intent-classify-*`). El worker respondió con `intent-classify-*`, nadie lo
escuchaba bajo esa clave, y `search-5` expiró a los 5s. Ese prefijo `search-5` en el error es la prueba del mismatch.

## Causa raíz — `src/lib/intentClassifier.ts`, `workerRequest` (líneas 75-90)
```ts
function workerRequest(type: string, payload: Record<string, any>): Promise<any> {
  return new Promise((resolve, reject) => {
    if (!_worker) return reject(new Error('Worker not set'));
    const id = nextId(type);                       // ← clave del _pending = 'search-N'
    _pending.set(id, { resolve, reject });
    _worker.postMessage({ type, id, ...payload });  // ← ...payload trae id:'intent-classify-M' y SOBRESCRIBE el id
    ...
```
`classifyIntent` (línea ~203) y `matchFAQ` (línea ~264) pasan su propio id en el payload para el prefijo único de P2:
```ts
await workerRequest('search', { query: text, corpusTexts: _intentCorpusTexts, id: nextId('intent-classify') });
await workerRequest('search', { query: text, corpusTexts,                     id: nextId('faq-match') });
```
Como en `{ type, id, ...payload }` el spread `...payload` va **después** de `id`, el `id` del payload gana en el
**mensaje**, pero el `_pending` quedó registrado bajo el `id` de `nextId(type)` (`search-N`). Clave del pending ≠ id
del mensaje → la respuesta del worker nunca resuelve la promesa → timeout 5s → `classifyIntent`/`matchFAQ` caen al LLM.

(El `index` no sufría esto porque `initIntentClassifier` llama `workerRequest('index', { corpusTexts })` **sin** `id`
en el payload → no hay override → clave = mensaje = `index-N`. Por eso el #2 quedó bien y este bug estaba enmascarado.)

## Fix (una sola función — hazlo autoritativo y coherente)
En `workerRequest`, usa el id del caller si viene, y ponlo **al final** para que sea autoritativo:
```ts
function workerRequest(type: string, payload: Record<string, any>): Promise<any> {
  return new Promise((resolve, reject) => {
    if (!_worker) return reject(new Error('Worker not set'));
    const id = payload.id ?? nextId(type);          // usa el prefijo único del caller (intent-classify / faq-match) si lo trae
    _pending.set(id, { resolve, reject });
    _worker.postMessage({ type, ...payload, id });  // id AL FINAL → nadie lo sobrescribe; clave del pending == id del mensaje
    const timeoutMs = type === 'index' ? 15000 : 5000;
    setTimeout(() => {
      if (_pending.has(id)) {
        _pending.delete(id);
        reject(new Error(`Worker timeout: ${id}`));
      }
    }, timeoutMs);
  });
}
```
- Con esto: `search` de `classifyIntent` → clave y mensaje = `intent-classify-M` (prefijo único P2 intacto, sin
  cross-wiring con el `search-*` del SemanticSearch); `faq-match` igual; `index` sigue en `index-N`. Todo coherente.
- **No** cambies `worker.js` (ya devuelve el `id` correctamente); **no** toques SemanticSearch.
- Verifica que no quede otro sitio que dependa del viejo comportamiento (grep `workerRequest(` — solo `index`,
  `classifyIntent`, `matchFAQ`).

## Verificación (reaudito EN VIVO otra vez, mismo método)
1. **Router activo:** consola **sin** `Worker timeout: search-*` ni `index-*` (bundle nuevo). `isClassifierReady()` = true.
2. **FAQ 0-LLM:** "¿cuánto cuesta?" → respuesta **de la taxonomía** (`objectionResponses`), **sin** `POST /api/chat.php`
   en Network, + botón de escape. **Este es el check que hoy falla.**
3. **cita 0-LLM:** "quiero agendar una reunión" → abre `AppointmentPicker`, sin `chat.php`.
4. **guiado 0-LLM:** "¿qué servicios ofrecen?" → recomendación/flujo guiado, sin `chat.php`.
5. **Duda → LLM:** consulta compleja/ambigua → `POST /api/chat.php` 200 (LLM). La duda cae al LLM, no a FAQ forzada.
6. **`trackEvent('intent_routing', …)`** registra intención/confianza/0llm-vs-llm (ahora que el router realmente decide).
7. **Sin regresión:** buscador semántico ok (mismo worker); guiado por botones 0-LLM intacto; sitio sano
   (app 200 / admin 302 / WP 200); chat.php sigue 200 para texto libre complejo (bug #1 no revierte).

## Deploy
- Es solo TS → build de Astro (re-emite `Chatbot.*.js`). No hay PHP nuevo, pero si tocas algo PHP corre `php -l`.
- Deploy **dry-run → `--confirm`** del usuario. Verifica 200 tras subir. Entrégame el reporte y **reaudito en vivo**.

---
**Nota:** Claude (Opus 4.8) confirmó #1 y #2 arreglados en vivo, y detectó el #3 porque el fix del #2 activó por primera
vez la ruta de `search`, que estaba latente-rota por el override de `id` en el spread. Un solo cambio en `workerRequest`
lo cierra. Tras esto el 0-LLM de la 019 debería por fin rutear FAQ/cita/guiado en producción.
