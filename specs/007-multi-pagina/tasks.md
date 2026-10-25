# Tasks: Expansión Multi-Página de Soluciones

**Branch**: `007-multi-pagina` | **Spec**: [Spec 007](file:///c:/xampp/htdocs/datanestiq/specs/007-multi-pagina/spec.md)

## Phase 1: Setup & Data Layer
- [X] T001 Inicializar el archivo de base de datos en `src/data/taxonomyCorpus.json` con la estructura base de los pilares de servicio (extraídos de Spec 003).

## Phase 2: Foundational (SEO & Meta Components)
- [X] T002 [P] Crear el componente `src/components/ui/SEO.astro` para inyectar dinámicamente `<title>`, `<meta description>`, OpenGraph y Canonical tags.
- [X] T003 [P] Incorporar lógica en `src/components/ui/SEO.astro` para generar e inyectar el script JSON-LD de tipo `Service` basado en los props.
- [X] T004 Actualizar `src/layouts/BaseLayout.astro` (si existe, o crear `SolutionLayout.astro`) para recibir y delegar metadatos al componente `SEO.astro`.

## Phase 3: [US-01] Búsqueda y Navegación Directa (Dynamic Routing)
*Goal*: Generar subpáginas estáticas para cada pilar tecnológico de Datanestiq usando Astro SSG.
- [X] T005 [P] [US-01] Crear los componentes UI modulares `SolutionHero.astro` y `SolutionContrast.astro` en `src/components/ui/` para maquetar el contenido específico.
- [X] T006 [US-01] Crear la ruta dinámica `src/pages/soluciones/[id].astro`.
- [X] T007 [US-01] Implementar la función `getStaticPaths()` en la ruta dinámica para leer `taxonomyCorpus.json` y mapear los parámetros.
- [X] T008 [US-01] Componer la vista dentro de `[id].astro` utilizando el layout base y los componentes modulares (Hero, Contrast), pasándoles los datos de la taxonomía.

## Phase 4: [US-03] Continuidad del Contexto (Integración con Wizard)
*Goal*: Pasar el contexto del servicio visitado al Diagnostic Wizard para personalizar la experiencia.
- [X] T009 [P] [US-03] Actualizar los botones "Call to Action" en `[id].astro` y componentes asociados para que apunten a `/?servicio=[id]` o pasen el identificador.
- [X] T010 [US-03] Modificar `src/components/islands/DiagnosticWizard.jsx` para que capture el parámetro de búsqueda `?servicio` de la URL al montarse (usando `useEffect` o `URLSearchParams`).
- [X] T011 [US-03] Hacer que el Wizard inicialice el estado de `lastUserQuery` en Nano Stores si detecta un servicio en la URL, disparando el contexto del Chatbot.

## Phase 5: [US-02] Rastreo e Indexación
*Goal*: Asegurar la generación del sitemap y accesibilidad para bots (Googlebot).
- [X] T012 [US-02] Instalar y configurar la integración `@astrojs/sitemap` en `astro.config.mjs` (incluyendo la propiedad `site`).
- [X] T013 [US-02] Actualizar `src/components/ui/Services.astro` (Home) para que las tarjetas dirijan correctamente hacia las nuevas rutas dinámicas `/soluciones/[id]`.

## Phase 6: Polish
- [X] T014 Ejecutar un `npm run build` localmente y verificar que la carpeta `dist/soluciones/` contenga todos los HTML generados estáticamente.
- [X] T015 Ejecutar auditoría Lighthouse sobre una de las rutas generadas para validar el SEO Score 100/100 y la presencia del JSON-LD.
