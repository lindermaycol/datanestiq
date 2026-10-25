# Plan de Implementación — Spec 018: Analítica de Micro-Interacciones (Engagement)

Este plan detalla los pasos para construir e implementar la telemetría de comportamiento agregada de los usuarios, protegiendo las especificaciones previas y cumpliendo los lineamientos de seguridad, privacidad y desempeño.

## User Review Required

> [!IMPORTANT]
> - **Coexistencia intacta de la Spec 016/014:** La tabla `interactions` y el registro secuencial de leads persisten tal como están. No se alterarán datos ni consultas de leads históricos.
> - **Endpoint Write-Only:** El endpoint `track_event.php` recibirá únicamente solicitudes POST y no tendrá consultas de lectura pública para evitar fugas de información.
> - **Políticas de Privacidad y Redacción:** Se omitirán cookies de terceros y se aplicará redacción por regex antes de la inserción para remover correos electrónicos y teléfonos de entradas textuales.

## Proposed Changes

---

### Componente: Base de Datos y Endpoint

#### [MODIFY] [`scripts/init_crm_db.php`](file:///C:/xampp/htdocs/datanestiq/scripts/init_crm_db.php)
- Añadir la creación de la tabla `interaction_events` y sus índices:
  - Campos: `id`, `session_id`, `event_type`, `event_target`, `event_value`, `created_at`.
  - Índices sobre: `session_id`, `event_type`, `created_at`.

#### [NEW] [`public/api/track_event.php`](file:///C:/xampp/htdocs/datanestiq/public/api/track_event.php)
- Implementar endpoint público que acepte solicitudes `POST` con tipo de contenido `application/json` o `application/x-www-form-urlencoded`.
- Rechazar solicitudes `GET` con código `405 Method Not Allowed`.
- Aplicar función regex de redacción de PII (`redactPii`) en `$event_value` solo si `event_type` es `'search_query'` o `'chatbot_step'`.
- Insertar el registro de forma segura en SQLite en un bloque `try/catch` silencioso.

---

### Componente: Cliente Astro (Instrumentación)

#### [MODIFY] [`src/components/islands/ContextChips.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/ContextChips.jsx)
- Instrumentar clics de chips con `navigator.sendBeacon` o `fetch` asíncrono no-bloqueante enviando tipo `'chip_click'`.

#### [MODIFY] [`src/components/islands/SemanticSearch.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/SemanticSearch.jsx)
- Instrumentar disparadores de búsqueda enviando la frase consultada con tipo `'search_query'`.

#### [MODIFY] [`src/components/islands/CopilotDemo.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/CopilotDemo.jsx)
- Instrumentar la selección de escenarios de demostración con tipo `'copilot_click'`.

#### [MODIFY] [`src/components/islands/DiagnosticWizard.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/DiagnosticWizard.jsx)
- Instrumentar avance por pasos del asistente (sector, rol, etc.) y conversión final con tipo `'wizard_step'`.

#### [MODIFY] [`src/components/islands/Chatbot.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- Instrumentar eventos del chatbot interactivo (saludos de rol, pasos guiados) con tipo `'chatbot_step'`.

---

### Componente: Panel de Administración e Higiene

#### [MODIFY] [`public/admin/api.php`](file:///C:/xampp/htdocs/datanestiq/public/admin/api.php)
- Añadir la acción `ops_behavior_analytics` protegida por `requireAuth()`.
- Generar agregados para top chips, top búsquedas, drop-off y tendencias de interacción.
- Implementar guardarraíl de honestidad (§2): mostrar "Datos de comportamiento insuficientes" si el conteo acumulado de eventos es inferior a 20.
- Ejecutar limpieza de retención (idempotente) eliminando registros mayores a 180 días en cada invocación:
  ```sql
  DELETE FROM interaction_events WHERE created_at < datetime('now', '-180 days');
  ```

#### [MODIFY] [`public/admin/index.php`](file:///C:/xampp/htdocs/datanestiq/public/admin/index.php)
- Añadir pestaña "Comportamiento" al menú de navegación del panel.
- Crear contenedor HTML y funciones JavaScript para consumir `ops_behavior_analytics` y renderizar gráficos/tablas analíticas de microinteracciones.

---

## Verification Plan

### Automated Tests
- Validaciones de sintaxis PHP: `php -l public/api/track_event.php` y `php -l public/admin/api.php`.
- Ejecución de `node scripts/build-specs-status.mjs` para garantizar que no exista desincronización de specs.
- Ejecución del static build: `npm run build` local.

### Manual Verification
- Comprobar que peticiones `GET` a `/api/track_event.php` retornen `405`.
- Comprobar que peticiones `POST` a `/api/track_event.php` guarden los eventos en la base de datos local.
- Realizar pruebas de simulación con datos de correo y teléfono para certificar la redacción de PII antes de guardar en SQLite.
- Ejecución de deploy a IONOS con `deploy_ionos.py` y validación en vivo.
