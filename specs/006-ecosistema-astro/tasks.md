# Tasks: Ecosistema Web con Astro

**Branch**: `006-ecosistema-astro` | **Spec**: [Spec 006](file:///c:/xampp/htdocs/datanestiq/specs/006-ecosistema-astro/spec.md)

## Phase 1: Setup
- [X] T001 Inicializar proyecto Astro en el subdirectorio o raíz con `npm create astro@latest` (configuración SSG).
- [X] T002 Instalar integraciones base de Astro: `@astrojs/tailwind`, `@astrojs/react` (si se usan islas React) y `tailwindcss`.
- [X] T003 Configurar `astro.config.mjs` para incluir las integraciones de Tailwind y React.

## Phase 2: Foundational (Layout & Global Styles)
- [X] T004 [P] Configurar el esquema de Zod en `src/content/config.ts` para la colección `openwiki`.
- [X] T005 Migrar la configuración de tipografía y variables de Tailwind de `prototype/styles.css` hacia `tailwind.config.mjs` y `src/styles/global.css`.
- [X] T006 Crear el layout base `src/layouts/BaseLayout.astro` con semántica HTML5 y estructura de metadatos SEO.

## Phase 3: [US-02] Carga Instantánea en Redes Lentas sin FOUC (Migración Estática)
*Goal*: Portar la landing page del prototipo hacia componentes de Astro 100% estáticos (0 JS).
- [X] T007 [P] [US-02] Migrar la Navbar a un componente de Astro puramente estático en `src/components/ui/Navbar.astro`.
- [X] T008 [P] [US-02] Migrar el Footer a un componente estático en `src/components/ui/Footer.astro`.
- [X] T009 [P] [US-02] Migrar la sección Hero del prototipo hacia `src/components/ui/Hero.astro`.
- [X] T010 [P] [US-02] Migrar la sección Services (Taxonomía) hacia `src/components/ui/Services.astro`.
- [X] T011 [US-02] Ensamblar la página principal estática integrando los componentes migradas en `src/pages/index.astro`.

## Phase 4: [US-03] Interactividad bajo Demanda sin Bloqueo (Astro Islands)
*Goal*: Implementar el Chatbot y el Wizard interactivo como islas aisladas hidratadas on-demand.
- [X] T012 [P] [US-03] Migrar el componente del Diagnostic Wizard a React o Preact en `src/components/islands/DiagnosticWizard.jsx`.
- [X] T013 [P] [US-03] Migrar el Chatbot a React o Preact en `src/components/islands/Chatbot.jsx`.
- [X] T014 [US-03] Inyectar las islas en `src/pages/index.astro` utilizando las directivas `client:load` y `client:visible`.
- [X] T015 [US-03] Configurar Nano Stores en `src/store/index.ts` para compartir el estado (ej. respuestas del chatbot/wizard) entre islas.

## Phase 5: [US-01] Indexación Perfecta para Motores de Búsqueda (OpenWiki SSG)
*Goal*: Generar rutas dinámicas SSG leyendo la colección de Markdown de OpenWiki.
- [X] T016 [US-01] Crear el template de ruta dinámica `src/pages/wiki/[slug].astro` para renderizar archivos Markdown.
- [X] T017 [US-01] Implementar la función `getStaticPaths()` consumiendo `getCollection('openwiki')` en el template de ruta dinámica.

## Phase 6: Polish
- [X] T018 Ejecutar Lighthouse CI (`npm run audit:lighthouse`) de manera local sobre el build de Astro para verificar el 100/100.
- [ ] T019 Eliminar los archivos residuales del prototipo anterior en `prototype/` para completar la migración (Cancelado: se preserva como referencia histórica).
