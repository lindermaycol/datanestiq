# Auditoría DISEÑO Spec 024 (Buscador Semántico sobre el Blog) — APROBADO para BUILD con 1 fix + 1 calibración

Diseño sólido y bien alineado: corpus **SSOT generado en build** (`blogCorpus.json` desde `src/content/blog/*.md`),
búsqueda **100% cliente 0-LLM** reusando `public/worker.js` (cache multi-corpus de 020), **estado vacío honesto §2**,
telemetría 018 con metadata útil (results_count/top_score), `handleIntent` (sin descarga eager), y OUT-OF-SCOPE correcto
(sin GraphRAG/LLM/backend). Apruebo el build con **1 corrección real** (concurrencia) y **1 calibración**.

## 🔴 Corrección 1 (concurrencia) — el `id` es una CONSTANTE, no único por petición (bug de 019/020 otra vez)
`data-model §2`: `id: 'blog-search-query'` con el comentario *"ID único para evitar colisión de concurrencia"* — pero es
una **cadena fija**, no única por petición. Esto **reintroduce** exactamente el patrón que arreglamos en el router 019 y
la demanda 020:
- Dos búsquedas rápidas (debounce/tecleo veloz) emiten dos requests con el **mismo** `id` → el handler no puede
  distinguirlas → la respuesta de la búsqueda #1 puede resolver la #2 → **resultados obsoletos** para la query actual.
- (No colisiona con el buscador de soluciones ni el clasificador porque esos usan otros ids — pero **sí consigo mismo**.)
- **Fix:** genera un **id único por petición** (`nextId('blog-search')` → `blog-search-<n>-<ts>`, como en 019). El handler
  resuelve por ese id y **descarta** respuestas cuyo id no sea el de la última búsqueda pendiente (ignora resultados
  fuera de orden). Corrige también el comentario (hoy afirma "único" siendo constante).

## 🟡 Corrección 2 (calibración) — `SIMILARITY_THRESHOLD = 0.25` es probablemente muy permisivo
0.25 de coseno es **bajo** para "relevante" (el MiniLM multilingüe suele dar 0.4-0.6+ en matches buenos). A 0.25 pueden
colarse artículos apenas relacionados presentados como "relevantes" — roza el §2 (mostrar contenido débil como match).
- El corpus del blog es pequeño hoy, así que un umbral bajo garantiza que aparezca **algo** — trade-off real, y
  `tech_debt` reconoce el ajuste. Pero recomiendo **calibrar con consultas reales** (como hicimos con el FAQ del router):
  prueba 5-8 queries y elige el umbral que devuelva artículos **genuinamente** relevantes, no "vagamente on-topic".
  Documenta el valor y el criterio. Si dudas, **sube** el umbral (mejor "no encontramos" honesto que un match flojo).

## OK tal como está (no cambiar)
- `blogCorpus.json` SSOT en `prebuild` con Zod/validación (como `build-taxonomy`) ✅; `text` = title+excerpt+body_head
  (buena densidad semántica) ✅.
- Reuso del **mismo worker/modelo** (sin descargar otro), cache por firma (020) ✅; `handleIntent` sin eager download ✅.
- Estado vacío honesto §2 ✅; telemetría `search_query` redactada (track_event.php) con metadata ✅.
- Plan que referencia el **auto-verify de `deploy_ionos.py`** ✅; deudas honestas (concurrencia, tamaño del corpus, umbral) ✅.

## Siguiente paso — PROCEDE AL BUILD
1. Aplica el **id único por petición** (corrección 1) + la **calibración del umbral** (corrección 2, con evidencia).
2. Build: corpus en `prebuild`; isla `BlogSearch.jsx`; vista en `/blog`; telemetría 018. **`npm run build` antes de
   `deploy_ionos.py --confirm`** (ahora auto-verificado). **Reaudito EN VIVO**: en `/blog`, una consulta en lenguaje
   natural devuelve artículos **reales** relevantes **sin** `chat.php` (0-LLM); una consulta sin match → estado vacío
   honesto; búsquedas rápidas consecutivas **no** muestran resultados obsoletos; el evento `search_query` (blog) aparece
   en "Engagement" del panel.

---
**Nota:** Claude (Opus 4.8) auditará en vivo. Lo estructural está bien; el bloqueante es el **id de concurrencia**
(hazlo único por petición, no una constante — lección de 019/020), más calibrar el umbral 0.25 (hoy muy permisivo, §2).
