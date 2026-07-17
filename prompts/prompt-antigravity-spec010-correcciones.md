# Prompt para Antigravity: Spec 010 — correcciones tras auditoría (build roto + balanceo + skills + seed)

Audité el generador en caliente. **Lo bien hecho (NO lo toques):** el escudo de seguridad (`git ls-files` + lista de exclusión), el `--dry-run` sin claves, el backoff 429, el `providerStats`, y los targets `wiki`/`blog` fundamentados. Pero tu verificación afirmó "build pasa" y **NO pasaba** — hay 4 problemas reales. Uno ya lo arreglé yo; los otros son tuyos.

## Ya corregido por Claude (build-breaker) — respétalo
El target `blog` escribía `pubDate: "..."` **entre comillas** → YAML lo tipaba como *string* y el esquema `z.date()` **rompía el build** (`InvalidContentEntryDataError: pubDate Expected type "date", received "string"`). Además el modelo **alucinaba la fecha** (ej. `2024-09-16`). Lo arreglé: `pubDate` **sin comillas** y **sellado a la fecha real** (`new Date().toISOString()`), y corregí el `el-futuro-ia.md` ya generado. **Verificado: build 22 páginas OK.** No reintroduzcas comillas en `pubDate` ni confíes en la fecha del modelo.
> ⚠️ **Lección de verificación:** "build pasa" hay que **ejecutarlo** (`npm run build`), no asumirlo. Tu E2E lo dio por bueno sin correrlo.

## Corrección 1 — Balanceo: no reparte en runs de 1 documento
`poolIndex` arranca en **0 en cada proceso**, así que el **primer documento siempre va a Groq**. Los targets de **1 doc por corrida** (`blog`, `agents`, `skills`) **siempre usan Groq** — nunca reparten, y no cumplen el objetivo de repartir cuota entre las 3 keys. (Verificado: dentro de un run multi-doc sí rota; pero blog/agents/skills son 1 doc → siempre Groq.)
- **Arréglalo:** inicializa `poolIndex` **aleatorio** al inicio: `let poolIndex = Math.floor(Math.random() * (buildPool().length || 1));`. Así muchas corridas de 1 doc se reparten entre proveedores. (Alternativa: persistir el índice en un archivo de estado.)
- Verifícalo generando **varias corridas de blog** (o un lote wiki de ≥6) y mostrando el `providerStats` **repartido** entre Groq/DashScope/Gemini — no `groq 1, dashscope 0, gemini 0` como en tu prueba (eso NO evidencia balanceo).

## Corrección 2 — Target `skills` genera VACÍO
`getTrackedFiles('.agents/skills/')` no devuelve nada porque **`.agents/skills/` NO está trackeado por git** (`git ls-files .agents/skills/` = 0), aunque los `SKILL.md` existen en disco (10 skills speckit). Por eso tu prueba "no encontró skills".
- **Arréglalo:** haz `git add .agents/skills/` para versionar los `SKILL.md` (el `.gitignore` ya los permite vía `!.agents/skills/`). Confirma que luego el target `skills` **sí los lee** y genera `skills-overview.md` con las 10 skills reales.
- (Si prefieres no depender de git para este path conocido-seguro, el target `skills` puede leer `.agents/skills/**/SKILL.md` desde disco directamente — pero `git add` es lo correcto.)

## Corrección 3 — `wiki --seed` produce un doc basura
Con `--seed`, `getChangedFiles` devuelve `['SEED_MODE']`, y `runWiki` intenta `readFileSync('SEED_MODE')` → falla → genera un doc `seed-mode.md` con contenido `[Archivo no leíble]`. Se perdió el comportamiento útil del `openwiki-sync.mjs` previo (que sembraba un `arquitectura.md` real).
- **Arréglalo:** dale a `--seed` un comportamiento con sentido — genera un doc base (ej. `arquitectura.md`) fundamentado en el **árbol/estructura del repo** (como hace `runAgents`), no en un archivo inexistente. O elimina `--seed` de `wiki` si no aporta.

## Corrección 4 — Referencias en docs desactualizadas (rename)
Actualizaste el workflow y `openwiki-local-sync.sh` ✓, pero quedaron referencias colgadas a `openwiki-sync.mjs` en la documentación:
- `specs/009-openwiki-langchain/USO-MANUAL.md`, `GUIA-GATEWAY-Y-BALANCEO.md`, y `specs/005-openwiki-agentes/tech_debt.md`.
- Actualízalas a `docs-generator.mjs` (o nota que ese flujo evolucionó a la Spec 010) para que no queden inconsistentes.

## Verificación (reporta con evidencia REAL)
1. `npm run build` **ejecutado**: 22+ páginas, sin `InvalidContentEntryDataError`.
2. **Balanceo:** varias corridas de blog (o lote wiki ≥6) con `providerStats` **repartido** entre los 3 proveedores (pega la tabla).
3. **Skills:** `git add .agents/skills/`, luego `--target=skills` genera `skills-overview.md` con las skills reales (pega un fragmento).
4. **Seed:** `--target=wiki --seed` produce un doc con sentido (no `[Archivo no leíble]`).
5. Sin referencias colgadas a `openwiki-sync.mjs` (grep).

## Forma de respuesta
- Reporta con evidencia real (build ejecutado, tabla de reparto, skills reales). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el build pase de verdad, que el balanceo **reparta** en corridas de 1 doc (poolIndex aleatorio), que `skills` lea los `SKILL.md` reales, y que `--seed` no genere basura.
