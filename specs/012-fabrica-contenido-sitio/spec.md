# Feature Specification: Fábrica de Contenido del Sitio (Single-Shot)

**Feature Branch**: `[012-fabrica-contenido-sitio]`
**Created**: 2026-07-12
**Status**: Fase 2 en progreso

## 1. Visión General
Esta especificación define la arquitectura para generar dinámicamente páginas y secciones del sitio web utilizando el patrón **Single-shot controlado (Spec 010)**.

## 2. Principios Arquitectónicos
1. **Contenido vs. Código:** El agente escribe EXCLUSIVAMENTE Markdown+JSON Frontmatter en colecciones. NUNCA código `.astro`.
2. **Plantillas Fijas:** La UI se renderiza mediante rutas estáticas desarrolladas por humanos.
3. **Draft-Gated:** Todo contenido nuevo nace con `draft: true`. Requiere revisión manual mediante Pull Requests.
4. **Seguridad y Motor:** Reutiliza `docs-generator.mjs` de la Spec 010 con balanceo de llaves API.

## 3. Modelo de Datos (Colecciones Astro 5)
La colección `pages` utilizará Zod para validar la estructura del Frontmatter:
- `title`, `draft`, `seo`
- `hero` (headline, subheadline, cta)
- `sections` (array de objetos con tipos específicos: `features`, `benefits`, `faq`, `trust`, `content`, `testimonials`, `stats`, `cta`, `steps`, `logos`).

El Home es gestionado mediante un singleton (`src/content/pages/home.md`) cuyos datos se inyectan como properties en los componentes fijos (`index.astro`).

## 4. Flujo de Trabajo
- Los LLMs proponen contenido (drafts) ejecutados a través de GitHub Actions.
- La aprobación humana se realiza por revisión de Pull Request.
- Al mergear a `main`, la publicación en producción depende del **pipeline de deploy existente a IONOS**.
