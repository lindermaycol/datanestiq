# Plan de Implementación: Spec 012 (Fase 2)

Este documento detalla la arquitectura y decisiones para la Fase 2 de la Fábrica de Contenido (Home data-driven, extensión de secciones y flujo de aprobación por PR).

## Decisiones Arquitectónicas Resueltas

### 1. Modelo del Home: Singleton `home.md`
**Decisión:** Utilizaremos un documento singleton `home.md` dentro de la colección existente `pages` (slug: `home` o `index`).
**Justificación:** El Home de Datanestiq (`src/pages/index.astro`) contiene una estructura visual altamente compleja que intercala Islas Reactivas (`SemanticSearch`, `CopilotDemo`, `DiagnosticWizard`). Intentar abstraer todo el Home como "secciones genéricas" destruiría la paridad visual. En su lugar, el `home.md` contendrá un Frontmatter extendido con los textos específicos (`hero.headline`, `trustLayer.logos`, títulos de las secciones de las islas). El código de `index.astro` simplemente leerá este archivo e inyectará los datos en los componentes existentes.
**Paridad Visual Garantizada:** Los componentes como `Hero.astro` recibirán las propiedades vía `Astro.props`. Mantendremos los textos hardcodeados actuales como **valores por defecto** (fallbacks). Esto garantiza 100% de paridad visual de forma inmediata.

### 2. Extensión de Tipos de Sección
Extendemos el esquema Zod `type: z.enum(...)` de la colección `pages` con las siguientes secciones estratégicas, cada una con su respectivo componente `.astro` en `src/components/ui/`:
- `testimonials`: Para citas de clientes (requerirá campos `author`, `role`, `quote`).
- `stats`: Para métricas de alto impacto (ej. "45% Reducción de costos", "1.5M Ahorro").
- `cta`: Banner de llamado a la acción completo (requerirá `buttonText`, `url`).
- `steps`: Procesos iterativos o metodologías numeradas (array de pasos).
- `logos`: Grillas de partners, certificaciones o tecnologías (array de imágenes o nombres).

### 3. Flujo de Aprobación por PR (Workflow CI)
**Decisión:** Se implementará un **GitHub Actions Workflow** (`.github/workflows/generate-content-pr.yml`).
**Flujo Exacto:**
1. El workflow se dispara por un evento manual (`workflow_dispatch`) donde el usuario ingresa el target (`home` o `page`) y el `brief`.
2. El runner inyecta las llaves de API desde **GitHub Secrets** (aislando las credenciales del entorno local).
3. Corre `node scripts/docs-generator.mjs --target=...`.
4. El script genera o actualiza el archivo en `src/content/pages/` con `draft: true`.
5. El action utiliza `peter-evans/create-pull-request` para abrir un PR contra `main`.
6. **El humano revisa el PR**. Si aprueba los textos, cambia `draft: false` en el editor web de GitHub, hace commit y **merge**.
7. Al mergear, Vercel/Netlify reconstruye el sitio en producción, incluyendo el contenido verificado.

## Open Questions & User Review
> [!IMPORTANT]
> **Revisión Requerida:** Por favor, revisa estas 4 decisiones estratégicas (Singleton, Paridad Visual por Defaults, Tipos de Sección, Flujo vía GitHub Actions).
> 
> Si estás de acuerdo, haz clic en **Proceed** para que yo pueda actualizar los artefactos formales (`spec.md`, `plan.md`, `tasks.md` en la ruta de `/specs/012-fabrica-contenido-sitio/`) dejando todo listo para que tú des la orden de implementar el código.

## Fases de Implementación (Acciones a Tomar Post-Aprobación)

### Paso 1: Update Documental (Ahora)
- Actualizar `/specs/012-fabrica-contenido-sitio/spec.md`, `plan.md` y `tasks.md` con estas definiciones.

### Paso 2: Ejecución de Código (Posterior)
1. **Zod:** Actualizar `src/content.config.ts` (Astro 5 API) con los nuevos enums de `sections` y la flexibilidad para el frontmatter del Home.
2. **Componentes UI:** Crear los nuevos componentes `.astro` (ej. `Testimonials.astro`, `Stats.astro`).
3. **Migración Home:**
   - Crear `src/content/pages/home.md` con los textos actuales.
   - Refactorizar `src/pages/index.astro` y sus subcomponentes para aceptar props (con defaults).
4. **Tooling:** Modificar `docs-generator.mjs` para poder actualizar o regenerar `home.md`.
5. **CI/CD:** Crear el archivo `.github/workflows/content-pr.yml`.
