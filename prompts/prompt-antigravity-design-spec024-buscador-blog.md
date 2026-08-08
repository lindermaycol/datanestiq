# DISEÑO SDD — Spec 024: Buscador Semántico sobre el Blog (0-LLM, reuso de Xenova)

**Tarea de DISEÑO, NO implementación.** Escribe los artefactos de la **Spec 024** (`specs/024-buscador-blog/`): 5
bloques + `data-model.md` + `plan.md` + `tech_debt.md`. **Entrégalos para auditoría de Claude ANTES de construir.** Cero runtime.

## WHY
El sitio ya tiene un **buscador semántico en el home** (isla `SemanticSearch`, embeddings cliente con Xenova sobre el
catálogo de **soluciones**) y un **blog** creciente (`src/content/blog/*.md`). Hoy no hay forma de **buscar
semánticamente los artículos** — un visitante que describe su problema en lenguaje natural no encuentra el contenido
relevante. Esta spec extiende el mismo motor 0-LLM al blog: **0 tokens LLM, reusando el worker Xenova ya montado**.

## Contexto (verificado — reusar, no reinventar)
- `public/worker.js` ya tiene **cache multi-corpus** (`cachedCorpora` por firma, Spec 020) → un corpus adicional (blog)
  encaja sin re-embeber los otros.
- La isla `SemanticSearch` (home) ya implementa el patrón: embeber la consulta en cliente, coseno contra el corpus
  cacheado, resultados ordenados, skeleton/CTA, `handleIntent` (index en hover/focus, sin descarga eager).
- La telemetría 018 (`trackEvent`/`search_query`) ya registra búsquedas.

## WHAT (alcance — diseño)
### 1. Corpus del blog (SSOT generado en build)
- Script `scripts/build-blog-corpus.mjs` (en `prebuild`, como `build-taxonomy`) que genera `src/data/blogCorpus.json`
  desde `src/content/blog/*.md`: por artículo → `{ slug, title, excerpt/description, tags, url }` (+ opcional primeros N
  caracteres del cuerpo para más señal semántica). **Fuente de verdad editable/versionada**, sin hardcodear en el JSX.
- Validación (Zod/exit 1) como el resto de builds; el corpus se sirve al cliente (no PII, es contenido público).

### 2. Búsqueda semántica en el blog (0-LLM, cliente)
- Isla de búsqueda en la **página del blog** (`/blog`) — reusa el patrón de `SemanticSearch` (o una `BlogSearch`
  derivada). Al escribir, **embebe la consulta** (Xenova, **mismo worker**, corpus `blog` cacheado por firma) → coseno →
  **top-N artículos** por similitud sobre un **umbral** → lista de resultados (título, excerpt, tags, enlace "Leer").
- **§2:** si nada supera el umbral → estado honesto ("No encontramos artículos para tu búsqueda; prueba otras palabras o
  el AI Concierge"), **nunca** resultados inventados ni forzados. El excerpt mostrado es el **real** del artículo.
- **Reuso del worker:** NO cargar un modelo nuevo; el catálogo del home y el del blog conviven en `cachedCorpora`.
  Cuida la concurrencia por `id` (lección de 019: ids únicos, sin cross-wiring con el buscador de soluciones).

### 3. Instrumentación (018)
- Registra la búsqueda del blog (`trackEvent('search_query', 'blog-search', <consulta redactada>)` o un target propio)
  para que aparezca en "Engagement & Comportamiento" del panel — así sabes **qué busca la gente en el blog**.

## CONSTRAINTS (declararlas)
- **§5 (0-LLM):** clasificación/búsqueda **100% cliente** (Xenova), **0 tokens LLM**. Reuso del worker y sus embeddings.
- **§2 (Honestidad):** resultados solo del corpus real; estado vacío honesto; excerpts reales; sin inflar relevancia.
- **Reuso:** mismo worker/modelo (sin descargar otro), patrón de la isla `SemanticSearch`, telemetría 018. Free-tier.
- **Rendimiento/UX:** `handleIntent` (index en intención, no eager); skeleton; el corpus del blog es pequeño-mediano →
  índice una vez (cache por firma). No bloquear la navegación del blog.
- **Privacidad:** la consulta del blog se registra **redactada** (reusa `redactPii` vía `track_event.php`), sin PII.

## OUT-OF-SCOPE (declararlo)
- **NO** GraphRAG, **NO** MCP endpoint, **NO** buscador full-text de backend, **NO** resúmenes por LLM de los resultados.
- **NO** reemplazar el buscador de soluciones del home (lo complementa; opcional/futuro: que el home también sugiera un
  artículo relevante — **fuera de alcance** en v1).

## data-model.md (define)
- Esquema de `blogCorpus.json` (`slug`, `title`, `excerpt`, `tags[]`, `url`, opcional `body_head`) + la fuente
  (`src/content/blog/*.md`) y el script generador.
- El flujo de búsqueda: consulta → worker (`type:'search'`, corpus `blog`, `id` único) → top-N ≥ umbral → render.
- Umbral de relevancia (parámetro versionado) y regla de estado vacío (§2).
- Evento de telemetría del buscador de blog.

## Entregables (solo diseño)
- `specs/024-buscador-blog/{spec.md, data-model.md, plan.md, tech_debt.md}`.
- Doc-sync: fila 024 en `ESTADO-SPECS.md` (🟠 diseñada) + `src/data/specsStatus.json` (build-gate) + `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

## Nota de build/deploy (para el plan)
- El corpus se genera en `prebuild`; la isla es frontend. **`npm run build` ANTES de `deploy_ionos.py --confirm`** (el
  sitio/PHP se sirve desde `dist/`), y **verifica en vivo** que el buscador del blog devuelve artículos reales.

---
**Nota:** Claude (Opus 4.8) auditará: búsqueda **100% cliente 0-LLM** reusando el worker (sin modelo nuevo, sin
cross-wiring con el buscador de soluciones), corpus del blog como **SSOT generado en build**, estado vacío **honesto**
(§2), y telemetría redactada. Reuso máximo del patrón `SemanticSearch` y del cache multi-corpus de la 020.
