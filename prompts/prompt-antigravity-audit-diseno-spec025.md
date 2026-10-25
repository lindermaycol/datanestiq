# Auditoría DISEÑO Spec 025 (GraphRAG sobre Taxonomía) — APROBADO para BUILD con 2 correcciones (bloqueantes) + 2 precisiones

Diseño **sólido y bien alineado**: explota el grafo real de la taxonomía (nodos/aristas con integridad referencial ya
validada), **0-LLM en el edge** para anclaje+traversal reusando `worker.js` con el **contrato real** (`corpusTexts` + `index`
numérico + `id` único `graph-anchor-<seq>-<ts>` — lección de 024 aplicada), **síntesis free-tier solo** sobre el subgrafo,
**§2** (datos insuficientes, `[EST]`, citas), build-gate antidrift reusando `build-taxonomy.mjs`, OUT-OF-SCOPE correcto, y
`k=2`/umbral `0.35` bien justificados. Apruebo el build con **2 correcciones reales** y **2 precisiones**.

## 🔴 Corrección 1 (BLOQUEANTE) — la telemetría NO usa el contrato real de `track_event.php` (mismo error de clase que el worker de 024)
`data-model.md §4` propone `{ "event_type":"graphrag_query", "category":"engagement", "data":{ anchors, subgraph_nodes_count, cited_urls, hops, ... } }`.
Pero `public/api/track_event.php` **lee un payload plano** y guarda en `interaction_events`:
```php
$session_id  = $input['session_id'];   // REQUERIDO, regex /^[a-zA-Z0-9]{32,}$/
$event_type  = $input['event_type'];   // REQUERIDO
$event_target= $input['event_target']; // REQUERIDO (vacío -> 400 Bad Request)
$event_value = $input['event_value'];  // string (aquí va el detalle)
INSERT INTO interaction_events (session_id, event_type, event_target, event_value)
```
Es decir: **`category` y `data` se IGNORAN** → toda la metadata rica (anchors, counts, cited_urls, hops) **se pierde**.
- **Fix:** usar el helper `trackEvent(...)` como el resto (p. ej. `BlogSearch`: `trackEvent('search_query','blog-search', val)`),
  empaquetando el detalle en **`event_value`** como **JSON string** (igual que hace `demand_signal`), con un **`event_target`**
  corto (p. ej. `'graphrag'`) y `session_id` válido (32+ alfanum). Nada de `category`/`data` anidado.

## 🔴 Corrección 2 (BLOQUEANTE, §6 PII) — `event_type:'graphrag_query'` **evade** la redacción de PII
`track_event.php` solo redacta PII cuando `event_type ∈ {'search_query','chatbot_step'}`:
```php
if (in_array($event_type, ['search_query','chatbot_step'])) { $event_value = redactPii($event_value); }
```
Si la consulta del usuario (que puede traer email/teléfono) viaja con `event_type='graphrag_query'`, **se guarda SIN redactar**
→ fuga de PII a SQLite (viola §6). **Elige una** y documenta:
- (a) Reusar `event_type:'search_query'` con `event_target:'graphrag'` (hereda la redacción), **o**
- (b) Añadir `'graphrag_query'` al allowlist de `redactPii` en `track_event.php`, **o**
- (c) Redactar la query en el cliente antes de enviarla.
La opción (a) es la de menor riesgo y menos código.

## 🟡 Corrección 3 (precisión) — las URLs de nodo deben DERIVARSE de los slugs reales, no inventarse
Los ejemplos del diseño **no existen**: `/soluciones/ingenieria-de-datos` y `/sectores/banca-finanzas` son inventados.
Slugs reales:
- **Pilares:** `ai-data-science, business-intelligence, data-engineering, estrategia-datos-ia, hiperautomatizacion, sistemas-digitales`
- **Sectores:** `educacion, finanzas, logistica, manufactura, mineria, publico, retail, salud, seguros, telecomunicaciones`
Además las **rutas difieren**: `src/pages/soluciones/[id].astro` vs `src/pages/sectores/[slug].astro`. En el build, **deriva
`node.url` del campo real que usa `getStaticPaths` de cada ruta** (no hardcodees; usa el mismo criterio que
`build-taxonomy.mjs` que ya emite `/soluciones/${p.slug}`). Verifica que cada `url` generada resuelva a una página 200.

