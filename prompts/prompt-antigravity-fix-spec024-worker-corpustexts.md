# FIX (P0) — Buscador del blog roto: `BlogSearch` no habla el contrato real del worker (`corpusTexts`)

Reauditoría EN VIVO en `/blog` (skill `verificar-en-vivo`). El buscador **no devuelve nada**: la consola arroja
`BlogSearch Worker error: Corpus not indexed yet and no corpusTexts provided.` (0-LLM sí se respeta — `chat.php` no se
llama — y la telemetría `search_query`/`blog-search` sí dispara, pero **no hay resultados ni estado vacío**).

## Causa raíz (confirmada en vivo y en código)
El diseño (data-model) inventó un contrato `{corpus: [{slug/id, text}], corpusName: 'blog'}`, y `BlogSearch.jsx`
(líneas 64-95) lo implementa así:
```js
workerRef.current.postMessage({ type:'search', corpusName:'blog', corpus: blogCorpus.map(b => ({id:b.slug, text:b.text})), ... });
```
Pero **`public/worker.js` NO lee ese shape**. El worker (de 019/020) espera **`corpusTexts`** (array de **strings**), computa
`computeSignature(corpusTexts)` e indexa con `indexCorpus(extractor, corpusTexts)`. Como no llega `corpusTexts`, el corpus
del blog **nunca se indexa** y el handler de búsqueda lanza exactamente ese error. El worker devuelve resultados **por
índice** (`{index, score}`), no por slug.

## Fix (`src/components/islands/BlogSearch.jsx`) — hablar el contrato REAL del worker (reuso correcto)
1. **Enviar `corpusTexts` (strings), no `corpus` (objetos):**
   ```js
   const corpusTexts = blogCorpus.map(b => b.text); // text = title + excerpt + body_head
   // índice (en handleIntent):
   workerRef.current.postMessage({ type:'index', corpusTexts });
   // búsqueda:
   workerRef.current.postMessage({ type:'search', query, corpusTexts, id: <id-único> });
   ```
   (Mantén el **id único por petición** de la Corrección 1 de la auditoría de diseño y descarta respuestas de id viejo.)
2. **Mapear resultados por índice → post:** el worker responde `{status:'complete', id, results:[{index, score}]}`.
   Convierte con el índice a `blogCorpus[index]` (mismo orden que `corpusTexts`):
   ```js
   const ranked = results
     .filter(r => r.score >= 0.35)          // umbral calibrado (Corrección 2)
     .slice(0, 5)
     .map(r => ({ ...blogCorpus[r.index], score: r.score }));
   ```
   Si `ranked.length === 0` → estado vacío honesto (§2).
3. **No inventar contratos:** usa el **mismo** patrón que `SemanticSearch` (home) ya usa con `worker.js` (corpusTexts +
   resultados por índice). Alternativa NO recomendada: modificar `worker.js` para aceptar `corpus:[{id,text}]` — cambia el
   worker compartido y arriesga regresión del buscador de soluciones y el router. Prefiere adaptar `BlogSearch`.
4. Actualiza el **data-model.md** de la 024 para reflejar el contrato real (`corpusTexts` strings + resultados por índice),
   no el inventado.

## Verificación (reaudito EN VIVO en `/blog`)
- Consulta en lenguaje natural (ej. "estrategia de datos e IA para banca") → **lista de artículos reales** relevantes con
  su enlace, **sin** `chat.php` (0-LLM), **sin** el error de consola "Corpus not indexed…".
- Consulta sin match (ej. "recetas de cocina") → **estado vacío honesto** (§2).
- Búsquedas rápidas consecutivas → sin resultados obsoletos (id único).
- Evento `search_query`/`blog-search` en "Engagement" del panel.

## Deploy
- Solo frontend (`BlogSearch.jsx`). **`npm run build` antes de `deploy_ionos.py --confirm`** (ya auto-verificado).
- **OJO:** el auto-verify del deploy chequea endpoints de `api.php`, **no** las islas cliente → esta feature exige QA de
  navegador en vivo (consola sin errores + resultados). No basta con "SELF-GUARD PASSED".

---
**Nota:** Claude (Opus 4.8) verificó en vivo el error del worker. El `id` único y el umbral 0.35 están bien, pero son
inútiles porque el corpus **nunca se indexa** por el mismatch de contrato (`corpus` vs `corpusTexts`). Adapta `BlogSearch`
a la API real del worker (strings + índices), recompila, y **verifica en el navegador** que el buscador liste artículos y
la consola quede limpia.
