# Adopción de OpenWiki de LangChain con Gemini (FASE 1)

Este plan detalla la adopción del CLI `langchain-ai/openwiki` en el repositorio, la configuración con Gemini (`gemini-2.5-flash`), y la separación limpia entre la documentación dirigida a agentes (OpenWiki de LangChain) y la documentación web para consumo humano (Astro).

## User Review Required

> [!IMPORTANT]
> El objetivo final de la **Fase 1** es ejecutar `langchain-ai/openwiki` en CI y generar documentación enriquecida en la carpeta `openwiki/` para que los agentes la lean. 
> La convergencia de esta salida estructurada hacia nuestro sistema web humano será un trabajo de **Fase 2** (para evitar un fuerte acoplamiento temprano).

> [!WARNING]
> Dado un conflicto de "peer dependencies" (`ERESOLVE`) en Astro al intentar instalar en el directorio raíz local, se verificó el CLI a través de una instalación temporal de la cual se verificaron comandos como `--help`. Se asume la validez técnica de `openwiki --update` pero requeriremos la confirmación de la prueba local o en CI con el modelo Gemini para validar los resultados reales en esta misma fase de ejecución.

## Open Questions

> [!NOTE]
> 1. He verificado localmente que el CLI acepta `--update`. El comando en CI será `openwiki --update`. ¿Estás de acuerdo con el uso de `--update` para CI en lugar de `--print`?
> 2. Una vez apruebes el plan, ejecutaré el flujo localmente inyectando tu API Key (o asumiéndola desde el `.env` existente si lo provees) para asegurar que se crea la carpeta `openwiki/` y se modifique el archivo de agentes. Luego integraré eso al PR.

## Proposed Changes

### Archivos de Planificación de Spec (Ya generados localmente)
#### [NEW] [specs/009-openwiki-langchain/spec.md](file:///C:/xampp/htdocs/datanestiq/specs/009-openwiki-langchain/spec.md)
#### [NEW] [specs/009-openwiki-langchain/plan.md](file:///C:/xampp/htdocs/datanestiq/specs/009-openwiki-langchain/plan.md)
#### [NEW] [specs/009-openwiki-langchain/tasks.md](file:///C:/xampp/htdocs/datanestiq/specs/009-openwiki-langchain/tasks.md)

### Higiene del Frontend (Astro)
Se renombra todo aquello relativo a la colección `openwiki` a `wiki` para no colisionar con la carpeta raíz `openwiki/` del CLI de LangChain.
#### [MODIFY] [src/content.config.ts](file:///C:/xampp/htdocs/datanestiq/src/content.config.ts)
#### [MODIFY] [scripts/docs-generator.mjs](file:///C:/xampp/htdocs/datanestiq/scripts/docs-generator.mjs)
(Renombramiento de `src/content/openwiki` a `src/content/wiki` y actualizaciones en páginas).

### Integración CI y Generación Inicial
#### [NEW] [.github/workflows/openwiki-langchain.yml](file:///C:/xampp/htdocs/datanestiq/.github/workflows/openwiki-langchain.yml)
#### [NEW] openwiki/ (Carpeta generada automáticamente por el CLI)
#### [MODIFY] AGENTS.md (Anexado por el CLI)
#### [MODIFY] [planes/ESTADO-SPECS.md](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md) (Actualización de fila 009)

## Verification Plan

### Automated Tests
- En CI, el pipeline `openwiki-langchain.yml` correrá y abrirá un PR con la carpeta `openwiki/` y el archivo de agentes modificado.
- El build estático `npm run build` debe culminar exitosamente demostrando que las 22 páginas previas siguen compilando correctamente en Astro y la ruta `/wiki/arquitectura` está viva.

### Manual Verification
- Inspección manual del repositorio local resultante de ejecutar la herramienta: Validaremos que `openwiki/` se pobló con información basada fielmente en el código del repositorio actual, y el `AGENTS.md` o `CLAUDE.md` fueron anexados correctamente sin sobreescritura destructiva.
