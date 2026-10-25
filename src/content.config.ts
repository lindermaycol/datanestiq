import { z, defineCollection } from 'astro:content';
import { glob } from 'astro/loaders';

const wikiCollection = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/wiki" }),
  schema: z.object({
    title: z.string().max(100),
    description: z.string().max(160),
    author: z.string().default('AI Documenter'),
    lastUpdated: z.date(),
    tags: z.array(z.string()),
    seoScore: z.number().min(0).max(100).default(100)
  })
});

const blogCollection = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/blog" }),
  schema: z.object({
    title: z.string().max(120),
    description: z.string().max(200),
    pubDate: z.date(),
    author: z.string().default('Datanestiq'),
    tags: z.array(z.string()).default([]),
    draft: z.boolean().default(false)
  })
});

const pagesCollection = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/pages" }),
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
      type: z.enum(['features', 'benefits', 'faq', 'trust', 'content', 'testimonials', 'stats', 'cta', 'steps', 'logos']),
      heading: z.string().optional(),
      body: z.string().optional(),
      items: z.array(z.string()).optional(),
      author: z.string().optional(),
      role: z.string().optional(),
      quote: z.string().optional(),
      buttonText: z.string().optional(),
      url: z.string().optional(),
      value: z.string().optional(),
      label: z.string().optional(),
      logos: z.array(z.object({ src: z.string(), alt: z.string() })).optional()
    }))
  })
});

export const collections = {
  'wiki': wikiCollection,
  'blog': blogCollection,
  'pages': pagesCollection
};
