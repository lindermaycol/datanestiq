# Modelo de Datos & Contratos API — Spec 026: Analítica de Contenido/Blog en el Panel (Refinado tras Auditoría)

Este documento especifica la extensión del formato de telemetría de la Spec 024, el esquema SQL de consulta sobre `interaction_events` y la estructura de respuesta del endpoint de analítica.

---

## 1. Extensión del Payload de Telemetría (Resolución del Gap §3 & Corrección 1)

### 1.1 Emisión en el Handler de Respuesta del Worker (`BlogSearch.jsx`)
En `src/components/islands/BlogSearch.jsx`, la telemetría **NO se emite en `handleSearch`** (para evitar enviar contadores `stale` o desactualizados antes de ejecutar la búsqueda). Se emite dentro del handler de respuesta del Web Worker (`status === 'complete'` o `type === 'results'`) tras calcular los resultados filtrados (`filtered`):

```javascript
// En BlogSearch.jsx: Guardar la query activa y reqId en refs
activeQueryRef.current = queryVal;
activeReqIdRef.current = reqId;

// En el handler de mensajes del Worker (cuando responseId === activeReqIdRef.current):
const telemetryPayload = JSON.stringify({
  q: activeQueryRef.current,        // Consulta realizada
  n: filtered.length,               // Cantidad exacta de resultados devueltos (0 para gaps)
  top: filtered[0]?.score || 0      // Similitud coseno del mejor resultado (0.0 a 1.0)
});

// Emisión limpia con el contador de resultados real (n: 0 cuando no hay match)
trackEvent('search_query', 'blog-search', telemetryPayload);
```

### 1.2 Retrocompatibilidad Transparente & Parseo Seguro en PHP (Corrección 2)
Para evitar el error de SQLite *"malformed JSON"* al ejecutar `json_extract()` sobre eventos legacy almacenados como texto plano:
- El backend en PHP (`public/admin/api.php`) consulta las filas crudas `event_value` y las parsea de forma segura mediante `json_decode($row['event_value'], true)`.
- Si `json_decode` es exitoso y contiene la propiedad `'q'`:
  - `$q = mb_strtolower(trim($decoded['q']))`
  - `$n = isset($decoded['n']) ? (int)$decoded['n'] : null`
  - `$top = isset($decoded['top']) ? (float)$decoded['top'] : null`
- Si NO es JSON (evento legacy de texto plano):
  - `$q = mb_strtolower(trim($row['event_value']))`
  - `$n = null`
  - `$top = null`

---

## 2. Agregación en PHP y Denominador Transparente §2 (Correcciones 3 y 4)

### 2.1 Agregación por Query Normalizada (`$q`)
Las métricas se agrupan en PHP por la query normalizada `$q` (`mb_strtolower(trim(...))`), consolidando variantes idénticas y calculando el volumen total de búsquedas y sesiones únicas.

### 2.2 Denominador Honesto para `zero_match_rate`
- `$total_searches`: Conteo total de búsquedas registradas (incluye eventos legacy).
- `$known_n_count`: Conteo total de búsquedas que incluyen contador `$n` conocido (`$n !== null`).
- `$zero_match_count`: Conteo de búsquedas con `$n === 0`.
- `$zero_match_rate`: `round(($zero_match_count / max(1, $known_n_count)) * 100, 2)`.

---

## 3. Contrato del Endpoint API (`public/admin/api.php?action=blog_analytics`)

### 3.1 Solicitud HTTP
- **Método**: `GET`
- **URL**: `/admin/api.php?action=blog_analytics&days=30&include_demo=0`
- **Parámetros**:
  - `days` (opcional, entero: 7, 30, 180; defecto: 30).
  - `include_demo` (opcional, entero: 0 o 1; defecto: 0).

### 3.2 Respuesta para Muestra Insuficiente (< 15 búsquedas)
```json
{
  "insufficient_data": true,
  "total_queries": 8,
  "min_required": 15,
  "message": "Se requieren al menos 15 búsquedas registradas en el blog para generar tendencias de contenido."
}
```

### 3.3 Respuesta Exitosa Refinada
```json
{
  "insufficient_data": false,
  "summary": {
    "total_searches": 142,
    "known_n_count": 110,
    "unique_sessions": 87,
    "zero_match_count": 22,
    "zero_match_rate": 20.0,
    "period_days": 30
  },
  "top_searches": [
    { "query": "agentes de ia en finanzas", "count": 24, "unique_sessions": 18, "avg_results": 4.2 },
    { "query": "migracion astro wordpress", "count": 19, "unique_sessions": 15, "avg_results": 3.0 }
  ],
  "content_gaps": [
    { "query": "cuanto cuesta implementar graphrag", "count": 12, "last_searched": "2026-08-08 14:22:10" },
    { "query": "integracion con sap ciiu", "count": 9, "last_searched": "2026-08-07 09:15:33" }
  ],
  "daily_trend": [
    { "date": "2026-08-08", "total": 15, "zero_matches": 3 },
    { "date": "2026-08-07", "total": 12, "zero_matches": 2 }
  ]
}
```
