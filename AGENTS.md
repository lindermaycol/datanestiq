# AGENTS.md: Guía de Configuración y Operación para Agentes IA

Este documento sirve como una guía esencial para los agentes de Inteligencia Artificial que operan dentro del repositorio de Datanestiq. Detalla la estructura del proyecto, las convenciones clave, la arquitectura subyacente y un resumen de las especificaciones de características (Specs) para facilitar una interacción autónoma y efectiva.

## 1. Filosofía de Operación para Agentes IA

El repositorio de Datanestiq está diseñado para un desarrollo **Specification-Driven** y **Prompt-Driven**. Los agentes IA son actores centrales en la creación, refinamiento y ejecución de las características del producto.

*   **Constitución del Agente**: La base de la operación de cualquier agente se encuentra en `.specify/memory/constitution.md`. Este documento define los principios, directrices y restricciones fundamentales que rigen el comportamiento y las decisiones del agente. Es imperativo que todos los agentes consulten y adhieran a esta constitución.
*   **Enfoque Modular**: Las tareas se dividen en especificaciones (`specs/`) y planes (`planes/`), con prompts específicos (`prompts/`) para guiar la ejecución.
*   **Generación y Consumo de Documentación**: Los agentes son responsables de generar y consumir documentación estandarizada utilizando las plantillas provistas en `.specify/templates/`.

## 2. Estructura del Repositorio

A continuación, se describe la estructura de directorios clave y su propósito para los agentes IA:

*   **`.claude/`**:
    *   Contiene configuraciones específicas para agentes que utilizan la plataforma Claude, como `deploy-ops.md` para operaciones de despliegue y `launch.json` para configuraciones de lanzamiento.
*   **`.specify/`**:
    *   El núcleo de la metodología de especificación y configuración de agentes.
    *   `extensions.yml`, `feature.json`, `init-options.json`, `integration.json`: Archivos de configuración para definir capacidades, características e integraciones de los agentes.
    *   `integrations/`: Contiene manifiestos para integraciones específicas (e.g., `agy.manifest.json`, `speckit.manifest.json`).
    *   `memory/constitution.md`: La constitución fundamental que rige el comportamiento del agente.
    *   `templates/`: Colección de plantillas Markdown para la generación estandarizada de documentos (e.g., `spec-template.md`, `plan-template.md`, `tasks-template.md`, `constitution-template.md`, `checklist-template.md`).
    *   `workflows/workflow-registry.json`: Define los flujos de trabajo y secuencias de tareas para los agentes.
*   **`prompts/`**:
    *   Un catálogo exhaustivo de prompts diseñados para guiar la ejecución de tareas por parte de los agentes.
    *   **Prompts de Rol/Persona**: Prompts numerados (e.g., `01-premium-website-builder.md` a `10-objection-killing-faq.md`) que definen roles o especializaciones para la generación de contenido.
    *   **Prompts Operacionales**: Prompts con el prefijo `prompt-antigravity-` que dirigen acciones específicas como confirmaciones, correcciones, despliegues y cierres de tareas.
    *   **Prompts de Generación/Refinamiento de Specs**: Prompts como `add_user_stories_spec_XXX.md`, `create_spec_XXX.md`, `refine_spec_XXX.md`, `resolve_tech_debt_XXX.md` para interactuar directamente con el ciclo de vida de las especificaciones.
    *   **Prompts Específicos de IA**: Prompts con prefijos `prompt-ia-` o `prompt-perplexity-` para tareas especializadas o modelos específicos.
*   **`specs/`**:
    *   El directorio central para todas las especificaciones de características. Cada característica tiene su propia carpeta numerada y descriptiva (e.g., `001-elevacion-premium/`).
    *   Cada carpeta de spec contiene:
        *   `plan.md`: Plan de implementación.
        *   `spec.md`: Especificación detallada de la característica.
        *   `tasks.md`: Lista de tareas de implementación.
        *   `tech_debt.md`: Deuda técnica asociada a la spec.
        *   Algunas specs pueden incluir `data-model.md`, `quickstart.md`, `research.md`.
*   **`planes/`**:
    *   Contiene diversos planes de implementación, planes estratégicos y planes de acción detallados para proyectos y correcciones.
    *   `planes/insumos-conversion-consultiva/`: Materiales de entrada para la generación de contenido consultivo.
*   **`src/data/`**:
    *   Fuentes de datos maestras en formato JSON, cruciales para la generación de contenido y la lógica del sitio (e.g., `contentAngles.json`, `extendedIndustries.json`, `personas.json`, `taxonomyCorpus.json`).
*   **`scripts/`**:
    *   Colección de scripts de utilidad para automatización, despliegue, validación y otras operaciones (e.g., `deploy_ionos.py`, `docs-generator.mjs`, `test-specs.sh`, `validate-taxonomy.js`).
    *   `scripts/hooks/`: Contiene Git hooks (`pre-commit`, `post-commit`, `post-checkout`) para automatizar acciones en el ciclo de desarrollo.
*   **`public/api/`**:
    *   Endpoints de la API pública con los que los agentes pueden interactuar para funcionalidades como chat, reserva de citas o guardado de datos.
*   **`Walkthrough/`**:
    *   Documentos que detallan la ejecución y el resultado de implementaciones completadas, sirviendo como referencia y ejemplos prácticos.
*   **`UX/` y `auditorias/`**:
    *   Informes de auditorías de UX y de ingeniería inversa, proporcionando contexto y requisitos para mejoras y correcciones.

## 3. Convenciones Clave

*   **Formato de Documentación**: Todo el contenido textual y la documentación se gestionan en formato Markdown (`.md`).
*   **Estructura de Especificaciones**: Las especificaciones en `specs/` siguen una estructura consistente de archivos para cada característica.
*   **Nomenclatura de Prompts**: Los prompts utilizan prefijos descriptivos para indicar su propósito y contexto de uso.
*   **Datos y Configuración**: Los datos estructurados y las configuraciones se almacenan en formatos JSON (`.json`) o YAML (`.yml`).

## 4. Arquitectura de Alto Nivel

*   **Desarrollo Centrado en Agentes**: El flujo de trabajo está optimizado para que los agentes IA impulsen la generación de contenido, la gestión de especificaciones y la automatización de tareas.
*   **Frontend Astro**: El sitio web se construye con Astro, utilizando un enfoque de Generación de Sitios Estáticos (SSG) o Renderizado del Lado del Servidor (SSR) para la fachada de difusión.
*   **Backend PHP**: Las funcionalidades de API y administración (e.g., `public/api/chat.php`, `public/admin/`) se implementan con PHP.
*   **Integración OpenWiki**: Existe una integración con un sistema OpenWiki (referenciado en `specs/005-openwiki-agentes/`, `specs/009-openwiki-langchain/`, `specs/010-generador-multidestino/` y scripts) para la gestión de conocimiento y la generación de documentación.
*   **CI/CD y Hooks**: El repositorio incorpora scripts y Git hooks para automatizar procesos de integración continua y despliegue.

## 5. Resumen de Especificaciones (Specs)

A continuación, se presenta un resumen de las especificaciones de características implementadas o en desarrollo, que los agentes deben conocer y consultar:

*   **Spec 001: Elevación Premium del Prototipo**: Mejora y refinamiento de la interfaz de usuario y experiencia del prototipo.