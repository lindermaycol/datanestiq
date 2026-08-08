---
title: "Data Model — Spec 019: Router Determinístico Ampliado (0-LLM)"
description: "Modelo de datos técnico para el router de intenciones sin dependencia de LLM en rutas críticas: estructura de intents.json, integración con personasCorpus.js"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["router","intents","faq","0llm","data-model","semantic-search","chatbot"]
seoScore: 100
---
# Data Model — Spec 019: Router Determinístico Ampliado (0-LLM)

## 1. Archivo de Configuración de Intenciones: `src/data/intents.json`

**Rol:** Fuente de verdad versionada y editable para el clasificador. **Nunca hardcodeado en JSX.**

```jsonc
{
  "version": "1.0.0",
  "confidence_threshold": 0.72,
  "faq_match_threshold": 0.65,
  "intents": [
    {
      "id": "faq",
      "description": "Objeción o pregunta sobre servicios, precios, metodología o cómo funciona Datanestiq",
      "examples": [
        "¿Cuánto cuesta implementar una solución de datos?",
        "¿Cuánto tiempo tarda un proyecto de IA?",
        "¿Trabajan con empresas pequeñas?",
        "¿Reemplazan mi ERP o sistema actual?",
        "¿Mis datos van a la nube?",
        "¿Tienen experiencia en nuestro sector?",
        "¿Qué garantías dan sobre el ROI?",
        "¿El ROI de datos no es muy a largo plazo?",
        "Ya tenemos contrato con Microsoft",
        "¿Qué diferencia tienen frente a otras consultoras?",
        "¿Pueden integrarse con SAP?",
        "¿Cómo protegen la privacidad de los datos?"
      ]
    },
    {
      "id": "cita",
      "description": "Solicitud de agendar reunión, llamada, demo, o contacto directo",
      "examples": [
        "Quiero agendar una reunión",
        "¿Pueden llamarme?",
        "Me interesa una demo",
        "Quiero hablar con un experto",
        "¿Tienen disponibilidad esta semana?",
        "Me gustaría coordinar una llamada",
        "¿Cómo puedo contactar a un arquitecto de datos?",
        "Quiero una consultoría inicial",
        "¿Pueden mostrarme cómo funciona?",
        "Agenda una sesión de diagnóstico"
      ]
    },
    {
      "id": "guiado",
      "description": "Quiere saber qué servicio aplica, diagnóstico, recomendación por sector o rol",
      "examples": [
        "¿Qué solución aplica para mi empresa?",
        "¿Por dónde empezar con IA?",
        "¿Qué servicios tienen?",
        "¿Qué ofrecen para el sector salud?",
        "Necesito un diagnóstico de mis datos",
        "¿Qué recomiendan para una empresa de manufactura?",
        "¿Cuáles son sus pilares de servicio?",
        "¿Qué hace Datanestiq exactamente?",
        "Explícame sus áreas de especialización",
        "¿Cuál es la metodología de trabajo?"
      ]
    },
    {
      "id": "complejo",
      "description": "Consulta técnica profunda, caso de uso custom, arquitectura específica",
      "examples": [
        "Necesito una arquitectura de Data Lakehouse con Delta Lake y Databricks",
        "¿Cómo migraría mi pipeline Airflow a Prefect sin downtime?",
        "Tenemos 50TB de logs sin estructurar, ¿qué harían?",
        "¿Pueden auditar nuestro modelo de machine learning de detección de fraude?",
        "Necesito un sistema de RAG sobre documentos internos en español"
      ]
    }
  ]
}
```

---

## 2. Corpus FAQ: `personasCorpus.json` → `objectionResponses`

**No se crea un nuevo archivo.** La fuente de verdad son los `objectionResponses` de `src/data/personasCorpus.json`.

### Estructura de cada entrada:
```json
{
  "objection": "El ROI de proyectos de datos suele ser abstracto o a muy largo plazo",
  "response": "Trazamos modelos de Payback concretos con victorias tempranas (Quick Wins) en los primeros 3 a 4 meses de implementación."
}
```

