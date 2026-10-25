---
title: "Modelo de Datos — Spec 018: Analítica de Micro-Interacciones (Behavioral)"
description: "Este documento detalla el esquema de base de datos para la telemetría de comportamiento, la coexistencia no destructiva con el journey de la Spec 016/014 y l"
author: "AI Documenter"
lastUpdated: 2026-08-04
tags: ["data model","analytics","microinteractions","behavioral telemetry","sqlite","pii sanitization","sql","php","spec 018"]
seoScore: 100
---
# Modelo de Datos — Spec 018: Analítica de Micro-Interacciones (Behavioral)

Este documento detalla el esquema de base de datos para la telemetría de comportamiento, la coexistencia no destructiva con el journey de la Spec 016/014 y las consultas analíticas optimizadas.

---

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

---

## 2. Coexistencia y Relación con el Journey de la Spec 016/014

Para garantizar la estabilidad y confiabilidad de los datos históricos en producción:

1.  **Coexistencia, no reemplazo:**
    -   **`interactions` (Spec 014/015):** Continúa siendo la tabla confiable y transaccional para almacenar el journey secuencial de un lead convertido. Se registra en el payload de confirmación (`save_wizard.php`), asegurando que no se pierdan datos por problemas de red.
    -   **`interaction_events` (Spec 018):** Registra eventos best-effort de comportamiento para **todos** los usuarios (conviertan o no). Es adecuada para el análisis agregado.
2.  **Solapamiento Menor Permitido:** Se acepta que ciertos clics (como la progresión del Wizard) se registren de forma concurrente en ambas tablas. Es una redundancia deliberada para mantener la robustez transaccional del lead sin comprometer los datos de conversión históricos.
3.  **No-Refactor:** El panel `/admin/` continuará leyendo el journey individual del lead desde la tabla `interactions`, asegurando que los leads históricos sigan visualizándose con sus datos intactos.

---

## 3. Consultas SQL de Analítica de Comportamiento (Panel `/admin/`)

### Consulta 1: Popularidad de Sectores Seleccionados (Chips e Asistente)
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

---

## 4. Algoritmo de Sanitización PII (Servidor PHP)

El endpoint `public/api/track_event.php` interceptará las consultas de búsqueda y mensajes libres para limpiar datos sensibles antes de la inserción.

```php
function redactPii($text) {
    // Redactar Emails
    $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
    // Redactar Teléfonos (patrones comunes internacionales/locales)
    $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[PHONE_REDACTED]', $text);
    return $text;
}
```

-   **Ámbito:** La redacción de PII se aplica exclusivamente a los campos de entrada de texto libre (`search_query` y mensajes de chat en `chatbot_step`) en `$event_value`. Los eventos categóricos y estructurados (`chip_click`, `copilot_click`) no pasan por este filtro ya que son enums controlados del sistema.
-   **Gate/Guardarraíl de seguridad:** Si el proceso de redacción o saneamiento de PII falla, el valor del evento se reemplaza por un string vacío (`""`) o se ignora de manera fail-closed.