# Implementation Plan: Elevación Premium del Prototipo (Spec 001)

Este plan detalla la implementación técnica de la **Spec 001**, cuyo objetivo es llevar el prototipo actual a estándares de calidad premium, mejorando las animaciones, SEO, accesibilidad y el flujo del chatbot de captación.

## Propuesta Técnica por Componentes

### 1. Sistema de Animaciones (Motion)
Implementaremos las animaciones usando las utilidades de transición de Tailwind CSS en combinación con la API nativa de JavaScript `IntersectionObserver` para lograr un rendimiento óptimo de 60fps sin librerías externas pesadas (como GSAP).

#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Añadir clases iniciales de Tailwind (`opacity-0`, `translate-y-8`, `transition-all`, `duration-700`) a las tarjetas de servicio, héroe, e íconos de la sección industrias.
- Añadir una clase de utilidad en `<head>` para efecto parallax básico en el hero background (usando variables CSS controladas por JS).

#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- Implementar clase `ScrollReveal`:
  - Instanciar `IntersectionObserver`.
  - Cuando un elemento entra en el viewport, remover `opacity-0 translate-y-8` y añadir `opacity-100 translate-y-0`.
- Añadir listener de evento de `scroll` para ajustar la posición `transform: translateY()` del background del Hero (Parallax).

### 2. SEO, Meta Tags y Datos Estructurados (JSON-LD)

#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Insertar en el `<head>`:
  - `<meta name="description" content="...">`
  - Open Graph tags (`og:title`, `og:description`, `og:type`, `og:url`).
  - Bloque `<script type="application/ld+json">` con schema `Organization` y `ProfessionalService`.

### 3. Accesibilidad y Navegación Móvil

#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Envolver el contenido principal en `<main>` y actualizar el nav y footer con tags semánticos.
- Agregar botón de menú hamburger para mobile en el `<nav>` con `aria-expanded="false"`, `aria-controls="mobile-menu"`.
- Crear el menú desplegable móvil.
- Añadir `aria-label` a botones de ícono (ej. cerrar chatbot).
- Actualizar el chatbot con roles ARIA (`role="dialog"`, `aria-live="polite"` en el contenedor de mensajes).

### 4. Expansión del Chatbot (Flujo y Formularios)

#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- **Typing Indicator**:
  - Modificar `selectSector` y `selectProblem` para que muestren inmediatamente un loader simulando "escribiendo...".
  - Usar un `setTimeout` que elimine el loader antes de invocar `renderChatbotUI()`.
- **Nuevo Flujo ("Otro Sector")**:
  - Actualizar HTML estático en `index.html` para incluir el botón "Otro sector B2B / No listado".
  - Actualizar el switch de `renderChatbotUI` para manejar el estado genérico.
- **Campos Adicionales**:
  - En la vista `solution`, añadir los inputs para `Organización` y `Urgencia` (select).
  - Modificar `collectLead()` para validar y capturar los nuevos campos y agregarlos al payload JSON.
- **Seguridad (Mitigación XSS)**:
  - Cambiar el uso de interpolación directa en `innerHTML` a la creación controlada de nodos con `textContent` para las entradas del usuario (ej. `sector`, `label`), o sanitizar la salida.

### 5. Consolidación de Footer

#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Expandir el footer simple a una estructura grid en escritorio y stack en mobile:
  - Logo y breve descripción.
  - Links rápidos (Servicios, Casos, Contacto).
  - Información de contacto base.
  - Línea legal de copyright.

## Verification Plan

### Manual Verification
1. **Auditoría de Accesibilidad**: Ejecutar Lighthouse o Axe DevTools en el navegador. Validar puntaje >90.
2. **Auditoría SEO**: Validar la estructura del HTML y el JSON-LD en la herramienta oficial de Schema.org.
3. **Responsive / Navegación**: Probar en viewports de móvil, tablet y escritorio. Verificar apertura/cierre del menú móvil.
4. **Validación de Animaciones**: Observar las transiciones al hacer scroll hacia abajo y arriba.
5. **Flujo de Chatbot**:
   - Probar seleccionar opción genérica.
   - Observar el indicador "typing".
   - Rellenar y enviar el formulario con los 4 campos (validando campos obligatorios).
   - Revisar payload `chatbotState.leadData` en consola (Console Log).
