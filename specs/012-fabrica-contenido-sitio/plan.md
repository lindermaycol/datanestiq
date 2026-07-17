# Implementation Plan: Fábrica de Contenido (Fases 1 y 2)

## Fase 1: Páginas Base (COMPLETADO)
- [x] Configuración de Zod Schema en `src/content.config.ts`.
- [x] Catch-all en `src/pages/[...slug].astro` filtrando drafts en producción.
- [x] Soporte para `--target=page` en el generador.

## Fase 2: Home Data-Driven y Flujo PR (ACTUAL)

### 1. Migración del Home (Paridad Visual)
- Crear el singleton `src/content/pages/home.md`.
- Excluir explícitamente `page.id !== 'home'` en el generador estático del catch-all (`src/pages/[...slug].astro`) para prevenir la ruta `/home/` duplicada.
- Refactorizar `src/pages/index.astro` para leer el frontmatter de `home.md`.
- Inyectar los datos en los componentes hijos (e.g. `<Hero />`, `<TrustLayer />`) mediante `Astro.props`, manteniendo los textos duros originales como defaults para garantizar una transición con **0% de cambio visual**.

### 2. Extensión de Tipos de Sección
- Actualizar `src/content.config.ts` extendiendo el esquema Zod `sections` para soportar nuevos tipos: `testimonials`, `stats`, `cta`, `steps`, `logos`. Se usarán campos `.optional()` para garantizar compatibilidad hacia atrás.
- Crear los correspondientes componentes `.astro` para estos tipos en `src/components/ui/`.

### 3. Flujo de Aprobación por PR
- Crear `.github/workflows/content-pr.yml` activado por `workflow_dispatch`.
- El runner ejecutará el script con llaves provistas vía **GitHub Secrets** (GROQ_API_KEY, DASHSCOPE_API_KEY, GEMINI_API_KEY - Acción requerida del usuario).
- Al fusionar el PR, el contenido ingresa a la rama principal (`main`). 
- **Despliegue:** El usuario publica en producción ejecutando su pipeline habitual SSH/IONOS. No se asume despliegue automático de Vercel/Netlify.
