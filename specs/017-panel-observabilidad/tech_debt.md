# Tech Debt — Spec 017: Panel de Observabilidad Interna (Ops & Specs)

**Estado:** Fase de Diseño SDD (2026-08-02)

---

## Riesgos e Implicaciones de Diseño Identificados

### 1. Muestra en Frío para Cálculo de Latencias p50 / p95
- **Riesgo:** En etapas iniciales de despliegue o con bajo volumen de peticiones registradas en `chat_metrics`, las estimaciones percentiles (p50 y p95) pueden no ser estadísticamente representativas.
- **Mitigación de Diseño:** Al consultar la API, si el número de registros en `chat_metrics` es menor a 10, se muestra una indicación clara de "Muestra insuficiente (< 10 peticiones)" respetando el principio de Honestidad Radical (§2).

### 2. Sincronización entre `ESTADO-SPECS.md` y `specsStatus.json`
- **Riesgo:** Tener tanto la tabla en markdown (`planes/ESTADO-SPECS.md`) como el JSON estructurado (`src/data/specsStatus.json`) puede introducir desfasamiento si un desarrollador actualiza uno sin modificar el otro.
- **Mitigación de Diseño:** `src/data/specsStatus.json` es la **Única Fuente de Verdad (SSOT)**. Se incluye un paso de validación en `scripts/build-specs-status.mjs` que notifica durante el build si hay una incongruencia en los IDs o estados declarados.

### 3. Ausencia de Métricas de Infraestructura de Bajo Nivel (CPU / RAM)
- **Riesgo:** Al ejecutarse sobre hosting compartido IONOS PHP, el entorno no otorga acceso a métricas de kernel (uso de CPU, consumo de memoria RAM del servidor web o ancho de banda del socket).
- **Mitigación de Diseño:** La observabilidad se delimita estrictamente a la capa de aplicación (telemetría de peticiones en `chat_metrics`, tiempos de respuesta HTTP y estado de compilación estática), declarando explícitamente fuera de alcance el monitoreo de hardware.
