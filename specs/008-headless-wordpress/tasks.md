# Tasks: Headless WordPress (Backend CMS)

**Branch**: `008-headless-wp` | **Spec**: [Spec 008](file:///c:/xampp/htdocs/datanestiq/specs/008-headless-wp/spec.md)

## Phase 1: Entorno y Core
- `[ ]` T001 Instalar WordPress local (o validar entorno existente en `/datanestiq`).
- `[ ]` T002 Crear y activar Theme "Empty" (headless) para deshabilitar SSR de WordPress.
- `[ ]` T003 Instalar plugins core: ACF PRO, WPGraphQL (o WP REST API extensions) y Custom Post Type UI.

## Phase 2: Arquitectura de Datos (CPTs & Fields)
- `[ ]` T004 Registrar CPT `Servicios` con jerarquía y metadatos SEO.
- `[ ]` T005 Registrar CPT `Leads` (privado) para captura desde el Frontend.
- `[ ]` T006 Registrar CPT `Prompt Logs` (privado) para telemetría de IA.
- `[ ]` T007 Configurar campos ACF para el CPT Servicios (Pilares vs Sectores, descripciones, hooks comerciales).

## Phase 3: APIs y Endpoints
- `[ ]` T008 Configurar WPGraphQL / REST API endpoints para exponer el catálogo completo de Servicios públicamente.
- `[ ]` T009 Crear un endpoint seguro `POST /wp-json/datanestiq/v1/lead` para recepción de datos del Wizard/Chatbot (requiere validación CORS y Nonce/Token).
- `[ ]` T010 Hardening: Restringir acceso a la API REST de usuarios no autenticados y bloquear listado de usuarios (`/wp-json/wp/v2/users`).

## Phase 4: Integración Frontend (Astro)
- `[ ]` T011 Configurar variables de entorno (`PUBLIC_WP_URL`, `WP_SECRET_TOKEN`) en `.env` del frontend.
- `[ ]` T012 Modificar `src/pages/soluciones/[slug].astro` para consumir la API de WP (getStaticPaths basado en WordPress) en lugar de `taxonomyCorpus.json`.
- `[ ]` T013 Modificar `DiagnosticWizard.jsx` y `Chatbot.jsx` para despachar el Lead final (nombre, email, sector, desafío) hacia el endpoint seguro de WP.

## Phase 5: Validación
- `[ ]` T014 Realizar pruebas E2E desde captura de lead en Astro hasta aparición en WP Admin (`Leads`).
- `[ ]` T015 Ejecutar build de Astro comprobando regeneración estática (SSG) de páginas.
