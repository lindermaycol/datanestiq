# Prompt para Antigravity: aprovechar la taxonomía enriquecida en la UX + cerrar deuda técnica (varios frentes) — SDD

Este prompt cubre varios frentes pendientes. **Está priorizado.** Por disciplina SDD y para que sea auditable, **NO implementes todo a ciegas de una vez**: primero entrega un **plan consolidado** (actualización de specs + plan por parte) para **mi revisión**, y luego implementa **en orden de prioridad, parte por parte**. Marca claramente lo que requiere una **decisión** (Parte D) y lo que es **acción del usuario** (Parte E).

---

## PARTE A (PRIORIDAD 1 — alto valor) — Spec 002: microinteracciones IA impulsadas por la taxonomía enriquecida
**Contexto:** la taxonomía es la fuente única de verdad y se enriqueció fuerte en la Spec 011, pero las islas React aún consumen solo los campos viejos. Conéctalas a los **campos nuevos** para interacciones más exactas y personalizadas — sin hardcoding.

**Campos nuevos disponibles del lado cliente** (ya en los JSON que cargan las islas):
- **Sectores** (`sectorsCorpus.json`): `subSectors`, `kpis`, `regulations`, `relevantPersonas`.
- **Pilares** (`taxonomyCorpus.json`): `techStack`, `proofPoints`, `competitivePositioning`.
- **Personas** (`personas.json`): `buyerRole`, `objections`, `decisionCriteria`, `triggers`, `relevantSectors`.
- (Los `contentAngles` NO están en el cliente — no los uses aquí.)

**Consumidores a refactorizar** (identifica los reales en `src/components/islands/`):
- **DiagnosticWizard / MultiStepWizard:** usa `objections` + `decisionCriteria` de la persona y `kpis`/`regulations` del sector → diagnóstico más preciso y recomendaciones que hablan el idioma del comprador.
- **Chatbot (máquina de estados):** rutea/adapta por `buyerRole`, aborda `objections`, y muestra `problems`+`kpis` del sector. Mantén **0 llamadas LLM** en el flujo guiado.
- **SolutionsByRoleAndIndustry:** usa las aristas `relevantPersonas` ↔ `relevantSectors` para mapear rol↔sector con precisión, y muestra `proofPoints`/`competitivePositioning` del pilar.
- **SemanticSearch:** enriquece el corpus de búsqueda con `subSectors`/`regulations` para ranking más exacto.
- **CopilotDemo:** aprovecha el posicionamiento/proofPoints donde aplique.

**SDD:** actualiza `specs/002-microexperiencias-ia/spec.md` (+ tech_debt) reflejando el consumo de la taxonomía enriquecida.

---

## PARTE B (PRIORIDAD 2) — Surfacing del enriquecimiento en las páginas (UX visible)
Hacer visible el enriquecimiento a los visitantes:
- **Páginas de sector** (`src/pages/sectores/[slug].astro`): mostrar `subSectors`, `kpis` (con su `[EST]`), `regulations`.
- **Páginas de solución/pilar**: mostrar `techStack`, `proofPoints`, `competitivePositioning`.
- Aditivo y condicional (si el campo existe). Build en verde.

---

## PARTE C (PRIORIDAD 3) — Deuda técnica concreta
- **Spec 006:** reemplaza los stubs `echo` de validación en `package.json` (`audit:lighthouse`, lint) por **runners reales** (ej. `@lhci/cli` o `lighthouse` CLI para Lighthouse; el linter real del proyecto).
- **Spec 007:** añade **Breadcrumbs Schema** (JSON-LD BreadcrumbList) a las páginas y ejecuta una **auditoría Lighthouse** real sobre las rutas SSG; reporta scores.

---

## PARTE D (DECISIÓN TOMADA — abandonar la Spec 004)
- **Spec 004 (Metodología / LangGraph):** **ABANDONADA por decisión del usuario.** El pipeline Python (`backend/langgraph_pipeline.py`) nunca existió y **no se construirá**. Documenta el abandono en `specs/004-*/tech_debt.md` y `spec.md` con el motivo: el objetivo (agentes que redacten/mantengan el sitio) se sirve con el patrón **controlado single-shot** de la Spec 010 (`docs-generator.mjs`), no con un agente multi-turno frágil/costoso (lección de la Spec 009). NO construyas nada de LangGraph.

---

## PARTE E (ACCIÓN DEL USUARIO — no codeable por Antigravity)
- **Spec 008 (SSH):** el código **ya está remediado** (`remote_extract.py` lee credenciales de `.env` y está gitignored). Lo **CRÍTICO pendiente** es que **el usuario ROTE la contraseña SSH en el panel de IONOS** (está comprometida en el historial de git). Antigravity **no puede** hacerlo. Solo: (1) confirma que el código no tiene secretos en claro, (2) deja un recordatorio explícito de la rotación en `tech_debt.md` de la 008. No intentes conectarte ni tocar credenciales.

---

## PARTE F (OPCIONAL — menores de la Spec 011)
- **Coherencia bidireccional de aristas:** donde un sector liste una persona en `relevantPersonas`, asegúrate de que esa persona liste el sector en `relevantSectors` (y viceversa). Solo consistencia, IDs válidos.
- **Duplicación de content-angles de personas:** hoy quedan en `personas.json` (fuente) además de `contentAngles.json`. Sepáralos si es trivial; si no, documéntalo.

---

## Verificación (por parte, con evidencia real)
- **A:** `npm run build` verde; el chatbot guiado sigue en **0 llamadas** (network); demuestra 1-2 interacciones usando los campos nuevos (ej. wizard que menciona una objeción/KPI real del sector-persona).
- **B:** build verde; una página de sector muestra KPIs/regulaciones; una de solución muestra techStack/proofPoints.
- **C:** los comandos de validación ejecutan runners reales (pega salida/scores).
- **D:** solo el plan/decisión (sin implementar).
- **E:** confirmación del estado del código + recordatorio de rotación (sin tocar credenciales).
- Sin regresión: 24+ páginas, islas intactas.

## Forma de respuesta
- **Primero:** plan consolidado (specs actualizadas + plan por parte + la decisión de la Parte D) para **mi revisión**. Implementa después, por prioridad.
- Evidencia real por parte (build ejecutado, network del chatbot, scores de Lighthouse). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que las microinteracciones **realmente** usen los campos nuevos de la taxonomía (no hardcoding, chatbot 0 llamadas), que el surfacing no rompa el build, que los runners de validación sean reales (no stubs), que la Parte D se quede en decisión, y que en la Parte E no se toquen credenciales. **Pendiente del usuario:** rotar la contraseña SSH de IONOS.
