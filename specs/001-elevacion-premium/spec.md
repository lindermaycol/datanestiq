# Feature Specification: Elevación Premium del Prototipo

**Feature Branch**: `[001-elevacion-premium]`

**Created**: 2026-07-04

**Status**: Implementado en Astro (producción)

**Input**: User description: "Scroll animations con IntersectionObserver, micro-interacciones y hover effects premium, parallax sutil en hero, typing indicator en chatbot, footer completo, SEO (meta description, datos estructurados), Accesibilidad ARIA completa, performance (lazy loading, optimización), 5to flujo genérico B2B en chatbot, campos adicionales de lead (organización, urgencia)."

## Backlog y Deuda Técnica Cerrada
- **[RESUELTO]:** La arquitectura Tailwind compilado, scoping nativo y `@layer components` ya han sido resueltos exitosamente gracias a la migración total hacia Astro.
- **[x]** 5to flujo genérico B2B en chatbot: La máquina de estados con salida "Otro/Escribir libremente" + LLM ya cubre este caso de uso.
- **[BACKLOG]:** Creación de componentes reutilizables `Button.astro` / `Card.astro`.
- **[BACKLOG]:** View Transitions API para navegación fluida entre páginas.
- **[BACKLOG]:** Validación de Lighthouse automatizada real.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sincronización Estructural con el Copywriting (Spec 004) y Animación Dinámica (Priority: P1)

Como usuario navegando el sitio, quiero experimentar que la estructura narrativa (Hero, Trust Layer, FAQ) dictada por la Spec 004 se revele orgánicamente con animaciones GSAP, para sentir una narrativa inmersiva y secuencial que eleve el valor del mensaje.

**Why this priority**: La interfaz (Spec 001) es el lienzo que da vida al storytelling (Spec 004). Si el texto premium aparece bruscamente, se rompe la percepción "High-Ticket".
**Independent Test**: Navegar por la página e identificar que cada bloque semántico (Hero, Trust Layer, FAQ) se activa con su propia coreografía de animación sin que el diseño opaque al copy.

**Acceptance Scenarios**:
1. **Given** el usuario llega a la Trust Layer (definida en Spec 004), **When** esta entra al viewport, **Then** los logotipos de clientes y testimonios se revelan con un efecto `stagger` de GSAP fluido y sofisticado.
2. **Given** el usuario interactúa con la sección de FAQ, **When** expande una pregunta, **Then** la respuesta se despliega usando animaciones físicas (spring physics) que mantienen el layout estable, respetando los copies de Spec 004.
3. **Given** el Hero Section renderizado, **When** el usuario hace scroll inicial, **Then** los títulos H1 generados por los agentes (Spec 004) escalan armónicamente utilizando *fluid typography* sin romper su jerarquía estructural.

---

### User Story 2 - Renderizado del Grid Taxonómico (Spec 003) (Priority: P1)

Como prospecto B2B, quiero visualizar los servicios de Datanestiq presentados en un layout ordenado y premium que refleje exactamente los 6 pilares estratégicos definidos en la Spec 003, para comprender la oferta de forma rápida y sin fricciones.

**Why this priority**: El diseño debe acomodar dinámicamente la matriz de servicios oficial de la compañía (Spec 003) sin requerir refactorizaciones si los nombres de los servicios cambian.
**Independent Test**: Verificar visualmente la sección de servicios para confirmar que utiliza una estructura de Grid de 3x2, con tarjetas de igual altura y comportamiento responsivo correcto.

**Acceptance Scenarios**:
1. **Given** la sección de "Nuestros Servicios", **When** se renderiza en desktop, **Then** el layout despliega un grid matemático de 3x2 que expone los 6 pilares tecnológicos de la Spec 003.
2. **Given** una tarjeta de servicio del grid 3x2, **When** el usuario realiza hover, **Then** se activa un efecto de *advanced glassmorphism* que resalta la taxonomía dictada sin alterar el tamaño de la tarjeta (evitando CLS).
3. **Given** el grid de servicios en un dispositivo móvil, **When** se evalúa el responsive design, **Then** las 6 tarjetas se apilan en un diseño de 1 columna preservando los espacios (gaps) consistentes.

---

### User Story 3 - Accesibilidad (WCAG 2.2) y Documentación Continua (Spec 005) (Priority: P2)

Como auditor técnico o bot de OpenWiki (Spec 005), requiero que el código implemente la máxima accesibilidad y que las decisiones de diseño complejas queden transparentemente documentadas, permitiendo auditorías automáticas y una evolución del código libre de silos de conocimiento.

