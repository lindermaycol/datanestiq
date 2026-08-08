# Prompt para IA con navegador — Verificar las mejoras de UX del home de Datanestiq (reporte para Claude)

Eres un QA con **navegador real** (clic, escribir, panel Network, consola, inspección de DOM y clases CSS). Verifica un conjunto acotado de mejoras recién implementadas en el home y **emite un reporte estructurado con evidencia** para que Claude (Opus 4.8) lo audite. No es una revisión amplia: son checks puntuales con criterio binario y **valores concretos**.

## Acceso al sitio (IMPORTANTE)
Astro estático + islas React + backend PHP. **Reconstruye y sirve con PHP** (para probar la última versión y que el chatbot/backend funcionen):
1. `npm run build`
2. `C:/xampp/php/php.exe -S localhost:8080 -t dist`
3. Abrir `http://localhost:8080/`

## Reglas anti-falsos-positivos (imprescindibles — Claude no pudo probar esto en su entorno headless porque el modelo de IA no cargaba)
- **`localStorage.clear()` + recarga antes de cada bloque** que dependa del contexto (el sitio persiste la elección de rol/búsqueda).
- **El buscador semántico usa un modelo de embeddings (Edge AI) que tarda en cargar.** Haz scroll hasta *"Buscador Semántico impulsado por IA"*, **espera** a que aparezca *"IA lista para búsqueda instantánea"* (o similar) **antes** de buscar. Si el modelo no carga, dilo (no marques fallo del código).
- **Highlights:** mide las **clases** `is-highlighted` / `is-dimmed` (o el resaltado visible), no el `opacity` crudo (GSAP lo controla); haz scroll a las tarjetas para que GSAP las monte.
- **0-LLM:** el flujo guiado del chatbot (botones) **no** debe llamar a `chat.php`; el buscador es **client-side** (worker, sin fetch a `chat.php`). Ten la consola y el panel Network abiertos; reporta cualquier error JS.

---

## Checks a verificar (PASS/FALLA + evidencia concreta)

### 🎯 V1 — Copiloto CONTEXTUAL por la búsqueda (lo más importante)
1. **Antes de buscar:** anota los **3 escenarios** que muestra el "Copiloto Estratégico (C-Level)" (los prompts/pilares por defecto).
2. Busca en el buscador semántico: **"quiero mejorar la eficiencia operativa"**. Espera resultados.
3. **Después de buscar:** ¿los 3 escenarios del Copiloto **cambiaron** a los pilares que la búsqueda resaltó (ej. Hiperautomatización, Sistemas Digitales, AI & Data Science)? **Anota los escenarios antes vs después.**
4. **Criterio:** PASS si los escenarios post-búsqueda corresponden a pilares relevantes a "eficiencia" (no los 3 fijos). Reporta un encabezado tipo *"Basado en tu búsqueda…"* si aparece.

### 🎯 V2 — CTA encadenado tras la búsqueda
1. Tras la búsqueda, ¿aparece un bloque **"Encontramos [N] soluciones relevantes para tu búsqueda"** con 3 botones? Anota **N**.
2. Clic en **"Ver cómo razona nuestra IA →"** → ¿hace smooth-scroll a la sección del Copiloto?
3. Clic en **"Iniciar Diagnóstico con este contexto →"** → ¿hace scroll a "Descubre el Impacto en tu Sector"?
4. Clic en **"Consultar con el AI Concierge →"** → ¿abre el chatbot **con la consulta ya cargada** ("quiero mejorar la eficiencia…")? Anota qué muestra el chatbot.
5. **Criterio:** PASS si el bloque aparece con N correcto y los 3 botones funcionan.

### 🎯 V3 — Sección de sectores reactiva + inputs pre-llenados
1. Tras la búsqueda, en **"Descubre el Impacto en tu Sector"**: ¿se **resaltan** los sectores relevantes y se **atenúan** los demás? (Antes del fix, se atenuaban **todos** — reporta si discrimina o no.)
2. ¿Los inputs *"¿Cuál es tu mayor desafío en {sector}?"* vienen **pre-llenados** con "quiero mejorar la eficiencia operativa"?
3. **Editabilidad:** borra el texto de un input. ¿Se queda vacío (correcto) o **salta de vuelta** a la consulta (bug)? Repórtalo.
4. **Criterio:** PASS si discrimina sectores + pre-llena + el input es editable/borrable.

### V4 — 0-LLM intacto
1. Abre el chatbot (botón flotante) y recorre el **flujo guiado por botones** (sector → rol → problema). En Network, **¿hay algún `POST /api/chat.php`?** Debe ser **cero**.
2. La búsqueda semántica **no** debe hacer POST a `chat.php` (es Edge AI local).

### V5 — Microcopy del hero por rol (regresión)
1. `localStorage.clear()` + recarga. Elige el chip **"Finanzas / CFO"**. ¿Aparece una micro-línea tipo *"Para el CFO…: ROI medible · …"* debajo de los chips? Sin contexto no debe aparecer; el reset la quita.

### V6 — Claridad Copiloto vs Chatbot (regresión)
1. ¿El subtítulo del Copiloto aclara que es una **demo** ("…no es el chatbot" / "distinto del AI Concierge")? ¿Hay un puente *"¿Tu caso no está…? Pregúntale al AI Concierge →"* que abre el chatbot?

### V7 — Orden del embudo (regresión)
1. ¿"Descubre el Impacto en tu Sector" (diagnóstico) aparece **antes** que el "Copiloto Estratégico" al hacer scroll? (Zona EXPLORA antes que PROFUNDIZA.)

---

## Formato del reporte (para Claude)
1. **Tabla:** `Check (V1–V7) · PASS/FALLA/NO-VERIFICADO · Evidencia concreta (valores: escenarios antes/después, N, sectores resaltados, texto pre-llenado, presencia/ausencia de chat.php en Network, texto del microcopy)`.
2. **Errores de consola** encontrados (texto literal), si los hay.
3. **Qué NO pudiste verificar y por qué** (ej. el modelo de embeddings no cargó → V1–V3 quedan pendientes).
4. **Veredicto:** ¿la continuidad buscador → copiloto/sectores/chatbot arrastra el contexto de punta a punta?

Sé **literal** con la evidencia (qué hiciste y qué observaste). Si algo "parece" mal pero no lo interactuaste, márcalo *no verificado*, no como defecto. No inventes resultados.
