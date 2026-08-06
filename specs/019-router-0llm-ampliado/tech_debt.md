# Tech Debt — Spec 019: Router Determinístico Ampliado (0-LLM)

**Fecha de creación:** 2026-08-05  
**Estado:** Pre-build (deuda anticipada)

---

## TD-019-01 — Worker compartido: gestión de concurrencia

**Severidad:** 🟠 Media  
**Descripción:** El `public/worker.js` es usado simultáneamente por `SemanticSearch.jsx` (corpus del buscador) y, tras esta spec, por el `intentClassifier` del `Chatbot.jsx`. El worker procesa mensajes de forma secuencial. Si el `SemanticSearch` está en medio de una búsqueda cuando el chatbot recibe un mensaje, el orden de respuestas podría mezclarse.

**Mitigación en v1:** El worker ya incluye el campo `id` en cada respuesta (`postMessage({ id, results })`). Cada llamante debe filtrar solo las respuestas con su `id`. Esta disciplina debe mantenerse.

**Solución definitiva (v2):** Extraer el worker a un singleton en `src/lib/workerSingleton.ts` con un `Map<id, resolve>` de promesas pendientes. Eliminar el patrón `onmessage` ad-hoc en cada componente.

**Esfuerzo estimado:** 2h

---

## TD-019-02 — Corpus FAQ pequeño (ACTUALIZADO)

**Severidad:** 🟡 Baja-Media  
**Descripción:** El corpus de FAQ en v1 proviene solo de los `objectionResponses` de `personasCorpus.json` (~14 pares). 
**Solución parcial (2026-08-06):** Se creó `src/data/faq.json` como base dedicada para FAQ operativas (precio, horario, contacto). Actualmente varias están marcadas como `pendiente_dato_usuario` porque el proyecto no dispone de esta data real.
**Próximo paso:** Completar las respuestas operativas en `faq.json` con datos reales cuando el cliente los provea.

**Esfuerzo estimado:** 1h curación inicial + iteración continua

---

## TD-019-03 — Prototipado de intenciones: calidad de ejemplos

**Severidad:** 🟠 Media  
**Descripción:** La calidad del clasificador depende directamente de la diversidad y representatividad de las frases-ejemplo en `intents.json`. Un corpus pequeño (10-12 ejemplos por intención en v1) puede clasificar mal consultas fuera de distribución.

**Síntoma esperado:** Una consulta como "¿tienen oficinas en México?" puede ser clasificada como `guiado` o `faq` erróneamente.

**Mitigación en v1:** Umbral alto (0.72) → la duda cae al LLM. Ningún daño UX.

**Solución:** Tras 2-4 semanas de datos de `interaction_events` (Spec 018) con `event_type: 'intent_routing'`, analizar clasificaciones incorrectas y añadir ejemplos al `intents.json` de las intenciones más confundidas.

**Esfuerzo estimado:** 1h cada ciclo de mejora

---

## TD-019-04 — Latencia de inicialización: carga dual del worker

**Severidad:** 🟡 Baja  
**Descripción:** Al montar el Chatbot, se indexarán dos corpus en el worker: el de intenciones (fijo, pequeño) y el FAQ (variable, pequeño). Esto añade ~100-200ms adicionales en el primer uso del chatbot (el worker ya está warm si el usuario usó el `SemanticSearch`).

**Impacto:** Solo en el primer mensaje del chatbot. Imperceptible si el chatbot se abre después de que el buscador calentó el worker.

**Solución (v2):** Pre-indexar ambos corpus en el `onMount` del layout o en un Web Worker de alto nivel, antes de que el usuario abra el chatbot.

**Esfuerzo estimado:** 1h

---

## TD-019-05 — Escape UX: no hay feedback de "por qué esta respuesta"

**Severidad:** 🟡 Baja  
**Descripción:** El usuario ve la respuesta FAQ pero no sabe si proviene de un conocimiento curado o de un LLM. Esto puede generar desconfianza o confusión.

**Solución (v2):** Añadir un badge sutil "Respuesta de nuestra base de conocimiento" en respuestas FAQ, vs. "Respuesta generada" en respuestas LLM.

**Esfuerzo estimado:** 0.5h

---

## TD-019-06 — Umbral fijo no adaptativo (ACTUALIZADO: Calibración realizada)

**Severidad:** ✅ Resuelto (por ahora)
**Descripción:** Los umbrales `confidence_threshold` (antes 0.72) y `faq_match_threshold` (antes 0.65) generaban muchos falsos negativos en FAQ debido a la naturaleza estricta del modelo (`paraphrase-multilingual-MiniLM-L12-v2`).
**Solución Aplicada (2026-08-06):** Se creó el script `scripts/eval_router.mjs` para evaluar offline contra un dataset curado de 23 consultas (incluyendo out-of-catalog negativos). Los umbrales fueron ajustados empíricamente a:
- `confidence_threshold: 0.65` (permite que consultas cortas como "¿cómo los contacto?" pasen el filtro de intención).
- `faq_match_threshold: 0.89` (exige coincidencia casi verbatim con las *variants* de la FAQ operativa, garantizando 0 Falsos Positivos frente a queries ambiguas como "¿qué hora es?").

**Esfuerzo estimado futuro:** Automatizar el script para que corra en CI.

---

## Resumen de Deuda

| ID | Descripción | Severidad | Esfuerzo | Prioridad |
|----|-------------|-----------|---------|-----------|
| TD-019-01 | Worker singleton (concurrencia) | 🟠 Media | 2h | P1 (post-build) |
| TD-019-02 | Corpus FAQ pequeño | 🟡 Media | 1h+ | P2 |
| TD-019-03 | Calidad de ejemplos de intención | 🟠 Media | 1h/ciclo | P1 (2-4 semanas) |
| TD-019-04 | Latencia carga dual | 🟡 Baja | 1h | P3 |
| TD-019-05 | Sin feedback de fuente al usuario | 🟡 Baja | 0.5h | P3 |
| TD-019-06 | Umbral fijo no adaptativo | 🟡 Baja | 3h | P4 (futuro) |