## 🟡 Corrección 4 (precisión del data-model vs esquema real `src/lib/schemas.js`)
- **Respuesta del worker:** el campo real es **`status`**, no `type`. `worker.js` emite `{ status:'complete', id, results:[{index,score}] }`.
  Alinea la isla para leer `status === 'complete'` (como `BlogSearch`), no `type:'results'|'complete'`.
- **`roiCases`:** el esquema real es `roiCases: [{ title, description?, context?, metrics: [{ value, label }] }]` — **`metrics` es
  un array de `{value,label}`**, no `metrics: string`. Corrige el tipo en `data-model.md`. Y no confundas `sector.kpis
  [{metric, expectedRoi}]` (de donde sale el `[EST]` de ROI) con `roiCases` en `groundedFacts`.
- **IDs compuestos y resolución de aristas:** `relatedPillars`/`pillarsOfInterest` referencian **slugs de pilar**, y las claves
  de `roleEquivalents` son **ids de rol** (`cfo`…). El build debe **resolver** esos refs a los ids compuestos de nodo
  (`pillar-<slug>`, `persona-<roleId>`, `sector-<slug>`) y el **build-gate debe fallar (`exit 1`)** si alguno no resuelve
  (evita aristas que se caen en silencio). Declara la regla de namespacing en `data-model.md`.

## OK tal como está (no cambiar)
- 0-LLM estricto en anclaje+traversal; free-tier **solo** para redactar sobre el subgrafo ✅.
- Reuso de `build-taxonomy.mjs` + integridad referencial como gate ✅; `k=2` y umbral `0.35` justificados ✅.
- §2: "datos insuficientes", `[EST]`, citas y trazabilidad ✅; OUT-OF-SCOPE (sin grafo DB externa ni LLM de pago) ✅.
- Degradación cliente con plantilla pre-formateada si el free-tier cae (TD-025.3) ✅ — **manténla §2-safe** (solo hechos del
  subgrafo + `[EST]`, sin redactar de más).
- **Conteos:** el `data-model`/`plan` estiman ~45 nodos/~120 aristas; los reales rondan **6 pilares · 10 sectores · 14
  industrias · 7 roles · 4 orgTypes**. Marca los conteos como `[EST]` hasta que el build imprima los **reales**.

## Siguiente paso — PROCEDE AL BUILD
1. Aplica **Corrección 1 y 2** (bloqueantes: contrato de telemetría plano + PII §6) y **3 y 4** (precisiones URL/esquema).
2. Build: grafo en `prebuild` (reusa `build-taxonomy.mjs`, gate de integridad), `graphTraversal.ts` (BFS k=2, subgrafo),
   integración en la ruta compleja del router/`chat.php` (grounded + citas + `[EST]` + "datos insuficientes"), telemetría 018
   con el contrato correcto. **`npm run build` antes de `deploy_ionos.py --confirm`** (ahora con el **JS syntax gate** activo).
3. **Reaudito EN VIVO** (skill `verificar-en-vivo`): pregunta multi-salto de CFO/banca → respuesta **grounded** con citas
   `/soluciones/*` `/sectores/*` **reales** (200), **0-LLM** en anclaje/traversal (sin `chat.php` en las rutas
   determinísticas), consulta sin relación → "datos insuficientes", evento `graphrag`/`search_query` **PII-redactado** en
   "Engagement", consola sin `SyntaxError`/`ReferenceError`.

---
**Nota:** Claude (Opus 4.8) auditará en vivo. Lo estructural está bien; los bloqueantes son de **contrato real** (telemetría
plana de `track_event.php` + redacción PII §6), no de arquitectura — exactamente el tipo de "shape inventado" que ya nos
costó en 024. Deriva URLs de slugs reales y resuelve los ids de aristas en el build-gate.
