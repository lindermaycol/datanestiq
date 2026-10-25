# Plan de Implementación: Spec 009 (OpenWiki LangChain - Fase 1)

## 1. Objetivo
Integrar la herramienta oficial `langchain-ai/openwiki` en el flujo de CI para generar documentación automatizada para agentes de IA (en `openwiki/` y `AGENTS.md`), utilizando el modelo `gemini-2.5-flash` de Google. 

## 2. Decisiones Técnicas (SDD)
- **Comando no interactivo para CI:** Se utilizará el comando `openwiki --update` para la ejecución en CI, ya que refresca la documentación basada en cambios recientes en modo no interactivo.
- **Archivos a commitear:** Se commiteará toda la carpeta `openwiki/` generada, así como los cambios anexados a `AGENTS.md` o `CLAUDE.md`, empaquetados en un Pull Request automatizado.
- **Alcance y Costo:** El tool escaneará el subconjunto relevante del repositorio (excluyendo dependencias, configuraciones ignoradas y la carpeta del wiki humano) usando `gemini-2.5-flash`. El costo se mantendrá bajo y controlado gracias al uso del modelo `flash`.
- **Numeración y Dependencias:** Se crea la Spec `009-openwiki-langchain`. Se actualizará `planes/ESTADO-SPECS.md` añadiendo la fila correspondiente y estableciendo la nota explícita de que Spec 005 (Fase 2) dependerá de Spec 009.

## 3. Resolución de Riesgos y "Higiene"
- **Higiene del Frontend:** Para evitar colisiones y mantener una separación semántica estricta, la colección Astro en `src/content/openwiki/` se renombrará a `src/content/wiki/`. Se validará con `npm run build` que ninguna página se rompa (se deben conservar las 22 páginas y la ruta `/wiki/arquitectura`).
- **Secretos:** La configuración se pasará por variables de entorno dentro del Action de GitHub (`OPENWIKI_PROVIDER`, `OPENAI_COMPATIBLE_BASE_URL`, `OPENWIKI_MODEL_ID`), y se inyectará el secreto de GitHub `GEMINI_API_KEY`. No se versionará ningún archivo `.env` del tool de LangChain.

## 4. Cambios Propuestos
### [NEW] `specs/009-openwiki-langchain/spec.md` (Ya completado)
### [NEW] `specs/009-openwiki-langchain/tasks.md` (Checklist técnico)
### [NEW] `.github/workflows/openwiki-langchain.yml` (Pipeline de CI)
### [MODIFY] `src/content.config.ts` y scripts (Renombrado higiénico a `wiki/`)
### [MODIFY] `planes/ESTADO-SPECS.md`

## 5. Preguntas / Aprobación
- ¿Apruebas el uso de `openwiki --update` para el CI y el renombramiento de la colección de Astro de `openwiki` a `wiki`?
- (Nota sobre la ejecución local: Debido a un conflicto de peer dependencies con Astro 5 y problemas de binarios locales en Windows, la validación del comando se comprobó a través de la documentación oficial y la confirmaremos al momento de la implementación real. ¿Damos luz verde para continuar con la implementación en CI?).
