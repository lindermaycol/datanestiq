# OpenWiki Real Motor: Task List

## Configuración Inicial
- [ ] Actualizar `.env.example` añadiendo `GROQ_API_KEY`, `GROQ_MODEL`, `DASHSCOPE_API_KEY`, `DASHSCOPE_URL`, `DASHSCOPE_MODEL`, `LLM_WEIGHT_GROQ` y `LLM_WEIGHT_DASHSCOPE`.
- [ ] Actualizar el workflow `.github/workflows/openwiki-audit.yml` para usar los nuevos secrets, pasar variables de entorno y eliminar el flag de modelo obsoleto de Claude. Reemplazar la instalación global del CLI ficticio por la invocación del script en Node.
- [ ] Actualizar `scripts/openwiki-local-sync.sh` para invocar el script en Node con `--dry-run` en vez del CLI obsoleto.

## Desarrollo del Motor (scripts/docs-generator.mjs)
- [ ] Crear archivo base `scripts/docs-generator.mjs`.
- [ ] Implementar helper de red `callOpenAICompatible(url, key, model, messages, responseFormat)` usando `fetch` nativo.
- [ ] Implementar parseo defensivo (JSON mode) y validación previa de longitudes/tipos para que cumpla estrictamente con el esquema Zod (title ≤ 100, description ≤ 160).
- [ ] Implementar lógica de extracción temporal y re-inserción de bloques inmutables (`<!-- OPENWIKI:IGNORE:START -->`).
- [ ] Implementar balanceo round-robin ponderado entre Groq y DashScope y *failover atómico* (reintento cruzado) en caso de fallo (HTTP != 2xx o JSON inválido).
- [ ] Implementar lógica de recolección de cambios (`git diff` HEAD~1..HEAD), filtrando las rutas monitoreadas (`specs/` y `src/content/`).
- [ ] Implementar salida limpia sin LLM si no hay diff relevante (salvo si se pasa flag `--seed` o `--all`).
- [ ] Implementar flag `--dry-run` para simular la ruta sin quemar la API.

## Contenido y Validación
- [ ] Borrar el archivo obsoleto `src/content/openwiki/dummy.md`.
- [ ] Generar un archivo inicial real `src/content/openwiki/arquitectura.md` usando `--seed` que cumpla estrictamente el esquema Zod.
- [ ] Validar localmente (E2E):
    - Ejecución `--dry-run` sin consumir APIs y repartiendo documentos.
    - Verificación del failover pasando una llave inválida a propósito.
    - Verificación del *guard* Zod truncando textos largos.
    - Validación del *build* local de Astro (`npm run build`).

## Estado del Proyecto
- [ ] Actualizar `tech_debt.md` en Spec 005.
- [ ] Actualizar `planes/ESTADO-SPECS.md` reflejando el estatus de la Spec 005.
