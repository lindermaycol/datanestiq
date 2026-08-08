# Tareas de Implementación: Spec 009 (Fase 1)

- [ ] 1. **Limpieza inicial (Higiene del Frontend):**
  - Renombrar la carpeta de colección `src/content/openwiki` a `src/content/wiki`.
  - Actualizar `src/content.config.ts` para que apunte a `src/content/wiki`.
  - Actualizar el script `scripts/docs-generator.mjs` para que escriba en `src/content/wiki`.
  - Actualizar cualquier ruta o componente Astro (ej. `src/pages/wiki/[slug].astro`) que dependa de la ruta antigua.
  - Ejecutar `npm run build` para asegurar que todo el sitio estático compila sin errores (22 páginas, ruta `/wiki/arquitectura` debe seguir funcionando).

- [ ] 2. **Configuración y prueba local del CLI:**
  - Configurar las variables de entorno temporalmente en la terminal (`OPENWIKI_PROVIDER=openai-compatible`, `OPENAI_COMPATIBLE_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai`, `OPENWIKI_MODEL_ID=gemini-2.5-flash`, `OPENAI_COMPATIBLE_API_KEY=<clave>`).
  - Ejecutar localmente `openwiki --init` (utilizando la instalación temporal para sortear ERESOLVE, o mediante `npx` directo) para generar la carpeta `openwiki/` inicial y el anexo a `AGENTS.md`.
  - Hacer un commit de la estructura resultante generada por la herramienta para tener una versión base.
  - Capturar y documentar el esquema/estructura de salida generada en la Spec 009 (Contrato de Interfaz).

- [ ] 3. **Integración en CI (GitHub Actions):**
  - Crear el workflow `.github/workflows/openwiki-langchain.yml`.
  - Configurar en el workflow:
    - Instalación segura de `openwiki` (ej. instalando globalmente con `npm install -g openwiki`).
    - Configuración de variables de entorno (las variables estáticas y `OPENAI_COMPATIBLE_API_KEY` inyectada desde `${{ secrets.GEMINI_API_KEY }}`).
    - Ejecución de `openwiki --update` para refrescar los cambios de manera no interactiva.
    - Utilización de `peter-evans/create-pull-request@v6` para commitear automáticamente los cambios generados y abrir un PR.
  - Asegurar que el trigger del workflow contemple `workflow_dispatch` y un crontab o en empujes a `main`.

- [ ] 4. **Documentación final:**
  - Actualizar `planes/ESTADO-SPECS.md` con la fila de `009-openwiki-langchain` marcada como [FASE 1] Finalizado, y detallar la dependencia (005 -> 009).
  - Incluir el output real final en la documentación del "Contrato de interfaz" en `spec.md`.
