# Prompt AntiGravity — Fix: widget "Top Búsquedas Semánticas" muestra JSON crudo

## Contexto
La Spec 026 (analítica de blog) cambió el `event_value` de los eventos `search_query` con
`event_target='blog-search'` de un string plano a un **payload JSON** `{"q":"...","n":0,"top":0}`.
El chatbot GraphRAG también emite `search_query` con payloads JSON.

Efecto colateral (regresión, **verificado en vivo** el 2026-08-10 en el panel `/admin/` →
Engagement & Comportamiento): el widget **genérico** "Top Búsquedas Semánticas"
(endpoint `ops_behavior_analytics`) ahora muestra el **JSON crudo** como término de búsqueda,
p. ej.:

```
"{"q":"gobernanza y calidad de datos...","n":0,"top":0}"
```

Además, al agrupar por `event_value` completo, **fragmenta los conteos**: la misma query con
distinto `n`/`top` no agrupa.

## Causa raíz
En `public/admin/api.php`, dentro del `case 'ops_behavior_analytics'`, la consulta
"Top búsquedas semánticas" (aprox. líneas 510–517) agrega en SQL sin parsear el JSON:

```php
$queries_stmt = $db->query("SELECT event_value, COUNT(*) as qty
    FROM interaction_events
    WHERE event_type = 'search_query'
    GROUP BY event_value
    ORDER BY qty DESC
    LIMIT 8");
$queries = $queries_stmt->fetchAll(PDO::FETCH_ASSOC);
```

`GROUP BY event_value` toma el JSON literal como clave.

## Fix pedido
Mover la agregación a PHP y **reutilizar el mismo patrón de parseo seguro** que ya existe en
`blog_analytics` (mismo archivo, líneas ~1132–1143 — "Corrección 2: Detección de JSON vs String
Legacy"). No inventes un parser nuevo; copia ese patrón para mantener consistencia:

1. Cambiar la consulta a traer filas crudas (sin `GROUP BY`):
   `SELECT event_value FROM interaction_events WHERE event_type = 'search_query'`
   (sin límite en SQL; el `LIMIT 8` se aplica **después** de agrupar en PHP).
2. Por cada fila, extraer el término real:
   - Si `str_starts_with(trim($val), '{')` → `json_decode`; si es array con `q`, usar `$decoded['q']`.
   - Si no, usar el string tal cual (compatibilidad con eventos legacy previos a la 026).
   - `if (empty($query_text)) continue;`
3. Acumular conteos en un mapa por query **normalizada** (`mb_strtolower(..., 'UTF-8')`),
   conservando una versión legible para mostrar.
4. Ordenar desc por conteo y quedarte con el top 8.
5. **Preservar la forma de salida** que ya consume el frontend: cada item debe seguir teniendo
   las claves `event_value` (texto limpio de la query) y `qty` (entero). Así **no hay que tocar
   el render** en `index.php`. Verifica en `index.php` cómo se pintan estos items y confirma que
   las claves coinciden; si el render usa otras claves, ajústate a ellas sin romper nada.

No filtres por `event_target`: el objetivo es que el widget muestre **todas** las búsquedas
semánticas (chatbot, blog, graphrag) con su texto limpio y agrupadas correctamente. Blog tiene su
sub-sección dedicada, pero este widget genérico debe seguir siendo el agregado global — solo que
legible.

## Alcance (NO tocar)
- No modificar el endpoint `blog_analytics` (ya está correcto y LIVE).
- No cambiar el esquema ni cómo se **escriben** los eventos (`chat.php`, islas). Solo lectura/render.
- No tocar otras ramas del `switch` de `ops_behavior_analytics`.

## Constitución §2
- Cero datos fabricados: si `q` viene vacío, se descarta la fila (no placeholders).
- Mantener los guards de "datos insuficientes" existentes del widget si los hay.

## Build & Deploy (obligatorio)
Backend PHP se sirve desde `dist/`. Antes de desplegar:
1. `npm run build` (copia `public/` → `dist/`).
2. `python scripts/deploy/deploy_ionos.py --confirm`.
Sin recompilar, se sube la versión vieja.

## Criterios de aceptación (verificación EN VIVO, no local)
1. `curl`/fetch al endpoint real en prod (`app.datanestiq.com`, con cache-buster) del
   `ops_behavior_analytics` → 200 y en "top búsquedas" **ningún** valor empieza por `{` (sin JSON crudo).
2. En el panel `/admin/` → Engagement & Comportamiento, el widget "Top Búsquedas Semánticas"
   muestra términos legibles (p. ej. `gobernanza y calidad de datos`), sin llaves `{...}`.
3. Búsquedas idénticas quedan **agrupadas** (una sola fila con su conteo sumado), no fragmentadas.
4. Consola del navegador sin `ReferenceError`/errores.
5. Reporta el JSON real devuelto (marcador: los valores de `event_value` ya sin `{`), no solo
   "hecho".
