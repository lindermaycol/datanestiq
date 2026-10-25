# Plan de Implementación: Enriquecimiento de Taxonomía v3 (Industrias, Personas y Madurez)

Este plan define la hoja de ruta técnica para llevar la taxonomía de Datanestiq a un estándar competitivo equiparable a IBM, Oracle, Microsoft y Deloitte, incorporando los ejes de Industria Extendida, Rol/Persona y Madurez Analítica (Gartner). Todo sin romper la lógica determinista y E2E actual.

## Propuesta Técnica

### PARTE 1: Cobertura Total de Industrias
- **[NEW] `src/data/extendedIndustries.json`**: Se creará un corpus de ~15 industrias que abarcan sectores económicos completos (Agricultura, Energía, Turismo, ONG, etc.). No tendrán landing propia ni tarjeta en el Home, serán exclusivamente `searchable`.
- **[MODIFY] `SemanticSearch.jsx`**:
  - Se inyectará `extendedIndustries` al Web Worker (prefijo `industry-`).
  - Al procesar `handleResults`, si el match de mayor puntaje es una industria extendida, se interceptará para encender visualmente los `relatedPillars` correspondientes (tarjetas de servicios actuales) y se mostrará un sutil badge informativo: *"Para el sector {Industria}, recomendamos estas soluciones"* debajo del input.

### PARTE 2: Taxonomía de Personas
- **[NEW] `src/data/personas.json`**: Se creará el catálogo del comité de decisión B2B (CFO, CIO, CDO, CTO, COO, CISO, CEO, CMO) y los tipos de organizaciones (Corporación, Mid-Market, etc.), cada uno con sus dolores (`pains`), metas (`goals`) y `pillarsOfInterest` enlazados.

### PARTE 3: Eje de Madurez Analítica (Gartner)
- **[MODIFY] `src/data/taxonomyCorpus.json`**: Se inyectará a cada uno de los 6 pilares actuales un `maturityStage` basado en la escalera de valor de Gartner (Descriptivo → Diagnóstico → Predictivo → Prescriptivo) y Fundamentos/Gobernanza.

### PARTE 4: Reflejo en UI (Estructura de Páginas)
- **[MODIFY] `src/pages/soluciones/[id].astro`**:
  - Nueva insignia en el Hero/Contrast para mostrar el `maturityStage`.
  - Nueva sección lateral (o bloque de contenido) *"Ideal para:"* que importe de `personas.json` la meta y dolor de los roles relacionados.
  - Bloque *"Casos de Uso por Industria"*: Listado cruzando la capacidad actual con 3-5 sectores (de `sectorsCorpus` o `extendedIndustries`).
- **[MODIFY] `src/pages/index.astro`**: 
  - Se añadirán dos nuevos bloques (reutilizando estilos `glass-card` aditivos, sin destruir el diseño):
    - **Soluciones por Industria**: Grid compacto apuntando a las verticales.
    - **Soluciones por Rol**: Botonera/Grid de tarjetas para CFO, CIO, etc.
- **[MODIFY] `src/pages/nosotros.astro`**:
  - Se inyectará un bloque de texto que explique la metodología anclada al Value Ladder de Gartner y estándares (DAMA, CRISP-DM).

### PARTE 5: Documentación
- **[MODIFY] `specs/003-taxonomia-servicios/tech_debt.md`**:
  - Anotar la deuda de llevar estas nuevas fuentes de verdad (`extendedIndustries`, `personas.json`) y el campo `maturityStage` hacia el futuro Schema de Zod (Astro Content Collections).

## Plan de Verificación (E2E)
1. **Search Invisible**: Buscar "tractor agricultura", verificar que no hay rediseño de home, sino un mensaje sutil en el buscador y el highlight correcto en los pilares.
2. **Rol/Páginas**: Verificar las nuevas secciones "Soluciones por Rol" y "Soluciones por Industria" en el Home (`npm run dev`).
3. **Build Exitoso**: Correr `npm run build` garantizando 0 errores y compilación SSG limpia.

¿Deseas que proceda con la implementación de estos 5 pasos?