### Flatten en runtime:
Al montar el Chatbot, se extrae un array plano de todas las `objectionResponses` de todos los roles:
```js
const faqCorpus = personasCorpus.roles.flatMap(r =>
  (r.objectionResponses || []).map(or => ({
    question: or.objection,
    answer: or.response
  }))
);
```
Este corpus se pasa al worker para indexación (igual que el corpus del `SemanticSearch`).

### Estadísticas actuales del corpus FAQ:
- Roles en `personasCorpus.json`: 7 (CFO, CIO, COO, CDO, Head Analytics, Gerente IT, CEO PYME)
- `objectionResponses` por rol: 2 promedio → **~14 pares objeción-respuesta** disponibles en v1.

---

## 3. Flujo de Resolución `faq` (Doble Umbral)

```
query_text
    │
    ▼
[Xenova worker] embed(query_text)
    │
    ▼
coseno vs. prototipos de intenciones (intents.json examples promediados)
    │
    ├─ intención != 'faq'  ──────────────────────► ruta correcta
    │
    ├─ intención = 'faq' + confianza < 0.72 ────► LLM fallback
    │
    └─ intención = 'faq' + confianza ≥ 0.72
           │
           ▼
       coseno vs. faqCorpus (objection texts)
           │
           ├─ best_score < 0.65 ─────────────────► LLM fallback (no forzar FAQ)
           │
           └─ best_score ≥ 0.65
                  │
                  ▼
             mostrar faqCorpus[best_idx].answer
             + botón escape → LLM
             + trackEvent('intent_routing', 'chatbot-router', {intent:'faq', confidence, resolved:'0llm'})
```

---

## 4. Parámetros Configurables

| Parámetro | Archivo | Valor por defecto | Descripción |
|-----------|---------|-------------------|-------------|
| `confidence_threshold` | `intents.json` | `0.72` | Umbral mínimo de confianza de clasificación de intención. Bajo este → LLM |
| `faq_match_threshold` | `intents.json` | `0.65` | Umbral mínimo de similitud coseno para aceptar una FAQ como respuesta. Bajo este → LLM |
| `INTENT_CORPUS_VERSION` | `intents.json → version` | `"1.0.0"` | Semver para invalidar cache de embeddings de intenciones si cambian los ejemplos |

---

## 5. Carga y Ciclo de Vida del Clasificador

### Al montar el Chatbot (una sola vez):
1. **Calentar el worker** (ya lo hace `SemanticSearch` — potencial race condition a gestionar: el Chatbot debe esperar `status: 'ready'` antes de clasificar).
2. **Indexar corpus de intenciones**: pasar al worker todas las frases-ejemplo de `intents.json` concatenadas con su ID.
3. **Indexar corpus FAQ**: pasar al worker el `faqCorpus` extraído de `personasCorpus.json`.

### En cada mensaje de texto libre:
1. `postMessage({ type: 'search', query: text, id: 'intent-classify' })` → embeddings de intención.
2. Calcular intención y confianza (ver §3).
3. Si `faq`: `postMessage({ type: 'search', query: text, id: 'faq-match' })` → coincidencia FAQ.
4. Rutear según resultado.

### Compartición del worker:
- El worker existe en `public/worker.js` y es un singleton.
- El `SemanticSearch` ya lo usa. El Chatbot debe usar la **misma instancia** (referencia al worker compartida vía una store de nanostores o un módulo singleton en `src/lib/`).
- **Tech Debt:** Gestionar correctamente las llamadas concurrentes con `id` único por solicitud (el worker ya soporta el campo `id` en la respuesta).

---

## 6. Invariantes de Honestidad (§2)

| Invariante | Garantía |
|-----------|----------|
| FAQ respuesta = texto de la taxonomía | `faqCorpus[best_idx].answer` verbatim, sin paráfrasis |
| Umbral alto → duda cae al LLM | Si `confidence < 0.72` → LLM, nunca FAQ forzada |
| Doble umbral | Si `faq_match < 0.65` → LLM, aunque la intención sea `faq` |
| Escape siempre visible | Toda respuesta FAQ incluye botón de escape al LLM |
| Sin respuestas inventadas | El LLM puede inventar; la ruta FAQ nunca inventa |