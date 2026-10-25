# Deuda Técnica y Mitigaciones — Spec 024: Buscador Semántico sobre el Blog

## 1. Deudas Técnicas Identificadas & Mitigaciones

| Ítem de Deuda | Riesgo | Estrategia de Mitigación |
|---|---|---|
| **Concurrencia de Mensajes en Web Worker** | Riesgo de colisión si el usuario busca simultáneamente en el Home y en el Blog. | Utilizar un `id` único explícito (`'blog-search-query'`) en las peticiones enviadas a `public/worker.js` para filtrar las respuestas en el oyente de eventos de la isla `BlogSearch.jsx`. |
| **Crecimiento de `blogCorpus.json`** | A medida que aumenten los artículos del blog, el archivo JSON servido al cliente puede crecer. | Limitar la propiedad `body_head` a los primeros 300 caracteres sanitizados de cada artículo. Mantener el corpus liviano (<50 KB). |
| **Umbral de Similitud Coseno Estático** | Si el umbral (0.25) es muy alto o muy bajo, puede filtrar resultados válidos o mostrar falsos positivos. | Centralizar la constante `SIMILARITY_THRESHOLD` en la configuración de la isla para permitir ajuste continuo basado en la telemetría del panel. |

---

## 2. Declaración de Cumplimiento de Principios
- **§5 (Principio 0-LLM)**: Cumplido. Cero llamadas a API LLM en la búsqueda.
- **§2 (Honestidad Radical)**: Cumplido. Excerpts reales y estado vacío sin invención de resultados.
- **§6 (Privacidad)**: Cumplido. La telemetría sanitiza las búsquedas mediante `redactPii()`.
