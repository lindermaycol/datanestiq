# Prompt para Antigravity: (1) cerrar higiene SDD de la Spec 010 + (2) NUEVA Spec 011 — Enriquecimiento profundo de la Taxonomía

Actúa como **Arquitecto de datos/taxonomía + Ingeniero Node + auditor SDD**. Este prompt tiene **dos partes**. La Parte 1 es documentación (hazla directa). La Parte 2 sigue **SDD estricto**: entrega `spec.md` + `plan.md` + `tasks.md` para **mi revisión ANTES de implementar** (la taxonomía es la fuente única de verdad; un cambio a ciegas rompe muchos consumidores).

---

# PARTE 1 — Cerrar la higiene SDD de la Spec 010 (directo, no cambia código)
La Spec 010 (Generador Multi-Destino, `scripts/docs-generator.mjs`) está implementada y verificada (4 targets wiki/agents/skills/blog, balanceo Groq⇄DashScope⇄Gemini con `poolIndex` aleatorio + failover + backoff 429, seguridad `git ls-files` + lista de exclusión, build 23 páginas). **Pero le faltan artefactos SDD formales.** Crea:
- **[NEW] `specs/010-generador-multidestino/plan.md`**: el plan de implementación real (qué se construyó, arquitectura del motor, targets, balanceo, seguridad).
- **[NEW] `specs/010-generador-multidestino/tasks.md`**: la lista de tareas, marcadas como completadas, incluyendo las correcciones post-auditoría (pubDate sin comillas, poolIndex aleatorio, skills tracked, seed con sentido).
No modifiques el código de la 010; es solo asentar el SDD.

---

# PARTE 2 — NUEVA Spec 011: Enriquecimiento profundo de la Taxonomía (SDD)

## Contexto
La taxonomía es la **fuente única de verdad** de Datanestiq: pilares, sectores, industrias extendidas y personas. Alimenta las microinteracciones de IA, las páginas de sector, y **ahora el generador de blog/wiki de la Spec 010** (`docs-generator.mjs --target=blog` lee `src/data/*.json` como contexto). Ya está enriquecida (CIIU, DAMA/CRISP-DM, ESCO, madurez Gartner, personas). **Queremos profundizarla mucho más**, de forma **aditiva** (sin romper consumidores), con integridad referencial y build en verde.

## Pipeline actual (respétalo)
- **Fuente:** YAML en `src/content/{pillars,sectors,industries}/*.yaml` (+ la fuente de personas).
- **Esquemas Zod:** `src/lib/schemas.js` (`pillarSchema`, `sectorSchema`, `industrySchema`).
- **Generador/validador:** `scripts/build-taxonomy.mjs` valida con Zod, verifica **integridad referencial** (falla `exit 1` ante refs inválidas) y regenera `src/data/{taxonomyCorpus,sectorsCorpus,extendedIndustries,personas}.json`. Corre en `predev`/`prebuild`.
- **Consumidores:** islas React (microinteracciones), `src/pages/sectores/[slug].astro`, y `docs-generator.mjs` (blog). Los JSON deben mantener **shape compatible** (solo añadir campos, nunca renombrar/quitar) para no romper nada.

## Dimensiones a enriquecer (defínelas en el `spec.md`)
1. **Sectores:** sub-sectores, más pares `problem → solution`, **KPIs/benchmarks de ROI**, regulación aplicable por sector.
2. **Personas:** comité de compra completo (comprador **económico / técnico / usuario**), **objeciones**, **criterios de decisión**, disparadores (triggers).
3. **Pilares:** rutas de madurez, **proof points / casos**, stack tecnológico por pilar.
4. **Ontología / grafo:** relaciones cruzadas explícitas (pilar ↔ sector ↔ persona ↔ problema) como edges, para navegación y recomendación.
5. **Benchmarking:** mapeo más profundo vs **Oracle / IBM / Gartner / Deloitte / Microsoft** + el posicionamiento diferencial de Datanestiq.
6. **Cuantitativo:** tamaños de mercado / estadísticas de adopción — **marcar claramente estimación vs. dato verificable** y evitar cifras inventadas con falsa precisión.
7. **🔑 Sinergia con Spec 010 (contenido):** añade un eje de **"ángulos de contenido / temas de blog"** por pilar/sector/persona. Debe quedar en los JSON de forma que `docs-generator.mjs --target=blog` pueda consumirlo como **briefs** → base para un **calendario de contenidos** on-brand.

## Requisitos técnicos (obligatorios)
- **Aditivo y byte-compatible:** extiende `src/lib/schemas.js` con **campos opcionales** (no rompas lo existente). Los JSON generados solo ganan campos; los consumidores actuales siguen funcionando.
- **Integridad referencial:** `build-taxonomy.mjs` debe seguir validando y **fallar ante refs inválidas** (nuevas relaciones incluidas).
- **Poblar los YAML fuente** con el contenido enriquecido (no editar los JSON a mano; son artefactos generados).
- **Sin romper consumidores:** microinteracciones, páginas de sector y el blog generator. `npm run build` en verde.
- **Calidad de datos:** contenido **fundamentado y on-brand** (B2B AI & Data), no relleno genérico; benchmarks reales donde se pueda.

## Decisiones a resolver en el plan (para mi revisión)
- **Profundidad/alcance:** ¿cuántos sub-sectores por sector, cuántas personas nuevas? Empieza con cobertura razonable — **no exploten el tamaño** de los JSON (los cargan las islas en el cliente).
- **Fuentes cuantitativas:** cómo marcar estimaciones vs. verificables.
- **Estructura del eje "content angles":** forma exacta para que el blog generator lo consuma como brief.
- **Numeración:** propón **Spec 011** (enriquecimiento) manteniendo la 003 como base, o justifica extender la 003.

## Verificación (E2E, al implementar tras mi aprobación)
1. `node scripts/build-taxonomy.mjs`: valida y regenera; introduce a propósito una ref inválida → **falla `exit 1`**; revierte.
2. **No-regresión:** `npm run build` en verde; una microinteracción y una página de sector siguen funcionando (shape de JSON compatible).
3. **Muestra de enriquecimiento:** un sector y una persona enriquecidos (pega fragmentos).
4. **Sinergia probada:** deriva un **brief** desde el nuevo eje de content-angles y genera un post con `docs-generator.mjs --target=blog` que lo use (evidencia de que la taxonomía enriquecida alimenta el blog).

## Forma de respuesta
- **Parte 1:** entrega `plan.md`/`tasks.md` de la 010 (hecho).
- **Parte 2:** entrega `spec.md` + `plan.md` + `tasks.md` de la Spec 011 para mi revisión. **NO implementes la Parte 2 aún.**
- `planes/ESTADO-SPECS.md`: fila 011 (nueva) en estado "spec/plan/tasks para revisión".
- Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el enriquecimiento sea **aditivo** (sin romper consumidores ni el build), que la integridad referencial siga fallando ante refs inválidas, que los datos sean fundamentados (estimaciones marcadas), y que el eje de content-angles realmente alimente el blog generator de la Spec 010.
