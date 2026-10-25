# Spec 012: Fábrica de Contenido del Sitio (Fase 1)

Este documento contiene el plan de implementación SDD para la nueva **Spec 012**, que introduce la fábrica de contenido del sitio web basándose en el patrón **Single-shot controlado** de la Spec 010.

## User Review Required
> [!IMPORTANT]
> **Aprobación Requerida:** Por favor, revisa el diseño arquitectónico detallado a continuación. Una vez que lo apruebes, procederé con la implementación de la Fase 1 (crear las plantillas, el modelo, la actualización del generador y generar un borrador de muestra).

## Diseño Arquitectónico

### 1. Modelo de Contenido (Colecciones Astro)
Crearemos una nueva colección de Astro en `src/content/pages/` (y/o `src/content/sections/` para secciones reutilizables).
**Esquema Zod (`src/content/config.ts`):**
```typescript
import { defineCollection, z } from 'astro:content';

const pagesCollection = defineCollection({
  type: 'content',
  schema: z.object({
    title: z.string(),
    draft: z.boolean().default(true),
    seo: z.object({
      title: z.string(),
      description: z.string()
    }),
    hero: z.object({
      headline: z.string(),
      subheadline: z.string(),
      cta: z.string().optional()
    }),
    sections: z.array(z.object({
      type: z.enum(['features', 'benefits', 'faq', 'trust']),
      heading: z.string(),
      body: z.string(),
      items: z.array(z.string()).optional()
    }))
  })
});

export const collections = {
  'pages': pagesCollection
};
```
*Este enfoque asegura que el agente autónomo solo interactúe con datos (JSON/Markdown frontmatter) y nunca inyecte código en la aplicación.*

### 2. Plantilla de Render (Frontend)
Crearemos una ruta dinámica `src/pages/[...slug].astro` que renderice la colección `pages`.
La lógica incluirá un filtro crítico de seguridad:
```javascript
const pages = await getCollection('pages', ({ data }) => {
  return import.meta.env.PROD ? data.draft !== true : true;
});
```
*Esto garantiza que los borradores no sean publicados en producción.*

### 3. Target del Generador (`docs-generator.mjs`)
Ampliaremos el script `docs-generator.mjs` de la Spec 010 para admitir el nuevo target:
- `npm run generate -- --target=page --slug=mi-pagina`
- El script consumirá la taxonomía como contexto, enviará un prompt *single-shot* al LLM (usando el balanceo de 3 keys existente) solicitando un JSON válido según el esquema.
- El script escribirá el archivo en `src/content/pages/mi-pagina.md` forzando `draft: true`.

### 4. Flujo de Revisión (Draft → Publish)
1. El agente (vía webhook o local) corre el generador. Se crea el archivo markdown con `draft: true`.
2. El desarrollador o copywriter arranca el servidor local (`npm run dev`) y navega a la URL local (los drafts son visibles en modo dev).
3. Si requiere cambios, los edita manualmente en el Markdown.
4. Para publicar, cambia `draft: false`, comitea el archivo y el CI/CD pipeline publica los cambios en producción.

## Alcance Fase 1
- **Implementar** la configuración de la colección (`src/content/config.ts`).
- **Implementar** la plantilla Astro de renderizado (`src/pages/[...slug].astro`).
- **Modificar** el script generador (`scripts/docs-generator.mjs`) con `--target=page`.
- **Ejecutar** un `--dry-run` exitoso y luego **generar una página de muestra real** en estado `draft`.
- **Verificar** que la página aparece en `dev` pero NO se incluye en el `build` de producción.
- *Fuera de alcance:* Regenerar la página de inicio (home) o refactorizar las secciones actuales de la web.

---
Si estás de acuerdo con esta arquitectura, haz clic en **Proceed** para que yo escriba los documentos `spec.md`, `plan.md` y `tasks.md` en la ruta `/specs/012-fabrica-contenido-sitio/` y dejemos el trabajo preparado.
