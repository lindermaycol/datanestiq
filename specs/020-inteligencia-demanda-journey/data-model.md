# Data Model — Spec 020: Inteligencia de Demanda y Journey Reconstructor

Este documento define la estructura de persistencia, las relaciones de datos y las consultas analíticas necesarias para dar soporte a la Spec 020.

---

## 1. Esquema de Base de Datos (Secure Leads CRM — SQLite)

Para evitar sobrecargar la tabla de telemetría de comportamiento genérico `interaction_events`, se crea una tabla dedicada llamada `demand_signals` dentro del archivo de base de datos seguro `secure_leads/crm.sqlite`.

### 1.1 Tabla: `demand_signals`

Almacena de forma estructurada cada señal de demanda capturada en las consultas de texto libre del chatbot.

```sql
CREATE TABLE IF NOT EXISTS demand_signals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(100) NOT NULL,
    query_redacted TEXT NOT NULL,
    intent VARCHAR(50) NOT NULL,
    confidence REAL NOT NULL,
    matched_service VARCHAR(100) DEFAULT NULL, -- ID del servicio de la taxonomía
    offered INTEGER NOT NULL,                  -- 1 = true, 0 = false (umbral de coincidencia superado)
    resolved VARCHAR(50) NOT NULL,             -- '0llm' | 'llm'
    created_at DATETIME DEFAULT (datetime('now'))
);
```

#### Índices de Performance:
```sql
CREATE INDEX IF NOT EXISTS idx_demand_signals_session ON demand_signals(session_id);
CREATE INDEX IF NOT EXISTS idx_demand_signals_offered ON demand_signals(offered);
CREATE INDEX IF NOT EXISTS idx_demand_signals_created ON demand_signals(created_at);
```

---

## 2. Costura de Datos (Session-to-Lead Stitching)

La reconstrucción del Journey se fundamenta en la consistencia de la clave `session_id`. El ecosistema de base de datos actual ya cuenta con este campo distribuido en las siguientes entidades:

1. **`leads`**: `session_id VARCHAR(100) UNIQUE`
2. **`appointments`**: `session_id VARCHAR(100)`
3. **`chat_metrics`**: `session_id VARCHAR(100)`
4. **`interaction_events`**: `session_id VARCHAR(100)`
5. **`demand_signals`**: `session_id VARCHAR(100)`

### Relaciones del Journey:
```
                [leads] (session_id)
                   |
     +-------------+-------------+-------------+-------------+
     |             |             |             |             |
[appointments] [chat_metrics] [interaction_events] [demand_signals]
(session_id)   (session_id)   (session_id)        (session_id)
```

---

## 3. Umbrales y Configuración de Demanda

Se define en `src/data/intents.json` (o archivo de configuración equivalente) el nuevo parámetro:
- `catalog_match_threshold: 0.60`
  Cualquier coincidencia de texto libre contra las descripciones del catálogo de servicios (`taxonomyCorpus.json`) con un score inferior a este valor marcará `offered = 0` (falso).

---

## 4. Consultas Analíticas (Panel Admin)

### 4.1 Bucket 1: Demanda No Atendida (Oportunidades de Mercado)
Agrupa semánticamente las consultas no atendidas con frecuencia y representativas.
```sql
SELECT 
    matched_service, 
    COUNT(*) as total_requests,
    GROUP_CONCAT(query_redacted, ' | ') as examples
FROM demand_signals
WHERE offered = 0
GROUP BY matched_service
HAVING COUNT(*) >= :min_sample_size -- Guard §2 (ej. N = 20)
ORDER BY total_requests DESC;
```

### 4.2 Bucket 2: Fugas de Conversión por Servicio
Muestra las sesiones donde hubo interés en un servicio pero el visitante no completó el formulario de lead.
```sql
SELECT 
    ds.matched_service,
    COUNT(DISTINCT ds.session_id) as total_interested_sessions,
    COUNT(DISTINCT l.id) as converted_leads,
    (COUNT(DISTINCT ds.session_id) - COUNT(DISTINCT l.id)) as leaked_sessions,
    ROUND((1.0 - (CAST(COUNT(DISTINCT l.id) AS REAL) / COUNT(DISTINCT ds.session_id))) * 100, 2) as leakage_percentage
FROM demand_signals ds
LEFT JOIN leads l ON ds.session_id = l.session_id
WHERE ds.offered = 1
GROUP BY ds.matched_service
HAVING COUNT(DISTINCT ds.session_id) >= :min_conversion_sample -- Guard §2 (ej. N = 10)
ORDER BY leakage_percentage DESC;
```

### 4.3 Reconstructor de Journey por Lead (Buckets 3 & 4)
Reúne toda la cronología de interacciones pre y post conversión para un lead en específico.
```sql
SELECT 'behavior' as source, event_type as activity, event_target as target, event_value as detail, created_at as ts
FROM interaction_events
WHERE session_id = :session_id

UNION ALL

SELECT 'demand' as source, intent as activity, matched_service as target, query_redacted as detail, created_at as ts
FROM demand_signals
WHERE session_id = :session_id

UNION ALL

SELECT 'crm_status' as source, 'change_status' as activity, old_status || ' -> ' || new_status as target, notes as detail, created_at as ts
FROM status_history sh
JOIN leads l ON sh.lead_id = l.id
WHERE l.session_id = :session_id

ORDER BY ts ASC;
```
