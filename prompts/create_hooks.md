# Prompt para Generar el Hooks File (extensions.yml)

**Rol:** Ingeniero DevOps Senior y Experto en Flujos CI/CD Automáticos.

**Contexto:**
En Datanestiq estamos utilizando la metodología Spec-Kit (GitHub SDD) orquestada por Agentes de IA. Queremos inyectar automatizaciones personalizadas (Hooks) que se ejecuten automáticamente ANTES o DESPUÉS de comandos críticos de la metodología, como `/speckit-plan`, `/speckit-tasks` y `/speckit-implement`. Estos hooks se configuran en el archivo `.specify/extensions.yml`.

**Tarea:**
Diseña un archivo `.specify/extensions.yml` avanzado y adaptado a nuestro ecosistema corporativo. Debes configurar:
1. **Pre-Hooks (before_implement):** Un gancho que, antes de dejar que un agente empiece a escribir código, obligue a correr un linter estricto o un chequeo de seguridad (ej. validación de vulnerabilidades).
2. **Post-Hooks (after_tasks):** Un gancho que, tras generarse el listado de tareas técnicas (`tasks.md`), ejecute un script simulado de sincronización bidireccional con Jira o notifique a un canal de Slack para aprobación humana.
3. **Post-Hooks (after_implement):** Un gancho que invoque automáticamente a la herramienta de auditoría de OpenWiki (Spec 005) para asegurar que el código recién escrito no ha roto la documentación viva.

**Formato de Salida:**
Devuelve el archivo YAML estructurado con comentarios explicativos sobre cómo funciona cada hook y las condiciones de ejecución. Además, incluye una breve guía de dónde guardar y cómo probar este archivo en el repositorio.
