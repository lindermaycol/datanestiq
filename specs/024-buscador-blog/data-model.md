# Modelo de Datos y Esquemas — Spec 024: Buscador Semántico sobre el Blog

## 1. Esquema del Corpus SSOT (`src/data/blogCorpus.json`)

El archivo es generado automáticamente en `prebuild` por `scripts/build-blog-corpus.mjs` escaneando los archivos Markdown en `src/content/blog/*.md`.

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "BlogCorpus",
  "type": "array",
  "items": {
    "type": "object",
    "required": ["slug", "title", "excerpt", "tags", "url"],
    "properties": {
      "slug": {
        "type": "string",
        "description": "Identificador único de la publicación (nombre del archivo md)"
      },
      "title": {
        "type": "string",
        "description": "Título de la publicación"
      },
      "excerpt": {
        "type": "string",
        "description": "Resumen o descripción breve de la publicación"
      },
      "tags": {
        "type": "array",
        "items": { "type": "string" },
        "description": "Etiquetas o pilares/sectores asociados"
      },
      "url": {
        "type": "string",
        "description": "Ruta relativa canónica de la publicación (/blog/{slug})"
      },
      "publishDate": {
        "type": "string",
        "description": "Fecha de publicación en formato ISO"
      },
      "body_head": {
        "type": "string",
        "description": "Primeros 300 caracteres limpios del cuerpo para mayor densidad semántica"
      }
    }
  }
}
```

---

## 2. Contrato de Mensajería del Web Worker (`public/worker.js`)

El worker reusa la caché multi-corpus por firma introducida en la Spec 020 utilizando un array de cadenas de texto (`corpusTexts`). Cada búsqueda genera un **ID único por petición** (`blog-search-<secuencia>-<timestamp>`) para prevenir condiciones de carrera durante el tecleo veloz.

### Petición del Cliente (`BlogSearch.jsx` &rarr; `worker.js`)
```typescript
interface WorkerBlogSearchRequest {
  type: 'search';
  query: string;
  corpusTexts: string[]; // Array plano de textos: title + excerpt + body_head
  id: string; // Dinámico: `blog-search-${++reqCounter}-${Date.now()}`
}
```

### Respuesta del Worker (`worker.js` &rarr; `BlogSearch.jsx`)
```typescript
interface WorkerBlogSearchResponse {
  type: 'results' | 'complete';
  id: string; // Coincide con el id de la petición correspondiente
  query: string;
  results: Array<{
    index: number; // Índice numérico dentro del array corpusTexts enviado
    score: number; // Similitud coseno en rango [-1.0, 1.0]
  }>;
}
```

---

## 3. Configuración de Umbrales & Regla de Estado Vacío (§2)

| Parámetro | Valor Calibrado | Descripción |
|---|---|---|
| `SIMILARITY_THRESHOLD` | `0.35` | Score mínimo de similitud coseno (calibrado para evitar coincidencias débiles). |
| `MAX_RESULTS` | `5` | Número máximo de publicaciones retornadas en la lista de resultados. |

### Regla de Honestidad (§2)
Si $\forall r \in \text{results}, r.\text{score} < 0.35$, el componente conmuta al estado vacío honesto:
```jsx
<div className="empty-search-state">
  <p>No encontramos artículos para tu búsqueda; prueba otras palabras o consulta a nuestro AI Concierge.</p>
</div>
```

---

## 4. Esquema del Evento de Telemetría (Spec 018)

Petición `POST` emitida hacia `public/api/track_event.php`:

```json
{
  "event_name": "search_query",
  "event_value": "blog-search",
  "event_target": "optimización de almacenamiento en nube",
  "session_id": "session_1785616588186",
  "metadata": {
    "corpus": "blog",
    "results_count": 3,
    "top_score": 0.482
  }
}
```
