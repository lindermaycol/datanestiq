# Auditoría higiene — 004 ✅, 011 ✅, 010 🔴 corregir: `docs:sync` no debe regenerar el `AGENTS.md` de gobernanza

Auditado el commit `d3165f4`. **Spec 004 y Spec 011 quedaron bien.** **Spec 010 introdujo un footgun** que hay
que corregir antes de darlo por cerrado.

## ✅ Aprobado (no tocar)
- **Spec 004:** banner `> [!IMPORTANT] STATUS: SUPERSEDED_BY: 010` presente en `specs/004/spec.md`, honesto (§2). ✅
- **Spec 011:** `build-taxonomy.mjs` genera `personasCorpus.json` **sin** `contentAngles`; las 6 dependencias del
  cliente importan el JSON limpio; **verificado por mí:** `personasCorpus.json` conserva `id/title/goals/pains/
  decisionCriteria/objectionResponses/...` (las islas siguen con sus datos), build 57 págs, `grep contentAngles dist/` = **0**. ✅
- **Spec 010 — la paralelización en sí:** `Promise.all([runWiki, runAgents, runSkills, runBlog])` con targets
  independientes (rutas disjuntas) + `plan.md`/`tasks.md` formales: bien. ✅

## 🔴 Corrección 010.1 (importante) — NO repuntes `docs:sync` a `--all`
Cambiaste `package.json` a `"docs:sync": "node scripts/docs-generator.mjs --all"`. Problema: `runAgents()`
**sobrescribe el `AGENTS.md` de la raíz vía LLM** (`saveDoc(ROOT_DIR/AGENTS.md, ...)`), y `AGENTS.md` es el
**documento de gobernanza curado a mano** que TÚ (AntiGravity) lees al arranque (roles, guardarraíles, reporting).
Con `docs:sync = --all`, **cada sync rutinario regenera esa gobernanza vía LLM** → riesgo de drift/alucinación en
las reglas que te constriñen. Es un footgun de gobernanza.
- **Fix:** deja `docs:sync` con su significado establecido (**solo wiki**): `"docs:sync": "node scripts/docs-generator.mjs --target=wiki"`.
- **Añade un comando separado** para el fan-out paralelo: `"docs:sync:all": "node scripts/docs-generator.mjs --all"`.
  Así el diamante (speedup) queda disponible **opt-in y deliberado**, sin que el sync rutinario toque `AGENTS.md`.
- **Actualiza** las referencias que digan `npm run docs:sync` esperando "solo wiki" para que sigan siendo wiki
  (ej. el `plan.md` de la Spec 016 y cualquier doc/hook). El `--all` se invoca a mano cuando de verdad quieras regenerar todo.

## 🟠 Corrección 010.2 (robustez) — `Promise.allSettled` + reporte, no `Promise.all`
Con `Promise.all`, si un target falla, los otros ya escribieron sus archivos → estado parcial silencioso (el
comentario dice "reduce en informe atómico" pero no hay reduce real). Cambia a **`Promise.allSettled`** y al final
imprime un **resumen por target** (`ok/failed`), para que un fallo parcial sea visible y no deje docs a medias sin aviso.

## Verificación (evidencia real)
1. `package.json`: `docs:sync` = `--target=wiki`; existe `docs:sync:all` = `--all`.
2. `npm run docs:sync` **NO** modifica `AGENTS.md` (corre solo wiki). `npm run docs:sync:all` corre los 4 en paralelo.
3. `Promise.allSettled` con resumen por target (simula un fallo y verifica que se reporta sin dejar estado silencioso).
4. `--target=wiki|agents|skills|blog` individuales siguen funcionando; build verde.
5. `ESTADO-SPECS.md` fila 010 refleja: `docs:sync` (wiki) + `docs:sync:all` (fan-out paralelo).

## Guardarraíles
- No auto-regenerar gobernanza (`AGENTS.md`) en flujos rutinarios. Free-tier only. Commit en `007-multi-pagina`; pre-commit OK.

---
**Nota:** Claude (Opus 4.8) reauditará: `docs:sync` = wiki-only (no toca `AGENTS.md`), `docs:sync:all` para el fan-out,
`allSettled` con reporte de fallos, y los targets individuales intactos. 004 y 011 ya cerrados.
