# Plan de Migración a Content Collections: Taxonomía y Sectores (Spec 003)

## Objetivos
1. Migrar la taxonomía B2B hardcodeada en `src/data/*.json` a **Astro Content Collections** (YAML) validadas estrictamente con Zod, permitiendo edición segura por parte de Marketing.
2. Mantener cero disrupción para los componentes React (Islas) generando artefactos JSON idénticos en tiempo de compilación.
3. Crear páginas de aterrizaje individuales para cada uno de los 10 sectores (`/sectores/[id]`) capitalizando SEO long-tail.
4. Validar integridad referencial (ejes cruzados) durante el build y romper la compilación si hay inconsistencias.

## Estrategia Técnica

### 1. Definición del Esquema (Zod)
Crearemos `src/content/config.ts` para definir las colecciones `type: 'data'` usando YAML:
- `pillars`: Esquema exhaustivo con `maturityStage`, `standards`, `targetRoles`, `skills`, `hero`, `contrast`, `features`, `keywords`, `copilotPrompts`.
- `sectors`: Esquema con `icon`, `ciiu`, `keywords`, `hero`, `contrast`, `problems`.
- `industries` (extendidas): Esquema con `ciiu`, `keywords`, `relatedPillars`.
*Nota sobre Personas:* `personas.json` se mantendrá como JSON por ser configuración estructural, pero se validará en el script de build.

### 2. Extracción a YAML
Convertiremos `taxonomyCorpus.json`, `sectorsCorpus.json` y `extendedIndustries.json` en archivos `.yaml` individuales dentro de `src/content/pillars/`, `src/content/sectors/` y `src/content/industries/`. 

### 3. Generación y Validación de Integridad (`build-taxonomy.mjs`)
Crearemos un script `scripts/build-taxonomy.mjs` que:
1. Lea las carpetas de colecciones YAML usando un parser (ej. `js-yaml` o nativamente) o cargando vía Astro. (Como debe correr en pre-build, leeremos los archivos directamente con `fs` y `js-yaml`, y los validaremos usando los esquemas Zod exportados desde un archivo base).
2. Valide que todo `relatedPillars` (en industrias) y `pillarsOfInterest` (en personas) apunte a un `slug` real de pilar. Si no, `process.exit(1)` con un mensaje claro.
3. Escriba los JSON regenerados en `src/data/` (sobreescribiendo los originales).
4. Integraremos el script en `package.json` (`"prebuild": "node scripts/build-taxonomy.mjs"`, etc.).

### 4. Páginas de Sector (`/sectores/[id].astro`)
Usaremos `getStaticPaths` alimentado por la colección `sectors` (o el JSON generado).
Estructura de la landing:
- Reutilización de `SolutionHero` y `SolutionContrast`.
- Referencias de dominio (retos regulatorios/operativos) extrayendo datos de la clave `problems`.
- Cross-linking hacia los pilares.

### 5. Adaptación UI
En `SolutionsByRoleAndIndustry.jsx`, los clics en los 10 sectores principales navegarán a `/sectores/[id]` en lugar de enfocar el buscador (manteniendo el foco del buscador para industrias extendidas).

### 6. Cierre Documental
- Actualizar `specs/003-taxonomia-servicios/tech_debt.md` como resuelto.
- Actualizar `planes/ESTADO-SPECS.md` reflejando la nueva arquitectura.

## Open Questions / User Review Required
> [!IMPORTANT]
> **Páginas de Sector:** El prompt indica usar `/sectores/[id]`. Sin embargo, los `sectorsCorpus.json` actuales usan IDs como `sector-finanzas`. Si generamos URLs como `/sectores/sector-finanzas`, será redundante. Limpiaré el prefijo `sector-` para las URLs (ej. `/sectores/finanzas`) manteniendo el ID original en el JSON o unificando el prefijo en el JSON si es posible (en el JSON generado el ID será `sector-finanzas` para mantener compatibilidad, pero la URL será `/sectores/finanzas`). 
> ¿Estás de acuerdo con este enfoque?
