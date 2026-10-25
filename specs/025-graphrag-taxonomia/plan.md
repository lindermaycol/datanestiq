# Plan de Implementación — Spec 025: GraphRAG sobre la Taxonomía (Refinado tras Auditoría)

Este documento detalla la hoja de ruta técnica para construir, integrar, verificar y desplegar el sistema GraphRAG en la plataforma Datanestiq.

---

## 1. Fases de Implementación

### Fase 1: Extensión de `scripts/build-taxonomy.mjs` y Build-Gate Antidrift
- Modificar `scripts/build-taxonomy.mjs` para compilar la estructura de nodos y aristas de `src/data/taxonomyGraph.json` durante el `prebuild`.
- **Namespacing de IDs Compuestos**: Asignar IDs normalizados (`pillar-<slug>`, `sector-<slug>`, `persona-<roleId>`, `industry-<ciiu>`).
- **Derivación de URLs Reales**: Generar `node.url` usando las rutas reales de la plataforma: `/soluciones/${slug}` para pilares y `/sectores/${slug}` para sectores.
- **Build-Gate de Integridad de Aristas**: Resolver todas las referencias cruzadas (`relatedPillars`, `pillarsOfInterest`, `relevantSectors`, `relevantPersonas`, `roleEquivalents`). Si se detecta un objetivo de arista nulo o inexistente, el script aborta inmediatamente con error (`process.exit(1)`).

### Fase 2: Módulo Traversal en Cliente (`src/lib/graphTraversal.ts`)
- Crear la utilidad de recorrido BFS a $k=2$ saltos que consume `taxonomyGraph.json`.
- Implementar la función `buildSubgraphContext(anchorNodeIds, graphData)` para extraer y resumir los hechos del subgrafo (soluciones, sectores, objeciones, KPIs con `[EST]`).
- Integración con `public/worker.js` usando la propiedad real `status: 'complete'`, el contrato `corpusTexts` e ID dinámico por búsqueda (`graph-anchor-<seq>-<ts>`).

### Fase 3: Integración en el Router Conversacional y Backend (`public/api/chat.php`)
- En la ruta de texto libre compleja del Router 019, adjuntar el subgrafo serializado en la llamada a `chat.php`.
- Actualizar el Prompt de Sistema de `chat.php` para exigir que las respuestas se fundamenten exclusivamente en el subgrafo de contexto proporcionado, citando las URLs reales (`/soluciones/*`, `/sectores/*`) y preservando la etiqueta `[EST]`.
- En caso de que el subgrafo esté vacío o desconectado, retornar el estado "Datos insuficientes".
- Mantener la plantilla de respaldo en el cliente (TD-025.3) 100% segura bajo §2 (sólo hechos del subgrafo y marcadores `[EST]`).

### Fase 4: Instrumentación de Telemetría (Spec 018 / Contrato Plano & PII §6)
- Emitir eventos de telemetría mediante `trackEvent('search_query', 'graphrag', jsonPayload)` garantizando que el backend PHP procese el payload plano y aplique la función `redactPii()` automáticamente.

---

## 2. Plan de Verificación y QA

1. **Build-Gate Antidrift**:
   - `node scripts/build-taxonomy.mjs` &rarr; Genera `taxonomyGraph.json` y valida integridad de aristas.
   - `node scripts/build-specs-status.mjs` &rarr; PASSED 100%.
2. **Pre-Deploy Syntax Gate**:
   - `python scripts/deploy/deploy_ionos.py` &rarr; Pasa `verify_inline_js_syntax()` (`node --check`).
3. **Verificación en Vivo (Skill `verificar-en-vivo`)**:
   - Probar preguntas multi-salto (ej. "Soy CFO de banca, ¿cómo justifico el ROI?") en el navegador real.
   - Confirmar que **0-LLM** aplica en las fases de anclaje y traversal.
   - Verificar citas explícitas a `/soluciones/*` y `/sectores/*` reales (que devuelvan HTTP 200).
   - Comprobar estado de "datos insuficientes" ante consultas no relacionadas.
   - Verificar en la base de datos de interacción que los eventos `search_query` con `graphrag` hayan redactado PII.
