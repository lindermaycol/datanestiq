# Tech Debt — Spec 021: Panel-First

Este documento detalla la deuda técnica identificada para la Spec 021.

---

## TD-021-01 — Sobrecarga de escritura dual en chat.php
- **Severidad:** 🟡 Baja
- **Descripción:** La escritura paralela en SQLite y en archivos planos (`usage_metrics.jsonl` y `alerts.jsonl`) añade una latencia marginal en cada mensaje del chatbot procesado por el LLM.
- **Mitigación en v1:** El flujo de escritura está protegido por bloques `try-catch` silenciosos que evitan que un fallo de E/S o bloqueo de base de datos interrumpa la experiencia del usuario final.
- **Solución a largo plazo:** Delegar la escritura de logs a un proceso de cola en segundo plano (background worker) o utilizar un gestor de colas asíncrono.

---

## TD-021-02 — Lectura ineficiente de CSV de leads detectados
- **Severidad:** 🟡 Baja
- **Descripción:** El endpoint `leads_detected` lee y procesa el archivo CSV `leads_datanestiq.csv` de forma síncrona en cada petición del administrador. Si el volumen de conversaciones analizadas y leads detectados crece significativamente (>10,000 registros), el parsing del archivo y su cruce en memoria contra la base de datos ralentizará la interfaz del panel admin.
- **Mitigación en v1:** Dado el flujo actual de desarrollo, el archivo CSV se mantiene pequeño y manejable.
- **Solución a largo plazo:** Migrar el almacenamiento de leads detectados por el extractor por lotes directamente a una tabla SQLite dedicada (`leads_extracted`) equipada con índices por `session_id` y `created_at`.

---

## TD-021-03 — Duplicidad conceptual con chat_metrics
- **Severidad:** 🟡 Baja
- **Descripción:** La tabla `usage_metrics` comparte información similar de telemetría a la existente `chat_metrics` (ambas registran latencia y tokens estimados).
- **Mitigación en v1:** Se justifican por separado dado que `chat_metrics` alimenta tendencias Ops agregadas de SLA de la Spec 016, mientras que `usage_metrics` conserva la granularidad por invocación del modelo de la Spec 021 para auditorías precisas.
- **Solución a largo plazo:** Unificar ambos esquemas en una única tabla de telemetría consolidada de solicitudes de chat.
