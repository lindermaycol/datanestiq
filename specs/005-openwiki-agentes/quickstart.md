# Quickstart Validation: OpenWiki Audit

Para validar el sistema localmente antes de pasarlo a GitHub Actions, sigue estos pasos:

1. **Instalar Dependencias**
   ```bash
   npm install -g openwiki
   ```

2. **Configurar API Key**
   Configura tu variable de entorno en PowerShell o `.env`:
   ```bash
   $env:ANTHROPIC_API_KEY="sk-ant-..."
   ```

3. **Ejecutar Auditoría en modo Dry-Run**
   ```bash
   openwiki --sync --model claude-3-5-sonnet-20240620 --threshold 0.85 --dry-run
   ```

4. **Validación Esperada**
   El sistema analizará `AGENTS.md` y la carpeta `specs/`. Si hay divergencias, mostrará un reporte en consola indicando qué archivos `.md` necesitan actualizarse, sin sobreescribir bloques marcados con `<!-- OPENWIKI:IGNORE:START -->`.
