# Modelo de Datos — Spec 018: Analítica de Micro-Interacciones (Behavioral)

Este documento detalla el esquema de base de datos para la telemetría de comportamiento, la unificación del modelo con la Spec 016 y las consultas analíticas optimizadas.

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

---

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

---

## 4. Algoritmo de Sanitización PII (Servidor PHP)

El endpoint `public/api/track_event.php` interceptará las consultas de búsqueda y textos libres para limpiar cualquier dato sensible antes de guardarlo.

```php
function redactPii($text) {
    // Redactar Emails
    $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
    // Redactar Teléfonos (patrones comunes internacionales/locales)
    $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[PHONE_REDACTED]', $text);
    return $text;
}
```
 Gato de seguridad: Si la sanitización falla, el evento se guarda con el texto vacío o ignorado.
