# Spec 021: Panel-First (Journey navegable y unificación de monitoreo en panel)

## WHY
El panel de administración `/admin/` cuenta con dos fricciones operativas que limitan su usabilidad práctica:
1. **Journey Reconstructor manual:** Para reconstruir el recorrido de un usuario, el administrador debe copiar y pegar un `session_id` crudo. Dicho identificador es desconocido para el usuario, lo que imposibilita una auditoría exploratoria fluida.
2. **Fragmentación en archivos planos:** Diversas especificaciones y procesos de fondo vuelcan métricas, alertas de consumo, reportes de optimización y leads extraídos por LLM en archivos locales (`.csv`, `.jsonl`, `.md`) ubicados en carpetas seguras. El administrador debe descargarlos y abrirlos de forma externa, fragmentando la experiencia de monitoreo.

## WHAT (Alcance)

### Parte 1 — Journey Navegable y Lista de Sesiones
- **Endpoint `action=journey_sessions`:** Nuevo endpoint administrativo privado (detrás de `auth.php`) que retorna una lista paginada y ordenada de las sesiones recientes (leads registrados y visitas anónimas con actividad).
- **Enfoque en Demanda:** Las visitas anónimas se listarán exclusivamente si cuentan con al menos una señal de demanda (`demand_signals`). Las visitas anónimas que solo contengan navegación o clics (`interaction_events`) sin consultas de texto libre no se listarán.
- **Resumen Legible:** Cada fila de sesión anónima derivará de forma autónoma su contexto:
  - Último sector y rol detectado (no vacíos) de `demand_signals`.
  - Primera consulta literal redactada.
  - Conteo total de eventos de comportamiento (`interaction_events`) de la sesión.
  - Marca de última actividad.
- **Ocultamiento de ID (§2):** El `session_id` se transporta como un atributo de datos HTML (`data-session-id`) oculto. Nunca se renderiza visiblemente ni se expone en la URL de navegación del panel.
- **UI Integrada:** La pestaña "Demanda & Journey" mostrará esta lista de sesiones clickeables. Al hacer clic en cualquier fila, se cargará su Journey Reconstructor.

### Parte 2 — Migración de Archivos Planos a SQLite e Interfaz
1. **Métricas y Alertas (Dual-Write Fail-Safe):**
   - El script `public/api/chat.php` insertará en caliente las alertas de infraestructura/failover en la nueva tabla `alerts`, y el desglose de tokens (`prompt_tokens` y `completion_tokens`) en la tabla `chat_metrics` extendida de forma no destructiva.
   - Todo resguardado por un bloque `try-catch` autónomo (fail-safe) que garantice que fallos de escritura de base de datos no afecten la respuesta de chat al usuario.
   - Se mantendrá la escritura paralela en los archivos planos de respaldo (`usage_metrics.jsonl` y `alerts.jsonl`) para redundancia.
   - Pestaña **"Observabilidad Ops"** del panel leerá la tabla `alerts` y la agregación de `chat_metrics` con paginación y filtros de fecha.
2. **Leads Detectados en Conversaciones:**
   - Endpoint `action=leads_detected` parseará `leads_datanestiq.csv` (generado por `extraer_leads.php`), realizando una deduplicación semántica contra la tabla `leads` del CRM (omitirá aquellos `session_id` o correos/emails que ya cuenten con formulario completado en la tabla `leads`).
   - **Contacto Real (§2):** Esta vista mostrará la información de contacto original sin redactar, puesto que su fin es puramente comercial. Se restringe de forma absoluta tras la autenticación de `auth.php` y nunca se expondrá públicamente.
3. **Insights del Loop Learn:**
   - Endpoint `action=learn_insights` cargará el reporte de optimización (`PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md`) y lo renderizará formateado en la pestaña "Analítica de Conversión".

### Parte 3 — Datos Demo Marcados y Separables
- **Seeder y Purger:** Los scripts `seed_demand_demo.php` y `purge_demand_demo.php` poblarán e higienizarán la base de datos de señales demo.
- **Exclusión por Defecto (§2):** Las queries de analítica del panel y de la lista de sesiones excluirán por defecto los registros con `session_id LIKE 'demoseed%'` para no contaminar las métricas de negocio reales.
- **Toggle de Previsualización:** Un control en la interfaz administrativa permitirá activar la visualización de datos demo, mostrando un banner destacado indicando que la visualización contiene datos de prueba sembrados.

## CONSTRAINTS
- **Seguridad (Fail-Closed):** Todos los endpoints y vistas de monitoreo operarán estrictamente detrás del validador `auth.php`.
- **Diferenciación de PII (§2):** 
  - Las listas anónimas y de comportamiento mantendrán PII estrictamente redactada mediante regex.
  - La vista `leads_detected` mostrará los datos comerciales de contacto de forma íntegra (sin redactar), accesible única y exclusivamente a usuarios administradores autenticados.
- **Estética e Integración:** La UI se construirá usando Vanilla CSS y HTML nativo, en línea con el diseño de observabilidad previo.
- **Idempotencia:** Las migraciones sobre `crm.sqlite` se ejecutarán mediante CLI y mantendrán la regla de backup y validación de conteo pre/post.

## OUT-OF-SCOPE
- No se generarán nuevas exportaciones manuales a formatos CSV/Excel.
- No se retirarán las tuberías de escritura a archivos planos de respaldo.
- No se expondrá ningún API pública de lectura de logs o leads.

## TASKS
- [x] Implementar la migración de base de datos (`init_crm_db.php`) para la tabla `alerts` y la extensión de `chat_metrics`.
- [x] Modificar `public/api/chat.php` para incorporar el dual-write fail-safe de alertas y desglose de tokens en `chat_metrics`.
- [x] Desarrollar `seed_demand_demo.php` y `purge_demand_demo.php` con el prefijo `demoseed%`.
- [x] Implementar endpoints en `public/admin/api.php` con exclusión por defecto de datos demo y deduplicación por session_id y email.
- [x] Diseñar y programar los elementos UI interactivos (lista clickeable con toggle demo y first_query, vista de leads detectados dedupados, y reportes ops) en `public/admin/index.php`.
