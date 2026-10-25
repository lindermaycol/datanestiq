# CIERRE — Spec 025 GraphRAG: marcar LIVE + documentar deuda + afinar ancla de persona (cfo vs cio)

Reauditoría en vivo: los **2 bloqueantes quedaron resueltos en lo esencial** — guard §2 determinista 0-LLM
(receta → mensaje honesto + agenda, **0 `chat.php`**) y **citas enfocadas** (CFO/banca → `citedUrls` solo
`/sectores/finanzas` + soluciones, **sin** salud/minería; subgrafo 36→26; paráfrasis "entidad crediticia" → ancla
`sector-finanzas`, 10 nodos). `roiCases[EST]` incluido. **Apruebo cerrar la 025 como LIVE** con este cierre.

## 1. Afinar el ancla de persona (precisión menor, verificada en vivo)
En vivo, la consulta *"Soy **CFO** de un banco…"* ancló **`persona-cio`**, no `persona-cfo` (el walkthrough afirmó
`persona-cfo` — no coincidió). Causa: en `findAnchorNodesFallback` (`src/lib/graphTraversal.ts`), el ruido de
coincidencia por palabras de la **descripción** de `persona-cio` supera el `+0.85` del alias directo `'cfo'` de
`persona-cfo`.
- **Fix:** cuando el **código de rol exacto** aparece en la consulta (` cfo`, ` cio`, ` cdo`, ` ceo`, ` ciso`, ` coo`, ` cto`),
  ese persona debe **ganar el slot** `bestPersona` de forma determinista (p. ej. peso del alias de código de rol muy por
  encima del match por palabras de descripción, o desempate explícito "token de rol exacto > word-match"). Reduce el ruido
  del match por `descLower` para personas (baja/quita el `+0.05` por palabra en descripciones de persona).
- **Verificación en vivo:** *"Soy CFO de un banco…"* → `anchors` incluye **`persona-cfo`** (no `persona-cio`) + `sector-finanzas`;
  `citedUrls` sigue enfocado (finanzas + soluciones, sin sectores ajenos). *"responsable de las finanzas de una entidad
  crediticia"* → sigue anclando `sector-finanzas` (aunque sin persona, es aceptable).

## 2. Documentar deuda técnica honesta en `specs/025-graphrag-taxonomia/tech_debt.md`
Añade dos ítems (§2 honestidad del diseño — hoy el diseño dice "anclaje semántico" y no lo es):
- **TD-025.4 — Anclaje léxico por alias, no semántico:** el anclaje usa un diccionario de alias/substrings en
  `findAnchorNodesFallback`, **no** el worker Xenova (`getGraphCorpusTexts()` existe pero **no** se pasa al worker con el
  contrato `corpusTexts`/`graph-anchor-<seq>-<ts>` que el `data-model.md §2` especifica). Frágil ante paráfrasis/sinónimos
  no cubiertos por el diccionario. **Mejora futura:** cablear el anclaje semántico por embeddings (el corpus ya está listo).
- **TD-025.5 — Foco del subgrafo aún amplio por hubs no-sector:** aunque las **citas** quedan enfocadas al sector anclado,
  el conteo de nodos sigue alto (26 en CFO) porque personas/pilares/industrias se expanden libremente en `k=2`. Aceptable
  hoy (las citas son limpias), pero documentar como límite; posible poda por relevancia/top-N a futuro.
- Alinea el `data-model.md` para que **no afirme** "anclaje semántico" mientras la implementación sea léxica (o corrígelo al
  implementar TD-025.4).

## 3. Marcar 025 como LIVE en el SSOT (build-gate antidrift)
- `planes/ESTADO-SPECS.md` ↔ `src/data/specsStatus.json`: pasar Spec 025 de `DESIGNED` → **`LIVE`** con
  `openTechDebt` reflejando TD-025.4/5 y `lastAuditDate` = hoy. Corre `node scripts/build-specs-status.mjs` → PASSED 100%.
- `planes/Fases.md`: refleja 025 como entregada.

## 4. Deploy/QA
- Solo `graphTraversal.ts` (+ docs SSOT). **`npm run build` antes de `deploy_ionos.py --confirm`** (JS gate + auto-verify).
- **Reaudito EN VIVO** (skill `verificar-en-vivo`): CFO/banca → ancla `persona-cfo`+`sector-finanzas`, citas enfocadas;
  paráfrasis → `sector-finanzas`; receta → honesto 0-LLM (sin regresión); consola sin errores.

## OK / no tocar (verificado en vivo)
- Guard §2 determinista (receta → 0 `chat.php`) ✅ · citas enfocadas al sector ✅ · `roiCases[EST]` ✅ · telemetría plana
  PII-redactada ✅ · router 019 0-LLM preservado ✅ · fix backend `ai_efficiency` `tokens_est` (200) ✅.

---
**Nota:** Claude (Opus 4.8) verificó en vivo. El feature está **operativo y honesto** (guard sólido, citas enfocadas). Este
cierre es para (a) que el ancla de persona respete el rol exacto de la consulta, y (b) **declarar con honestidad** que el
anclaje es léxico, no el semántico prometido — sin sobre-vender. Con eso, 025 queda LIVE.
