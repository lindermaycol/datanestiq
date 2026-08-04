---
title: "Plan de Implementación — Spec 018: Analítica de Micro-Interacciones"
description: "Este documento define la ruta crítica de desarrollo, migración e integración del backend y frontend para la Spec 018"
author: "AI Documenter"
lastUpdated: 2026-08-04
tags: ["Spec 018","Analítica de Micro-Interacciones","Desarrollo","Migración","Integración"]
seoScore: 100
---
# Plan de Implementación — Spec 018: Analítica de Micro-Interacciones

Este documento define la ruta crítica de desarrollo, migración e integración del backend y frontend para la **Spec 018**, a ejecutarse una vez aprobada la fase de diseño.

---

## Detalle de Fases de Construcción (FUTURO BUILD)

### FASE 1 · Inicialización e Integración en Base de Datos
1. **Copia de Respaldo Preventiva:** El script de inicialización (`scripts/init_crm_db.php`) generará un respaldo físico en producción: `secure_leads/crm.sqlite.bak`.
2. **Migración Idempotente:** Crear la tabla `interaction_events` y sus índices correspondientes. Validar la estructura mediante `sqlite3` y verificar la cuenta de registros pre y post migración. La tabla `interactions` y la estructura preexistente de la Spec 014/016 se mantiene intacta para no degradar el journey de leads históricos.

### FASE 2 · Endpoint de Captura y Redacción (`public/api/track_event.php`)
1. **Endpoint de Escritura:** Crear `public/api/track_event.php`. Debe:
   - Recibir solicitudes POST con `session_id`, `event_type`, `event_target`, `event_value`.
   - Aplicar el sanitizador de PII por regex para remover correos electrónicos y teléfonos únicamente en eventos de texto libre (`search_query`, `chatbot_step` con entrada libre).
   - Insertar el registro en la base de datos de manera fail-safe.
2. **Seguridad:** Bloquear peticiones de tipo `GET` o lecturas, retornando código `405 Method Not Allowed`.

### FASE 3 · Instrumentación del Cliente Astro (Beacons no-bloqueantes)
1. **Utilidad de Tracking:** Implementar una función JavaScript global `trackEvent(type, target, value)` que:
   - Emita la solicitud de forma asíncrona mediante `navigator.sendBeacon()` o `fetch()` fire-and-forget.
   - Envuelva el envío en un bloque `try/catch` silencioso para evitar interrumpir la UX del usuario.
2. **Instrumentación en Componentes:**
   - `<ContextChips />`: Clic en chips de sector y rol.
   - `<SemanticSearch />`: Registro de búsquedas textuales de los usuarios.
   - `<CopilotDemo />`: Clic en escenarios.
   - `<DiagnosticWizard />` & `<Chatbot />`: Progresión e hitos alcanzados.

### FASE 4 · Visualización en Panel de Administración e Higiene de Retención
1. **Pestaña de Comportamiento:** Agregar la pestaña "Comportamiento" dentro de la sección "Analítica" en `/admin/index.php`.
2. **Agregados Analíticos:** Implementar el llamado a las APIs agregadas en `public/admin/api.php` para renderizar las tablas de popularidad, drop-off y tendencias de microinteracciones.
3. **Mecanismo de Retención de 180 Días:**
   - Crear una rutina de mantenimiento en PHP dentro de `api.php` o ejecutada de forma periódica:
     ```sql
     DELETE FROM interaction_events WHERE created_at < datetime('now', '-180 days');
     ```
   - El script se ejecuta de forma idempotente y silenciosa en cada consulta a la pestaña de administración, manteniendo la base de datos limpia de eventos antiguos.

### FASE 5 · Validación y Despliegue en Vivo
1. **Pruebas de compilación:** `npm run build` y validaciones de sintaxis PHP (`php -l`).
2. **Deploy Gateado:** Ejecución de `deploy_ionos.py` con pre-vuelos de verificación.
3. **Comprobación de Fugas:** Validar que `public/api/track_event.php` rechace lecturas y no existan archivos JSON expuestos en la carpeta pública del build.