# Modelo de Datos — Spec 017: Panel de Observabilidad Interna (Ops & Specs)

Este documento especifica la fuente de verdad estructurada (SSOT) para el portafolio de specs y las consultas SQL de telemetría operativa basadas en la reutilización de `chat_metrics`.

---

## 1. Decisión de Diseño: SSOT del Estado de Specs y Build Gate Antidrift (`src/data/specsStatus.json`)

Para evitar la desincronización y el *drift* entre la narrativa rica de `planes/ESTADO-SPECS.md` y la vista estructurada del panel, se establece la siguiente regla de gobierno:

1. **`src/data/specsStatus.json` es la Única Fuente de Verdad (SSOT)** de los campos estructurados consumidos por la API y la UI (`id`, `name`, `status`, `phase`, `openTechDebt`, `lastAuditDate`, `isHonest`).
2. **Build-Gate de Consistencia:** En cada compilación (`npm run build`), el script `scripts/build-specs-status.mjs` lee `src/data/specsStatus.json` y valida que el `status` de cada especificación coincida exactamente con la declaración de la tabla en `planes/ESTADO-SPECS.md`.
3. **Fallo en Build ante Drift:** Si se detecta cualquier discrepancia entre `specsStatus.json` y `ESTADO-SPECS.md`, **el build falla inmediatamente** (`process.exit(1)`), impidiendo que cambios silenciosos o contradictorios lleguen a producción.

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

### Consulta 2: Desglose por Proveedor LLM y Percentiles Exactos p50 / p95 (vía CTE & Window Functions)
Para corregir el desajuste de poblaciones entre peticiones exitosas y fallidas, la consulta filtra estrictamente `success = 1` utilizando funciones de ventana (`ROW_NUMBER()`) sobre la misma población de éxitos:

```sql
WITH successful_metrics AS (
    SELECT 
        backend_used,
        latency_ms,
        ROW_NUMBER() OVER (PARTITION BY backend_used ORDER BY latency_ms ASC) as row_num,
        COUNT(*) OVER (PARTITION BY backend_used) as total_success
    FROM chat_metrics
    WHERE success = 1 AND created_at >= datetime('now', '-30 days')
)
SELECT 
    cm.backend_used,
    COUNT(cm.id) as total_calls,
    ROUND(SUM(CASE WHEN cm.success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(cm.id), 1) as success_pct,
    ROUND(AVG(cm.latency_ms), 0) as avg_latency_ms,
    CASE 
        WHEN COALESCE(sm.total_success, 0) < 10 THEN NULL 
        ELSE MAX(CASE WHEN sm.row_num = MAX(1, CAST(sm.total_success * 0.50 AS INT)) THEN sm.latency_ms END)
    END as p50_latency_ms,
    CASE 
        WHEN COALESCE(sm.total_success, 0) < 10 THEN NULL 
        ELSE MAX(CASE WHEN sm.row_num = MAX(1, CAST(sm.total_success * 0.95 AS INT)) THEN sm.latency_ms END)
    END as p95_latency_ms
FROM chat_metrics cm
LEFT JOIN successful_metrics sm ON cm.backend_used = sm.backend_used
WHERE cm.created_at >= datetime('now', '-30 days')
GROUP BY cm.backend_used
ORDER BY total_calls DESC;
```

> [!NOTE]
> **Guardarraíl de Honestidad (§2):** Si un proveedor cuenta con menos de 10 peticiones exitosas (`total_success < 10`), los percentiles `p50` y `p95` se evalúan como `NULL` y la API/UI responderá "Datos insuficientes (< 10 peticiones)", evitando presentar percentiles engañosos o inestables.

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
