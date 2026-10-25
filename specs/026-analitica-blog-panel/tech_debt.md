# Deuda Técnica — Spec 026: Analítica de Contenido/Blog en el Panel (Refinado tras Auditoría)

Este documento registra los compromisos de arquitectura, límites de rendimiento y posibles áreas de mejora identificadas durante el diseño e implementación de la Spec 026.

---

## 1. Deuda Técnica Registrada y Calibraciones

### TD-026.1: Tratamiento de Eventos Históricos Anteriores a la Spec 026
- **Descripción**: Las búsquedas grabadas en la Spec 024 antes de la Spec 026 se almacenaron como texto plano sin la clave `"n"` (`results_count`).
- **Mitigación**: El endpoint agregador en PHP analiza de forma segura mediante `json_decode` la cadena `event_value`. Si no es un JSON válido con la propiedad `n`, asigna `results_count = null` y contabiliza la búsqueda dentro de `total_searches`, excluyéndola del filtro estricto de gaps (`n = 0`) y del denominador de `zero_match_rate` para no distorsionar las métricas.

### TD-026.2: Muestra Mínima de 15 Búsquedas para Evitar Distorsiones (Guard §2)
- **Descripción**: Si un blog tiene muy pocas visitas o búsquedas iniciales, mostrar gráficos de tendencia o porcentajes de gaps sobre una muestra de 2 o 3 búsquedas generaría conclusiones distorsionadas.
- **Mitigación**: Guardarraíl §2 con umbral estricto en < 15 búsquedas. Responde `insufficient_data: true` de forma transparente.

### TD-026.3: Retención de 180 Días y Purga Idempotente
- **Descripción**: La analítica se limita a eventos de los últimos 180 días. Los registros más antiguos se purgan periódicamente en las rutinas de mantenimiento de `interaction_events`.
- **Mitigación**: Coincide con la política de retención global de analítica de comportamiento (Spec 018).

---

## 2. Estado de Deuda Técnica
- **Items Abiertos**: 3 (TD-026.1, TD-026.2, TD-026.3)
- **Riesgo Global**: Bajo (Operación 100% de solo-lectura sobre la tabla existente `interaction_events` y procesamiento seguro en PHP).
