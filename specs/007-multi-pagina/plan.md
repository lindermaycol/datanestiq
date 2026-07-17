# Implementation Plan: Expansión Multi-Página de Soluciones

**Branch**: `007-multi-pagina` | **Date**: 2026-07-06 | **Spec**: [Spec 007](file:///c:/xampp/htdocs/datanestiq/specs/007-multi-pagina/spec.md)

**Input**: Feature specification from `/specs/007-multi-pagina/spec.md`

## Summary
Expansión de la arquitectura Astro SSG para soportar múltiples páginas de servicio (Landing Pages Long-Tail) basadas en la taxonomía oficial de Datanestiq (Spec 003). Se implementará un sistema de Dynamic Routing (`/soluciones/[id].astro`) que leerá el archivo `taxonomyCorpus.json` en tiempo de compilación. Cada página generada inyectará metadatos SEO específicos, JSON-LD estructurado, y conectará su "Call to Action" con el Diagnostic Wizard pasando el contexto del servicio (ej. `?servicio=[id]`).

## Technical Context
**Language/Version**: TypeScript / Astro 4.x
**Primary Dependencies**: `astro`, `zod`
**Storage**: `taxonomyCorpus.json` (Base de datos local en JSON)
**Target Platform**: SSG (Static Site Generation)
**Performance Goals**: 100/100 Lighthouse en cada URL generada.
**Constraints**: Todo el HTML debe ser pre-renderizado (Zero JS en layout), solo el Wizard será hidratado.
**Scale/Scope**: Generar 6+ URLs estáticas correspondientes a los pilares tecnológicos.

## Constitution Check
*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*
- [x] Cumple con el estándar de rendimiento (SSG puro, sin FOUC).
- [x] Soporta Schema.org y SEO Técnico Avanzado (Canonical, OpenGraph, JSON-LD).
- [x] Mantiene la UI Premium.

## Project Structure

### Documentation (this feature)
```text
specs/007-multi-pagina/
├── plan.md              
├── research.md          
├── data-model.md        
├── quickstart.md        
└── tasks.md             
```

### Source Code (repository root)
```text
src/
├── data/
│   └── taxonomyCorpus.json      # Fuente de verdad de los servicios
├── layouts/
│   └── SolutionLayout.astro     # Plantilla base para cada servicio
├── pages/
│   ├── index.astro              # (Update: Enlazar a las soluciones)
│   └── soluciones/
│       └── [id].astro           # Dynamic Route
└── components/
    └── ui/
        ├── SolutionHero.astro
        ├── SolutionContrast.astro
        └── SEO.astro            # Metaetiquetas y JSON-LD
```

**Structure Decision**: El proyecto adoptará un enrutador dinámico en `src/pages/soluciones/[id].astro` que llamará a `getStaticPaths()`. La data provendrá de un archivo JSON estático en `src/data/`.

## Complexity Tracking
| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Mover datos a JSON estático | Evita una BD externa para la taxonomía. | CMS Headless, rechazado aquí porque la Spec 008 se encargará de WordPress, por ahora Astro SSG con JSON es óptimo. |
