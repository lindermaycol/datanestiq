# Archivos JSON Generados

⚠️ **ATENCIÓN: NO EDITAR ESTOS ARCHIVOS MANUALMENTE** ⚠️

Los archivos `taxonomyCorpus.json`, `sectorsCorpus.json` y `extendedIndustries.json` son **artefactos generados automáticamente** en tiempo de compilación. 

La fuente de verdad ahora reside en las colecciones de contenido (YAML) ubicadas en:
- `src/content/pillars/`
- `src/content/sectors/`
- `src/content/industries/`

Para hacer cambios en la taxonomía, los sectores o las industrias, por favor **edita los archivos YAML correspondientes**. 
Los JSON se regenerarán automáticamente gracias al script `scripts/build-taxonomy.mjs` que se ejecuta en los hooks `predev` y `prebuild` definidos en el `package.json`.

*(Nota: `personas.json` sí es un archivo fuente y puede editarse directamente).*
