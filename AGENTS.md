# AGENTS.md: Guía de Configuración y Estructura del Repositorio para Agentes IA

Este documento sirve como una guía esencial para los agentes de IA que interactúan con el repositorio de Datanestiq. Detalla la estructura del proyecto, las convenciones clave, la arquitectura subyacente y un resumen de las especificaciones de las características, facilitando la comprensión y la operación autónoma de los agentes.

## 1. Introducción

El repositorio de Datanestiq está diseñado para ser un ecosistema dinámico y gestionado por especificaciones, donde los agentes de IA juegan un papel central en el desarrollo, despliegue y mantenimiento. La organización del repositorio busca maximizar la claridad, la automatización y la escalabilidad de las operaciones.

## 2. Estructura del Repositorio

La estructura de directorios está diseñada para segregar responsabilidades y facilitar la navegación. A continuación, se describen los directorios clave:

*   **`.claude/`**: Contiene configuraciones específicas para agentes de IA, como planes de despliegue (`deploy-ops.md`) y configuraciones de lanzamiento (`launch.json`).
*   **`.specify/`**: Este directorio es fundamental para la gobernanza de los agentes. Aloja configuraciones generales (`extensions.yml`, `feature.json`, `init-options.json`, `integration.json`), manifiestos de integración (`integrations/`), la constitución de la memoria del agente (`memory/constitution.md`), plantillas estandarizadas (`templates/`) y el registro de flujos de trabajo (`workflows/workflow-registry.json`).
*   **`AGENTS.md`**: Este documento.
*   **`DEPLOY.md`**: Documentación relacionada con los procesos de despliegue.
*   **`UX/`**: Documentos relacionados con la experiencia de usuario, incluyendo auditorías y reportes de QA.
*   **`Walkthrough/`**: Registros detallados y resúmenes de la ejecución de especificaciones y tareas completadas.
*   **`auditorias/`**: Informes de auditorías de ingeniería inversa y otros análisis.
*   **`planes/`**: Contiene planes de implementación de alto nivel, planes estratégicos, roadmaps y documentos de planificación general.
*   **`prompts/`**: Un extenso catálogo de prompts utilizados por los agentes, muchos de ellos con prefijos como `prompt-antigravity-` o `prompt-ia-`, indicando su propósito o el agente al que están dirigidos.
*   **`prototype/`**: Archivos de un prototipo web (`app.js`, `index.html`, `styles.css`).
*   **`public/`**: Contiene los archivos públicos de la aplicación, incluyendo endpoints de API (`api/chat.php`, `api/book_appointment.php`), archivos de administración (`admin/`), y assets estáticos.
*   **`scripts/`**: Colección de scripts de utilidad para diversas tareas, como aplicar cambios, construir estados de specs, desplegar, generar documentación, gestionar hooks de Git, y sincronizar datos.
*   **`specs/`**: El corazón de la definición de características. Cada subdirectorio numerado (`001-feature-name/`) corresponde a una especificación de característica y contiene archivos estandarizados como `plan.md`, `spec.md`, `tasks.md`, `tech_debt.md`, y opcionalmente `data-model.md`, `quickstart.md`, `research.md`.
*   **`src/`**: Código fuente de la aplicación web principal, construida con Astro. Incluye configuraciones de contenido, datos, layouts, librerías, páginas y estilos.

## 3. Convenciones Clave

Para mantener la coherencia y facilitar la automatización, se siguen las siguientes convenciones:

*   **Nomenclatura de Especificaciones**: Las especificaciones se organizan en el directorio `specs/` con un formato `NNN-nombre-de-la-caracteristica/`, donde `NNN` es un número secuencial de tres dígitos.
*   **Archivos Estándar de Especificación**: Dentro de cada directorio de especificación, se espera encontrar:
    *   `plan.md`: El plan de implementación.
    *   `spec.md`: La especificación detallada de la característica.
    *   `tasks.md`: Las tareas de implementación asociadas.
    *   `tech_debt.md`: La deuda técnica identificada para esa especificación.
*   **Prompts**: Los prompts están centralizados en el directorio `prompts/` y a menudo siguen patrones de nomenclatura que indican su función o el contexto de su uso (ej. `prompt-antigravity-`, `prompt-ia-`).
*   **Documentación en Markdown**: La mayoría de la documentación, planes y especificaciones se redactan en formato Markdown (`.md`).
*   **Configuración y Datos Estructurados**: Se utilizan archivos JSON y YAML para configuraciones y datos estructurados (ej. `.specify/`, `src/data/`).

## 4. Arquitectura General

La arquitectura del proyecto Datanestiq se caracteriza por:

