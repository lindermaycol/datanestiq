# Spec 006: Ecosistema Web con Astro (Fachada de Difusión Premium)

## 1. Objetivo Estratégico
Migrar la actual fachada digital (basada en un prototipo monolítico HTML/Vanilla JS) hacia una arquitectura moderna, escalable y ultra-optimizada utilizando **Astro**. El propósito fundamental es garantizar una presencia B2B de élite mediante un SEO técnico impecable (100/100 Lighthouse), tiempos de carga casi instantáneos (Zero JS by default) y una separación de responsabilidades clara que permita el renderizado nativo de la **OpenWiki** y la integración modular de herramientas de IA (Chatbot, Wizard).

## 2. Arquitectura de Sistemas y Componentes

### 2.1. Astro Islands (Arquitectura de Islas)
Para mantener el máximo rendimiento sin sacrificar la interactividad avanzada que requieren nuestros clientes B2B, implementaremos el patrón de **Astro Islands**. Esto permite hidratar parcial y estratégicamente solo los componentes que requieren JavaScript en el cliente (provenientes de las Specs 001 y 002):
- **Chatbot Asistente:** Aislado en su propio componente interactivo. Solo se hidratará cuando sea visible o bajo demanda del usuario (`client:idle` o `client:visible`), minimizando el impacto en el hilo principal durante la carga inicial.
- **Diagnostic Wizard:** Componente interactivo altamente dinámico. Se encapsulará como una Isla independiente (ej. `<DiagnosticWizard client:load />`) para procesar el "Context-Aware Chaining" sin bloquear el renderizado del resto del DOM estático.
- **Layout y Contenido:** Permanecerá 100% estático (cero JS al cliente por defecto), garantizando que los motores de búsqueda corporativos puedan indexar el valor y las narrativas comerciales sin requerir renderizado de JS.

### 2.2. Content Collections (Motor de la OpenWiki)
La OpenWiki (definida en la Spec 005) será el núcleo de nuestra autoridad técnica. El sistema nativo de Astro **Content Collections** será el mecanismo principal para tipar, validar y renderizar esta base de conocimiento de forma robusta.
- **Estructura de Directorios:** Los archivos Markdown/MDX de la OpenWiki residirán centralizados en `src/content/openwiki/`.
- **Esquema de Datos (Zod):** Implementaremos esquemas estrictos (Frontmatter) usando Zod en `src/content/config.ts` para obligar a que cada artículo técnico contenga metadatos críticos: `title`, `description`, `author`, `lastUpdated`, `tags`, y un `seoScore` objetivo.
- **Generación Estática de Rutas:** Astro utilizará `getCollection('openwiki')` para generar rutas dinámicas en tiempo de compilación (`/wiki/[slug]`), convirtiendo el Markdown técnico de los agentes en HTML semántico, estructurado y altamente rankeable (SSG por defecto).

## 3. User Scenarios & Testing (User Stories)

### US-01: Indexación Perfecta para Motores de Búsqueda
**Como** un Motor de Búsqueda Corporativo (Google Crawler),
**Quiero** acceder a la página web y encontrar todo el contenido de valor en HTML plano y semántico,
**Para** poder indexar y clasificar correctamente los servicios y artículos técnicos de Datanestiq sin depender de la ejecución de JavaScript.
- **Acceptance Scenario (Given/When/Then):**
  - **Given** que un crawler accede a la página principal o a un artículo de la OpenWiki con la ejecución de JS deshabilitada.
  - **When** se recibe la respuesta HTTP inicial.
  - **Then** el HTML devuelto contiene todo el texto, jerarquía de encabezados (`<h1>`, `<h2>`) y metaetiquetas necesarias para SEO técnico perfecto (SEO Score 100).

### US-02: Carga Instantánea en Redes Lentas sin FOUC
**Como** un prospecto B2B operando desde una red corporativa restringida o de ancho de banda limitado,
**Quiero** que la página cargue de forma instantánea sin mostrar estilos sin procesar (FOUC),
**Para** percibir una plataforma de alto rendimiento y confiabilidad desde el primer segundo.
- **Acceptance Scenario (Given/When/Then):**
  - **Given** que el usuario accede al sitio a través de una red 3G simulada.
  - **When** la página carga sus recursos iniciales.
  - **Then** el "First Contentful Paint" (FCP) ocurre en menos de 1 segundo, no hay parpadeos de CSS sin procesar y el LCP es inferior a 1.5s, gracias a la integración nativa y procesada de Tailwind CSS.

### US-03: Interactividad bajo Demanda sin Bloqueo
**Como** un usuario interesado en interactuar con el ecosistema de IA de Datanestiq,
**Quiero** poder usar el Chatbot o el Diagnostic Wizard de manera fluida,
**Para** resolver mis dudas sin que esto afecte el tiempo de carga inicial de la página.
- **Acceptance Scenario (Given/When/Then):**
  - **Given** que un usuario entra a la web y hace scroll hacia la sección del Wizard.
  - **When** el componente de la Isla del Wizard entra en el viewport.
  - **Then** Astro hidrata el JavaScript del componente (`client:visible`), permitiendo la interacción inmediata del usuario, mientras que el resto de la página estática sigue operando con 0 KB de JS bloqueante inicial.

## 4. Requisitos Funcionales (FRs)

- **FR-01: Componentización de Islas (Specs 001/002):** Todo componente interactivo (Chatbot, Wizard) debe importarse de forma aislada y montarse con directivas de hidratación parcial explícitas como `client:visible`, `client:idle` o `client:load` según la criticidad.
- **FR-02: Generación Estática (SSG):** El ecosistema operará en modo SSG por defecto para el layout principal y las colecciones de la OpenWiki, generando archivos HTML inmutables en tiempo de build.
- **FR-03: Colecciones y Frontmatter:** Debe existir la configuración `src/content/config.ts` que lea la colección `openwiki` de archivos `.md`. Todo `.md` sin el frontmatter válido (definido vía Zod) debe fallar en tiempo de compilación.
- **FR-04: Gestión de CSS Nativa:** Se debe utilizar la integración oficial `@astrojs/tailwind` eliminando cualquier script de CDN. El CSS generado debe ser purgado y minificado para producción.
- **FR-05: Estado Compartido Reactivo:** Implementar una librería de señales ligeras (ej. Nano Stores) para persistir e intercambiar estado entre las diferentes "Islas" sin acoplarse a un framework UI en particular.

## 5. Criterios de Éxito (Success Criteria)

- **SC-01:** La puntuación en las auditorías de Google Lighthouse es consistentemente de **100/100** en todas las métricas principales (Rendimiento, Accesibilidad, Mejores Prácticas, SEO).
- **SC-02:** Eliminación total del CDN de TailwindCSS; el código fuente final no contiene peticiones HTTP bloqueantes hacia CDNs externos para estilos base.
- **SC-03:** El tamaño del bundle de JavaScript bloqueante (pre-LCP) se mantiene en **0 KB** para el contenido estático.
- **SC-04:** Las rutas dinámicas de la OpenWiki (`/wiki/...`) se generan estáticamente de forma automatizada leyendo la colección de archivos `.md` de la Spec 005.
