# Tasks: Spec 012 - Fábrica de Contenido

## Fase 1: Arquitectura y Prueba de Concepto
- [x] Actualizar `src/content.config.ts` con Zod Schema para la colección `pages`.
- [x] Crear la plantilla Astro `src/pages/[...slug].astro` con renderizado seguro.
- [x] Modificar `scripts/docs-generator.mjs` para soportar `--target=page` y forzar output de drafts.
- [x] Validar comportamiento de drafts en producción.

## Fase 2: Home Data-Driven y Flujo PR
- [ ] Zod: Extender el esquema Zod en `src/content.config.ts` para nuevos campos de sección.
- [ ] Seguridad: Excluir `'home'` de `getStaticPaths()` en `[...slug].astro`.
- [ ] Contenido: Crear `src/content/pages/home.md` con los textos actuales del Home.
- [ ] UI: Refactorizar `src/pages/index.astro` y subcomponentes (`Hero`, `TrustLayer`, `Services`) para soportar props con fallbacks.
- [ ] UI: Crear componentes para las nuevas secciones (`Testimonials.astro`, `Stats.astro`, `CtaBanner.astro`, `Steps.astro`, `Logos.astro`).
- [ ] Validación: Correr `npm run build` y validar que `/home` no se genera y el home `/` es exacto visualmente.
- [ ] CI: Escribir `.github/workflows/content-pr.yml`.
