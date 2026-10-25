# Especificación SDD — Spec 025: GraphRAG sobre la Taxonomía (Refinada tras Auditoría)

## 1. WHY (Propósito y Justificación de Negocio)
La oferta comercial y estratégica de Datanestiq está modelada en una taxonomía rica y estructurada en formato de Grafo (6 pilares de soluciones, 10 sectores económicos, 14 industrias CIIU, 7 roles del comité de compra y escenarios de ROI ilustrativos). Sin embargo, hasta ahora las interacciones del chatbot y los buscadores consumen esta información de forma plana mediante similitud vectorial simple.

Cuando un integrante del comité de compra (ej. un CFO de Banca o un CDO de Retail) realiza preguntas **multi-salto** que cruzan su rol, su sector, sus objeciones de costo y los escenarios de retorno esperado, la búsqueda por cercanía semántica simple no logra encadenar las relaciones contextuales de la taxonomía.

Esta especificación implementa **GraphRAG 0-LLM en el Edge sobre la Taxonomía**:
- **0-LLM en Anclaje & Traversal**: Identificación de nodos-ancla mediante embeddings locales (Xenova/MiniLM) y recorrido del grafo por aristas reales ($k=2$ saltos) 100% en el cliente.
- **Síntesis Grounded en LLM Free-Tier**: Únicamente en consultas complejas de texto libre, se suministra el subgrafo recuperado a `chat.php` para sintetizar la respuesta citando expresamente los nodos y URLs reales de origen (`/soluciones/{slug}`, `/sectores/{slug}`).
- **Honestidad Radical (§2)**: Si el grafo no contiene los nodos o aristas para justificar la respuesta, el sistema emite "datos insuficientes" sin inventar hechos, y preserva la marca **`[EST]`** en todas las métricas de retorno ilustrativo.
- **Telemetría y Privacidad (§6)**: Uso del contrato plano `trackEvent('search_query', 'graphrag', payload)` garantizando la redacción automática de PII.

---

## 2. WHAT (Alcance Funcional y Técnico)

### 2.1 Grafo SSOT de la Taxonomía (`src/data/taxonomyGraph.json`)
- Generado durante el proceso de `prebuild` extendiendo `scripts/build-taxonomy.mjs`.
- Estructura formal de Nodos y Aristas con namespacing (`pillar-<slug>`, `sector-<slug>`, `persona-<roleId>`, `industry-<ciiu>`) e integridad referencial estricta. Si existe una arista huérfana o un rol equivalente inexistente, el build aborta inmediatamente (`exit 1`).

### 2.2 Motor de Anclaje & Traversal Multi-Salto (0-LLM en Edge)
- **Anclaje semántico**: Utiliza el Web Worker `public/worker.js` con el contrato real `corpusTexts`, evaluando `status === 'complete'` e ID dinámico por petición (`graph-anchor-<seq>-<timestamp>`) para ubicar los nodos-ancla iniciales que superen el umbral ($\text{score} \ge 0.35$).
- **Traversal BFS a $k=2$ saltos**: A partir de los nodos-ancla, la función `traverseTaxonomyGraph(anchors, k=2)` recorre las aristas activas y ensambla un **subgrafo de contexto**.

### 2.3 Síntesis Grounded en `chat.php` (Ruta Compleja)
- Para preguntas de texto libre en la ruta compleja del router (019), el subgrafo recuperado se serializa en el prompt de sistema.
- El modelo LLM (Groq/DashScope/Gemini free-tier) redacta la respuesta final limitándose **exclusivamente** a los hechos presentes en el subgrafo, insertando citas explícitas a las páginas reales del sitio (`/soluciones/data-engineering`, `/sectores/finanzas`, etc.).
- Las métricas financieras o ROI generadas deben incorporar la marca obligatoria **`[EST]`**.
