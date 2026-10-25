# FIX (P0/P1) — Spec 025 GraphRAG: el guard §2 "datos insuficientes" NO se sostiene + anclaje léxico mal enfocado

Reauditoría **en vivo** en el chatbot de `app.datanestiq.com` (skill `verificar-en-vivo`, `fetch` a `chat.php`
instrumentado). El build está desplegado y la ruta compleja produce respuestas útiles, **pero dos cosas fallan de verdad**
(el auto-verify de endpoints no las toca porque GraphRAG es feature cliente + `chat.php`).

## 🔴 BLOQUEANTE 1 (§2 honestidad) — el bot respondió una receta de cocina
Consulta de prueba: *"quiero una receta de lasaña boloñesa con mucho queso"*.
- Recuperación **correcta**: `anchors: []`, `nodes: 0`, `hasSufficientData: false` (capturado en el `subgraph_context`).
- `chat.php` **sí** inyectó la regla obligatoria ("DEBES responder exactamente: 'No disponemos de información suficiente…'").
- **PERO el LLM la ignoró** y devolvió la receta completa: *"…puedes reducir la cantidad de queso… ¡Disfruta de tu deliciosa
  lasaña boloñesa con mucho queso!"*. Viola §2 y actúa como asistente genérico off-topic.

**Causa:** confiar en que el LLM free-tier "obedezca" una instrucción de sistema de responder-exactamente-X es frágil y
**ya falló en vivo**. La honestidad no puede depender del prompt.

**Fix (determinista, 0-LLM):** cuando `hasSufficientData === false`, **NO llames a `chat.php`/LLM en absoluto**. Corta en el
cliente (`Chatbot.jsx` → `_callLLM`) y renderiza **directamente** el mensaje fijo honesto (el mismo que ya define el diseño):
> *"No disponemos de información suficiente en la taxonomía para responder a esta combinación de requerimientos. ¿Deseas
> agendar una evaluación estratégica con nuestros expertos?"* (+ botón/oferta de agendar).
Esto además ahorra tokens y hace el guard **inquebrantable** (0-LLM). Como defensa en profundidad, mantén la regla en
`chat.php` por si algo llega igual, pero el corte del cliente es el que garantiza §2.

## 🔴 BLOQUEANTE 2 (recuperación) — el anclaje es léxico, mal enfocado, y el subgrafo explota a casi todo el grafo
Consulta: *"Soy CFO de un banco y necesito justificar el ROI … y responder objeciones de presupuesto"*.
- **Anclas devueltas: `["persona-cio","persona-cto"]`** — rol **equivocado**. NO ancló `persona-cfo` (el rol real) ni
  `sector-finanzas` (banca). El match por substrings de descripción pescó CIO/CTO por solapamiento de palabras.
- **`nodes: 33` de 37** y **12 `citedUrls`** incluyendo `/sectores/salud`, `/sectores/mineria`, `/sectores/educacion`,
  `/sectores/seguros`… y **sin `/sectores/finanzas`**. El BFS `k=2` desde nodos `persona` (hubs muy conectados) **arrastra
  casi todo el grafo** → el "subgrafo grounded" no está focalizado y cita sectores irrelevantes (roza §2: grounding
  engañoso). La respuesta sale bien **por mérito del LLM y porque la consulta ya trae el contexto**, no por la recuperación.

**Causa raíz:** `findAnchorNodes` en `src/lib/graphTraversal.ts` hace **matching léxico de substrings**, NO el **anclaje
semántico con el worker Xenova** que el diseño (spec §2.2 y data-model §2: `corpusTexts` + `graph-anchor-<seq>-<ts>`)
prometió. Ese contrato del worker **no se usa en ningún lado**.

