# Modelo de Datos — Spec 017: Panel de Observabilidad Interna (Ops & Specs)

Este documento especifica la fuente de verdad estructurada (SSOT) para el portafolio de specs y las consultas SQL de telemetría operativa basadas en la reutilización de `chat_metrics`.

---

## 1. Decisión de Diseño: SSOT del Estado de Specs (`src/data/specsStatus.json`)

Para evitar parsear markdown con expresiones regulares frágiles desde `ESTADO-SPECS.md`, se define un esquema JSON estructurado como **Única Fuente de Verdad (SSOT)** en `src/data/specsStatus.json`. Este archivo es validado en tiempo de compilación por `scripts/build-specs-status.mjs`.

### Esquema JSON (`src/data/specsStatus.json`):
```json
[
  {
    "id": "016",
    "name": "Analítica de Conversión y Loop chat -> lead -> learn",
    "status": "LIVE",
    "phase": "Producción Activa",
    "openTechDebt": "Muestra escasa en arranque requiere acumulado para loop learn (§2)",
    "lastAuditDate": "2026-08-02",
    "isHonest": true
  },
  {
    "id": "017",
    "name": "Panel de Observabilidad Interna (Ops)",
    "status": "DESIGNED",
    "phase": "Diseño SDD",
    "openTechDebt": "Acumulación de peticiones en frío para p50/p95",
    "lastAuditDate": "2026-08-02",
    "isHonest": true
  }
]
```

---

## 2. Reutilización de Tabla de Telemetría: `chat_metrics`

No se crean tablas adicionales en SQLite. Se reutiliza la tabla existente `chat_metrics` en `secure_leads/crm.sqlite` creada en la Spec 016:

```sql
-- Tabla preexistente en secure_leads/crm.sqlite
CREATE TABLE IF NOT EXISTS chat_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(100) NOT NULL,
    backend_used VARCHAR(50) NOT NULL,    -- 'groq', 'dashscope', 'gemini', 'none'
    latency_ms INTEGER NOT NULL,          -- Latencia en milisegundos
    success INTEGER DEFAULT 1,            -- 1 = éxito, 0 = failover / error
    tokens_est INTEGER DEFAULT 0,         -- Estimado de tokens consumidos
    created_at DATETIME DEFAULT (datetime('now'))
);
```

---

## 3. Consultas SQL de Telemetría Operativa (Ops)

### Consulta 1: Métricas Globales de SLA y Failover (Últimos 30 días)
```sql
SELECT 
    COUNT(id) as total_requests,
    SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_requests,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_requests,
    ROUND(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) as sla_success_rate_pct,
    ROUND(AVG(latency_ms), 0) as avg_latency_ms
FROM chat_metrics
WHERE created_at >= datetime('now', '-30 days');
```

### Consulta 2: Desglose por Proveedor LLM y Latencias p50 / p95 (Aproximación SQL)
```sql
SELECT 
    backend_used,
    COUNT(id) as total_calls,
    ROUND(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 1) as success_pct,
    ROUND(AVG(latency_ms), 0) as avg_latency_ms,
    (
        SELECT latency_ms FROM chat_metrics cm2 
        WHERE cm2.backend_used = cm.backend_used AND cm2.success = 1 
        ORDER BY latency_ms ASC LIMIT 1 OFFSET (COUNT(cm.id) * 50 / 100)
    ) as p50_latency_ms,
    (
        SELECT latency_ms FROM chat_metrics cm3 
        WHERE cm3.backend_used = cm.backend_used AND cm3.success = 1 
        ORDER BY latency_ms ASC LIMIT 1 OFFSET (COUNT(cm.id) * 95 / 100)
    ) as p95_latency_ms
FROM chat_metrics cm
GROUP BY backend_used
ORDER BY total_calls DESC;
```

### Consulta 3: Tendencia Diaria de Peticiones y Latencia (Últimos 7 días)
```sql
SELECT 
    date(created_at) as date_day,
    COUNT(id) as daily_calls,
    ROUND(AVG(latency_ms), 0) as avg_latency_ms,
    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as errors_count
FROM chat_metrics
WHERE created_at >= datetime('now', '-7 days')
GROUP BY date_day
ORDER BY date_day ASC;
```
