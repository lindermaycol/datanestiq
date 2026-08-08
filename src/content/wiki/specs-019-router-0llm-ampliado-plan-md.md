---
title: "Plan de Implementación — Spec 019: Router Determinístico Ampliado (0-LLM)"
description: "Este documento detalla el plan de implementación para el Spec 019, un router determinístico ampliado (0-LLM) para el chatbot de Datanestiq. Incluye la arquit"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["router","0-LLM","implementation plan","chatbot","intent classification","Xenova","FAQ","architecture","development phases"]
seoScore: 100
---
# Plan de Implementación — Spec 019: Router Determinístico Ampliado (0-LLM)

**Estado:** 🟠 Diseñada · PENDIENTE BUILD  
**Precondición:** Visto bueno de Claude Code en auditoría del diseño.

---

## Arquitectura Final (post-build)

```
[Chatbot.jsx]
    │
    │ texto libre
    ▼
[IntentClassifier] ── src/lib/intentClassifier.ts
    │ (postMessage al worker.js existente)
    │
    ├── faq (≥0.72) ──► [FAQMatcher] ── coseno vs. faqCorpus → respuesta verbatim
    │                         │
    │                         └── si match < 0.65 → LLM fallback
    │
    ├── cita (≥0.72) ──► setShowScheduler(true) → <AppointmentPicker>
    │
    ├── guiado (≥0.72) ──► retomar flujo guiado (reset/continuar state machine)
    │
    └── complejo / unknown / confianza < 0.72 ──► chat.php (LLM, sin cambios)

[trackEvent] ← instrumentación en cada decisión (Spec 018)
```

---

## Fases de Implementación

### Fase 1 — Datos (sin código de runtime)
**Entregable:** `src/data/intents.json`
- Crear el archivo con 4 intenciones y sus frases-ejemplo curadas.
- Definir `confidence_threshold: 0.72` y `faq_match_threshold: 0.65` como parámetros.
- Revisar y aprobar las frases-ejemplo (calidad del clasificador depende de ellas).

**Estimación:** 0.5h · **Riesgo:** Bajo.

---

### Fase 2 — Módulo clasificador (nuevo archivo)
**Entregable:** `src/lib/intentClassifier.ts`

Funciones exportadas:
```ts
// Cargar y cachear los embeddings de prototipos de intención
initIntentClassifier(worker: Worker, intents: IntentConfig): Promise<void>

// Clasificar un texto → { intent, confidence }
classifyIntent(worker: Worker, text: string): Promise<IntentResult>

// Buscar la FAQ más cercana → { answer, score } | null
matchFAQ(worker: Worker, text: string, faqCorpus: FAQEntry[]): Promise<FAQMatch | null>
```

El módulo gestiona los `id` únicos de cada `postMessage` y las promesas de resolución.

**Estimación:** 2h · **Riesgo:** Medio (coordinación con worker compartido).

---

### Fase 3 — Integración en Chatbot.jsx
**Archivo modificado:** `src/components/islands/Chatbot.jsx`

Cambios mínimos:
1. Importar `initIntentClassifier`, `classifyIntent`, `matchFAQ` desde `../lib/intentClassifier`.
2. En el `useEffect` de montaje: `initIntentClassifier(worker, intents)`.
3. En `handleUserSubmit`: antes del fetch a `chat.php`, llamar a `classifyIntent` y derivar a la ruta correcta.
4. Añadir estado `showFAQAnswer` y `faqEscapeButton` para la UI de respuesta FAQ.
5. Instrumentar con `trackEvent('intent_routing', ...)` en cada decisión.

**Estimación:** 2h · **Riesgo:** Medio (state machine existente compleja).

---

### Fase 4 — Worker: gestión del corpus de intenciones
**Archivo modificado:** `public/worker.js`

Cambio menor: el worker ya soporta múltiples corpus vía el campo `corpusTexts` en cada mensaje. El clasificador reutilizará esta capacidad con dos corpus:
- Corpus de intenciones (frases-ejemplo curadas).
- Corpus FAQ (`objectionResponses`).

