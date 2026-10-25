# Implementation Plan: Taxonomía de Servicios (Spec 003)

Basado en la ejecución del comando `/speckit-plan`, he extraído los requerimientos de la Especificación 003 y generado el plan técnico para desacoplar y estructurar la taxonomía B2B.

## 1. Summary
**Objetivo Principal:** Extraer la matriz estática de 6 Pilares Tecnológicos y 10 Sectores Estratégicos desde el código duro y persistirla como una fuente de verdad JSON (`taxonomyCorpus.json`) enriquecida con metadatos NLP. Esto permite que el frontend la consuma dinámicamente y el modelo local de IA realice búsquedas semánticas ultra-rápidas.

## 2. Technical Context & Constitution Check
- **Lenguaje:** JSON (Data Layer), JavaScript/React (UI), Transformers.js (Inferencia).
- **Check Constitución:** PASSED. El plan aísla los datos (cumpliendo con la regla de Fuente de Verdad) y estructura los campos necesarios para evitar Prompt Injections durante la búsqueda.

## 3. Proposed Changes (Arquitectura de Datos)

### Data Layer
#### [NEW] [taxonomyCorpus.json](file:///c:/xampp/htdocs/datanestiq/prototype/api/taxonomyCorpus.json)
Se creará este nuevo archivo que contendrá el esquema de datos.
**Modelo de Datos Propuesto (Data Model):**
```json
{
  "pillars": [
    {
      "id": "p1-data-science",
      "title": "Inteligencia Artificial & Ciencia de Datos",
      "description": "Diseñamos e implementamos sistemas cognitivos...",
      "strategic_services": [...],
      "nlp_metadata": {
        "keywords": ["machine learning", "rag", "llm", "agentes"],
        "synonyms": ["ia", "ciencia de datos", "modelos predictivos"]
      }
    }
  ],
  "sectors": [
    {
      "id": "s1-publico",
      "title": "Sector Público & Gobierno",
      "description": "Modernización del Estado mediante automatización...",
      "nlp_metadata": {
        "keywords": ["gobierno", "estado", "licitaciones", "ciudadanos"]
      }
    }
  ]
}
```

### Presentation Layer
#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- **[DELETE]** Se eliminarán las variables estáticas y quemadas (hardcoded) de la taxonomía.
- **[NEW]** Se añadirá la función `loadTaxonomyCorpus()` usando `fetch()` para leer de `api/taxonomyCorpus.json`.

#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- **[NEW]** Inyectaremos el contenedor vacío `<div id="taxonomy-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3"></div>` y lo llenaremos dinámicamente con JS, garantizando la semántica y A11y exigida (FR-003.3).

## 4. Verification Plan (Quickstart)
1. Iniciar el servidor local (`php -S localhost:8000` en la carpeta `prototype`).
2. Validar que la interfaz renderice dinámicamente los 6 pilares.
3. Ejecutar una auditoría de Lighthouse para validar **0 errores de contraste (WCAG 2.2 AA)**.

---

> [!IMPORTANT]
> **User Review Required:**
> El modelo JSON propuesto añade campos de `nlp_metadata` (keywords, synonyms) para enriquecer la búsqueda de la IA local, tal como lo exige el **FR-003.2**. ¿Estás de acuerdo con este esquema de datos para poder proceder a generar las tareas (`/tasks`) y escribir el JSON real?
