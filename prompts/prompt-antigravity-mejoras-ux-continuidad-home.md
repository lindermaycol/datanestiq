# Prompt para Antigravity — Mejoras de UX del home (Copiloto contextual + continuidad + claridad + microcopy por rol + orden del embudo)

Sigue **SDD** y respeta la **Constitución** ([`.specify/memory/constitution.md`](../.specify/memory/constitution.md)) y [`AGENTS.md`](../AGENTS.md). **No es una spec nueva:** son extensiones de **Spec 002 (microexperiencias)** y **Spec 013 (A9 — adaptación por contexto)**. Documenta la extensión en `planes/ESTADO-SPECS.md` (doc-sync, §11). Puedes implementar directo, con evidencia real.

Origen: una navegación real del home encontró 3 mejoras concretas de conversión. La continuidad entre herramientas **existe técnicamente pero es invisible** al usuario.

## 🎯 Mejora 1 (alta) — Copiloto CONTEXTUAL + CTA encadenado tras el buscador (continuidad visible)

### 1a — El Copiloto se adapta a la búsqueda (en vez de escenarios fijos)
**Diagnóstico:** en `src/components/islands/CopilotDemo.jsx`, `demoItems = taxonomyCorpus.slice(0, 3)` toma los **primeros 3 pilares por posición** (fijos), no por relevancia. Sus escenarios ya salen de la taxonomía (`copilotPrompts` de cada pilar).

**Fix:** que el Copiloto seleccione los pilares **que la búsqueda resaltó**:
- Lee el store **`semanticHighlight`** (el buscador ya lo setea con claves `service-<slug>` y score). Si tiene entradas, arma `demoItems` con los **pilares top por score** (sus `copilotPrompts`), en vez de `slice(0,3)`.
- **Fallback:** si no hay contexto de búsqueda, mantiene los primeros 3 (comportamiento actual).
- Cuando esté guiado por búsqueda, añade un encabezado contextual: *"Basado en tu búsqueda, mira cómo razona nuestra IA sobre estos escenarios."*
- **Honestidad (Constitución §2):** los escenarios siguen siendo **pre-construidos de la taxonomía**; solo se elige *cuáles* según la búsqueda. No inventes escenarios ni afirmes que procesa la consulta libre.

### 1b — CTA encadenado en el buscador
**Diagnóstico:** en `SemanticSearch.jsx`, el bloque `status === 'done'` no tiene ninguna llamada a la acción; la continuidad (que `DiagnosticWizard` hereda `userChallenge`) es invisible.

**Fix:** cuando hay resultados, renderiza un **bloque de CTA encadenado**:
- Texto: *"Encontramos **[N]** soluciones relevantes para tu búsqueda."* (N = servicios resaltados).
- Botones (scroll anclado a cada herramienta, que ya consumen el contexto):
  - **"Ver cómo razona nuestra IA →"** → scroll al **Copiloto** (ahora contextual, 1a).
  - **"Iniciar Diagnóstico con este contexto →"** → scroll al `DiagnosticWizard` (lee `userChallenge`).
  - **"Consultar con el AI Concierge →"** → setea `lastUserQuery` con la consulta y abre el chatbot (`chatbotOpen.set(true)` / `open-chatbot`); `Chatbot.jsx` ya hereda `lastUserQuery`.
- Reutiliza los **stores existentes** (`semanticHighlight`, `userChallenge`, `lastUserQuery`, `chatbotOpen`). Son navegación/triggers, **sin llamadas LLM** en el flujo guiado (0-LLM intacto).

### 1c — La sección de sectores ("Descubre el Impacto en tu Sector" = `DiagnosticWizard`) reacciona a la búsqueda
**Diagnóstico:** `DiagnosticWizard.jsx` ya lee `semanticHighlight` y su input de desafío ya abre el chatbot con contexto. Pero: (a) su resaltado de sectores está **roto tras la discretización** — `maxScore` toma el `1.0` de los servicios top, subiendo el umbral a `0.75`, que ningún sector alcanza → **atenúa todos los sectores**; (b) los inputs no reflejan la consulta.

**Fix (coherencia, sin fabricar):**
- **Resaltar los sectores relevantes:** calcula el resaltado a partir de los **scores de sector** únicamente (ignora las claves `service-*` para ese cálculo, o discretiza sectores a top-N como los servicios). Así se resaltan los sectores que matchean y se atenúan los demás, con discriminación real.
- **Pre-llenar** el input "¿Cuál es tu mayor desafío en {sector}?" con la consulta del buscador (`userChallenge`) — así continuar hacia el flujo sector→chatbot arrastra el contexto.
- **NO reescribas** las descripciones de los cards (son taxonomía; reescribir por consulta libre exige LLM + riesgo de fabricación — Constitución §2).

