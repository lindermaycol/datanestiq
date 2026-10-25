# Data Model: Content Collections (OpenWiki)

Astro gestiona los datos de los archivos Markdown internamente a través de Zod.

## Esquema Zod (Frontmatter)
La colección `openwiki` debe validar los metadatos de los artículos técnicos generados por la Spec 005.

```typescript
import { z, defineCollection } from 'astro:content';

const openwikiCollection = defineCollection({
  type: 'content',
  schema: z.object({
    title: z.string().max(100),
    description: z.string().max(160), // Límite SEO
    author: z.string().default('AI Documenter'),
    lastUpdated: z.date(),
    tags: z.array(z.string()),
    seoScore: z.number().min(0).max(100)
  })
});

export const collections = {
  'openwiki': openwikiCollection,
};
```

## Relación con Spec 005
Cada archivo Markdown generado automáticamente por el flujo CI/CD debe inyectar un frontmatter que cumpla estrictamente con este esquema Zod; de lo contrario, el build de Astro (y consecuentemente el despliegue) fallará de forma segura.
