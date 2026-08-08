# Tech Debt — Spec 016: Analítica de Conversión y Loop `chat → lead → learn`

**Estado:** Fase de Diseño SDD (2026-08-01)

## Riesgos e Implicaciones de Diseño Identificados

### 1. Crecimiento de la BD SQLite (`chat_metrics`)
- **Riesgo:** En escenarios de alto tráfico, la tabla `chat_metrics` acumulará un registro por cada mensaje de texto libre enviado al chatbot.
- **Mitigación de Diseño:** Almacenar solo métricas numéricas esenciales (sin cuerpo del mensaje). Implementar una tarea de purga/rotación o archivado periódico para registros con más de 90 días de antigüedad.

### 2. Autenticación del Loop `learn` Offline
- **Riesgo:** El script de optimización `scripts/learn_prompt_optimizer.mjs` requiere acceso a las claves API free-tier de Groq/Gemini y lectura de `crm.sqlite`.
- **Mitigación de Diseño:** El script se ejecuta únicamente desde entorno CLI local o GitHub Action segura con secretos inyectados, sin exponer ningún endpoint HTTP público.

### 3. Ausencia de Gráficos JavaScript pesados en el Panel
- **Riesgo:** Evitar sobrecargar el panel `/admin/` con dependencias pesadas de gráficos (Chart.js / D3).
- **Mitigación de Diseño:** El embudo y las tarjetas de métricas se construirán utilizando HTML/CSS nativo con barras de porcentaje estilizadas en Vanilla CSS (respetando las guías de ligereza y desempeño del proyecto).

### 4. Migración de la BD SQLite en Producción (`crm.sqlite`)
- **Riesgo:** La incorporación de las columnas `sector` y `rol` a la tabla `leads` debe ejecutarse sobre una base de datos activa con registros reales.
- **Mitigación de Diseño:** Ejecutar `PRAGMA table_info(leads)` en `init_crm_db.php` para realizar la adición de columnas de forma strictly idempotente. Realizar copia de respaldo preventiva `cp crm.sqlite crm.sqlite.bak` en el servidor antes de aplicar la migración en producción.

### 5. Guardarraíl de Honestidad Radical en el Loop `learn` (§2)
- **Riesgo:** En etapas iniciales con bajo volumen de tráfico (< 10 conversiones), el análisis estadístico automatizado corre el riesgo de derivar patrones falsos o emitir métricas fabricadas.
- **Mitigación de Diseño:** `scripts/learn_prompt_optimizer.mjs` incorpora un umbral mínimo de muestra (`MIN_CONVERSIONS = 10`). Si los datos de conversión reales en `crm.sqlite` no alcanzan este umbral, el script emite un reporte transparente de "Datos insuficientes" omitiendo la generación de sugerencias ficticias o diffs simulados al `SYSTEM_PROMPT`.
