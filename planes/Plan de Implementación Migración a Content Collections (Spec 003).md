# Plan de Implementación: Migración a Content Collections (Spec 003)

Este plan aborda la resolución de la deuda técnica referida a la taxonomía, integrando Astro Content Collections y habilitando las nuevas landings SEO por sector.

## Problema y Contexto
- La taxonomía se encuentra fuertemente acoplada a archivos JSON (`src/data/*.json`). Esto dificulta su escalabilidad por parte del equipo de marketing sin riesgo a introducir errores de sintaxis.
- Además, aunque tenemos 10 sectores definidos en `sectorsCorpus`, no disponemos de landings individuales para capturar tráfico long-tail (ej. `/sectores/finanzas`).
- Sin embargo, **múltiples componentes React (Islas)** dependen de importar de forma síncrona estos JSON en tiempo de ejecución de Webpack/Vite. Si los eliminamos y pasamos 100% a las API de Astro, todas estas islas se romperían.

## Proposed Changes

### [Migración de Fuentes a YAML (Astro Content)]
Crearemos una estructura amigable para edición humana, extrayendo la data de los JSON hacia archivos individuales YAML.
- **[NEW]** `src/content/config.ts`: Esquemas Zod exhaustivos para `pillars`, `sectors`, e `industries`.
- **[NEW]** `src/content/pillars/*.yaml` (6 archivos).
- **[NEW]** `src/content/sectors/*.yaml` (10 archivos).
- **[NEW]** `src/content/industries/*.yaml` (14 archivos).

### [Script Generador y Validador (Build-time)]
En lugar de romper las Islas React, inyectaremos un paso intermedio que lea los YAML (fuente de verdad), los valide, garantice la integridad referencial y sobreescriba los JSON antiguos que actúan ahora como artefactos de compilación.
- **[NEW]** `scripts/build-taxonomy.mjs`:
  - Lee los YAML y valida usando Zod.
  - Verifica que todo `relatedPillars` de la industria apunte a un pilar existente.
  - Verifica que todo `pillarsOfInterest` en `personas.json` apunte a un pilar existente.
  - Si una referencia falla, emite un error y cancela el build (`process.exit(1)`).
  - Sobrescribe los archivos JSON en `src/data/`.
- **[MODIFY]** `package.json`: Modificar el comando `"build"` para incluir la ejecución previa del script.

### [Nuevas Páginas de Sector]
- **[NEW]** `src/pages/sectores/[id].astro`: Plantilla para el enrutamiento dinámico utilizando `getStaticPaths` sobre los sectores (con URLs estéticas como `/sectores/finanzas` omitiendo el prefijo `sector-`).
- Mostrará el Hero, el contraste, y un listado de los retos de negocio del sector (campo `problems`) interconectado con los Pilares (cross-linking B2B).
- **[MODIFY]** `src/components/islands/SolutionsByRoleAndIndustry.jsx`: Actualizar el grid de sectores para que navegue directamente hacia su respectiva landing page `/sectores/[id]`.

## Verification Plan
### Automated Tests
- Correr `node scripts/build-taxonomy.mjs` y comprobar que falla si introducimos intencionalmente un pilar inválido.

### Manual Verification
- `npm run dev`: Comprobar que el chatbot, buscador semántico y los wizards funcionan 100% con cero regresiones de red (cero llamadas).
- Modificar una letra en un `.yaml` y verificar que, al correr el build, el JSON se actualice sin errores y se refleje en la web.
- Ejecutar `npm run build` para asegurar la existencia de las +22 páginas generadas.

## Open Questions / User Review Required
> [!IMPORTANT]
> - ¿Consideras prudente instalar la librería `js-yaml` para que el script `build-taxonomy.mjs` procese ágilmente los archivos YAML fuera del contexto Astro?
> - Las URLs de sector se limpiarán de prefijos (de `sector-finanzas` a `/sectores/finanzas`). ¿Estás de acuerdo con esta convención para SEO?
