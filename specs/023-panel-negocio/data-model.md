# Data Model — Spec 023: Panel de Negocio (Refocus Business-First)

## 1. Agregaciones SQL & Endpoints (`public/admin/api.php`)

### 1.1 Endpoint `ai_efficiency` (Eficiencia 0-LLM y Ahorro Estimado)
- **Fuente de Datos**: `interaction_events` (`event_type = 'intent_routing'`) y `chat_metrics`.
- **Query de Agregación con Manejo de Nulos**:
```sql
SELECT 
    COALESCE(NULLIF(json_extract(event_value, '$.route'), ''), 'llm') as route,
    COUNT(*) as total_requests
FROM interaction_events
WHERE event_type = 'intent_routing'
  AND (:inc = 1 OR session_id NOT LIKE 'demoseed%')
GROUP BY route;
```
- **Fórmula de Ahorro Estimado `$ [EST]` (Honestidad Radical §2)**:
```php
// Obtener el promedio de tokens reales por llamada exitosa a LLM desde chat_metrics
$avg_tokens_per_llm_call = $db->query("SELECT AVG(total_tokens) FROM chat_metrics WHERE success = 1 AND total_tokens > 0")->fetchColumn() ?: 1200;

// Tarifa de mercado de referencia declarada (ej. $0.30 USD por millón de tokens combinados)
$ref_price_per_1m_tokens = 0.30; 

$zero_llm_saved_calls = $total_faq + $total_cita + $total_guiado;
$estimated_savings_usd = round(($zero_llm_saved_calls * $avg_tokens_per_llm_call / 1000000) * $ref_price_per_1m_tokens, 4);
```
- **Estructura de Respuesta**:
```json
{
  "insufficient_data": false,
  "total_queries": 150,
  "zero_llm_count": 95,
  "llm_count": 55,
  "zero_llm_percentage": 63.33,
  "routes_breakdown": {
    "faq": 45,
    "cita": 25,
    "guiado": 25,
    "llm": 55
  },
  "estimated_savings_usd": 0.0342,
  "badge": "[EST]",
  "note": "Ahorro estimado basado en promedio de tokens reales y tarifa de referencia ($0.30/1M). Proveedores actuales operan en free-tier."
}
```

---

### 1.2 Endpoint `agenda` (Agenda Global de Citas)
- **Fuente de Datos**: `appointments` JOIN `leads` sobre el **esquema real de la Spec 015 (`init_crm_db.php`)**.
- **Query de Agregación**:
```sql
SELECT 
    a.id as appointment_id,
    a.lead_id,
    a.session_id,
    l.nombre,
    l.email,
    l.telefono,
    l.organizacion,
    l.sector,
    l.rol,
    a.type as service_type,
    a.requested_date,
    a.duration_minutes,
    a.status,
    a.created_at
FROM appointments a
JOIN leads l ON a.lead_id = l.id
WHERE a.status IN ('solicitada', 'confirmada')
ORDER BY a.requested_date ASC;
```

> **Estados Reales del Enum**: `'solicitada'` (pendiente de atención), `'confirmada'` (atendida/agendada), `'cancelada'`. Las acciones del panel invocan `update_appointment` pasando estos estados canónicos.

---

### 1.3 Lógica de Captura y Backfill de Sector / Rol (`leads`)

#### Captura en Caliente (`public/api/save_wizard.php` / Chatbot)
Cuando un lead envía un formulario o solicita una cita, el backend rescata `sector` y `role` de la última `demand_signal` vinculada a su `session_id`:
```sql
UPDATE leads 
SET sector = COALESCE(NULLIF(sector, ''), (SELECT sector FROM demand_signals WHERE session_id = :sid AND sector IS NOT NULL AND sector != '' ORDER BY id DESC LIMIT 1)),
    rol    = COALESCE(NULLIF(rol, ''),    (SELECT role FROM demand_signals WHERE session_id = :sid AND role IS NOT NULL AND role != '' ORDER BY id DESC LIMIT 1))
WHERE id = :lead_id;
```

#### Script CLI de Backfill (`scripts/backfill_lead_personas.php`)
Itera sobre todos los registros en `leads` donde `sector` o `rol` estén vacíos o sean `'no_especificado'`, e inyecta la información recuperada de `demand_signals` para la misma sesión.

---

## 2. Mapa de Retiro de Componentes Dev del Panel
| Vista / Componente Removido | Ubicación Anterior en `/admin/index.php` | Destino Definitivo |
|---|---|---|
| Tabla de 21 Especificaciones + Deuda | Pestaña "Observabilidad Ops" | **Retirada del Panel**. SSOT se mantiene en `src/data/specsStatus.json` y scripts CLI (`build-specs-status.mjs`). |
| "Páginas Estáticas Compiladas / Build" | Pestaña "Observabilidad Ops" | **Retirada del Panel**. Monitoreado vía pipeline CI/CD / CLI `npm run build`. |
| Estado de Linter ESLint | Pestaña "Observabilidad Ops" | **Retirada del Panel**. Monitoreado vía CLI `npm run lint`. |
| Reglas `.htaccess` | Pestaña "Observabilidad Ops" | **Retirada del Panel**. Monitoreado vía `check_public_permissions.py`. |

---

## 3. Componentes de Visualización Ligeros (CSS / SVG Nivel UI)

### 3.1 Componente Embudo de Conversión (Horizontal Funnel)
Renderizado mediante barras flexibles CSS con degradado azul-cyan:
```html
<div class="funnel-stage">
    <div class="stage-label">Nuevos Leads (100%)</div>
    <div class="stage-bar" style="width: 100%; background: #22d3ee;">50</div>
</div>
<div class="funnel-stage">
    <div class="stage-label">Contactados (60%)</div>
    <div class="stage-bar" style="width: 60%; background: #0284c7;">30</div>
</div>
```

### 3.3 Guardarraíles §2 de Muestra Mínima por Gráfico
Cada gráfico/visualización en la interfaz evalúa de forma independiente el umbral mínimo de muestra antes de renderizarse:
- **Embudo de Conversión**: Exige al menos 20 leads totales en la base de datos CRM. Si $N < 20$, se ocultan las barras y se muestra el aviso *"Datos insuficientes para embudo de conversión ($N = X/20$)"*.
- **Dona 0-LLM vs LLM**: Exige al menos 20 eventos `intent_routing`. Si $N < 20$, despliega *"Datos insuficientes para distribución 0-LLM ($N = X/20$)"*.
- **Tendencias Diarias (Peticiones / Latencia / Costo)**: Exige al menos 3 días distintos registrados en `usage_daily` / `chat_metrics`. Si es menor, oculta las barras de tendencia.
- **Barras de Demanda & Fugas (Buckets 1 y 2)**: Aplican los umbrales de $N \ge 20$ señales y $N \ge 10$ interesados de la Spec 020/021. Si no se cumplen (con el toggle Demo desactivado), muestran el contenedor de datos insuficientes en lugar de gráficos engañosos.

