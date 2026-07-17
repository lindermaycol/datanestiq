# Spec 007: Expansión Multi-Página de Soluciones (Arquitectura SSG con Astro)

**Estado:** Implementado (6 páginas SSG, verificado).

## Backlog y Refinamientos Futuros
- **[BACKLOG]:** Validación SC "Lighthouse 100/100" nunca se ejecutó con datos reales (scripts stubs en package.json).
- **[BACKLOG]:** Implementación de Breadcrumb Schema y cross-linking de servicios relacionados.
- **[BACKLOG]:** Creación de páginas de sector específicas (`/sectores/[id]`), además de las de los pilares actuales.

## 1. Objetivo de Negocio y Arquitectónico
Escalar la arquitectura monolítica actual (Single Page Application / Landing Page) hacia un ecosistema web distribuido y robusto, generado de forma estática (SSG) mediante Astro. El propósito central es dedicar una landing page de alto rendimiento (URL única) para cada pilar de negocio definido en `taxonomyCorpus.json` (ej. Hiperautomatización, Data Science). Esta expansión maximiza el alcance del tráfico "Long-Tail" (SEO), personaliza el embudo de conversión para distintos arquetipos de decisión (CTOs, CEOs) y proyecta una autoridad premium en servicios B2B.

## 2. Contexto Arquitectónico y Dependencias
- **Estado Actual:** Las soluciones se presentan estáticamente mediante un componente de cuadrícula (grid de tarjetas) en el `index.html` monolítico, limitando el detalle técnico y la indexación granular por parte de motores de búsqueda.
- **Estado Objetivo (SSG con Astro):** Implementar enrutamiento dinámico (Dynamic Routing) acoplado a colecciones de contenido. Astro consumirá `taxonomyCorpus.json` en tiempo de compilación para iterar sobre los pilares y generar páginas optimizadas y pre-renderizadas (`/soluciones/[id].html`).
- **Grafo de Dependencias:**
  - **Spec 006:** Utiliza el enrutamiento dinámico y la arquitectura de Astro definida en esta spec.
  - **Spec 003 y 004:** Depende de la ingesta de servicios de `taxonomyCorpus.json` y de los textos persuasivos inyectados por los Agentes 02 y 05.

## 3. User Scenarios & Testing

**User Story 1: Búsqueda y Navegación Directa (CTO)**
- **Como** un Director de Tecnología (CTO) buscando "automatización de procesos de datos",
- **Quiero** aterrizar directamente en una página dedicada a la hiperautomatización en lugar de una home genérica,
- **Para** evaluar rápidamente los beneficios técnicos y la viabilidad de la solución para mi sector.
- **Acceptance Scenario:**
  - **Given** que el CTO realiza una búsqueda en Google sobre "RPA y automatización de datos",
  - **When** hace clic en el enlace de Datanestiq (`/soluciones/hiperautomatizacion`),
  - **Then** el sistema sirve instantáneamente una página estática con un Hero específico, lista de beneficios (Dolor vs Solución) y métricas relevantes sin necesidad de scroll infinito.

**User Story 2: Rastreo e Indexación (Motor de Búsqueda)**
- **Como** Crawler de Google (Googlebot),
- **Quiero** descubrir y rastrear un mapa del sitio con URLs específicas para cada servicio,
- **Para** indexar contenido relevante y mostrarlo en resultados de búsqueda (SERP) para palabras clave long-tail.
- **Acceptance Scenario:**
  - **Given** que se despliega una nueva versión del sitio web,
  - **When** el bot accede a `/sitemap.xml` y visita `/soluciones/ciencia-de-datos`,
  - **Then** encuentra meta etiquetas únicas (`<title>`, `<meta description>`), Schema.org inyectado correctamente y etiquetas `<link rel="canonical">` apuntando a sí mismas.

**User Story 3: Continuidad del Contexto (Conversión)**
- **Como** un prospecto interesado en el servicio de Analítica Avanzada,
- **Quiero** poder hacer clic en "Inicia tu Diagnóstico" y que el sistema recuerde qué servicio estaba viendo,
- **Para** no tener que repetir mi interés al asistente o en el formulario.
- **Acceptance Scenario:**
  - **Given** que el usuario lee la página `/soluciones/analitica-avanzada`,
  - **When** hace clic en el CTA (botón) principal hacia el Wizard,
  - **Then** es redirigido al Wizard y el `window.DatanestiqContext` (o query param `?servicio=analitica-avanzada`) se inicializa, personalizando la primera interacción del Chatbot.

## 4. Requirements (FRs - Functional Requirements)
- **FR-01 (Generación de Rutas Dinámicas):** El sistema debe usar la función `getStaticPaths()` de Astro en `[id].astro` para leer `taxonomyCorpus.json` en tiempo de compilación y generar tantas páginas estáticas como elementos haya en el Corpus.
- **FR-02 (Inyección Semántica SEO):** El sistema debe inyectar dinámicamente OpenGraph tags, `<title>`, `<meta description>`, y canonical tags para cada ruta renderizada.
- **FR-03 (Generación de Schema.org):** Cada página debe generar e incluir un script JSON-LD válido de tipo `Service` utilizando los datos del Corpus.
- **FR-04 (Inyección de Contexto en Wizard):** Todos los botones CTA dentro de la página generada deben adjuntar un identificador del servicio (por ejemplo, `?servicio=[id]`) al enrutar hacia el Wizard, permitiendo a este último capturar el contexto.
- **FR-05 (Integración de Agentes de Copy):** El template base debe proveer slots o props que consuman directamente los outputs estructurados generados por los Agentes 02 y 05 (ej. Ganchos del Hero, y puntos de Dolor/Solución).

## 5. Diseño Técnico (Implementación)

### 5.1. Implementación Astro y Dynamic Routing
- Crear el archivo de ruta dinámica `src/pages/soluciones/[id].astro`.
- La función `getStaticPaths()` retornará un mapeo de los slugs del JSON para compilar las páginas exactas.

### 5.2. Componentes y Template Base (`SolutionLayout.astro`)
- **Estructura Modular:** El layout compondrá dinámicamente:
  - Hero Section (Adaptativo según el servicio provisto por el Agente 02).
  - Sección de Contraste (Problema Actual vs. Solución Datanestiq provisto por el Agente 05).
  - Prueba Social / Casos de Estudio específicos del pilar.
- **Cross-Linking:** La página principal (`index.astro`) utilizará un componente de tarjetas que enlazará a cada `/soluciones/[id]`, distribuyendo el link juice hacia el long-tail.

## 6. Success Criteria
1. **Volumen de Cobertura:** El número de páginas estáticas generadas en `/soluciones/*` es exactamente igual a la cantidad de servicios definidos en el `taxonomyCorpus.json`.
2. **Validación SEO:** El 100% de las URLs generadas cuentan con etiquetas `<title>`, `<meta description>` únicas, OpenGraph y su propio `<link rel="canonical">` que previene el contenido duplicado.
3. **Métricas de Performance:** El framework de validación (Lighthouse) marca una puntuación de 100/100 en Performance, Best Practices, Accessibility y SEO para cada subpágina.
4. **Validación de Conversión:** Las pruebas End-to-End demuestran que al hacer clic en el CTA de cualquier solución, el Wizard de destino inicializa el diálogo mencionando o contextualizando el servicio de origen.
