# Deuda Técnica: Spec 003 (Taxonomía de Servicios)

## Estado
- **Fase actual:** Content Collections validadas con Zod (fuente YAML, artefacto JSON) + 10 páginas de sector.
- **Impacto:** Medio
- **Severidad:** Debe resolverse antes de delegar la administración a personal no técnico.

## Lista de Deuda Técnica (Technical Debt)

### 0. Fragmentación de Datos y Enriquecimiento de Taxonomía - [RESUELTO]
- **Descripción:** La taxonomía no era la fuente única de verdad para las microinteracciones de IA (DiagnosticWizard, CopilotDemo, Chatbot, etc.), lo cual generaba hardcoding de sectores y prompts en los componentes React.
- **Riesgo:** Inconsistencia de datos, dificultad para mantener y escalar la oferta de servicios, y riesgo de contradicción entre el mensaje comercial y la funcionalidad del producto.
- **Resolución Aplicada:** Se enriqueció `taxonomyCorpus.json` y `sectorsCorpus.json` con `keywords`, `copilotPrompts`, `problems` y estructuras completas de SEO/Copy (hero, contrast). Todos los componentes interactivos se refactorizaron para inyectar estos JSON en tiempo de ejecución de manera determinista, erradicando el hardcoding. Adicionalmente, se ejecutó un enriquecimiento avanzado (multilingüe, códigos CIIU Rev.4, marcos de industria DAMA/CRISP-DM, roles/skills ESCO y expansión de keywords balanceadas) y una versión v3 de la taxonomía (cobertura total de industrias `extendedIndustries`, eje de Personas `personas.json` y eje de Madurez Analítica `maturityStage` de Gartner).

### 1. Hardcoding de Corpus (Base de Datos JSON) - [✅ RESUELTO]
- **Descripción:** La estructura de negocio vivía en el archivo estático JSON.
- **Riesgo:** Si marketing desea añadir un nuevo sector, debían editar JSON.
- **Estado:** ✅ **[RESUELTO]** - Migrado a Astro Content Collections (fuente YAML), validación estricta (Zod) e integridad referencial en pre-build (`scripts/build-taxonomy.mjs`). Los JSON son ahora artefactos generados. Las 10 páginas de sector están implementadas.

#### Diseño de Solución Técnica: Astro Content Collections
Para eliminar la dependencia del JSON estático y dotar al equipo de Marketing de una interfaz segura (basada en Markdown/YAML con validación estricta), se transicionará a la arquitectura **Content Collections** nativa de Astro.

**1. Definición de Esquema (Zod) - `src/content/config.ts`:**
```typescript
import { z, defineCollection } from 'astro:content';

const taxonomySchema = defineCollection({
  type: 'data', // Usamos YAML o JSON
  schema: z.object({
    id: z.string(),
    title: z.string(),
    description: z.string(),
    services: z.array(z.string()).optional(),
    keywords: z.array(z.string()),
    ciiu: z.string().optional(),
    standards: z.array(z.string()).optional(),
    targetRoles: z.array(z.string()).optional(),
    skills: z.array(z.string()).optional(),
    maturityStage: z.string().optional(),
    relatedPillars: z.array(z.string()).optional(), // Para industrias
  })
});

const personaSchema = defineCollection({
  type: 'data',
  schema: z.object({
    id: z.string(),
    title: z.string(),
    goals: z.array(z.string()),
    pains: z.array(z.string()),
    pillarsOfInterest: z.array(z.string())
  })
});

export const collections = {
  'pillars': taxonomySchema,
  'sectors': taxonomySchema,
  'extended-industries': taxonomySchema,
  'personas': personaSchema
};
```

**2. Estructura de Directorios para el Equipo:**
El equipo de marketing o contenido ya no tocará un JSON masivo. Crearán archivos `.yaml` individuales.
```text
src/
 └── content/
      ├── pillars/
      │    ├── data-engineering.yaml
      │    └── applied-ai.yaml
      └── sectors/
           ├── finance.yaml
           └── healthcare.yaml
```

**3. Agregación (Build Time):**
Se creará un Endpoit de API interno en Astro (`src/pages/api/taxonomyCorpus.json.ts`) que leerá todas las colecciones (`getCollection('pillars')`) en tiempo de compilación y generará el JSON final. 
De esta manera, el motor de NLP local (`Transformers.js`) del Frontend seguirá consumiendo su objeto JSON esperado sin requerir modificaciones en su lógica, pero el origen de datos será 100% seguro y tipado.
