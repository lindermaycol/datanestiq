# Research: Astro Architecture & Optimizations

## Decisiones Técnicas

### 1. Framework Frontend (Astro vs Next.js vs Vite/Vanilla)
- **Decision:** Astro (Static Site Generator).
- **Rationale:** Astro extrae el HTML estático por defecto enviando 0KB de JavaScript al navegador, lo que asegura un FCP instantáneo y scores 100/100 en Lighthouse, requisitos estrictos en la Constitución para clientes C-Level B2B. Next.js añade un overhead innecesario de reactividad global para una landing page orientada al contenido y la OpenWiki.
- **Alternatives considered:** Next.js (rechazado por el tamaño del JS bundle inicial), Vite SPA (rechazado porque degrada el SEO orgánico).

### 2. Gestión del Estado entre Astro Islands
- **Decision:** Nano Stores (`@nanostores/react` o vanilla).
- **Rationale:** Astro recomienda Nano Stores para compartir estado entre componentes interactivos (Islas) porque es framework-agnostic y extremadamente ligero. Permitirá que el "Diagnostic Wizard" y el "Chatbot" se comuniquen sin acoplar la aplicación a Redux o Context API globales que romperían el paradigma de islas.
- **Alternatives considered:** Zustand (atado a React), Redux (overkill).

### 3. Tailwind CSS Nativo
- **Decision:** Integración `@astrojs/tailwind` eliminando CDN.
- **Rationale:** Al compilar los estilos en build-time, el CSS final solo incluirá las clases utilizadas, reduciendo drásticamente el peso de la página y erradicando el FOUC (Flash of Unstyled Content).
