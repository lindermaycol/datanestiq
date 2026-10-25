# Plan de Implementación — Spec 026: Analítica de Contenido/Blog en el Panel (Refinado tras Auditoría)

Este documento guía la fase de construcción de la Spec 026 con las correcciones aprobadas por la auditoría de diseño.

---

## 1. Arquitectura y Componentes Afectados

```text
[BlogSearch.jsx] ---> Worker emite 'complete' ---> trackEvent('search_query', 'blog-search', JSON.stringify({q, n, top}))
                                                            │
                                                            ▼
                                    [public/api/track_event.php] ---> (redactPii) ---> [SQLite: interaction_events]
                                                                                               │
                                                                                               ▼
                                                                                [public/admin/api.php?action=blog_analytics]
                                                                                               │
                                                                                               ▼
                                                                                [public/admin/index.php (view-behavior)]
```

---

## 2. Fases de Trabajo en Construcción (Build)

### Fase 1: Extensión y Correcto Timing de Telemetría (`src/components/islands/BlogSearch.jsx`)
- **Eliminar la emisión prematura** de `trackEvent` en `handleSearch`.
- Guardar la consulta activa y el reqId en refs (`activeQueryRef`, `activeReqIdRef`).
- Mover la emisión de `trackEvent` al **handler de respuesta del Web Worker** (`status === 'complete'` o `type === 'results'`) tras calcular `filtered.length`.
- Enviar el JSON enriquecido `{ q: activeQueryRef.current, n: filtered.length, top: filtered[0]?.score || 0 }`.

### Fase 2: Endpoint Agregador Backend (`public/admin/api.php`)
- Añadir `case 'blog_analytics'` en `public/admin/api.php`.
- Filtrar `event_type = 'search_query'` AND `event_target = 'blog-search'` usando desambiguación de parámetros PDO (`:inc_b1`, `:inc_b2`, `:inc_b3`).
- Iterar sobre los registros en PHP ejecutando `json_decode` seguro (fallback para eventos legacy a `q = event_value`, `n = null`).
- Normalizar `$q` (`mb_strtolower(trim(...))`) y agrupar top búsquedas y gaps de contenido (`n = 0`).
- Calcular `zero_match_rate` dividiendo estrictamente sobre `$known_n_count` (búsquedas con `n` no nulo).
- Aplicar guardarraíl §2 para muestra mínima (< 15 búsquedas).

### Fase 3: Interfaz de Administración (`public/admin/index.php`)
- Crear los contenedores HTML explícitos `#blog-analytics-summary`, `#blog-top-queries`, `#blog-content-gaps` y `#blog-search-trend` dentro de la vista `view-behavior`.
- Implementar la función de carga JavaScript `loadBlogAnalytics()` reusando el helper defensivo `setHTML(id, html)`.
- Diseñar tarjetas visuales, tablas de gaps con etiquetas destacadas en ámbar y gráfico de barra/progreso en CSS/SVG.

### Fase 4: Build-Gate Antidrift, Auto-Verificación y Despliegue
- Registrar `026` como `COMPLETED` en `planes/ESTADO-SPECS.md` y `src/data/specsStatus.json`.
- Añadir el marcador de auto-verificación `zero_match_rate` para `blog_analytics` en `scripts/deploy/deploy_ionos.py`.
- Ejecutar `node scripts/build-specs-status.mjs` (Build-Gate Antidrift 100%).
- Compilar con `npm run build`.
- Desplegar a IONOS con `python scripts/deploy/deploy_ionos.py --confirm` validando el **JS Syntax Gate (`node --check`)**.

---

## 3. Plan de Verificación y Pruebas en Vivo

### 3.1 Pruebas de Sintaxis y Linting
- Validar sintaxis PHP con `php -l public/admin/api.php`.
- Validar sintaxis de scripts de panel con `verify_inline_js_syntax()` (`node --check`).

### 3.2 Pruebas en Vivo de Endpoints y Telemetría
- Realizar en `/blog` una búsqueda con resultados y otra **sin resultados** (ej. `"consulta sin match 123"`).
- Verificar en el endpoint real `/admin/api.php?action=blog_analytics` que la consulta sin resultados aparezca con `n = 0` en `content_gaps`.
- Verificar que con muestra < 15 búsquedas responda `insufficient_data: true`.

### 3.3 QA de Click-Through en Panel
- Abrir `/admin/` en el navegador, navegar a la pestaña **Engagement & Comportamiento** (`view-behavior`), confirmar que la sub-sección de **Analítica de Blog** renderice los datos sin errores en la consola del navegador.
