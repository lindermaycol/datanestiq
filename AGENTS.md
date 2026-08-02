# Guía para Agentes IA: Estructura y Convenciones del Repositorio Datanestiq

Este documento sirve como una guía esencial para los agentes de Inteligencia Artificial que interactúan con el repositorio de Datanestiq. Detalla la estructura del proyecto, las convenciones clave, la arquitectura general y un resumen de las especificaciones (specs) para facilitar la comprensión y la colaboración autónoma.

## 1. Propósito del Repositorio

El repositorio de Datanestiq es el centro neurálgico para el desarrollo, despliegue y gestión de la plataforma Datanestiq. Incluye un ecosistema web premium, microexperiencias de IA, gestión de leads y una fábrica de contenido. Los agentes IA son actores clave en la evolución y mantenimiento de este sistema, desde la generación de código y documentación hasta la ejecución de planes y auditorías.

## 2. Estructura del Repositorio

A continuación, se describen los directorios y archivos más relevantes para la operación de los agentes IA:

*   **`.claude/`**: Contiene configuraciones específicas para agentes basados en Claude, como `agents/deploy-ops.md` para operaciones de despliegue y `launch.json` para configuraciones de lanzamiento.
*   **`.specify/`**: Directorio central para la gobernanza de agentes y especificaciones.
    *   `extensions.yml`, `feature.json`, `init-options.json`, `integration.json`: Archivos de configuración para extensiones e integraciones.
    *   `integrations/`: Manifiestos de integración (`agy.manifest.json`, `speckit.manifest.json`).
    *   `memory/constitution.md`: La "constitución" o principios rectores para los agentes.
    *   `templates/`: Plantillas para diversos documentos (`checklist-template.md`, `constitution-template.md`, `plan-template.md`, `spec-template.md`, `tasks-template.md`).
    *   `workflows/workflow-registry.json`: Registro de flujos de trabajo automatizados.
*   **`AGENTS.md`**: Este documento.
*   **`DEPLOY.md`**: Documentación relacionada con los procesos de despliegue.
*   **`UX/`