*   **Desarrollo Centrado en Agentes**: El repositorio está intrínsecamente diseñado para ser operado y gestionado por agentes de IA, como lo demuestran los directorios `.claude/`, `.specify/` y la vasta colección de `prompts/`.
*   **Desarrollo Dirigido por Especificaciones (SDD)**: Un fuerte énfasis en la definición detallada de características a través del directorio `specs/`, asegurando que cada implementación esté bien planificada y documentada.
*   **Frontend Moderno con Astro**: La aplicación web principal se construye utilizando Astro, lo que sugiere un enfoque en el rendimiento, la optimización SEO y la generación de sitios estáticos (SSG) o híbridos.
*   **Backend Híbrido (PHP/API)**: La presencia de archivos `.php` en `public/admin/` y `public/api/` indica un backend basado en PHP para la lógica de negocio, administración y exposición de APIs.
*   **Sistema de OpenWiki**: Las especificaciones `005` y `009` apuntan a la implementación de un sistema de "OpenWiki" (posiblemente basado en LangChain), lo que sugiere una base de conocimiento dinámica y posiblemente generada por agentes.
*   **Capacidades Conversacionales y de IA**: La existencia de `public/api/chat.php`, `src/lib/intentClassifier.ts`, y especificaciones como `002` (Microexperiencias de IA) y `019` (Router 0-LLM Ampliado) indica una arquitectura robusta para chatbots y experiencias de usuario impulsadas por IA.
*   **Observabilidad y Analítica**: Las especificaciones `016`, `017` y `018` revelan un compromiso con la analítica de conversión, micro-interacciones y un panel de observabilidad interna para monitorear el rendimiento y la gobernanza.
*   **Automatización y CI/CD**: Los scripts en `scripts/` (especialmente los de despliegue y hooks de Git) subrayan un enfoque en la automatización de tareas y la integración continua/despliegue continuo.

## 5. Resumen de Especificaciones (Specs)

Las especificaciones definen las características y funcionalidades del proyecto. A continuación, se presenta un resumen de las especificaciones actuales:

*   **Spec 001: Elevación Premium del Prototipo**: Mejora y refinamiento del prototipo inicial.
*   **Spec 002: Microexperiencias de IA**: Implementación de pequeñas interacciones impulsadas por IA.
*   **Spec 003: Taxonomía de Servicios y Desarrollo Digital**: Definición y migración de la taxonomía de servicios a Content Collections.
*   **Spec 004: Metodología de Desarrollo Digital Premium (Fábrica de Agentes)**: Establecimiento de una metodología de desarrollo digital, incluyendo la creación de agentes especializados (ej. Website Builder, Copywriter).
*   **Spec 005: OpenWiki y Documentación Viva**: Creación de un sistema de documentación dinámica y viva, posiblemente con integración CI/CD.
*   **Spec 006: Ecosistema Web con Astro**: Desarrollo del ecosistema web utilizando Astro como fachada de difusión premium.
*   **Spec 007: Expansión Multi-Página de Soluciones**: Arquitectura SSG con Astro para la expansión de páginas de soluciones.
*   **Spec 008: Headless WordPress Architecture**: Implementación de WordPress como CMS backend y para la gestión de leads.
*   **Spec 009: OpenWiki LangChain (FASE 1)**: Primera fase de la implementación de OpenWiki utilizando LangChain, incluyendo guías técnicas para gateways y balanceo.
*   **Spec 010: Generador Multi-Destino de Documentación**: Herramienta para generar documentación en múltiples formatos y destinos desde OpenWiki.
*   **Spec 011: Enriquecimiento Profundo de la Taxonomía**: Mejora y profundización de la taxonomía existente.
*   **Spec 012: Fábrica de Contenido del Sitio**: Sistema para la generación de contenido del sitio web (single-shot).
*   **Spec 013: Conversión Consultiva por Rol × Sector**: Marco para la conversión consultiva adaptada a roles y sectores específicos.
*   **Spec 014: Ciclo de Vida del Lead (Mini-CRM Interno)**: Implementación de un mini-CRM interno para gestionar el ciclo de vida de los leads.
*   **Spec 015: Agendador de Citas**: Desarrollo de una funcionalidad para agendar citas.
*   **Spec 016: Analítica de Conversión y Loop `chat → lead → learn`**: Implementación de analíticas para el seguimiento de conversiones y un ciclo de retroalimentación.
*   **Spec 017: Panel de Observabilidad Interna (Ops & Gobernanza de Specs)**: Creación de un panel para monitorear operaciones y la gobernanza de las especificaciones.
*   **Spec 018: Analítica de Micro-Interacciones (Behavioral & Engagement)**: Recopilación y análisis de datos sobre micro-interacciones del usuario.
*   **Spec 019: Router Determinístico Ampliado del Chatbot (más rutas 0-LLM)**: Mejora del router del chatbot para incluir más rutas determinísticas sin LLM.

Los agentes deben consultar los archivos `spec.md` y `plan.md` dentro de cada directorio de especificación para obtener detalles completos sobre cada característica.