**No se modifica el worker** en primera iteración — se usan dos llamadas secuenciales con corpus diferentes. Si esto genera performance issues → Fase 4b: añadir soporte multi-corpus con `corpusId`.

**Estimación:** 0h (no necesario en v1) · **Riesgo:** Bajo.

---

### Fase 5 — UI de respuesta FAQ
Mínima: mensaje con la `response` verbatim de la taxonomía + botón de escape.

```jsx
// Ejemplo de mensaje FAQ con escape
{
  role: 'assistant',
  content: faqAnswer,
  isFAQ: true,
  escapeLabel: '¿No era lo que buscabas? Escríbeme más detalles →'
}
```

El botón de escape llama directamente a `chat.php` con el texto original.

**Estimación:** 0.5h · **Riesgo:** Bajo.

---

### Fase 6 — SSOT y documentación
- Añadir fila 019 en `ESTADO-SPECS.md` (→ LIVE tras build).
- Añadir entrada 019 en `specsStatus.json` (must match antidrift).
- Ejecutar `npm run docs:sync` (wiki).

**Estimación:** 0.25h · **Riesgo:** Bajo.

---

## Estimación Total

| Fase | Descripción | Esfuerzo |
|------|-------------|----------|
| 1 | `src/data/intents.json` | 0.5h |
| 2 | `src/lib/intentClassifier.ts` | 2h |
| 3 | Integración en `Chatbot.jsx` | 2h |
| 4 | Worker (v1: no requiere cambios) | 0h |
| 5 | UI respuesta FAQ + escape | 0.5h |
| 6 | SSOT + docs | 0.25h |
| **Total** | | **~5.25h** |

---

## Criterios de Aceptación (Checklist de Build)

- [ ] `intents.json` existe con 4 intenciones y frases-ejemplo curadas.
- [ ] `intentClassifier.ts` exporta `initIntentClassifier`, `classifyIntent`, `matchFAQ`.
- [ ] Al escribir "¿cuánto cuesta?" → ruta `faq` (0-LLM, sin fetch a `chat.php`).
- [ ] Al escribir "quiero agendar" → ruta `cita` (abre `AppointmentPicker`, 0-LLM).
- [ ] Al escribir "¿qué servicios tienen?" → ruta `guiado` (flujo guiado, 0-LLM).
- [ ] Al escribir "arquitectura Delta Lake con Databricks" → ruta `complejo` (LLM).
- [ ] **[P1 — Precisión Claude]** Texto libre con modelo Xenova NO cargado → respuesta por LLM sin demora perceptible (el router NO bloquea esperando la descarga del modelo; la clasificación 0-LLM es oportunista: si el modelo está listo, clasifica; si no, LLM al toque).
- [ ] Respuesta FAQ = texto verbatim de `objectionResponses`, nunca inventado.
- [ ] Botón de escape visible en respuestas FAQ.
- [ ] `trackEvent('intent_routing', ...)` se registra en cada decisión.
- [ ] **[P2 — Precisión Claude]** Respuestas del worker filtradas por `id` propio (cross-wiring cero: `faq-match` no llega al SemanticSearch, ni viceversa). `faqCorpus` indexado **una sola vez** en montaje (no re-embebido por consulta).
- [ ] Flujo guiado por botones sigue intacto (0-LLM).
- [ ] `chat.php` failover no modificado.
- [ ] `specsStatus.json` ↔ `ESTADO-SPECS.md` consistentes (antidrift).
- [ ] `npm run build` verde.

---

## Riesgos y Mitigaciones

| Riesgo | Mitigación |
|--------|-----------|
| Worker compartido con SemanticSearch (race condition) | Gestionar con `id` único por solicitud (ya soportado en worker.js) |
| Frases-ejemplo insuficientes → clasificación errónea | Umbral alto (0.72) → la duda cae al LLM. Iterar ejemplos en v2 |
| Corpus FAQ pequeño (14 entradas v1) → recall bajo | Aceptado: lo que no matchea → LLM. Ampliar corpus en v2 |
| Latencia extra del Xenova en cada mensaje | Xenova ya cargado en worker; ~15-40ms adicionales aceptables vs. 300-2000ms LLM |
