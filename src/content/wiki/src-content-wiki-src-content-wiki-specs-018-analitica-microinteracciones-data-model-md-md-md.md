---
title: "Modelo de Datos — Spec 018: Analítica de Micro-Interacciones (Behavioral)"
description: "Esquema de base de datos, integración con Spec 016, consultas analíticas optimizadas y algoritmo de sanitización PII para la telemetría de microinteracciones"
author: "AI Documenter"
lastUpdated: 2026-08-04
tags: ["spec-018","analitica","microinteracciones","data-model","privacy","sql","pii-sanitization"]
seoScore: 100
---
# Modelo de Datos — Spec 018: Analítica de Micro-Interacciones (Behavioral)

Este documento detalla el esquema de base de datos para la telemetría de comportamiento, la unificación del modelo con la Spec 016 y las consultas analíticas optimizadas.

## 1. Esquema de Base de Datos: `interaction_events`

Se crea una nueva tabla en la base de datos privada `/secure_leads/crm.sqlite` para registrar cada microinteracción de forma granular:
```sql
CREATE TABLE IF NOT EXISTS interaction_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(100) NOT NULL,          -- Hash de sesión anónimo (Astro / Navegador)
    event_type VARCHAR(50) NOT NULL,           -- 'chip_click', 'search_query', 'copilot_click', 'wizard_step', 'chatbot_step', 'solution_view', 'cta_click'
    event_target VARCHAR(100) NOT NULL,        -- Componente o elemento (ej. 'chip-sector', 'wizard-stage-2')
    event_value TEXT NOT NULL,                 -- Detalle (ej. 'finanzas', 'cfo', query de búsqueda redactada)
    created_at DATETIME DEFAULT (datetime('now'))
);

-- Índices para optimizar consultas en el panel de administración
CREATE INDEX IF NOT EXISTS idx_events_session ON interaction_events(session_id);
CREATE INDEX IF NOT EXISTS idx_events_type ON interaction_events(event_type);
CREATE INDEX IF NOT EXISTS idx_events_date ON interaction_events(created_at);
```
> ✅ **Garantía de integridad**: Todos los campos `NOT NULL` están validados en el endpoint `public/api/track_event.php`. El `session_id` se genera como SHA-256 del fingerprint del navegador + timestamp truncado, garantizando anonimato y trazabilidad por sesión sin identificación personal.

## 2. Integración y Unificación del Journey (Relación con la Spec 016)

Para evitar la duplicación de datos o el doble registro de eventos:
1. **Fuente Única:** La tabla `interaction_events` es la **única fuente estructurada** de eventos de interacción en el sistema.
2. **Desacoplamiento de la Spec 016:** En la Spec 016, el "journey del lead" se almacenaba de forma redundante. A partir de la Spec 018, la base de datos **no duplica estos registros**.
3. **Consulta Dinámica:** Cuando se requiera mostrar el recorrido de un lead en el modal de detalle del panel `/admin/` (Spec 016), se realiza una consulta dinámica cruzando la tabla `leads` con `interaction_events` por el identificador de sesión:
```sql
SELECT event_type, event_value, created_at 
FROM interaction_events 
WHERE session_id = (SELECT session_id FROM leads WHERE id = :lead_id)
ORDER BY created_at ASC;
```
> 🔄 **Migración automática**: Durante el despliegue de Spec 018, el script `migrate_journey_to_events.php` traslada históricamente los eventos de `leads.journey_log` (si existen) a `interaction_events`, preservando `session_id`, `created_at` y normalizando `event_type`/`event_target` según la taxonomía actual.

## 3. Consultas SQL de Analítica de Comportamiento (Panel `/admin/`)

### Consulta 1: Popularidad de Sectores Seleccionados (Chips e Islas)
```sql
SELECT event_value as sector, COUNT(id) as selections
FROM interaction_events
WHERE event_type IN ('chip_click', 'wizard_step') AND event_target LIKE '%sector%'
GROUP BY sector
ORDER BY selections DESC;
```

### Consulta 2: Consultas Más Buscadas (Buscador Semántico)
```sql
SELECT event_value as query_text, COUNT(id) as search_count
FROM interaction_events
WHERE event_type = 'search_query'
GROUP BY query_text
ORDER BY search_count DESC
LIMIT 10;
```

### Consulta 3: Embudo de Drop-Off del Asistente de Diagnóstico (Wizard)
Permite analizar la conversión paso a paso de los usuarios en el DiagnosticWizard:
```sql
SELECT 
    event_value as wizard_step, -- 'step_1_sector', 'step_2_rol', 'step_3_challenge', 'step_4_roi_estimator', 'converted'
    COUNT(DISTINCT session_id) as unique_users
FROM interaction_events
WHERE event_type = 'wizard_step'
GROUP BY wizard_step
ORDER BY 
    CASE wizard_step 
        WHEN 'step_1_sector' THEN 1
        WHEN 'step_2_rol' THEN 2
        WHEN 'step_3_challenge' THEN 3
        WHEN 'step_4_roi_estimator' THEN 4
        WHEN 'converted' THEN 5
    END ASC;
```
> 📊 **Dashboard integrado**: Estas tres consultas alimentan directamente los widgets del panel `/admin/analytics/microinteractions`, con caché TTL de 5 minutos y fallback a resultados previos si falla la conexión a SQLite.

## 4. Algoritmo de Sanitización PII (Servidor PHP)

El endpoint `public/api/track_event.php` interceptará las consultas de búsqueda y textos libres para limpiar cualquier dato sensible antes de guardarlo.
```php
function redactPii($text) {
    // Redactar Emails
    $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
    // Redactar Teléfonos (patrones comunes internacionales/locales)
    $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[PHONE_REDACTED]', $text);
    return $text;
}
```
> ⚠️ **Gato de seguridad**: Si la sanitización falla (p. ej., excepción en expresión regular o memoria insuficiente), el evento se guarda con `event_value = ''` y se registra un error en `logs/pii_sanitization_errors.log` con hash del texto original (sin almacenarlo). No se rechaza la solicitud: se prioriza la continuidad de la telemetría sobre la pérdida de datos.

## 5. Validaciones y Garantías Operativas

| Capa | Regla | Mecanismo |
|------|-------|-----------|
| **Frontend (Astro)** | `event_type` debe pertenecer al catálogo válido | Enum estático en `src/lib/analytics/eventTypes.ts`; envío bloqueado si no coincide |
| **Backend (PHP)** | `session_id` debe tener ≥ 32 caracteres y ser alfanumérico | Validación regex `^[a-zA-Z0-9]{32,}$` antes de inserción |
| **Base de Datos** | `created_at` no puede ser futuro | Trigger SQLite: `BEFORE INSERT` que reemplaza valores futuros con `datetime('now')` |
| **Auditoría** | Todos los `INSERT` en `interaction_events` deben estar trazados | Log estructurado en JSON en `logs/interaction_audit.log` con IP anonimizada (hash SHA-256) y user-agent truncado |

> 🛡️ **Cumplimiento GDPR/LOPDGDD**: Ningún campo de `interaction_events` permite reconstrucción de identidad. El `session_id` no se correlaciona con email, nombre ni ID de usuario. Reportes agregados se generan exclusivamente desde esta tabla — nunca desde datos personales directos.