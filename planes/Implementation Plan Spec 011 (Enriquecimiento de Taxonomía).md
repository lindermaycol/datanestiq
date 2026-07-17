# Implementation Plan: Spec 011 (Enriquecimiento de Taxonomía)

El objetivo es ampliar drásticamente la densidad ontológica de la taxonomía del proyecto sin romper los esquemas actuales, manteniendo compatibilidad hacia atrás en los endpoints de las interfaces (islas React) de Astro, al tiempo que alimentamos de briefs listos para usarse al motor multi-destino de la Spec 010.

## User Review Required

> [!WARNING]
> La taxonomía es la fuente única de verdad para el Frontend, los Endpoints JSON y ahora los Agentes Generadores (Docs/Blog). Alteraciones abruptas romperían el sitio web estático.
>
> Revisar y confirmar los siguientes puntos propuestos antes de que inicie la implementación (Fase 2 de este plan):

1. **Aprobación de la estructura de Zod:** Todo será `optional()` (aditivo). Ningún campo existente será borrado.
2. **Profundidad / Alcance inicial:** Para evitar crecer el megabyte de `taxonomyCorpus.json` y `sectorsCorpus.json` cargados en memoria por las islas, propongo poblar profundamente **solo un sector (ej. gobierno o salud)**, **un pilar (ej. AI & Data)**, y **una persona (ej. CIO)**, en esta primera etapa de validación. ¿Te parece bien este alcance inicial o prefieres que aplique esto a los 10 sectores inmediatamente?
3. **Manejo de estimaciones:** Los valores financieros o tamaños de mercado usarán el prefijo `[EST]` seguido del dato, ej. `[EST] $1.5M ahorrados`.

## Open Questions

> [!IMPORTANT]
> 1. Para la inyección de `contentAngles`, ¿quieres un archivo JSON separado (`content-angles.json`) para evitar que el payload del frontend en las islas de React pese más, o prefieres embeber la rama `contentAngles` directamente en los YAML de Pilares, Sectores y Personas y purgarla temporalmente si excede tamaño? (El plan actual la incluye embebida).

## Proposed Changes

### Zod Schemas (`src/lib/schemas.js`)

Se inyectarán propiedades opcionales a `sectorSchema`, `personaSchema` y `pillarSchema`.

#### [MODIFY] [schemas.js](file:///C:/xampp/htdocs/datanestiq/src/lib/schemas.js)
```diff
 export const sectorSchema = z.object({
   id: z.string(),
...
+  subSectors: z.array(z.string()).optional(),
+  kpis: z.array(z.object({ metric: z.string(), expectedRoi: z.string() })).optional(),
+  regulations: z.array(z.string()).optional(),
+  contentAngles: z.array(z.object({ id: z.string(), title: z.string(), brief: z.string() })).optional(),
 });
```
```diff
 export const personaSchema = z.object({
...
+      buyerRole: z.enum(['economic', 'technical', 'user']).optional(),
+      objections: z.array(z.string()).optional(),
+      decisionCriteria: z.array(z.string()).optional(),
+      triggers: z.array(z.string()).optional(),
+      contentAngles: z.array(z.object({ id: z.string(), title: z.string(), brief: z.string() })).optional(),
```
```diff
 export const pillarSchema = z.object({
...
+  techStack: z.array(z.string()).optional(),
+  proofPoints: z.array(z.object({ client: z.string(), result: z.string() })).optional(),
+  competitivePositioning: z.string().optional(),
+  contentAngles: z.array(z.object({ id: z.string(), title: z.string(), brief: z.string() })).optional(),
```

### Contenido YAML

Poblaré **al menos 1 nodo por cada tipo de esquema**, inyectando casos reales, KPIs estimativos (marcados) y un `contentAngle`.

#### [MODIFY] Sectores (ej. `gobierno.yaml` o `salud.yaml`)
Añadir `subSectors`, `kpis`, `regulations`, `contentAngles`.

#### [MODIFY] Personas (`src/data/personas.json`)
Añadir al menos a un rol el `buyerRole` y los `contentAngles`.

#### [MODIFY] Pilares (ej. `ai-data.yaml`)
Añadir `techStack`, posicionamiento competitivo y `contentAngles`.

## Verification Plan

### Automated Tests
- Validaré integridad referencial manipulando deliberadamente un ID en `sectors/` y corriendo `node scripts/build-taxonomy.mjs` para verificar el `exit 1`.
- Compilación Astro segura con `npm run build` confirmando no-regresión (0 errores, 100% páginas renderizadas).

### Manual Verification
- Validar el flujo real del pipeline: extraer un brief recién escrito de la taxonomía, mandárselo a `node scripts/docs-generator.mjs --target=blog --brief="<brief>"` y verificar que el post resultante sea congruente con el brief ontológico.