## Mejora 2 (media) — Claridad del Copiloto vs Chatbot
Aún con el Copiloto contextual, aclara la diferencia con el chatbot en `CopilotDemo.jsx`:
- **Subtítulo** (hoy "Visualiza cómo nuestro ecosistema asiste…") → *"Demo del tipo de análisis que produce nuestra plataforma — no es el chatbot de consulta."*
- **Puente al chatbot:** *"¿Tu caso no está en estos escenarios? Pregúntale al AI Concierge →"* que dispara `open-chatbot`.

## Mejora 3 (media) — Microcopy del hero por rol activo (profundiza A9)
**Objetivo:** que elegir un chip de rol no solo cambie el CTA/highlight, sino que muestre una **línea de valor específica del rol** en el hero.

**Fix:** una isla ligera (`client:load`, patrón de `ConsultativeCTA`) en el hero que lee `userContext` y, cuando hay `rol`, muestra una micro-línea **derivada de la taxonomía** (`personas.json`: `goals`/`decisionCriteria`/`pains` del rol). Ej.: CFO → *"Para el CFO: ROI medible, TCO y payback defendible ante tu directorio."*; sin contexto → no muestra nada (neutro).
- **Honestidad (Constitución §2):** el microcopy sale de los datos de la persona en la taxonomía, **no inventes** afirmaciones. Progressive enhancement: el hero funciona sin JS/contexto.

## Mejora 4 (media, IA/orden) — Agrupar el home por función del embudo
**Objetivo:** reducir la sensación de "interacciones dispersas" (reporte UX) agrupando las secciones por intención, en `src/pages/index.astro`. Orden objetivo:
1. Hero (+ chips de contexto + microcopy por rol) · credibilidad (tecnologías).
2. **Zona EXPLORA / describe tu problema:** Buscador semántico (texto libre) **+** "Soluciones por Rol / Industria" (`SolutionsByRoleAndIndustry`) **+** "Descubre el Impacto en tu Sector" (`DiagnosticWizard`, describe por sector) — **súbelo aquí desde el fondo**, junto al buscador; los tres son formas de decir "qué me aplica" y convergen en el chatbot/diagnóstico con contexto.
3. **Zona PROFUNDIZA (credibilidad):** Copiloto contextual (demo de razonamiento).
4. Metodología · FAQ · Footer.

- Es un **reordenamiento de secciones** (mover componentes en `index.astro`), no reescritura. **Sube el `DiagnosticWizard`** para que quede en la zona EXPLORA cerca del buscador (hoy está tras el Copiloto). Verifica que el **CTA encadenado** (Mejora 1b) haga scroll anclado correcto a cada destino aunque cambie el orden.
- No rompas el `set:html`/props del home ni la herencia de contexto entre islas; progressive enhancement intacto.

## Guardarraíles (Constitución)
- **0-LLM** del flujo guiado intacto; los CTA encadenados son navegación/triggers, no llamadas LLM.
- **Honestidad:** microcopy desde taxonomía; no encadenar el Copiloto como "contextual" (no lo es).
- **Taxonomía = fuente de verdad** (el microcopy del rol deriva de `personas.json`).
- `npm run build` verde; islas/consola limpias; progressive enhancement.

## Verificación (evidencia real)
1. **Copiloto contextual:** tras buscar (ej. "eficiencia"), los escenarios del Copiloto son los de los **pilares resaltados** (no los primeros 3 fijos); sin búsqueda, vuelve a los 3 por defecto.
2. **Continuidad:** una búsqueda muestra el bloque "Encontramos N soluciones…" con los CTA; "Diagnóstico"/"Copiloto" hacen scroll a su destino; "AI Concierge" abre el chatbot **con la consulta cargada**. Network: 0 llamadas extra en el flujo guiado.
2b. **Sección de sectores reactiva:** tras buscar (ej. "eficiencia"), en "Descubre el Impacto en tu Sector" se **resaltan los sectores relevantes** (no se atenúan todos) y los inputs "¿Cuál es tu mayor desafío?" vienen **pre-llenados** con la consulta. Las descripciones NO se reescriben.
3. **Copiloto vs chatbot:** subtítulo aclara que es demo; el puente abre el chatbot.
4. **Microcopy por rol:** elegir chip "Finanzas / CFO" muestra la micro-línea del CFO (de taxonomía); sin contexto, nada; reset la quita.
5. **Orden:** el home queda en zonas EXPLORA (buscador + rol/industria) → PROFUNDIZA (copiloto + diagnóstico); el scroll anclado del CTA encadenado funciona.
6. `npm run build` verde; consola limpia; `ESTADO-SPECS.md` actualizado (extensión Spec 002/013).
7. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: el CTA encadenado (que Diagnóstico y chatbot reciban el contexto de la búsqueda), la claridad del Copiloto, el microcopy por rol desde taxonomía, y que el 0-LLM y el build sigan verdes.