**Fixes:**
1. **Anclaje semántico real (0-LLM edge):** implementa el anclaje con el **worker `public/worker.js`** usando el contrato
   real (`type:'search'`, `corpusTexts` = un string por nodo [label+descripción+tipo], resultados por `index`, `id` único
   `graph-anchor-<seq>-<ts>`, `status:'complete'`), filtrando por coseno `≥ 0.35`. Es lo que el data-model ya especifica.
   (Ese es el "0-LLM en el edge" del proyecto; el substring-matching no lo es en espíritu y mal-ancla.)
2. **Traversal enfocado:** evita que los nodos `persona` (hubs) expandan a todo el grafo. Opciones: (a) priorizar/expandir
   por **relevancia de arista** hacia el rol+sector anclados; (b) `k` efectivo menor desde hubs, o límite de nodos por tipo;
   (c) puntuar el subgrafo y quedarte con los **N nodos** más relevantes al ancla. Objetivo: para "CFO de banca", el subgrafo
   debe centrarse en `persona-cfo` + `sector-finanzas` + sus `pillarsOfInterest`/`roiCases`/`objectionResponses`, **no** en
   salud/minería. Verifica que `citedUrls` contenga `/sectores/finanzas` y NO sectores ajenos.
3. **Bug de código muerto (`graphTraversal.ts` L172):** `if (qLower.includes(labelLower) || (labelLower.length > 4 &&
   qLower.includes(labelLower)))` — **ambos lados del `||` son idénticos**. Corrige la intención (probablemente match parcial)
   o elimínalo. (Al migrar a anclaje por worker, `findAnchorNodes` léxico se reemplaza; si lo conservas como fallback,
   arréglalo.)

## 🟡 Precisión — `groundedFacts` omite `roiCases`
`buildSubgraphContext` solo empuja `sector.kpis` a `kpisAndRoi[EST]`; **no** incluye `roiCases` (que el diseño US-025.1
enfatiza como `roiCases[EST]`). Añádelos (con `[EST]`) para que la síntesis pueda citar casos de retorno.

## OK / no tocar (verificado en vivo)
- **Correcciones 1 y 2 de la auditoría de diseño BIEN aplicadas:** telemetría con contrato plano
  `trackEvent('search_query','graphrag', jsonPayload)` → `track_event.php` la **PII-redacta** (event_type `search_query`) ✅.
- **URLs derivadas de slugs reales** y resuelven 200 (`/soluciones/ai-data-science`, `/sectores/finanzas`) ✅.
- **Build-gate:** 37 nodos / 124 aristas con integridad referencial ✅.
- **0-LLM del router 019 preservado:** `cita`/`guiado`/`faq` retornan antes de `_callLLM`; GraphRAG solo enriquece la ruta
  compleja ✅.
- `chat.php` inyecta el subgrafo + reglas `[EST]`/citas ✅ (el problema no es la inyección sino confiar el guard al LLM).

## Verificación (reaudito EN VIVO)
- *"receta de lasaña…"* → **mensaje honesto fijo** (0-LLM, sin receta), con oferta de agendar. `chat.php` **no** debe recibir
  la llamada cuando `hasSufficientData:false`.
- *"CFO de un banco, ROI, objeciones de presupuesto"* → `anchors` incluyen `persona-cfo` y `sector-finanzas`; `citedUrls`
  contiene `/sectores/finanzas` y NO sectores ajenos (salud/minería); `nodes` **acotado** (no ~todo el grafo).
- Anclaje ejecuta vía **worker** (0-LLM); consola sin `SyntaxError`/`ReferenceError`. `npm run build` antes de
  `deploy_ionos.py --confirm` (JS gate + auto-verify).

---
**Nota:** Claude (Opus 4.8) verificó en runtime real. El feature está desplegado y "responde", pero el **guard §2 se rompe**
(respondió una receta) y el **anclaje no es el semántico prometido** (mal-ancla CFO→CIO/CTO y el subgrafo es casi todo el
grafo). Ambos son de fondo, no cosméticos: haz el guard **determinista 0-LLM** y el anclaje **con el worker** + traversal
**enfocado**.
