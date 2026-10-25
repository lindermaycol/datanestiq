# Confirmación — Mejoras UX del home: LUZ VERDE con 3 precisiones + 2 respuestas → ejecutar

El plan (`planes/Plan de Implementación — Mejoras UX del Home...md`) es sólido y fiel: Copiloto contextual por `semanticHighlight`, CTA encadenado, fix del umbral de sectores (excluir `service-*` del `maxScore`), microcopy por rol desde taxonomía, reorden por zonas. **Apruebo.** Ajusta esto antes/durante la implementación:

## 🔴 Precisión 1 — IDs de ancla CONSISTENTES (o el scroll encadenado falla)
El plan usa **dos IDs distintos** para el mismo destino: en Mejora 1b el botón hace `scrollTo('#sectores-grid')`, pero en Mejora 4 la sección es `id="diagnostic-section"`. **Unifica a UN solo id.** Requisito:
- El `<section>` del `DiagnosticWizard` → **`id="diagnostic-section"`**; el del `CopilotDemo` → **`id="copilot-section"`**.
- Los botones del CTA encadenado apuntan a **exactamente** esos IDs. Verifica que existan en `index.astro` tras el reorden. Si un ancla no existe, el botón no debe romper (guard `document.querySelector(id)?.scrollIntoView(...)`).

## 🔴 Precisión 2 — Copiloto: proteger contra pilares sin `copilotPrompts`
Al rankear pilares por score (Mejora 1a), el top-3 podría incluir un pilar **sin `copilotPrompts`**, y `p.copilotPrompts[0]` reventaría. **Filtra** a pilares que tengan `copilotPrompts` **antes** de ordenar/`slice(0,3)` (tanto en el modo búsqueda como en el fallback). Que nunca falle si un pilar no tiene prompt.

## 🔴 Precisión 3 — Pre-llenado del input de sector: que el usuario pueda editarlo/borrarlo
`value={inputs[sector.id] || challenge || ''}` tiene un problema de UX: si el usuario **borra** el texto, `inputs[sector.id]` queda `''` y el campo **vuelve a saltar** a `challenge` (no lo puede vaciar). **Fix:** inicializa el estado `inputs` con `challenge` **una vez** (vía `useEffect` cuando llega `challenge` y el input está vacío), y luego el `value` es solo `inputs[sector.id]`. Así el usuario puede editar y borrar libremente, pero arranca pre-llenado.

## Respuestas a tus Open Questions
1. **Subtítulo del Copiloto:** la redacción propuesta está bien (clara y honesta). Pulido opcional para tono premium: *"Una demostración del razonamiento que produce nuestra arquitectura de IA — distinto del AI Concierge, que resuelve tu consulta."* Usa la que prefieras; ambas cumplen.
2. **Microcopy por rol:** **solo `goals[0]`** como línea limpia y punzante (una línea, no saturar). Si `goals[0]` sale muy corto, puedes añadir el 1er `decisionCriteria` separado por "·", pero prioriza brevedad. Deriva siempre de la taxonomía (§2), sin inventar.

## Guardarraíles (Constitución)
0-LLM del flujo guiado intacto (los CTA son scroll/eventos, no fetch); taxonomía = fuente de verdad (microcopy y escenarios derivan de datos, no inventados); **no reescribir descripciones de sector**; progressive enhancement; `npm run build` verde; doc-sync a `ESTADO-SPECS.md` (extensión Spec 002/013).

## Verificación (evidencia real)
1. **Copiloto contextual:** buscar "eficiencia" → los escenarios son los de los **pilares resaltados**; sin búsqueda → los 3 por defecto; nunca revienta si un pilar no tiene prompt.
2. **CTA encadenado:** aparece "Encontramos N…"; los 3 botones hacen scroll **anclado correcto** (IDs consistentes) y "AI Concierge" abre el chatbot **con la consulta cargada**. Network: 0 `fetch` en el flujo guiado.
3. **Sectores reactivos:** tras buscar, se **resaltan los sectores relevantes** (no se atenúan todos) y los inputs vienen pre-llenados **pero editables/borrables**.
4. **Copiloto vs chatbot:** subtítulo + puente.
5. **Microcopy por rol:** chip CFO muestra la línea del CFO (taxonomía); sin contexto, nada; reset la quita.
6. **Orden:** `DiagnosticWizard` en EXPLORA (`#diagnostic-section`), `CopilotDemo` en PROFUNDIZA (`#copilot-section`); anchors OK.
7. `npm run build` verde; consola limpia; `ESTADO-SPECS.md` actualizado. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: continuidad buscador → copiloto contextual/sectores/chatbot con contexto, scroll anclado, input de sector editable, microcopy por rol, y 0-LLM/build verdes.
