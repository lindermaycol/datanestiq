# Prompt para Antigravity: Confirmación del Plan Spec 011 (Enriquecimiento de Taxonomía) → ejecutar con decisiones

Revisé `specs/011-enriquecimiento-taxonomia/{spec.md,plan.md,tasks.md}` y el plan. Están **fieles y bien pensados** (Zod aditivo `.optional()`, integridad referencial, pilot acotado, sinergia con el blog generator). **Luz verde**, con las respuestas a tus preguntas y 4 precisiones.

## Respuestas a tus preguntas de revisión

**1. Zod todo `.optional()` (aditivo):** ✅ Aprobado. Ningún campo existente se toca/renombra.

**2. Alcance inicial (¿1 nodo o los 10 sectores?):** ✅ Aprobado tu enfoque **pilot-first**, pero con condiciones:
- Enriquece **completamente** (todas las dimensiones nuevas) **1 sector + 1 pilar + 1 persona** representativos, para validar el pipeline entero (schema → YAML/JSON → build → corpus → brief → blog).
- **Nodos sugeridos:** sector **`publico`/gobierno** (encaja con el contexto sector público / OECE) o `salud`; pilar **AI & Data / IA** (`ai-data-science` o el equivalente); persona **un rol de comité de compra real** (ej. CIO/CTO).
- **Compromiso de Fase 2:** tras validar el pilot, el **rollout a los 10 sectores, 6 pilares y todas las personas** es parte de esta spec (no lo difieras indefinidamente). Deja las tareas de rollout en `tasks.md`.

**3. Estimaciones con prefijo `[EST]`:** ✅ Aprobado (ej. `[EST] $1.5M`). Nunca cifras con falsa precisión sin marcar.

**4. `contentAngles`: ¿archivo separado o embebido? (tu Open Question):** → **Sepáralo del corpus del cliente.** Los `contentAngles` son para el **blog generator (herramienta de build), NO para las islas React**; embeberlos en `taxonomyCorpus.json`/`sectorsCorpus.json` (que se cargan en el navegador) es peso muerto. Decisión:
- **Mantén los `contentAngles` en la fuente** (YAML de pilar/sector; `personas.json` para personas) — así viven pegados a su nodo (buena mantenibilidad).
- **`build-taxonomy.mjs` debe EXTRAERLOS a un `src/data/contentAngles.json` separado** y **excluirlos** de los corpus que cargan las islas. `docs-generator.mjs --target=blog` lee ese archivo separado.
- Así: fuente única (pegado al nodo) + cliente ligero + briefs disponibles para la 010. (Si el cambio al build script resultara costoso, embeber es un fallback aceptable **solo para el pilot**, pero el separado es lo correcto.)

## Precisión 1 — `personas.json` es FUENTE (verificado), no artefacto generado
Confirmado: `build-taxonomy.mjs` **lee** `src/data/personas.json` y lo valida con `personaSchema` (no lo genera). Por eso editarlo a mano **es correcto** (a diferencia de pilares/sectores, que son YAML→JSON). Mantén esa distinción.

## Precisión 2 — Ontología: relaciones cruzadas explícitas + validadas
El eje "grafo" de mi pedido no está del todo en tus diffs. Donde aporte valor, añade **relaciones cruzadas explícitas** (ej. `sector.relevantPersonas: []`, `persona.relevantSectors: []`; ya existen `relatedPillars`/`pillarsOfInterest`) y **extiende la verificación referencial** de `build-taxonomy.mjs` para que **falle `exit 1`** ante un ID cruzado inexistente. No sobre-modeles: unas pocas aristas significativas.

## Precisión 3 — Rendimiento del corpus del cliente
Vigila el tamaño de `taxonomyCorpus.json`/`sectorsCorpus.json` (los cargan las islas). Con `contentAngles` fuera (Precisión 4 arriba) el riesgo baja; aun así, si algún campo de texto largo (proofPoints extensos, briefs) no lo usa el cliente, considéralo candidato al archivo separado, no al corpus.

## Precisión 4 — Calidad on-brand y fundamentada
Contenido B2B AI & Data real y on-brand (como tu ejemplo con OECE), no relleno genérico. KPIs/benchmarks plausibles y marcados si son estimación. Benchmarking competitivo (Oracle/IBM/Gartner/Deloitte/Microsoft) con posicionamiento diferencial de Datanestiq.

## Verificación (reporta con evidencia, al implementar)
1. `node scripts/build-taxonomy.mjs`: valida y regenera; **rompe una ref cruzada a propósito → `exit 1`**; revierte.
2. `npm run build` **100% verde** (23+ páginas), sin regresión en islas/páginas de sector (corpus shape-compatible).
3. Muestra: el sector, pilar y persona del pilot enriquecidos (pega fragmentos).
4. **Sinergia probada:** `contentAngles.json` generado; toma un `brief` de ahí, corre `docs-generator.mjs --target=blog --brief="..."`, y muestra el post resultante congruente con el brief. Build sigue verde con ese post.

## Forma de respuesta
- Reporta con evidencia real (build ejecutado, exit 1 de integridad, fragmentos, post de blog derivado del brief). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que todo sea **aditivo** (build verde, islas intactas), que la integridad referencial falle ante refs cruzadas inválidas, que `contentAngles` NO engorde el corpus del cliente (archivo separado), y que un brief de la taxonomía realmente genere un post on-brand vía la Spec 010.
