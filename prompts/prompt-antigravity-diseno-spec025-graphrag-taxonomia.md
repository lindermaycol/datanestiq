# DISEÑO — Spec 025: GraphRAG sobre la Taxonomía (respuestas multi-salto fundamentadas para el comité de compra)

Diseña la Spec 025 (SDD completo: `spec.md`, `data-model.md`, `plan.md`, `tech_debt.md` en
`specs/025-graphrag-taxonomia/`). **NO implementes todavía** — primero el diseño; Claude lo audita antes del build.
Al terminar, actualiza `planes/ESTADO-SPECS.md` ↔ `src/data/specsStatus.json` (build-gate antidrift) con la 025 en estado
`DESIGNED`.

## 1. Objetivo y valor
La taxonomía **ya es un grafo** (nodos + aristas con integridad referencial validada en `scripts/build-taxonomy.mjs`), pero
hoy el chatbot/buscadores la usan como **texto plano** (similitud vectorial). GraphRAG debe explotar las **relaciones** para
responder preguntas **multi-salto del comité de compra** con contexto ensamblado por **traversal**, no solo por cercanía
semántica. Ejemplo objetivo:
> *"Soy CFO de una empresa de banca; ¿qué solución me ofrecen, cómo respondo a la objeción de presupuesto y con qué ROI lo
> justifico?"*
El sistema debe recorrer `persona(cfo) → relevantSectors(banca) → pillarsOfInterest → roiCases/kpis[EST] →
objectionResponses` y devolver una respuesta **fundamentada solo en nodos reales**, citando las páginas `/soluciones/*` y
`/sectores/*` de origen.

## 2. Fuente de verdad: el grafo (SSOT generado en build) — usar el esquema REAL, no inventar
Genera `src/data/taxonomyGraph.json` (SSOT) en `prebuild` desde las mismas colecciones YAML/JSON que ya valida
`build-taxonomy.mjs` (reúsalo/extiéndelo; **no** dupliques la carga). Nodos y aristas reales (ver `src/lib/schemas.js`):

**Nodos** (≈ 6 pilares · 10 sectores · 14 industrias · 7 roles-persona · 4 orgTypes):
- `pillar` (servicio): `id, name, slug, features, techStack, proofPoints[{client,result}], targetRoles, contrast{problem,solution}` → url `/soluciones/{slug}`
- `sector`: `id, slug, title, problems[{code,label,solution}], kpis[{metric,expectedRoi}], roiCases[{title,metrics}], regulations, roleEquivalents{roleId→nombreLocal}, relevantPersonas` → url `/sectores/{slug}`
- `industry`: `id, ciiu, relatedPillars`
- `persona/role`: `id, title, goals, pains, pillarsOfInterest, buyerRole, objections, objectionResponses[{objection,response}], decisionCriteria, triggers, relevantSectors`

**Aristas** (todas ya con chequeo de integridad — reúsalo como gate):
- `industry --relatedPillars--> pillar`
- `role --pillarsOfInterest--> pillar`
- `role --relevantSectors--> sector`
- `sector --relevantPersonas--> role`
- `sector --roleEquivalents(key)--> role` (y el valor = nombre local del rol en ese sector)
- (deriva) `role --objectionResponses--> {objection,response}` y `sector/pillar --kpis/roiCases[EST]-->` como atributos citables

**Build-gate (antidrift, obligatorio):** extiende la validación existente — falla el build (exit 1) ante **aristas rotas**,
**nodos huérfanos** inesperados o `roleEquivalents`/`relevantPersonas` que apunten a ids inexistentes. Reporta conteos de
nodos/aristas al construir.

## 3. Recuperación multi-salto — **0-LLM en el edge** (invariante del proyecto)
- **Entrada→anclas:** identifica los nodos-ancla desde la consulta reusando el **worker Xenova** (`public/worker.js`,
  cache multi-corpus de 020) sobre un corpus de nodos (texto = nombre+campos densos por nodo). Mantén el **contrato real
  del worker** (`corpusTexts` strings, resultados por índice, `id` único por petición) — no inventes contratos (lección 024).
- **Traversal:** desde las anclas, expande por aristas hasta **k saltos** (parametrizable, sugerido 2) y ensambla un
  **subgrafo de contexto** (nodos + relaciones + atributos citables: objectionResponses, kpis[EST], roiCases, proofPoints).
- **100% cliente / 0-LLM** para anclaje + traversal (sin `chat.php`). El grafo es pequeño (~40 nodos) → cabe en el bundle
  o se carga como el resto de corpora.

