# DISEÑO — Spec 026: Analítica de Contenido/Blog en el Panel (cerrar el loop de la 024)

Diseña la Spec 026 (SDD completo: `spec.md`, `data-model.md`, `plan.md`, `tech_debt.md` en
`specs/026-analitica-blog-panel/`). **NO implementes todavía** — primero el diseño; Claude lo audita antes del build.
Al terminar, registra la 026 en `planes/ESTADO-SPECS.md` ↔ `src/data/specsStatus.json` como `DESIGNED` (build-gate antidrift).

## 1. Objetivo y valor
El buscador semántico del blog (Spec 024) ya **captura** telemetría, pero nadie la **visualiza**. Esta spec agrega al panel
`/admin/` una vista de **analítica de contenido/blog** que responde: ¿qué buscan los visitantes en `/blog`?, ¿qué consultas
**no encuentran match** (gaps de contenido)?, ¿qué términos son top? Convierte datos ya registrados en **decisiones
editoriales/SEO basadas en demanda real**. Es un quick-win: reusa telemetría existente y el patrón de las pestañas del panel.

## 2. Fuente de datos REAL (usar el esquema existente, no inventar)
- Tabla **`interaction_events`** (Spec 018), esquema real: `id, session_id, event_type, event_target, event_value (TEXT),
  created_at`. Retención **180 días** (limpieza idempotente ya existe en `ops_behavior_analytics`).
- El buscador del blog emite: `trackEvent('search_query', 'blog-search', <query>)` → fila con `event_type='search_query'`,
  `event_target='blog-search'`, `event_value=<query PII-redactada>` (track_event.php redacta `search_query`).
- ⚠️ **OJO — colisión de `event_type`:** el chatbot GraphRAG (025) también emite `search_query` pero con `event_target='graphrag'`.
  **Filtra SIEMPRE `event_target='blog-search'`** para aislar el blog. No mezcles ambos.
- Ya existe `case 'ops_behavior_analytics'` que agrega `search_query` de forma genérica (líneas ~485-560 de
  `public/admin/api.php`). **Reusa ese patrón** (min-sample, ventana 180d, `GROUP BY`), no lo dupliques.

## 3. 🔴 Gap de diseño a resolver — hoy NO se captura si la búsqueda tuvo 0 resultados
El escenario estrella ("consultas sin match = gaps de contenido") **no es posible hoy**: `BlogSearch.jsx` emite el mismo
evento tenga o no resultados, y `event_value` es solo la query (sin `results_count`). `interaction_events` no tiene columna
de metadata. **Decide y documenta en el diseño** una de estas (sin romper la 024):
- **(a) Recomendada:** extender `BlogSearch.jsx` para emitir `event_value` como **JSON** `{"q": <query>, "n": <results_count>,
  "top": <top_score>}` (redactPii corre sobre el string completo → sigue §6-safe). El endpoint parsea el JSON. Retro-compatible:
  filas viejas (string plano) se tratan como `n` desconocido.
- **(b) Alternativa:** emitir un `event_target` distinto cuando `results_count===0` (p. ej. `'blog-search-empty'`), dejando
  `blog-search` para las que sí tuvieron match. Menos rico (no guarda top_score) pero mínimo cambio.
Elige (a) o (b), justifícalo, y **mantén la telemetría de la 024 intacta** (0-LLM, PII-redactada).

## 4. Endpoint agregador (nuevo `case` en `public/admin/api.php`)
Un endpoint de solo-lectura (p. ej. `action=blog_analytics`, GET) que, filtrando `event_target='blog-search'` y ventana
180d, devuelva:
- **Top búsquedas** (`GROUP BY event_value`/`q`, con conteo y sesiones únicas).
- **Consultas sin resultados** (según decisión §3): lista + frecuencia → **gaps de contenido**.
- **Volumen y tendencia** (por día, últimos 7/30d) y **tasa de "sin match"** (%).
- **Guard §2 de muestra mínima:** si hay pocas búsquedas (define umbral), responde `insufficient_data` honesto (como
  `ai_efficiency`/demanda), no cifras fabricadas. Marca `[EST]` donde aplique.
