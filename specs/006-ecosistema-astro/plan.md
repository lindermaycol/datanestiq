# Implementation Plan: Ecosistema Web con Astro

**Branch**: `006-ecosistema-astro` | **Date**: 2026-07-06 | **Spec**: [Spec 006](file:///c:/xampp/htdocs/datanestiq/specs/006-ecosistema-astro/spec.md)

**Input**: Feature specification from `/specs/006-ecosistema-astro/spec.md`

## Summary
Migración del prototipo monolítico HTML/Vanilla JS hacia un framework robusto con Astro SSG. Se aplicará una arquitectura de Astro Islands para aislar la interactividad (Chatbot, Wizard) usando `client:visible` / `client:load`, se implementarán Content Collections con Zod para el tipado estricto de la OpenWiki (Markdown), y se integrará Tailwind CSS nativo para garantizar una calificación perfecta 100/100 en Lighthouse con 0KB de JavaScript bloqueante inicial.

## Technical Context
**Language/Version**: TypeScript / Astro 4.x
**Primary Dependencies**: `astro`, `@astrojs/tailwind`, `tailwindcss`, `zod`, `nanostores` (gestión de estado entre islas)
**Storage**: Content Collections (Markdown/MDX in `src/content/`)
**Testing**: Pruebas de Lighthouse CI (Performance, A11Y, SEO)
**Target Platform**: Servidor Estático / Edge CDN
**Project Type**: SSG Web Application (Static Site Generation)
**Performance Goals**: FCP < 1s, LCP < 1.5s, 0 FOUC, 100/100 Lighthouse
**Constraints**: 0KB JS bloqueante por defecto; solo cargar JS interactivo mediante directivas Astro.
**Scale/Scope**: Migración de Home page interactiva y soporte dinámico para `n` artículos de OpenWiki.

## Constitution Check
*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*
- [x] Cumple con el estándar de rendimiento (Lazy loading / Astro Islands).
- [x] Elimina CDNs externos, integrando Tailwind de forma nativa.
- [x] Soporta Schema.org y SEO Técnico Avanzado.
- [x] Mantiene la UI Premium (GSAP, Glassmorphism).

## Project Structure

### Documentation (this feature)
```text
specs/006-ecosistema-astro/
├── plan.md              
├── research.md          
├── data-model.md        
├── quickstart.md        
└── tasks.md             
```

### Source Code (repository root)
```text
# Estructura del Ecosistema Astro
src/
├── components/
│   ├── ui/             # Componentes estáticos puros
│   └── islands/        # Chatbot.jsx, Wizard.jsx (Islas interactivas)
├── content/
│   ├── config.ts       # Esquemas de Zod para colecciones
│   └── openwiki/       # Archivos Markdown
├── layouts/
│   └── BaseLayout.astro
├── pages/
│   ├── index.astro
│   └── wiki/
│       └── [slug].astro # Generación estática SSG
└── styles/
    └── global.css      # Directivas Tailwind
astro.config.mjs
tailwind.config.mjs
```

**Structure Decision**: El proyecto adoptará la estructura estándar de un framework Astro, segmentando explícitamente los componentes en `ui/` (estáticos) e `islands/` (interactivos) para forzar las mejores prácticas de rendimiento.

## Complexity Tracking
| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A | N/A | N/A |
