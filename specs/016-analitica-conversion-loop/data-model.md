# Modelo de Datos — Spec 016: Analítica de Conversión y Loop `chat → lead → learn`

Este documento especifica las extensiones al esquema SQLite de `secure_leads/crm.sqlite`, las consultas agregadas para el embudo de conversión y las uniones relacionales entre sesiones de chat, leads, citas e historial de estados.

---

## 1. Extensiones al Esquema SQLite (`secure_leads/crm.sqlite`)

### Nueva Tabla: `chat_metrics`
Registra el rendimiento técnico y la ejecución de cada interacción de texto libre procesada por `public/api/chat.php`.

```sql
CREATE TABLE IF NOT EXISTS chat_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(100) NOT NULL,
    backend_used VARCHAR(50) NOT NULL,    -- 'groq', 'dashscope', 'gemini'
    latency_ms INTEGER NOT NULL,          -- Tiempo de respuesta en milisegundos
    success INTEGER DEFAULT 1,            -- 1 = exitoso, 0 = error / failover
    tokens_est INTEGER DEFAULT 0,         -- Estimado de tokens consumidos
    created_at DATETIME DEFAULT (datetime('now'))
);

-- Índices de rendimiento
CREATE INDEX IF NOT EXISTS idx_chat_metrics_session ON chat_metrics(session_id);
CREATE INDEX IF NOT EXISTS idx_chat_metrics_backend ON chat_metrics(backend_used);
CREATE INDEX IF NOT EXISTS idx_chat_metrics_created ON chat_metrics(created_at);
```

---

## 2. Mapa de Relaciones entre Entidades

```mermaid
erdiagram
    leads ||--o{ interactions : "registra interacciones de journey"
    leads ||--o{ status_history : "registra cambios de estado"
    leads ||--o{ appointments : "solicita citas"
    leads ||--o{ chat_metrics : "comparte session_id"
```

- **`leads.session_id` ↔ `chat_metrics.session_id` (1:N):** Vincula todas las llamadas al LLM realizadas durante una sesión con el registro final del lead.
- **`leads.id` ↔ `appointments.lead_id` (1:N):** Conecta el prospecto con sus citas agendadas (Spec 015).
- **`leads.id` ↔ `status_history.lead_id` (1:N):** Permite calcular el tiempo de permanencia entre transiciones de estado (`nuevo` → `contactado` → `cita` → `ganado`).
- **`leads.id` ↔ `interactions.lead_id` (1:N):** Almacena los chips de contexto (sector, rol, reto) seleccionados durante la navegación de la Spec 013.

---

## 3. Consultas Analíticas Agregadas (`public/admin/api.php`)

### Consulta 1: Embudo General de Conversión (Funnel Overview)
Calcula el volumen de leads en cada etapa del ciclo de vida.

```sql
SELECT 
    status, 
    COUNT(*) as total_leads,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM leads), 2) as percentage
FROM leads 
GROUP BY status
ORDER BY 
    CASE status
        WHEN 'nuevo' THEN 1
        WHEN 'contactado' THEN 2
        WHEN 'cita/diagnóstico solicitado' THEN 3
        WHEN 'ganado' THEN 4
        WHEN 'perdido' THEN 5
        WHEN 'no-interesado' THEN 6
        ELSE 7
    END;
```

### Consulta 2: Tasa de Conversión a Citas Agendadas
Mide la efectividad de conversión de lead registrado a cita solicitada/confirmada.

```sql
SELECT 
    COUNT(DISTINCT l.id) as total_leads,
    COUNT(DISTINCT a.lead_id) as leads_con_cita,
    ROUND(COUNT(DISTINCT a.lead_id) * 100.0 / COUNT(DISTINCT l.id), 2) as tasa_conversion_cita_pct
FROM leads l
LEFT JOIN appointments a ON l.id = a.lead_id AND a.status != 'cancelada';
```

### Consulta 3: Tiempo Promedio de Permanencia por Etapa (Horas)
Calcula la velocidad del pipeline midiendo la diferencia entre `created_at` del lead y la fecha de transición en `status_history`.

```sql
SELECT 
    sh.old_status,
    sh.new_status,
    COUNT(sh.id) as transiciones,
    ROUND(AVG((JULIANDAY(sh.created_at) - JULIANDAY(l.created_at)) * 24), 1) as horas_promedio
FROM status_history sh
JOIN leads l ON sh.lead_id = l.id
GROUP BY sh.old_status, sh.new_status;
```

### Consulta 4: Conversión Desglosada por Sector y Rol (Journey Insights)
Analiza qué combinación de industria y rol produce el mayor ratio de clientes ganados.

```sql
SELECT 
    l.organizacion,
    COUNT(l.id) as total,
    SUM(CASE WHEN l.status = 'ganado' THEN 1 ELSE 0 END) as ganados,
    ROUND(SUM(CASE WHEN l.status = 'ganado' THEN 1 ELSE 0 END) * 100.0 / COUNT(l.id), 2) as tasa_ganados_pct
FROM leads l
GROUP BY l.organizacion
HAVING total >= 1
ORDER BY ganados DESC;
```

### Consulta 5: Rendimiento de Proveedores LLM (`chat_metrics`)
Evalúa la latencia promedio y la tasa de éxito de los proveedores de failover.

```sql
SELECT 
    backend_used,
    COUNT(*) as total_peticiones,
    ROUND(AVG(latency_ms), 0) as latencia_promedio_ms,
    ROUND(SUM(success) * 100.0 / COUNT(*), 1) as tasa_exito_pct
FROM chat_metrics
GROUP BY backend_used;
```

---

## 4. Estructura de Datos para el Loop `learn` (Offline)

El script offline `scripts/learn_prompt_optimizer.mjs` procesará lotes de sesiones para generar sugerencias estructuradas en el PR draft:

```json
{
  "period": "2026-W31",
  "analyzed_conversations": 42,
  "successful_patterns": [
    "Resaltar estimaciones de ROI cuantitativas en el sector público incrementó conversión en +18%.",
    "Explicar la soberanía de datos en LLMs locales redujo objeciones de compliance."
  ],
  "proposed_system_prompt_adjustments": [
    "Enfatizar la disponibilidad de despliegue On-Premise cuando el sector sea Gobierno/Salud."
  ],
  "pull_request_branch": "bot/prompt-optimization-2026-w31"
}
```
