# Walkthrough: Fábrica de Contenido (Spec 012 - Fase 1)

He completado exitosamente la implementación de la Fase 1 para la **Spec 012**, respetando rigurosamente todos los principios no negociables y aplicando las correcciones indicadas.

## 1. Actualización del Modelo de Datos (Colecciones Astro 5)
- Agregué `pagesCollection` directamente en **`src/content.config.ts`**.
- Utilicé la API moderna de Astro 5: `loader: glob({ pattern: "**/*.md", base: "./src/content/pages" })`.
- El esquema Zod asegura que el frontmatter contenga `hero`, `seo`, `sections` y obliga a un default de `draft: true`.

## 2. Plantilla de Render Seguro
- Creé el archivo dinámico **`src/pages/[...slug].astro`**.
- Implementé el filtro de `draft` dentro de `getCollection` para evitar que los borradores se rendericen en producción.
- Incorporé el diseño de componentes `glass-card` y `brandCyan`, mapeando la estructura de arrays desde el frontmatter directamente al HTML. Además, se añade un banner amarillo para advertir de que la página está en "Modo Borrador" cuando se visualiza en desarrollo.

## 3. Generador de Contenido Multi-LLM
- Se actualizó el motor **`docs-generator.mjs`** añadiendo soporte para el argumento `--target=page`.
- El script lee la taxonomía actual y mediante el prompt system fuerza al modelo seleccionado a producir un JSON válido que cumple con Zod.
- Inyecta `draft: true` en el frontmatter antes de grabar.

## 4. Evidencia de Ejecución Real

> [!NOTE]
> **Bloqueo Preventivo en Producción Probado:**
> 1. Al ejecutar el build con el archivo en `draft: true`, Astro generó **24 páginas**. El borrador fue correctamente purgado de la compilación estática de producción.
> 2. Al editar manualmente a `draft: false` y reconstruir, Astro detectó y generó **25 páginas** (`/pagina-muestra/index.html` incluida).

### Diff de Responsabilidades
- **El agente LLM (`docs-generator`)** solo modificó el directorio de contenido: `src/content/pages/pagina-muestra.md`.
- **La Lógica:** Los archivos estructurales (`.astro`, `.mjs`, `.ts`) permanecieron inmutables durante la generación de la página, manteniendo la estricta seguridad del sitio.

Todo el ecosistema multi-página es ahora dinámico, seguro y automatizable.
