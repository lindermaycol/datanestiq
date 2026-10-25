# Prompt para Antigravity — Cierre de gaps del checklist de validación (P2)

Un checklist de validación interactiva encontró varios ítems. **Ya trié contra el código**: la mayoría están resueltos (prueba social eliminada, calidad de padrones, cobertura de objeciones, sectores con KPIs). Quedan **2 gaps reales** (+ 1 mejora opcional de UX). No re-audites lo resuelto.

## 🔴 Gap 1 (P2) — La búsqueda semántica no discrimina servicios (el highlight no aterriza)
**Causa (verificada):** en `src/components/islands/SemanticSearch.jsx` (`handleResults`), se publica el store con **scores continuos para TODOS** los servicios:
```js
results.forEach(res => { highlightMap[item.id] = res.score; });
semanticHighlight.set(highlightMap);
```
`HighlightSync` resalta todo lo que supere `threshold = 0.2`. Como la similitud semántica da a varios servicios un score > 0.2, **se resaltan casi todos → sin diferenciación**. (Los chips SÍ funcionan porque discretizan a top-2.)

**Fix:** que la búsqueda **discretice como los chips**: en `handleResults`, para las entradas de **servicio** (`service-*`), quédate con los **top 2–3 por score** (y opcionalmente solo si superan un mínimo real de relevancia, p. ej. 0.35), asígnales un valor alto (ej. `1.0`) en el `highlightMap`, y **omite el resto** (para que HighlightSync los atenúe). Mantén el comportamiento para sectores/industrias si aplica. 
- Elimina el código muerto de `serviceMatches` que quedó tras el refactor a `HighlightSync` (ya no manipula el DOM aquí; solo el store manda).
- Verifica que si **ningún** servicio supera el mínimo, no se rompa (que no atenúe todo sin razón, o que deje estado neutro).

## Gap 2 (P2) — `hiperautomatizacion` no menciona despliegue On-Premise
En `src/content/pillars/hiperautomatizacion.yaml` (capa de datos), enriquece `techStack`/`competitivePositioning` para incluir **arquitecturas soportadas: Cloud / Híbrido / On-Premise** (relevante para CIO/sector público que no pueden ir 100% nube). Corre `build-taxonomy.mjs` (Zod + aristas). **Honestidad:** solo capacidades reales; nada inventado.

## Mejora opcional (UX, decides tú) — Cambiar de rol sin resetear
Hoy, con un contexto activo se muestra la píldora *"Viendo como: … ✕"* y para cambiar de rol hay que pulsar ✕ y re-elegir (2 pasos). **Opcional:** permitir cambiar de rol directamente (p. ej. que la píldora ofrezca los otros roles, o un pequeño selector). Si lo haces, **asegura que al cambiar se actualice el contexto Y el highlight en un solo clic** (sin el "primer clic usa el contexto anterior" que reportó el checklist). Si prefieres no tocarlo, déjalo — no es un bug.

## Guardarraíles
- **No rompas** lo que ya pasó: chips → highlight top-2, reset, chatbot 0-LLM (10 sectores), calculadora, prueba social ya limpia.
- Taxonomía = fuente de verdad (el on-prem va en el YAML, no en `.astro`).
- `npm run build` verde; consola limpia.

## Verificación (evidencia real)
1. **Búsqueda semántica:** ejecuta *"tengo mucha morosidad en mi cartera de créditos"* → deben resaltarse **1–3 servicios pertinentes** (ej. AI & Data Science, BI) y **atenuarse el resto** (mide clases `is-highlighted`/`is-dimmed`). Antes: 0 diferenciación.
2. **On-prem:** `grep` en `dist/soluciones/hiperautomatizacion` muestra Cloud/Híbrido/On-Premise.
3. (Si haces la mejora de UX) cambiar de rol desde la píldora actualiza CTA + highlight en 1 clic, sin estado stale.
4. `npm run build` verde. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: que la búsqueda semántica **discrimine** (no resalte los 6), el on-prem en hiperautomatización, y que nada de lo ya aprobado se rompa.
