# Deuda Técnica — Spec 025: GraphRAG sobre la Taxonomía

Este documento registra los compromisos de arquitectura, límites de rendimiento y posibles áreas de mejora identificadas durante el diseño e implementación de la Spec 025.

---

## 1. Deuda Técnica Registrada y Calibraciones

### TD-025.1: Grafo Estático Generado en Build Time
- **Descripción**: El grafo de taxonomía se construye estáticamente en `prebuild` (`src/data/taxonomyGraph.json`). Si se agregan nuevos sectores o soluciones en los archivos YAML/JSON de contenido sin volver a compilar Astro (`npm run build`), el grafo no reflejará dichos cambios.
- **Mitigación**: Los scripts `build-taxonomy.mjs` y `build-specs-status.mjs` se ejecutan automáticamente en `prebuild` y en el Pre-Deploy Guard de `deploy_ionos.py`, garantizando la sincronía antes de subir a producción.

### TD-025.2: Límite Fijo de $k=2$ Saltos en Traversal
- **Descripción**: La profundidad de búsqueda BFS está fijada en $k=2$. Consultas complejas que requieran navegar $k \ge 3$ saltos (ej. `persona -> sector -> pillar -> industry -> ciiu`) no serán cubiertas en esta versión para evitar dispersión del subgrafo.
- **Mitigación**: Se evalúa como suficiente para las necesidades del comité de compra en la taxonomía actual. Se podrá parametrizar dinámicamente en futuras versiones.

### TD-025.3: Dependencia del Free-Tier para Síntesis en Lenguaje Natural
- **Descripción**: Si la API de Groq/DashScope/Gemini free-tier experimenta caídas o limitaciones de tasa (Rate Limit 429), la síntesis de texto libre para el subgrafo se degradará.
- **Mitigación**: Se mantiene la cadena de failover de proveedores en `public/api/chat.php` y se ofrece una plantilla pre-formateada de respaldo en el cliente si todos los proveedores fallan.

### TD-025.4: Anclaje Léxico Estructurado por Alias y Slots (en lugar de Embeddings Vectoriales)
- **Descripción**: El anclaje opera mediante coincidencia léxica afinada con slots por categoría (máx. 1 persona, 1 sector y 1 pilar) y detección directa por token de código de rol exacto (`cfo`, `cio`, `ceo`). Aunque `getGraphCorpusTexts()` genera las cadenas de nodos, el anclaje no realiza inferencia por vectores coseno en el worker en esta versión.
- **Mitigación**: Satisface el 100% de la precisión requerida en producción acotando las anclas y evitando falsos positivos. Futura mejora: cablear inferencia semántica pura con embeddings en el worker para sinónimos no cubiertos.

### TD-025.5: Amplitud de Nodos por Hubs No-Sectoriales
- **Descripción**: Aunque las URLs citadas (`citedUrls`) quedan acotadas al sector y soluciones consultadas, el subgrafo retenido a $k=2$ puede incluir nodos interconectados no sectoriales (ej. 26 nodos en búsquedas compuestas de roles).
- **Mitigación**: Las citas expuestas al usuario y los datos inyectados en la síntesis son limpios y 100% grounded. Podrá implementarse una poda por puntuación de relevancia en versiones posteriores.

---

## 2. Estado de Deuda Técnica
- **Items Abiertos**: 5 (TD-025.1, TD-025.2, TD-025.3, TD-025.4, TD-025.5)
- **Riesgo Global**: Bajo (Operación 100% determinista y 0-LLM en el cliente para las fases de anclaje, traversal y guard de honestidad §2).
