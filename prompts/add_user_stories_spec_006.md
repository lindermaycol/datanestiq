# Prompt para Añadir User Stories y Requerimientos a Spec 006

**Rol:** Arquitecto Frontend de Élite y Especialista en SEO Técnico.

**Contexto y Grafo de Dependencias:**
La **Spec 006 (Ecosistema Astro)** define la migración arquitectónica del prototipo Vanilla JS hacia Astro, utilizando la Arquitectura de Islas para el Javascript y aislando la capa pública. Carece de la estructura de *User Stories*, *Functional Requirements (FRs)* y *Success Criteria (SCs)*.
Considera el Grafo de Dependencias:
- **Depende de Specs 001 y 002:** Debe importar el código UI y JS (Chatbot/Wizard) y encapsularlos en "Islas".
- **Depende de Spec 005:** Astro utilizará `Content Collections` para consumir y compilar los archivos `.md` de la OpenWiki en páginas web de difusión pública.

**Tarea:**
Añade las secciones faltantes a la Spec 006:
1. **User Scenarios & Testing:** Crea al menos 3 User Stories. Ejemplos: a) Un Motor de Búsqueda (Google Crawler) indexando el HTML plano sin necesidad de procesar Javascript. b) Un usuario con red lenta experimentando cargas instantáneas sin FOUC. Incluye Acceptance Scenarios (Given/When/Then).
2. **Requirements (FRs):** Define los requisitos funcionales de infraestructura frontend. Ej: Configuración de directivas `client:visible` para las Islas, purgado CSS, generación estática (SSG) y lectura de colecciones `.md`.
3. **Success Criteria:** Define cómo mediremos el éxito de la migración (ej. 100/100 en Google Lighthouse, eliminación del CDN de Tailwind).

**Formato de Salida:**
Devuelve la Spec 006 completa en formato Markdown, fusionando el contexto arquitectónico de Astro existente con las nuevas secciones de producto (User Stories, Requerimientos Funcionales y Criterios de Éxito).
