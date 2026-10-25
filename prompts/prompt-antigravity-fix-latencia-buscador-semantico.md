# Fix — Latencia del Buscador Semántico: el worker re-embebe el corpus en CADA consulta

Auditoría del reporte QA: **1 bug real de performance** (confirmado en código), varios **falsos positivos** (ver abajo — NO tocar). Extiende Spec 002. Guardarraíles intactos (0-LLM, taxonomía, honestidad).

## 🔴 Bug real (raíz confirmada en `public/worker.js`)
En el handler de búsqueda del worker:
```js
let queryOutput  = await extractor(query, { pooling:'mean', normalize:true });      // rápido (1 texto)
let corpusOutput = await extractor(corpusTexts, { pooling:'mean', normalize:true }); // ⛔ re-embebe ~40 ítems EN CADA consulta
```
El **modelo** se cachea en IndexedDB, pero los **embeddings del corpus se recalculan por consulta** → búsquedas de **~15s (y hasta >50s en máquinas lentas)**, aun con el modelo ya cargado. Además, `SemanticSearch.jsx > handleSearch` **reenvía todo `corpusTexts` por `postMessage` en cada búsqueda** (línea ~150), lo que agrava el costo.

El corpus es **estático** durante la sesión: hay que embeberlo **una sola vez** y cachearlo.

## Fix (worker + componente)
**`public/worker.js`:**
1. Añade caché a nivel de módulo de los embeddings del corpus: guarda el `Float32Array` aplanado del corpus + `dim` + el `length` del corpus como firma.
2. Separa responsabilidades por tipo de mensaje (recomendado):
   - `{ type: 'index', corpusTexts }` → embebe el corpus **una vez**, cachea, responde `{status:'indexed'}`. Si ya está cacheado (misma firma), no re-embebe.
   - `{ type: 'search', query, id }` → embebe **solo la query** y calcula la similitud coseno contra el corpus **cacheado**. Si aún no hay índice, que primero indexe.
   - Mantén `{ type:'warmup' }` como está (carga del modelo).
3. Conserva el mismo cálculo de coseno y el formato de `results` (`{index, score}` ordenado) para no romper `handleResults`.

**`src/components/islands/SemanticSearch.jsx`:**
4. Envía el corpus al worker **una sola vez** (en el `warmup`/primer uso) con `{type:'index', corpusTexts: corpusRef.current.map(c=>c.text)}`. En `handleSearch`, envía **solo** `{type:'search', query, id}` — **no** reenvíes `corpusTexts` en cada búsqueda.
5. (Defensivo) En el listener de mensajes, evita que un mensaje tardío de `progress` posterior a `complete` reviente el estado `done` (hoy `setStatus(prev => prev==='searching' ? 'searching' : 'loading_model')` puede pisar `done`). Ignora `progress` si el estado ya es `done`.

## Resultado esperado
- **1ª búsqueda de la sesión:** carga modelo (una vez) + indexado del corpus (una vez) — con el skeleton/progreso que ya existe.
- **Búsquedas siguientes:** **< 1s** (solo se embebe la query). Es la meta.

## ⚠️ NO tocar — falsos positivos del QA (ya reverificados por Claude en navegador/código)
- **HeroRoleLine "no cambia por chip"** → **FALSO.** Verificado en vivo: renderiza *"CDO / Chief Data Officer: Valor Del Dato · Criterio: Escalabilidad de la gobernanza"* para cada rol. **Funciona. No modificar.**
- **ConsultativeCTA "sin variante por rol"** → **FALSO.** `ConsultativeCTA.jsx` cambia el texto por contexto (cdo/publico, cfo, ceo) y el Hero lo renderiza `client:load`. **Funciona. No modificar.**
- **CopilotDemo "no se adapta al chip CDO"** → **NO es bug, es diseño.** El Copiloto se adapta a la **búsqueda** (`semanticHighlight`), no al chip (`userContext`). Correcto así. **No lo conectes al chip.**
- **Sección 0 (servidor PHP se cae)** → limitación conocida del `php -S` (dev, single-thread) al bloquearse en la llamada LLM de `chat.php`. **No es defecto del sitio**; producción usa Apache/PHP-FPM. Nota: el modelo WASM lo baja el worker desde el **CDN de jsdelivr**, no por `php -S`, así que el buscador **no** satura el servidor PHP.

## Re-verificar DESPUÉS del fix (el QA no pudo por la lentísima búsqueda >50s + servidor caído 2 veces)
Con la búsqueda ya rápida, comprobar en limpio (`localStorage.clear()` + esperar hidratación):
1. Latencia de la 2ª búsqueda **< 1s** (mídela con `performance.now()`).
2. Bloque **"Encontramos N soluciones"** + 3 botones (Ver cómo razona / Iniciar Diagnóstico / AI Concierge) aparece al completar (`status==='done'`, `serviceCount>0`).
3. En "Descubre el Impacto en tu Sector": sectores relevantes **resaltados** (no todos uniformes) e inputs **pre-llenados** con la query (`userChallenge` ya se setea en `handleSearch`).
4. `npm run build` verde; consola limpia; **cero `chat.php`** en el flujo del buscador.

## Doc-sync
Actualiza `planes/ESTADO-SPECS.md` (Spec 002): registrar el fix de latencia (embeddings de corpus cacheados en el worker; búsquedas subsecuentes <1s).

---
**Nota:** Claude (Opus 4.8) reauditará: latencia de la 2ª búsqueda (<1s medida), el bloque "Encontramos N", highlights de sectores, pre-llenado, y que los falsos positivos (HeroRoleLine/ConsultativeCTA/Copiloto) **sigan intactos**.
