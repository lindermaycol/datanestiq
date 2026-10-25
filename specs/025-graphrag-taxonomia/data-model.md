# Modelo de Datos — Spec 025: GraphRAG sobre la Taxonomía (Refinado tras Auditoría)

Este documento define la estructura SSOT del Grafo de Taxonomía (`src/data/taxonomyGraph.json`), los esquemas de intercambio del Web Worker y la representación del subgrafo de contexto.

---

## 1. Esquema del Grafo SSOT (`src/data/taxonomyGraph.json`)

El archivo JSON es generado en `prebuild` por `scripts/build-taxonomy.mjs` y exporta la siguiente estructura formal:

```typescript
export interface TaxonomyGraphSSOT {
  version: string;
  generatedAt: string;
  stats: {
    totalNodes: number; // [EST] ~45 nodos reales
    totalEdges: number; // [EST] ~120 aristas reales
    nodesByType: Record<string, number>;
  };
  nodes: Record<string, GraphNode>;
  edges: GraphEdge[];
}

export type NodeType = 'pillar' | 'sector' | 'industry' | 'persona' | 'orgType';

export interface GraphNode {
  id: string; // IDs compuestos: "pillar-data-engineering", "sector-finanzas", "persona-cfo"
  type: NodeType;
  label: string;
  url?: string; // URLs reales: "/soluciones/data-engineering", "/sectores/finanzas"
  attributes: {
    slug?: string;
    description?: string;
    features?: string[];
    techStack?: string[];
    proofPoints?: Array<{ client: string; result: string }>;
    problems?: Array<{ code: string; label: string; solution: string }>;
    kpis?: Array<{ metric: string; expectedRoi: string }>; // De donde sale el [EST]
    roiCases?: Array<{ title: string; description?: string; metrics: Array<{ value: string; label: string }> }>;
    regulations?: string[];
    roleEquivalents?: Record<string, string>; // roleId -> nombreLocal (ej: "cfo" -> "Director de Finanzas")
    objectionResponses?: Array<{ objection: string; response: string }>;
    decisionCriteria?: string[];
  };
}

export type EdgeRelation = 
  | 'relatedPillars' 
  | 'pillarsOfInterest' 
  | 'relevantSectors' 
  | 'relevantPersonas' 
  | 'roleEquivalent';

export interface GraphEdge {
  source: string; // Node ID resuelto (ej: "persona-cfo")
  target: string; // Node ID resuelto (ej: "sector-finanzas")
  relation: EdgeRelation;
  weight: number; // Relevancia por defecto (1.0)
  metadata?: Record<string, any>;
}
```

## 2. Contrato de Anclaje de Nodos y Conexión con Web Worker (`public/worker.js`)

El anclaje de nodos opera mediante un motor estructurado por **SLOTS** (Persona + Sector + Pilar) con prioridad determinista por token de rol exacto (`cfo`, `cio`, `ceo`) y alias de alta precisión (registrado en **TD-025.4**). Mantiene preparado el contrato del Web Worker para inferencia semántica por embeddings:

### Petición de Anclaje de Nodos (Worker Interface)
```typescript
interface WorkerGraphAnchorRequest {
  type: 'search';
  query: string;
  corpusTexts: string[]; // Cadenas formadas por node.label + node.attributes.description
  id: string; // `graph-anchor-${seq}-${timestamp}`
}
```

### Respuesta del Worker (`worker.js` &rarr; Cliente)
```typescript
interface WorkerGraphAnchorResponse {
  status: 'complete'; // Propiedad real devuelta por public/worker.js
  id: string;
  query: string;
  results: Array<{
    index: number; // Índice correspondiente en el array corpusTexts enviado
    score: number; // Similitud coseno (Filtrado por score >= 0.35)
  }>;
}
```

---

## 3. Estructura del Subgrafo de Contexto Recopilado (`ContextSubgraph`)

```typescript
export interface ContextSubgraph {
  anchors: string[]; // Node IDs iniciales
  visitedNodes: GraphNode[];
  activeEdges: GraphEdge[];
  citedUrls: string[]; // Lista deduplicada de URLs reales (/soluciones/* y /sectores/*)
  groundedFacts: {
    solutions: string[];
    sectorContexts: string[];
    personaPainsAndObjections: Array<{ objection: string; response: string }>;
    kpisAndRoi: Array<{ metric: string; expectedRoi: string; tagged: '[EST]' }>;
  };
}
```

---

## 4. Esquema del Evento de Telemetría (Contrato Plano de `track_event.php` & PII §6)

Para respetar el contrato de `track_event.php` y garantizar la redacción automática de PII (§6), el evento utiliza **`event_type: 'search_query'`** con **`event_target: 'graphrag'`** y empaqueta la metadata del subgrafo como un JSON string en **`event_value`**:

```json
{
  "session_id": "a1b2c3d4e5f678901234567890abcdef",
  "event_type": "search_query",
  "event_target": "graphrag",
  "event_value": "{\"query\":\"cfo banca objecion costo roi\",\"anchors\":[\"persona-cfo\",\"sector-finanzas\"],\"subgraph_nodes_count\":5,\"subgraph_edges_count\":6,\"cited_urls\":[\"/soluciones/data-engineering\",\"/sectores/finanzas\"],\"has_insufficient_data\":false,\"hops\":2}"
}
```
