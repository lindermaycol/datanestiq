# Spec 021: Panel-First (Journey navegable y unificación de monitoreo en panel)

## WHY
El panel de administración `/admin/` cuenta con dos fricciones operativas que limitan su usabilidad práctica:
1. **Journey Reconstructor manual:** Para reconstruir el recorrido de un usuario, el administrador debe copiar y pegar un `session_id` crudo. Dicho identificador es desconocido para el usuario, lo que imposibilita una auditoría exploratoria fluida.
2. **Fragmentación en archivos planos:** Diversas especificaciones y procesos de fondo vuelcan métricas, alertas de consumo, reportes de optimización y leads extraídos por LLM en archivos locales (`.csv`, `.jsonl`, `.md`) ubicados en carpetas seguras. El administrador debe descargarlos y abrirlos de forma externa, fragmentando la experiencia de monitoreo.

## WHAT (Alcance)

### Parte 1 — Journey Navegable y Lista de Sesiones
- **Endpoint `action=journey_sessions`:** Nuevo endpoint administrativo privado (detrás de `auth.php`) que retorna una lista paginada y ordenada de las sesiones recientes ( leads registrados y visitas anónimas con actividad).
- **Resumen Legible:** Cada fila de sesión anónima derivará de forma autónoma su contexto:
  - Último sector y rol detectado (no vacíos) de `demand_signals`.
  - Primera consulta literal redactada.
  - Conteo total de eventos de comportamiento (`interaction_events`).
  - Marca de última actividad.
- **Ocultamiento de ID (§2):** El `session_id` se transporta como un atributo de datos HTML (`data-session-id`) oculto. Nunca se renderiza visiblemente ni se expone en la URL de navegación del panel.
- **UI Integrada:** La pestaña "Demanda & Journey" mostrará esta lista de sesiones clickeables. Al hacer clic en cualquier fila, se cargará su Journey Reconstructor.

### Parte 2 — Migración de Archivos Planos a SQLite e Interfaz
1. **Métricas y Alertas (Dual-Write Fail-Safe):**
   - El script `public/api/chat.php` insertará en caliente las métricas de consumo de tokens/latencia y las alertas burst/failover en las nuevas tablas SQLite `usage_metrics` y `alerts` resguardado por un bloque `try-catch` autónomo (fail-safe).
   - Mantendrá la escritura paralela en los archivos planos de respaldo (`usage_metrics.jsonl` y `alerts.jsonl`) para redundancia.
   - Pestaña **"Observabilidad Ops"** del panel leerá estas tablas con paginación y filtros de fecha.
2. **Leads Detectados en Conversaciones:**
   - Endpoint `action=leads_detected` parseará `leads_datanestiq.csv` (generado por `extraer_leads.php`), realizando una deduplicación semántica contra la tabla `leads` del CRM (omitirá aquellos `session_id` o correos que ya cuenten con formulario completado en CRM).
   - **Contacto Real (§2):** Esta vista no aplicará `redactPii` dado que su fin es comercial. Se restringe de forma absoluta tras la autenticación de `auth.php` y nunca se expondrá públicamente.
3. **Insights del Loop Learn:**
   - Endpoint `action=learn_insights` cargará el reporte de optimización (`PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md`) y lo renderizará formateado en la pestaña "Analítica de Conversión".

### Parte 3 — Datos Demo Marcados y Separables
- **Seeder y Purger:** Los scripts `seed_demand_demo.php` y `purge_demand_demo.php` poblarán e higienizarán la base de datos de señales demo.
- **Exclusión por Defecto (§2):** Las queries de analítica del panel y de la lista de sesiones excluirán por defecto los registros con `session_id LIKE 'demoseed%'` para no contaminar las métricas de negocio reales.
- **Toggle de Previsualización:** Un control en la interfaz administrativa permitirá activar la visualización de datos demo, mostrando un banner destacado indicando que la visualización contiene datos de prueba sembrados.

## CONSTRAINTS
- **Seguridad (Fail-Closed):** Todos los endpoints y vistas de monitoreo operarán estrictamente detrás del validador `auth.php`.
- **Privacidad PII:** El visor de logs crudos y listas de sesiones anónimas mantendrán PII estrictamente redactada.
- **Estética e Integración:** La UI se construirá usando Vanilla CSS y HTML nativo, en línea con el diseño de observabilidad previo.
- **Idempotencia:** Las migraciones sobre `crm.sqlite` se ejecutarán mediante CLI y mantendrán la regla de backup y validación de conteo pre/post.

## OUT-OF-SCOPE
- No se generarán nuevas exportaciones manuales a formatos CSV/Excel.
- No se retirarán las tuberías de escritura a archivos planos de respaldo.
- No se expondrá ningún API pública de lectura de logs o leads.

## TASKS
- [ ] Implementar la migración de base de datos (`init_crm_db.php`) para las tablas `alerts` y `usage_metrics`.
- [ ] Modificar `public/api/chat.php` para incorporar el dual-write fail-safe.
- [ ] Desarrollar `seed_demand_demo.php` y `purge_demand_demo.php` con el prefijo `demoseed%`.
- [ ] Implementar endpoints en `public/admin/api.php` con exclusión por defecto de datos demo.
- [ ] Diseñar y programar los elementos UI interactivos (lista clickeable, toggle demo, vista de leads detectados, y reportes ops) en `public/admin/index.php`.