- **§6:** solo agregados de texto ya redactado; nada de PII; sin endpoint que exponga filas crudas sensibles.

## 5. UI en el panel (`public/admin/index.php`)
- Ubícala en la pestaña **"Engagement & Comportamiento"** (`view-behavior`) como sub-sección, **o** una pestaña nueva
  hermana — respeta la regla: los `view-*` deben ser **hermanos** y `switchTab` debe manejar la rama nueva (lección del
  incidente `view-ops`).
- **Usa el helper `setHTML(id, html)` (null-safe)** para inyectar cada contenedor; **todo `getElementById(...).innerHTML`
  debe tener su contenedor en el HTML** (no repetir el crash de contenedores faltantes de `view-ops`). Sin variables no
  declaradas (lección `specsData`).
- Tarjetas: Top búsquedas, **Gaps (sin resultados)**, tendencia, tasa sin-match. Gráficos ligeros CSS/SVG (como 023), sin
  librerías pesadas. Estado vacío honesto §2 cuando no hay muestra.

## 6. OUT OF SCOPE
- Sin LLM (agregación SQL pura, 0-LLM). Sin nueva infraestructura ni dependencias.
- Sin tracking cross-site ni PII nueva; solo lo ya capturado por 018/024.
- No tocar el comportamiento del buscador salvo el mínimo de §3 (results_count).

## 7. Entregables SDD (`specs/026-analitica-blog-panel/`)
- `spec.md` (problema, usuario=marketing/editorial, escenarios: top queries + detección de gaps, criterios §2).
- `data-model.md` (consulta a `interaction_events` con filtro `blog-search`; formato del `event_value` elegido en §3;
  esquema de respuesta del endpoint `blog_analytics`; guard de muestra mínima).
- `plan.md` (extensión mínima de telemetría §3; endpoint agregador; vista en panel reusando patrón; build/deploy).
- `tech_debt.md` (honesto: retro-compatibilidad de filas viejas sin `n`, tamaño de muestra, retención 180d).

## 8. Reglas de deploy/QA (recordatorio de campo)
- Backend PHP se sirve desde `dist/` → **`npm run build` ANTES de `deploy_ionos.py --confirm`**.
- El deploy corre **`verify_inline_js_syntax()` (node --check)** + auto-verify de endpoints; pero **"endpoints ✅ ≠ panel ✅"**:
  exige **click-through en vivo** — la nueva pestaña/sub-sección **carga datos** (no solo el encabezado), consola sin
  `SyntaxError`/`ReferenceError`, y el endpoint real `blog_analytics` responde 200 con un **marcador** del código nuevo.
- Considera añadir `blog_analytics` a la tabla de auto-verificación del `deploy_ionos.py` (marcador propio).

## 9. Decisiones abiertas para que resuelvas en el diseño (justifícalas)
- Opción (a) vs (b) de §3 (recomiendo (a) por captar `n` y `top_score`).
- Umbral de muestra mínima para el guard §2 (calibrar).
- Sub-sección en `view-behavior` vs pestaña nueva (peso del panel vs claridad).
- Normalización de queries para agrupar (minúsculas/trim/acentos) sin perder señal.

---
**Nota:** Claude (Opus 4.8) auditará el diseño y luego en vivo. Reusa `interaction_events` y el patrón de
`ops_behavior_analytics`; **filtra `event_target='blog-search'`** (no mezcles con `graphrag`); resuelve el gap de
`results_count` sin romper la 024; y respeta las lecciones del panel (contenedores presentes + `setHTML` null-safe + `view-*`
hermanas). §2/§6: agregados, PII redactada, muestra mínima honesta.