## 4. Síntesis en lenguaje natural — LLM free-tier **solo** para redactar, estrictamente fundamentado (§2)
- Solo si la consulta es de **texto libre complejo** (ruta `complejo/unknown` del router 019), se llama a `chat.php`
  (Groq→DashScope→Gemini free-tier) pasando **únicamente el subgrafo recuperado** como contexto, con instrucción de
  **no inventar** nada fuera de esos nodos y **citar** las URLs/nodos usados.
- Rutas guiadas/FAQ/cita siguen **0-LLM** (invariante 019). GraphRAG **no** debe añadir llamadas LLM a esas rutas.

## 5. Honestidad radical (§2) — guards de diseño
- **Cero fabricación:** la respuesta solo puede afirmar hechos presentes en el subgrafo. Sin ruta/aristas suficientes →
  **"datos insuficientes"** honesto (no rellenar con genéricos).
- **`[EST]`** en toda cifra de `kpis/expectedRoi/roiCases` (ya son estimaciones ilustrativas — ver disclaimer de
  `/casos-de-exito`); nunca presentarlas como métricas de clientes reales.
- **Trazabilidad:** cada respuesta cita los nodos y las páginas `/soluciones/*` `/sectores/*` de donde salió (auditable).
- **PII/seguridad:** el grafo es contenido público de marketing; sin datos privados. No exponer un endpoint que filtre
  nada más allá de la taxonomía pública.

## 6. Integración
- **Router 019 / chatbot:** GraphRAG es el motor de contexto para respuestas de solución/sector/rol; respeta el 0-LLM de
  las rutas determinísticas y solo enriquece la síntesis de la ruta compleja.
- **worker.js (020):** reúso del mismo modelo/worker y cache por firma; sin descargar otro modelo.
- **Telemetría 018:** emite `search_query`/evento con metadata útil (anclas, nº de saltos, nodos citados, si hubo
  "datos insuficientes") vía `track_event.php`, **PII-redactada**; visible en "Engagement" del panel.

## 7. OUT-OF-SCOPE (no hacer)
- Sin base de datos de grafos externa (Neo4j/etc.) ni servidor pesado — el grafo se genera en build y se recorre en cliente.
- Sin LLM de pago ni embeddings de pago — solo Xenova (edge) + free-tier.
- Sin relaciones fabricadas: solo las aristas que existen en la taxonomía. Nada de "inferir" vínculos no declarados.
- Sin reescribir la taxonomía: se **consume** la SSOT existente (a lo sumo se derivan índices).

## 8. Entregables SDD (en `specs/025-graphrag-taxonomia/`)
- `spec.md` (problema, usuarios/comité de compra, escenarios multi-salto, criterios de aceptación §2).
- `data-model.md` (esquema `taxonomyGraph.json`: nodos/aristas/atributos; contrato del worker `corpusTexts`+índices; formato
  del subgrafo de contexto; esquema del evento de telemetría).
- `plan.md` (build del grafo en `prebuild` reusando `build-taxonomy.mjs`; recuperación cliente; punto de integración con el
  router/chat; build-gate antidrift; deploy).
- `tech_debt.md` (honesto: tamaño del corpus, calibración de k-saltos y umbral, cobertura de la taxonomía, límites del
  free-tier).

## 9. Reglas de deploy/QA (recordatorio de campo)
- Backend PHP se sirve desde `dist/` → **`npm run build` ANTES de `deploy_ionos.py --confirm`**.
- El deploy ahora corre **`verify_inline_js_syntax()` (node --check)** en el PRE-DEPLOY GUARD y auto-verifica endpoints;
  pero **"endpoints ✅ ≠ panel/feature ✅"** → exige **verificación en vivo en el navegador** (0-LLM confirmado en rutas
  determinísticas, síntesis fundamentada y citada, "datos insuficientes" cuando toca, consola sin `SyntaxError`/`ReferenceError`).

## 10. Decisiones abiertas para que resuelvas en el diseño (justifícalas)
- **k de saltos** por defecto y umbral de anclaje (calibrar con consultas reales del comité; documenta el criterio, §2).
- **Ranking del subgrafo** cuando hay muchos caminos (¿peso por tipo de arista? ¿prioridad a objectionResponses/roiCases del
  rol+sector consultados?).
- **Dónde vive el traversal** (isla React dedicada vs. integrarlo en el flujo del chatbot existente) y cómo se **cita** en la UI.

---
**Nota:** Claude (Opus 4.8) auditará el diseño. La taxonomía ya trae el grafo y su integridad referencial —el diseño debe
**explotar las aristas reales** (no fabricar relaciones), mantener el **0-LLM en el edge** para anclaje+traversal, usar el
free-tier **solo** para redactar sobre el subgrafo recuperado, y respetar **§2** (cero fabricación, `[EST]`, "datos
insuficientes", trazabilidad). Reúsa `build-taxonomy.mjs` y el worker; no inventes contratos ni infraestructura pesada.
