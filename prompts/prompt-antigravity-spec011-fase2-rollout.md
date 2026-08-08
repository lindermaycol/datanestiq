# Prompt para Antigravity: Spec 011 Fase 2 — Rollout del enriquecimiento a TODA la taxonomía

El pilot (Fase 1) está **auditado y verificado**: el patrón aditivo funciona (Zod `.optional()`, `contentAngles.json` separado del corpus del cliente, integridad referencial de aristas cruzadas con `exit 1`, y sinergia probada brief→blog on-brand, build 24 páginas). Ahora **escala el MISMO patrón a todos los nodos**. Es de bajo riesgo técnico (aditivo, patrón probado); el riesgo real es la **calidad/especificidad del contenido a escala** — ese es el foco.

## Plantilla exacta (el pilot ya hecho — NO lo re-enriquezcas)
Replica la **estructura de campos nuevos** de estos 3 nodos ya enriquecidos:
- Sector: `src/content/sectors/publico.yaml` (subSectors, kpis, regulations, relevantPersonas, contentAngles).
- Pilar: `src/content/pillars/ai-data-science.yaml` (techStack, proofPoints, competitivePositioning, contentAngles).
- Persona: rol `cio` en `src/data/personas.json` (buyerRole, objections, decisionCriteria, triggers, relevantSectors, contentAngles).

## Alcance (todos los nodos restantes)
- **Sectores (los 9 restantes):** `finanzas, salud, retail, manufactura, logistica, educacion, mineria, seguros, telecomunicaciones` → añadir `subSectors`, `kpis` (con `[EST]`), `regulations`, `relevantPersonas`, `contentAngles`.
- **Pilares (los 5 restantes):** `business-intelligence, data-engineering, estrategia-datos-ia, hiperautomatizacion, sistemas-digitales` → `techStack`, `proofPoints`, `competitivePositioning`, `contentAngles`.
- **Personas (todos los roles restantes en `personas.json`):** `buyerRole`, `objections`, `decisionCriteria`, `triggers`, `relevantSectors`, `contentAngles`.

## Control de calidad (lo MÁS importante a escala)
- **Especificidad por industria — NADA de copy-paste genérico entre sectores.** Cada sector con sus sub-sectores, KPIs y **regulaciones REALES** de esa industria. Ejemplos de anclaje:
  - `salud` → HIPAA / HL7-FHIR / historia clínica electrónica / interoperabilidad.
  - `finanzas`/`seguros` → Basilea III / PCI-DSS / AML-KYC / SBS (Perú).
  - `retail` → demanda/inventario, personalización, CDP.
  - `manufactura`/`mineria` → IoT/OT, mantenimiento predictivo, seguridad, ESG.
  - `logistica` → ruteo, torre de control, trazabilidad.
  - `educacion` → analítica de aprendizaje, deserción.
  - `telecomunicaciones` → churn, red, fraude.
- **KPIs/benchmarks plausibles** y **marcados `[EST]`** cuando sean estimación (nunca falsa precisión).
- **Objeciones y criterios de decisión realistas** por rol del comité de compra (económico/técnico/usuario).
- **content-angles:** briefs **accionables y distintos por nodo** (sirven de base a un calendario de contenidos); no repitas el mismo ángulo entre sectores.
- Tono **on-brand Datanestiq** (B2B AI & Data), no marketing vacío. Benchmarking competitivo (Oracle/IBM/Gartner/Deloitte/Microsoft) con el diferencial de Datanestiq donde aplique.

## Integridad y coherencia de aristas (ontología)
- Todo `relevantPersonas` (sectores) y `relevantSectors` (personas) debe referenciar **IDs existentes** (el script valida → `exit 1`). Ojo con los IDs exactos: sectores usan `id` `sector-*`, pilares el `slug`, personas su `id`.
- **Coherencia bidireccional:** si un sector marca una persona como relevante, esa persona debería marcar ese sector (y viceversa) donde tenga sentido. Evita aristas huérfanas.

## Rendimiento del corpus del cliente
- Reporta el **tamaño de `taxonomyCorpus.json` y `sectorsCorpus.json` antes/después** del rollout. Si algún campo de texto largo **no lo consume el cliente** (islas), aplícale el patrón de archivo separado (como `contentAngles`) en `build-taxonomy.mjs` en vez de engordar el corpus.

## Opcional (baja prioridad)
- Duplicación de content-angles de personas: hoy quedan en `personas.json` (fuente) **además** de `contentAngles.json`. Si es trivial separarlos, hazlo; si no, déjalo documentado.

## Fuera de alcance (NO ahora)
- Mostrar los campos nuevos en las páginas del sitio (UI) — es un paso separado y posterior.

## Verificación (reporta con evidencia REAL)
1. `node scripts/build-taxonomy.mjs`: verde, genera todos los JSON; **rompe una arista cruzada a propósito → `exit 1`**; revierte.
2. `npm run build`: **24+ páginas verde**, sin regresión (islas y páginas de sector siguen funcionando; corpus shape-compatible).
3. **Tamaño de corpus antes/después** (que no explote).
4. Fragmentos de **2-3 sectores, 2 pilares y 2 personas** enriquecidos — mostrando la **especificidad por industria** (no genérico).
5. **Sinergia a escala:** genera **2-3 posts de blog** desde content-angles de sectores **DISTINTOS** (`docs-generator.mjs --target=blog --brief="..."`) → variedad, on-brand, y build verde con ellos.
6. `planes/ESTADO-SPECS.md` fila 011 → Fase 2 completa.

## Forma de respuesta
- Evidencia real (build ejecutado, `exit 1` de integridad, tamaños, fragmentos por industria, posts variados). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el contenido sea **específico por industria** (no relleno genérico ni copy-paste entre sectores), que las aristas cruzadas tengan integridad (exit 1) y coherencia, que el corpus del cliente **no explote**, y que los posts de blog derivados sean variados y on-brand — todo con build en verde.