**Why this priority**: La interoperabilidad con OpenWiki (Spec 005) garantiza que las soluciones UI avanzadas (como ARIA en grids y modales) no sean "cajas negras" inantenibles.
**Independent Test**: Auditorías automatizadas (axe-core) marcando 100/100, y confirmación de que OpenWiki puede extraer la justificación técnica de los componentes en base a los comentarios o meta-documentación.

**Acceptance Scenarios**:
1. **Given** el menú de navegación y modales, **When** un usuario opera por teclado, **Then** existe un focus trap completo y etiquetas `aria-expanded`/`aria-controls` correctamente asociadas.
2. **Given** la implementación de librerías como GSAP o WebGL, **When** se comitea el código, **Then** las decisiones de optimización deben cumplir los estándares para ser registradas en OpenWiki (Spec 005).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El DOM MUST estar estructurado modularmente para ingerir dinámicamente los bloques (Hero, Trust Layer, FAQ, Services) requeridos por la **Spec 004**, facilitando futuras inyecciones de CMS o Agentes.
- **FR-002**: La sección de servicios MUST implementar un CSS Grid robusto de 3 columnas y 2 filas (3x2 grid) en desktop para exponer los 6 pilares de la **Spec 003**, con *auto-fit/auto-fill* manejando breakpoints menores grácilmente.
- **FR-003**: Todas las animaciones de revelado (scroll-reveal) y parallax (orquestadas por GSAP/ScrollTrigger) MUST acoplarse a clases o atributos de datos (`data-animate`) genéricos, permitiendo que el contenido dictado por la Spec 004 se anime sin necesidad de acoplar IDs específicos al JS.
- **FR-004**: Las interfaces interactivas MUST aplicar *advanced glassmorphism* (backdrop-filter: blur) y *fluid typography* (`clamp()`), manteniendo ratios de contraste WCAG 2.2, asegurando que el copy (Spec 004) sea siempre legible.
- **FR-005**: El SEO técnico MUST incluir `meta description` orientados a conversión, Open Graph tags, y esquemas ricos JSON-LD (`@type: "Organization"`, `@type: "Service"` mapeado a la Spec 003).
- **FR-006**: Toda media y script no crítico en el viewport MUST implementar técnicas de diferimiento estrictas (`loading="lazy"`, `defer`, `async`) para asegurar un LCP sub 2.5s.
- **FR-007**: La arquitectura ARIA MUST ser robusta: landmarks correctos (`<main>`, `<nav>`), `aria-expanded` para estado de menús/FAQ, `aria-live` para actualizaciones de estado, y `focus-trap` en componentes superpuestos.
- **FR-008**: Decisiones de frontend críticas (uso de IntersectionObserver vs ScrollTrigger, polyfills) MUST estar documentadas in-code para ingesta por el sistema OpenWiki (**Spec 005**).
- **FR-009**: La navegación global MUST incorporar un menú off-canvas para móviles y el layout MUST finalizar en un 'Fat Footer' corporativo.
- **FR-010**: El payload final del lead (Chatbot) MUST recolectar y validar semánticamente los campos de `Organización` y `Urgencia` para alimentar los flujos posteriores.

### Key Entities

- **UI Component (Canvas)**: Contenedor modular capaz de renderizar los nodos estructurales definidos en Spec 004 y Spec 003.
- **Lead (Ampliación Corporativa)**: Representa el prospecto de alto valor. Atributos: `nombre`, `email`, `sector`, `problema`, `timestamp`, `organizacion`, `urgencia`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El diseño y las animaciones (Spec 001) **NO rompen ni ocultan** el texto y la estructura narrativa generada por los agentes de copywriting (Spec 004). El copy premium es 100% legible y escaneable.
- **SC-002**: La interfaz renderiza con éxito un Grid perfecto de 3x2 en resoluciones desktop, alojando los 6 pilares de la Spec 003 sin desbordamientos ni anomalías visuales.
- **SC-003**: Las métricas Core Web Vitals y puntuaciones de Lighthouse (Performance, Accessibility, Best Practices, SEO) MUST registrar 100/100 en emuladores Desktop y Mobile.
- **SC-004**: OpenWiki (Spec 005) puede rastrear y documentar las librerías frontend utilizadas y su justificación técnica, validando la integración en el Grafo de Dependencias.
- **SC-005**: Cumplimiento verificable de los criterios de conformidad WCAG 2.2 AA (sin violaciones en herramientas como axe-core o comprobaciones manuales de teclado).

## Assumptions

- Las Specs 003 y 004 definirán estructuras estables que el frontend puede consumir sin requerir cambios estructurales constantes en el DOM de la Spec 001.
- El motion design no comprometerá la usabilidad de usuarios con preferencias de `prefers-reduced-motion: reduce`; el sistema MUST respetar esta media query reduciendo animaciones a transiciones de opacidad instantáneas.
