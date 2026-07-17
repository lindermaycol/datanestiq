# Prompt para Antigravity: Enriquecer la taxonomía como FUENTE ÚNICA de las microinteracciones de IA

Actúa como **arquitecto de datos frontend**. Este prompt va **ANTES** de `prompt-antigravity-deuda-spec003-content-collections.md` (la migración a Content Collections operará sobre el esquema enriquecido que produzca este ciclo).

## Contexto (auditado por Claude / Sonnet 5)
Hoy la taxonomía **NO es la fuente única** de las microinteracciones de IA. Estado verificado:
- **SemanticSearch.jsx** ✅ consume `taxonomyCorpus.json` + `sectorsCorpus.json`.
- **DiagnosticWizard.jsx** ⚠️ importa `taxonomyCorpus.json` pero **duplica los 10 sectores hardcodeados** (líneas 7-64) en vez de usar `sectorsCorpus.json`.
- **CopilotDemo.jsx** ❌ 3 prompts C-level hardcodeados.
- **Chatbot.jsx** ❌ sectores (Gobierno/Salud/Finanzas/Retail) + `getSolutionMessage` hardcodeados, con IDs distintos a los de `sectorsCorpus.json`.
- **MultiStepWizard.jsx** ❌ preguntas de madurez hardcodeadas.

Objetivo: **enriquecer la taxonomía** (pilares y sectores) con los campos que cada microinteracción necesita, y **refactorizar las islas** para que TODAS lean de la taxonomía. Sin datos duplicados ni fragmentados.

## Restricción CRÍTICA — cero rupturas
Al terminar, todo debe seguir funcionando: buscador semántico, wizard de sectores + pitch, copilot demo, chatbot guiado (0 llamadas en flujo determinista), páginas de solución. Verifica E2E.

---

## PARTE 1 — Enriquecer el esquema de datos

### 1.1 Pilares (`src/data/taxonomyCorpus.json`)
Añade a cada uno de los 6 pilares (sin quitar los campos actuales):
- `keywords: string[]` — términos para afinar el buscador semántico (ej. para IA & Data Science: "machine learning", "LLM", "predicción", "RAG").
- `copilotPrompts: string[]` — 1–2 prompts C-level de demostración específicos de ese pilar (alimentan el CopilotDemo). Ej. para BI: "Proyectar ROI del Q3 con reducción de plantilla"; para Data Engineering: "Diagnosticar los cuellos de botella de mi pipeline de datos actual".

### 1.2 Sectores (`src/data/sectorsCorpus.json`)
Enriquece los 10 sectores (mantén id/title/description/icon) añadiendo:
- `keywords: string[]` — para el buscador semántico.
- `seo: {title, description}`, `hero: {headline, subheadline}`, `contrast: {problem, solution}` — para las landing pages de sector (que creará el prompt 003). Copy premium B2B, sin inventar clientes/cifras como verificados.
- `problems: [{code, label, solution}]` — **2 problemas por sector** con su mensaje de solución canónico. Esto alimenta el **flujo guiado determinista del Chatbot** (reemplaza el `getSolutionMessage` hardcodeado). Reutiliza y migra el contenido que hoy está hardcodeado en `Chatbot.jsx` (Gobierno/Salud/Finanzas/Retail) mapeándolo a los sectores oficiales (`sector-publico`, `sector-salud`, `sector-finanzas`, `sector-retail`), y redacta problems/solutions premium para los 6 sectores restantes.

**Importante:** mantén `sectorsCorpus.json` como el **único** origen de sectores. No dejes arrays de sectores duplicados en ningún `.jsx`.

---

## PARTE 2 — Refactorizar las islas para consumir la taxonomía

### 2.1 `DiagnosticWizard.jsx` — eliminar duplicación
Borra el array hardcodeado `sectorsCorpus` (líneas 7-64) y **importa** `../../data/sectorsCorpus.json`. Verifica que el grid de sectores, el pitch generativo y el resaltado semántico (`semanticHighlight`) sigan funcionando con los `id` de la fuente única.

### 2.2 `CopilotDemo.jsx` — prompts desde la taxonomía
Reemplaza el array `prompts` hardcodeado por prompts derivados de `taxonomyCorpus.json` (aplana los `copilotPrompts` de los pilares; muestra 3–4 representativos, o rota). Así los prompts del demo cubren la oferta real y se actualizan al editar la taxonomía.

### 2.3 `Chatbot.jsx` — flujo guiado desde la taxonomía
Refactoriza la máquina de estados para que los **botones de sector** y los **botones de problema** + las **soluciones canónicas** se generen desde `sectorsCorpus.json` (campos `problems[]`), en vez de estar hardcodeados. Requisitos que NO deben romperse (ya auditados):
- El flujo guiado por botones sigue con **cero llamadas** a `chat.php` (determinista).
- Se mantiene la salida "Otro / Escribir libremente" → LLM.
- Se mantiene la tarjeta de captura de lead y la herencia de contexto (`?servicio=`).
- Puedes mostrar un subconjunto curado de sectores en el intro (ej. 4–5 + "Otro"), pero tomados de la fuente única, no hardcodeados.

### 2.4 `MultiStepWizard.jsx` — (opcional, recomendado)
Las preguntas de arquitectura/madurez pueden quedar genéricas, pero haz que la **recomendación final** mapee a un pilar real de `taxonomyCorpus.json` (ej. según las respuestas, sugiere "Ingeniería de Datos" con enlace a `/soluciones/data-engineering`). Si lo dejas genérico, documenta por qué.

---

## Verificación (E2E, no solo build)
En `npm run dev`:
1. **DiagnosticWizard:** 10 sectores renderizan desde la fuente única; pitch y resaltado semántico OK.
2. **CopilotDemo:** los prompts mostrados provienen de la taxonomía; al hacer clic responde vía `/api/chat.php`.
3. **Chatbot:** flujo guiado (sector→problema→solución) desde la taxonomía con **0 requests** a `chat.php`; texto libre = 1 request.
4. **SemanticSearch:** sigue reordenando (ahora con `keywords` mejora la precisión).
5. **Grep de no-duplicación:** confirma que NO queda ningún array de sectores hardcodeado en `.jsx` (solo se importa de `sectorsCorpus.json`).
6. `npm run build` sin errores (10 páginas; las de sector llegan con el prompt 003).

## Cierre
- Documenta en `specs/002-microexperiencias-ia/tech_debt.md` y `003-taxonomia-servicios/tech_debt.md` que la taxonomía es ahora la fuente única de las microinteracciones (deuda de fragmentación resuelta).
- Nota para el siguiente ciclo: el prompt `prompt-antigravity-deuda-spec003-content-collections.md` migrará **este esquema enriquecido** a Content Collections; su schema Zod debe incluir los campos nuevos (`keywords`, `copilotPrompts`, `problems`, `seo/hero/contrast` de sectores).

## Forma de respuesta
- Entrega primero un breve plan (qué campos añades y cómo refactorizas cada isla) para mi revisión, luego implementa.
- Reporta la verificación E2E (con evidencia de Network para el chatbot: 0 llamadas en guiado).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que ninguna isla quede con datos de sectores/prompts hardcodeados y que todo consuma la taxonomía única, sin romper el flujo determinista del chatbot ni el buscador semántico.
