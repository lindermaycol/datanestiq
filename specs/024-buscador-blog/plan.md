# Plan de Implementación — Spec 024: Buscador Semántico sobre el Blog

## 1. Fases de Ejecución

### Fase 1: SSOT del Corpus del Blog (`prebuild`)
1. Crear `scripts/build-blog-corpus.mjs` para iterar `src/content/blog/*.md`, extraer metadata con `gray-matter`, limpiar formato Markdown y volcar `src/data/blogCorpus.json`.
2. Registrar el script en la sección `scripts` de `package.json` (`"build:blog-corpus": "node scripts/build-blog-corpus.mjs"`) e integrarlo en la tarea `prebuild`.

### Fase 2: Componente Frontend (`BlogSearch.jsx`)
1. Crear la isla interactiva `BlogSearch.jsx` reusando el patrón de `SemanticSearch.jsx`.
2. Configurar la comunicación con el Web Worker `public/worker.js` pasando `corpusName: 'blog'` e `id: 'blog-search-query'`.
3. Implementar el cargador por intención (`handleIntent`) al recibir foco o interacción en el input de búsqueda.
4. Renderizar tarjetas de resultados (título, excerpt real, tags, botón "Leer") para items con $\text{score} \ge 0.25$.
5. Implementar estado vacío honesto para búsquedas sin coincidencia (§2).

### Fase 3: Integración en la Vista del Blog (`src/pages/blog/index.astro`)
1. Incluir la isla `<BlogSearch client:visible />` en el encabezado de la página del blog `/blog`.

### Fase 4: Instrumentación de Telemetría (Spec 018)
1. Conectar la emisión de eventos de búsqueda mediante `trackEvent('search_query', 'blog-search', query)` hacia `public/api/track_event.php`.

---

## 2. Plan de Verificación y Compilación

### Verificación Local (Pre-deploy)
- **Script Antidrift**: `node scripts/build-specs-status.mjs` &rarr; Asegurar 100% de sincronía en el SSOT.
- **Compilador Astro**: `npm run build` &rarr; Verificar la generación limpia de `blogCorpus.json` y la compilación estática de la isla `BlogSearch`.

### Verificación en Producción IONOS (Post-deploy)
- **Deploy con Self-Guard**: `python scripts/deploy/deploy_ionos.py --confirm`
- **Auto-Verificación**: Comprobar que los endpoints reales de producción devuelvan 200 y que la búsqueda semántica en el blog responda adecuadamente con resultados reales o estado vacío honesto.
