# Auditoría DISEÑO Spec 026 (Analítica de Contenido/Blog en el Panel) — APROBADO para BUILD con 1 corrección (esencial) + 3 precisiones

Diseño **sólido y bien alineado**: reusa `interaction_events` y el patrón de `ops_behavior_analytics`, **aísla
`event_target='blog-search'`** (sin colisión con `graphrag`), payload JSON `{q,n,top}` con **retro-compatibilidad** de filas
viejas, guard §2 de muestra mínima (<15), §6 PII (redactada en `track_event.php`), 0-LLM (SQL puro), y aplica las lecciones
del panel (`setHTML` null-safe + contenedores **explícitos** `#blog-*` + sub-vista hermana en `view-behavior`). Deudas
honestas. Apruebo el build con **1 corrección esencial** y **3 precisiones**.

## 🔴 Corrección 1 (ESENCIAL — sin esto, la detección de gaps NO funciona) — la telemetría se emite ANTES de tener resultados
Hoy en `src/components/islands/BlogSearch.jsx`, `trackEvent('search_query','blog-search', val)` se dispara en
**`handleSearch` (≈L92) ANTES** de enviar la búsqueda al worker (L95). Los resultados llegan **después**, de forma asíncrona,
en el handler `message` del worker (donde se calcula `filtered`). Por tanto, si empaquetas `n: results.length` /
`top: results[0]?.score` en `handleSearch`, **`results` es el estado de la búsqueda ANTERIOR** (o `[]`): `n` y `top` saldrían
**stale/incorrectos** y las "búsquedas con 0 resultados" **nunca** se registrarían bien → US-026.2 (gaps) quedaría rota.
- **Fix:** **mover la emisión** de `handleSearch` al **handler de `complete`/`results` del worker**, donde ya existe
  `filtered` (tras el filtro por umbral 0.35). Empareja la query correcta: guarda `val` en un ref al enviar
  (p. ej. `activeQueryRef.current = val` junto a `activeReqIdRef.current = reqId`) y emite dentro del handler, respetando el
  **guard de concurrencia** (solo el `responseId === activeReqIdRef.current`):
  ```js
  // en el handler, rama complete, tras calcular `filtered`:
  trackEvent('search_query', 'blog-search', JSON.stringify({
    q: activeQueryRef.current, n: filtered.length, top: filtered[0]?.score || 0
  }));
  ```
  Así `n=0` (sin match) se captura de verdad. **Elimina** la emisión vieja en `handleSearch` (no dupliques el evento).
  Refleja esto explícitamente en `data-model.md §1.1` y `plan.md Fase 1` (hoy el comentario dice "tras el Worker" pero el
  plan no dice que hay que **sacarla** de `handleSearch`).

## 🟡 Corrección 2 (precisión SQL) — `json_extract` sobre filas legacy de texto plano **rompe** la consulta
Con filas mixtas (JSON nuevas + texto plano viejas), `json_extract(event_value,'$.q')` sobre un string no-JSON lanza
**"malformed JSON"** en SQLite (no devuelve null silenciosamente). Guarda SIEMPRE con `json_valid()`:
```sql
CASE WHEN json_valid(event_value) THEN json_extract(event_value,'$.q') ELSE event_value END AS q
CASE WHEN json_valid(event_value) THEN json_extract(event_value,'$.n') ELSE NULL END AS n
```
O alternativa igual de válida: traer las filas y **parsear en PHP** (`json_decode` con try/catch → fallback `q=event_value,
n=null`). Documenta cuál usas.

## 🟡 Corrección 3 (precisión de agregación) — agrupar por la query EXTRAÍDA y normalizada, no por `event_value` crudo
Si agrupas `GROUP BY event_value`, dos búsquedas idénticas con distinto `{n,top}` **no** se agrupan (el JSON difiere).
El ranking debe agrupar por la **`q` extraída y normalizada** (minúsculas + trim, como pide US-026.1), no por el string JSON
completo. Deja explícito en `data-model.md` el `GROUP BY LOWER(TRIM(q))`.

## 🟡 Corrección 4 (honestidad del denominador §2) — `zero_match_rate` solo sobre filas con `n` conocido
Las filas legacy (`n=null`) no se pueden clasificar como match/no-match. Calcula `zero_match_rate` **solo** sobre el universo
con `n` conocido (JSON), no sobre `total_searches` (que incluye legacy) — si no, la tasa sale sesgada. Documenta el
denominador y, si aplica, muestra "N con datos de resultados" junto al %.

## OK tal como está (no cambiar)
- Aislamiento `event_type='search_query' AND event_target='blog-search'` (evita colisión con GraphRAG) ✅.
- Retro-compat de filas viejas (`n=null`, excluidas de gaps) ✅; guard §2 <15 con `insufficient_data` honesto ✅.
- Reuso del patrón `:inc_b`/`NOT LIKE 'demoseed%'` (exclusión demo) ✅ — mantén el **mismo criterio** que 020/023.
- Panel: contenedores **explícitos** `#blog-analytics-summary/#blog-top-queries/#blog-content-gaps/#blog-search-trend` +
  `setHTML` null-safe + sub-vista en `view-behavior` (lecciones `view-ops`) ✅. Gráficos CSS/SVG ligeros ✅.
- OUT-OF-SCOPE 0-LLM, solo-lectura, sin PII nueva ✅.

## Siguiente paso — PROCEDE AL BUILD
1. Aplica **Corrección 1** (mover la telemetría al handler del worker — es lo que habilita los gaps) + precisiones 2-4.
2. Build: emisión corregida en `BlogSearch.jsx`; endpoint `blog_analytics` en `api.php`; vista en `view-behavior`.
   **`npm run build` antes de `deploy_ionos.py --confirm`** (JS gate + auto-verify; añade el marcador de `blog_analytics`).
3. **Reaudito EN VIVO** (skill `verificar-en-vivo`): en `/blog`, una búsqueda con match y otra **sin** match →
   verifico en el endpoint real `blog_analytics` que aparezcan `top_searches` y **`content_gaps`** con `n` correcto (no
   stale); guard `<15` responde `insufficient_data`; en `/admin/` (Engagement) la sub-sección **carga datos** (no solo el
   encabezado), consola sin `SyntaxError`/`ReferenceError`.

---
**Nota:** Claude (Opus 4.8) auditará en vivo. Lo estructural está muy bien; el bloqueante real es de **timing de la
telemetría** (hoy se emite antes de los resultados → `n`/`top` stale y gaps rotos): muévela al handler del worker con la
query en un ref. Las otras 3 son de robustez SQL/agregación. §2/§6 y las lecciones del panel están bien contempladas.
