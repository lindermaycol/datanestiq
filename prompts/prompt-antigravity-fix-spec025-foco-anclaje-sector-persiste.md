# FIX (P1) — Spec 025: el guard §2 ya quedó ✅, pero el subgrafo SIGUE sin enfocarse (cita todos los sectores)

Reauditoría **en vivo** de los fixes. **Bloqueante 1 resuelto y verificado**: "receta de lasaña" → mensaje honesto §2 +
tarjeta de agenda, **0 llamadas a `chat.php`** (guard determinista 0-LLM funciona). `roiCases[EST]` también incorporado ✅.

**Pero el Bloqueante 2 (foco de la recuperación) NO quedó resuelto.**

## Evidencia en vivo — misma consulta "Soy CFO de un banco… ROI… objeciones de presupuesto"
Subgrafo capturado en el `subgraph_context` enviado a `chat.php`:
- `anchors: ["persona-cio","persona-cfo","persona-cdo"]` — ahora **sí** incluye `persona-cfo` ✅, pero **`sector-finanzas`
  NO está anclado**, pese a que la consulta dice "un **banco**".
- `nodes: 36` de 37 (¡peor que los 33 previos!) y `citedUrls` con **todos** los sectores: `publico, educacion, manufactura,
  mineria, salud, seguros, finanzas, retail, telecomunicaciones`. Para una consulta de **banca**, cita salud/minería/etc.

## Causa raíz (precisa)
1. `findAnchorNodesFallback(text, 3)` con **`maxAnchors=3`**: los alias de **persona** (cfo→'presupuesto/costos/financiero',
   cdo→'datos', cio→'tecnologia') puntúan alto y **copan el top-3**. El alias de `sector-finanzas` (`'banco','banca'…`) sí
   matchea, pero **queda fuera del corte de 3** → **`sector-finanzas` no se ancla**.
2. En `traverseTaxonomyGraph`, la "regla de traversal enfocado" está **gateada por `anchorSectors.size > 0`**
   (L113 y L118). Como ningún sector quedó anclado, **`anchorSectors` está vacío** → la restricción **nunca se aplica** →
   el BFS `k=2` desde 3 hubs-persona vuelve a arrastrar casi todo el grafo (36/37).

Es decir: el foco depende de que haya un sector anclado, y justo el anclaje deja el sector fuera. El fix quedó a medias.

## Fix (elige y combina)
1. **Garantizar el ancla de sector cuando hay match** (mínimo imprescindible): separa "slots" de anclaje por tipo o asegura
   que, si algún `sector-*` supera el umbral por alias/semántica, **entre al menos 1 sector** a las anclas (no que 3
   personas lo desplacen). P. ej. top-2 personas + top-1 sector, o sube `maxAnchors` y **prioriza** incluir el mejor sector.
2. **No condicionar el foco a que haya sector anclado** (defensa en profundidad): aunque `anchorSectors` esté vacío, **acota
   la explosión desde hubs `persona`** — limita nº de vecinos por tipo, poda por relevancia al ancla, o **recorta el
   subgrafo a los top-N nodos** más cercanos a las anclas. Objetivo medible: para "CFO de banca", `nodes` **acotado**
   (no ~37) y `citedUrls` centrado en `/sectores/finanzas` + soluciones del CFO, **sin** salud/minería/educación.
3. **Reducir ruido de personas:** 3 anclas-persona (cio+cfo+cdo) para "CFO" es demasiado. Afinar el scoring/alias para que
   "CFO"/"presupuesto" no dispare también cio/cdo, o limitar a 1 persona dominante.
4. **(Pendiente del diseño original)** el anclaje sigue siendo **léxico por alias**, no el **semántico con el worker Xenova**
   que el data-model prometió (`getGraphCorpusTexts()` ya existe pero **no** se pasa al worker). El alias es frágil y no
   escala a paráfrasis. Si no vas a cablear el worker ahora, **decláralo como deuda técnica explícita** en `tech_debt.md`
   (hoy el diseño afirma "anclaje semántico" y la implementación no lo es).

## Verificación (reaudito EN VIVO)
- "CFO de un banco…" → `anchors` incluye `sector-finanzas`; `nodes` **acotado** (no ~todo el grafo); `citedUrls` con
  `/sectores/finanzas` y **sin** sectores ajenos (salud/minería/educación).
- Consulta paráfrasis sin la palabra exacta (p. ej. "responsable de las finanzas de una entidad crediticia") → aún ancla
  `sector-finanzas`/`persona-cfo` (prueba de que no es solo substring).
- "receta de lasaña" → sigue cortando 0-LLM con mensaje honesto (no regresión del Bloqueante 1).

## OK / verificado en vivo (no tocar)
- **Guard §2 determinista 0-LLM** (Bloqueante 1): ✅ receta → honesto + agenda, **0 `chat.php`**.
- `roiCases[EST]` en `groundedFacts` ✅. Telemetría plana PII-redactada ✅. Router 019 0-LLM preservado ✅.

---
**Nota:** Claude (Opus 4.8) verificó en vivo. El guard de honestidad quedó **sólido**; lo que falta es que la recuperación
**enfoque de verdad** — hoy el sector no se ancla (lo desplazan 3 personas) y el subgrafo es casi todo el grafo, así que la
respuesta buena viene del LLM, no del GraphRAG. Asegura el ancla de sector y acota la expansión desde hubs; el "traversal
enfocado" no puede depender de un sector que el anclaje deja fuera.
