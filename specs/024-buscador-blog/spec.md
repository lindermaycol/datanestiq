# Especificación SDD — Spec 024: Buscador Semántico sobre el Blog (0-LLM, Reuso de Xenova)

## 1. WHY (Propósito y Justificación de Negocio)
El ecosistema de Datanestiq cuenta con un buscador semántico en el Home (`SemanticSearch.jsx`) orientado al catálogo de soluciones comerciales, y con un Blog dinámico en constante expansión (`src/content/blog/*.md`). Actualmente, los visitantes del Blog no disponen de una herramienta para buscar artículos en lenguaje natural basándose en el significado de sus necesidades o retos organizacionales.

Esta especificación extiende la capacidad cognitiva 0-LLM al Blog:
- **0 Tokens LLM**: Clasificación y cómputo vectorial 100% en el cliente mediante Transformers.js (Xenova/all-MiniLM-L6-v2).
- **Máximo Reuso de Infraestructura**: Reusa el Web Worker de embeddings existente (`public/worker.js`) aprovechando la caché de firmas multi-corpus (introducida en la Spec 020) para alojar el corpus de soluciones y el corpus de blog de forma simultánea.
- **Transparencia & Conversión**: Ofrece respuestas inmediatas con enlaces a los artículos originales y registra las intenciones de búsqueda redactadas mediante la telemetría de la Spec 018 para alimentar la observación de demanda no atendida en el panel ejecutivo.

---

## 2. WHAT (Alcance Funcional y Técnico)

### 2.1 SSOT del Corpus del Blog (`src/data/blogCorpus.json`)
- Generado durante la fase de `prebuild` mediante el script `scripts/build-blog-corpus.mjs`.
- Extrae la información de las publicaciones en Markdown (`src/content/blog/*.md`): `slug`, `title`, `description/excerpt`, `tags`, `publishDate`, `readingTime` y el encabezado del contenido (`body_head`, primeros N caracteres sanitizados).
- Incluye validación mediante esquema Zod / JSON Schema. Si la estructura falla, el build aborta con error explicativo (Antidrift).

### 2.2 Isla Interactiva de Búsqueda del Blog (`BlogSearch.jsx`)
- Ubicada en la página principal del blog (`/blog/` y opcionalmente en el layout del blog).
- **Carga bajo Intención (`handleIntent`)**: El worker y el corpus `blog` no se descargan de forma eager al cargar la página, sino al interactuar con la barra de búsqueda (focus, hover o tipeo inicial).
- **Búsqueda Vectorial Coseno**: La consulta del usuario se envía al Web Worker (`type: 'search'`, `corpus: 'blog'`, `id: 'blog-search-<seq>-<timestamp>'`). El worker calcula el embedding de la consulta y realiza la similitud coseno contra las representaciones vectoriales precalculadas o en la memoria del cliente.
- **Ranking y Umbral de Relevancia (§2)**: Los artículos se ordenan por score de similitud. Únicamente se muestran los resultados que superen el umbral mínimo calibrado ($\text{score} \ge 0.35$).
- **Estado Vacío Honesto (§2)**: Si ninguna publicación alcanza el umbral de relevancia, la interfaz presenta un mensaje claro e inalterado: *"No encontramos artículos para tu búsqueda; prueba otras palabras o consulta a nuestro AI Concierge."* NUNCA se inventan o fuerzan artículos irrelevantes.

### 2.3 Registro de Telemetría (Spec 018)
- Emisión del evento `search_query` hacia `public/api/track_event.php` asociando `target: 'blog-search'` y la consulta sanitizada/redactada sin PII.
- Visibilidad inmediata en la pestaña **"Engagement & Comportamiento"** del Panel de Administración para auditar qué términos e inquietudes buscan los usuarios en la base de conocimientos del blog.

---

## 3. CONSTRAINTS (Reglas y Limitaciones Obligatorias)

1. **§5 (Principio 0-LLM)**:
   - La búsqueda y clasificación deben ser 100% locales en el navegador del cliente. 0 peticiones y 0 tokens consumidos de proveedores LLM.
2. **§2 (Honestidad Radical)**:
   - Cero resultados fabricados, manipulados o forzados. Los excerpts mostrados deben corresponder textualmente al contenido real de las publicaciones Markdown.
3. **Reuso de Infraestructura y Concurrencia**:
   - Reuso estricto del Web Worker `public/worker.js`. Queda prohibido descargar otro modelo u otro script de worker.
   - Aislamiento de mensajes mediante IDs únicos (`id: 'blog-search-query'`) para evitar colisiones con el buscador de soluciones del Home.
4. **Privacidad (§6)**:
   - Las consultas enviadas al endpoint de telemetría son filtradas por el middleware de sanitización `redactPii()` para descartar teléfonos, nombres o emails.

---

## 4. OUT OF SCOPE (Fuera de Alcance)

- **NO** GraphRAG ni consultas vectoriales en el backend.
- **NO** Generación de resúmenes sintéticos por LLM sobre los resultados de búsqueda.
- **NO** Reemplazo del buscador de soluciones del Home.
- **NO** Integración con motores de búsqueda externos (Algolia, ElasticSearch, etc.).

---

## 5. USER STORIES & ACCEPTANCE CRITERIA

### US-024.1: Búsqueda Semántica en el Blog
**Como** lector del blog de Datanestiq,  
**Quiero** ingresar mi necesidad o problema en lenguaje natural (ej. *"¿cómo organizar el almacenamiento de datos en retail?"*),  
**Para** descubrir instantáneamente los artículos más afines a mi consulta.

**Criterios de Aceptación:**
- Al escribir en la barra de búsqueda del Blog, se presentan los artículos cuyo vector tenga una similitud coseno $\ge 0.25$.
- Los resultados incluyen título, resumen oficial (`excerpt`), etiquetas (`tags`) y enlace directo al artículo.
- Si no hay coincidencias relevantes, se despliega el mensaje de estado vacío honesto.

### US-024.2: Rendimiento y Cómputo Ligero (0-LLM)
**Como** arquitecto de plataforma,  
**Quiero** que la búsqueda se realice en el cliente utilizando el Web Worker existente,  
**Para** garantizar una experiencia fluida sin incurrir en costos de API LLM ni ralentizar la carga inicial de la página.

**Criterios de Aceptación:**
- La descarga/inicialización del modelo en el worker solo inicia bajo interacción del usuario (`handleIntent`).
- Se reusa el objeto `cachedCorpora` del worker para almacenar los embeddings del corpus `blog` bajo la firma correspondiente.
