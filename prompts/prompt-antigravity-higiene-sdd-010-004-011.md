# Higiene SDD — cerrar deuda declarada: Spec 010 (diamante + formalizar), Spec 004 (superseded), Spec 011 (contentAngles)

Tres ítems de **deuda ya declarada** en `planes/ESTADO-SPECS.md`. Guardarraíles de siempre (Constitución;
`npm run build` verde; `php -l` si tocas PHP; pre-commit hook; doc-sync). Rama `007-multi-pagina`.

## 1. Spec 010 — diamante en `docs-generator.mjs` (paralelizar los 4 targets) + formalizar SDD
**Verificado:** `docs-generator.mjs` se invoca **por-target** (`--target=wiki|agents|skills|blog`) y es
**secuencial**; no hay orquestador que corra los 4.
- **Primero VERIFICA independencia (fake-edge test):** confirma que los 4 targets **no comparten estado
  mutable** ni orden (cada uno escribe a rutas distintas: `src/content/wiki/`, `AGENTS.md`,
  `skills-overview.md`, blog). Si hay alguna dependencia real, **NO** paralelices ese borde y decláralo.
- **Si son independientes:** crea un **orquestador** (ej. `docs:sync:all` / script) con patrón diamante:
  `load taxonomy → fan-out(wiki, agents, skills, blog) en Promise.all → reduce(commit atómico único) → fin`.
  Latencia = max(4), no sum(4). **El commit/escritura de resultados debe ser atómico** (reduce), no 4 commits sueltos.
  No metas frameworks (LangGraph); JS + `Promise.all` (patrón "graphs sin framework").
- **Formaliza la higiene SDD:** crea `specs/010-generador-multidestino/plan.md` y `tasks.md` (el plan estaba
  suelto en `/planes`). Mueve/consolida el contenido de diseño a `plan.md`.
- **Verificación:** los 4 targets siguen generando el mismo output que antes (compara), build verde,
  y (si mides) el tiempo total baja hacia `max(4)`.

## 2. Spec 004 — nota formal de SUPERSEDED
`ESTADO-SPECS.md` ya dice que la 004 (LangGraph) está **abandonada** y su objetivo se sirve con la Spec 010.
Falta dejarlo limpio en la carpeta de la spec:
- En `specs/004-metodologia-desarrollo-digital/spec.md`, añade al inicio un bloque/frontmatter honesto:
  **`STATUS: SUPERSEDED_BY: 010`** + 1-2 líneas: "el pipeline LangGraph nunca se construyó (decisión del
  usuario); el objetivo —agentes que redactan/mantienen el sitio— se sirve con el patrón single-shot
  controlado de la Spec 010 (`docs-generator.mjs`)." **No borres** el spec; solo márcalo. Honestidad §2 intacta.

## 3. Spec 011 — fix del leak de `contentAngles` al cliente
**Deuda pre-existente:** `personas.json` (generado por `build-taxonomy.mjs`) incluye `contentAngles`, que es
contexto interno, y se **bundlea al cliente** (islas como `HeroRoleLine`/`Chatbot` importan `personas.json`).
- **Fix:** que el JSON que llega al **bundle público NO** incluya `contentAngles` (strip en `build-taxonomy.mjs`
  al emitir el JSON consumido por el cliente, o generar un `personas.client.json` sin ese campo y que las islas
  importen ese). Mantén `contentAngles` disponible para lo que lo use **en build-time/servidor** si aplica.
- **Verificación:** `grep -r contentAngles dist/` (tras `npm run build`) → **cero** ocurrencias en el bundle
  del cliente; las islas siguen funcionando (HeroRoleLine muestra el rol, chatbot hereda contexto);
  `build-taxonomy` verde (Zod + aristas).

## Guardarraíles
- Taxonomía = SSOT; no hardcodear; 0-LLM del flujo guiado intacto; honestidad §2.
- `npm run build` verde; consola limpia; doc-sync a `ESTADO-SPECS.md` (010 formalizada, 004 superseded, 011 sin el leak).
- Commit(s) en `007-multi-pagina`; pre-commit hook OK.

## Verificación (evidencia real)
1. **010:** orquestador paralelo con output idéntico al secuencial (evidencia de comparación) + `specs/010/plan.md` y `tasks.md` presentes; build verde.
2. **004:** `specs/004/spec.md` con el marcador `SUPERSEDED_BY: 010` y la nota honesta.
3. **011:** `grep contentAngles dist/` = 0 en el bundle cliente; islas OK.
4. `ESTADO-SPECS.md` actualizado en las 3 filas.

---
**Nota:** Claude (Opus 4.8) reauditará: la paralelización solo donde los targets son realmente independientes
(sin romper el output ni el commit atómico), el marcador de superseded honesto en la 004, y **cero
`contentAngles` en el bundle del cliente** con las islas intactas.
