# Data Model — Spec 021: Panel-First

## 1. Esquema de Base de Datos SQLite (Nuevas Tablas)

Para soportar la migración de archivos planos a la base de datos de manera indexada y estructurada, se añaden las siguientes tablas a `crm.sqlite`:

### Tabla `alerts`
Almacena alertas de consumo anómalo (burst), cruce de umbrales del 80% diario y fallos de proveedores de IA (failovers).
```sql
CREATE TABLE IF NOT EXISTS alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(64) NULL,
    alert_type VARCHAR(32) NOT NULL, -- 'burst', 'cap_80', 'failover'
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_alerts_session_id ON alerts(session_id);
CREATE INDEX IF NOT EXISTS idx_alerts_created_at ON alerts(created_at);
```

### Tabla `usage_metrics`
Registra el consumo y costo indirecto de cada invocación al LLM.
```sql
CREATE TABLE IF NOT EXISTS usage_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(64) NOT NULL,
    model VARCHAR(64) NOT NULL,
    prompt_tokens INTEGER NOT NULL DEFAULT 0,
    completion_tokens INTEGER NOT NULL DEFAULT 0,
    total_tokens INTEGER NOT NULL DEFAULT 0,
    latency_ms INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_usage_metrics_session_id ON usage_metrics(session_id);
CREATE INDEX IF NOT EXISTS idx_usage_metrics_created_at ON usage_metrics(created_at);
```

---

## 2. Consultas y Filtros Clave

### Consulta de Sesiones Navegables (`journey_sessions`)
Devuelve la lista unificada de leads y visitas anónimas con actividad, soportando paginación e índices, y ocultando el identificador real en la UI.
```sql
-- Obtiene leads con formulario completado
SELECT 
    l.session_id,
    l.email,
    l.organizacion,
    'lead_crm' as session_type,
    MAX(ds.created_at) as last_activity,
    COUNT(ds.id) as signals_count
FROM leads l
LEFT JOIN demand_signals ds ON l.session_id = ds.session_id
WHERE (:include_demo = 1 OR l.session_id NOT LIKE 'demoseed%')
GROUP BY l.session_id

UNION ALL

-- Obtiene sesiones anónimas (sin leads registrados) que tengan señales de demanda
SELECT 
    ds.session_id,
    'Anónimo' as email,
    'S: ' || COALESCE((SELECT sector FROM demand_signals ds2 WHERE ds2.session_id = ds.session_id AND ds2.sector IS NOT NULL AND ds2.sector != '' ORDER BY ds2.id DESC LIMIT 1), 'N/A') || 
    ' / R: ' || COALESCE((SELECT role FROM demand_signals ds3 WHERE ds3.session_id = ds.session_id AND ds3.role IS NOT NULL AND ds3.role != '' ORDER BY ds3.id DESC LIMIT 1), 'N/A') as organizacion,
    'anonimo' as session_type,
    MAX(ds.created_at) as last_activity,
    COUNT(ds.id) as signals_count
FROM demand_signals ds
WHERE ds.session_id NOT IN (SELECT session_id FROM leads)
  AND (:include_demo = 1 OR ds.session_id NOT LIKE 'demoseed%')
GROUP BY ds.session_id

ORDER BY last_activity DESC
LIMIT :limit OFFSET :offset;
```

### Deduplicación de Leads en Conversaciones (`leads_detected`)
Para evitar duplicidades en la vista de leads extraídos por el LLM en la conversación:
- Se parsea el CSV `leads_datanestiq.csv`.
- Se filtra fila por fila descartando aquellas cuya `SessionID` ya exista en la tabla `leads` del CRM.
- Se presentan los leads restantes sin aplicar redacción PII (solo visible tras `auth.php`).
```sql
-- Lógica lógica de deduplicación contra CRM
SELECT session_id FROM leads WHERE session_id IN (:session_ids_from_csv)
```

---

## 3. Criterio de Datos Demo (Seeding)

Para poblar e identificar inequívocamente los registros demo en desarrollo y testeo:
- **`session_id`:** Utilizará el prefijo `demoseed_` seguido de un hash aleatorio de 24 caracteres (ej: `demoseed_77f439abef02845c1920e8d1`).
- **`query_redacted`:** Tendrá el prefijo `[DEMO]` en el texto descriptivo (ej: `[DEMO] necesito pipelines para optimizar inventario en retail`).
- **Exclusión:** Las queries de Bucket 1 y Bucket 2 incluirán por defecto la cláusula `WHERE session_id NOT LIKE 'demoseed%'` a menos que el toggle de UI envíe un parámetro `include_demo=1`.
