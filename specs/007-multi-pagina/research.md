# Research: SEO & Dynamic Routing in Astro

## Decisiones Técnicas

### 1. Sistema de Datos (JSON vs Content Collections)
- **Decision:** Archivo estático `src/data/taxonomyCorpus.json`.
- **Rationale:** A diferencia de la OpenWiki que usa Markdown/MDX (ideal para Content Collections), la taxonomía de servicios es una estructura matricial (6 pilares) que contiene copys persuasivos generados por los Agentes 02 y 05. Un JSON estructurado permite iterar fácilmente en componentes React/Astro y pasar las propiedades exactas al Schema.org y metadatos sin parseo complejo.
- **Alternatives considered:** Content Collections para servicios (rechazado por ser menos rígido que un contrato JSON para datos comerciales).

### 2. Componentización de SEO
- **Decision:** Crear un componente `SEO.astro`.
- **Rationale:** Cada solución (`/soluciones/[id]`) necesita `<title>`, `<meta description>`, OpenGraph, Twitter Cards y un JSON-LD tipo `Service`. Centralizar esto en un componente reutilizable asegura que ninguna página carezca de atributos críticos, blindando el criterio de éxito SC-02.

### 3. Contextualización del Wizard
- **Decision:** Pasar el contexto vía URL Query Params (`?servicio=[id]`).
- **Rationale:** Cuando el usuario haga clic en el CTA de `/soluciones/hiperautomatizacion`, la URL será `/diagnostico?servicio=hiperautomatizacion`. El Wizard leerá este parámetro durante el montaje (`useEffect`) y seteará el estado inicial en Nano Stores (`lastUserQuery`), garantizando que la IA arranque la conversación con contexto (FR-04).
