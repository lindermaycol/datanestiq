# Implementation Plan: Elevación Premium UI/UX (Spec 001)

Basado en la ejecución del comando `/speckit-plan`, he extraído los requerimientos de la Especificación 001 para orquestar la capa visual (Canvas) de Datanestiq con estándares B2B High-Ticket.

## 1. Summary
**Objetivo Principal:** Reemplazar las interacciones estáticas del prototipo por un motor de animación cinemática (GSAP + ScrollTrigger), implementar tipografía fluida, asegurar la accesibilidad estricta WCAG 2.2 AA (ARIA + Focus Traps), optimizar el SEO Técnico (JSON-LD) y recolectar data corporativa (Organización/Urgencia) en el Chatbot.

## 2. Technical Context & Constitution Check
- **Librerías:** GSAP, ScrollTrigger (vía CDN público, aprobado).
- **Check Constitución:** PASSED. El diseño se enfoca en C-Levels (animaciones fluidas sin estridencias, carga diferida para performance sub 2.5s, `prefers-reduced-motion`).

## 3. Proposed Changes (Arquitectura de Presentación)

### 3.1. Animación y Estilos (GSAP & Fluid Typography)
#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- **[NEW]** Inyectaremos los scripts de GSAP y ScrollTrigger al final del `<body>`.
- **[NEW]** Añadiremos JSON-LD (Schema.org `@type: "Organization" y "Service"`) en el `<head>` para SEO Corporativo (FR-005).
- **[MODIFY]** Cambiaremos las clases `text-3xl`, `text-5xl` rígidas por `text-[clamp(...)]` para garantizar tipografía fluida en el Hero (FR-004).

#### [MODIFY] [styles.css](file:///c:/xampp/htdocs/datanestiq/prototype/styles.css)
- **[NEW]** Añadiremos la media query `@media (prefers-reduced-motion: reduce)` para desactivar animaciones GSAP y cumplir con accesibilidad.
- **[NEW]** Refinaremos el `advanced glassmorphism` para evitar cambios de layout (CLS) en hover.

### 3.2. Orquestación JavaScript y Lead Capture
#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- **[DELETE]** Eliminaremos la función nativa `initScrollReveal()` (basada en `IntersectionObserver`).
- **[NEW]** Crearemos `initGSAPAnimations()` para animar con `stagger` la Trust Layer y con *spring physics* el FAQ (FR-003).
- **[MODIFY]** En la lógica final del Chatbot (AI Concierge), añadiremos los inputs de `Organización` y `Urgencia (Alta/Media/Baja)` al payload del Lead (FR-010).
- **[NEW]** Implementaremos un `Focus Trap` al abrir el modal del Chatbot o Menú Móvil para asegurar cumplimiento WCAG 2.2 por teclado.

## 4. Verification Plan (Lighthouse & A11y)
1. Iniciar servidor local y verificar visualmente que las tarjetas del Grid 3x2 tengan el efecto *Glassmorphism* en hover sin alterar el layout.
2. Navegar con la tecla `TAB` al abrir el Chatbot para comprobar que el Focus no escape del modal.
3. Ejecutar Lighthouse para garantizar que el LCP no haya sido degradado por GSAP y que SEO/Accesibilidad sigan en 100/100.

---

> [!IMPORTANT]
> **User Review Required:**
> La recolección de `Organización` y `Urgencia` en el chatbot añadirá dos pasos extras al final de la conversación con el AI Concierge. ¿Estás de acuerdo con este flujo conversacional extendido para asegurar la cualificación del prospecto C-Level?
