# Objetivo
Generar la "Spec 006: Ecosistema Web con Astro (Fachada de Difusión)" para Datanestiq.

# Contexto Estratégico
El prototipo actual (HTML/Tailwind/Vanilla JS) ha sido validado, pero el contenido dinámico renderizado por JS (como la futura OpenWiki) no es indexable por motores de búsqueda. Para solucionar esto y mantener la filosofía de rendimiento de élite, Datanestiq adoptará **Astro** como framework público. 
El objetivo es aislar la capa de difusión pública (Frontend Web) de la capa de documentación privada (Spec 005 - Motor Interno), usando Astro para compilar archivos Markdown a páginas ultra-rápidas con SEO perfecto.

# Requerimientos de la Spec a generar
Actúa como un Arquitecto de Software de Élite y genera el archivo `spec.md` para la Spec 006 siguiendo la metodología SDD.

Debe incluir:
1. **Migration Path (Vanilla JS to Astro):** Arquitectura técnica de cómo se portará el `index.html` y el `app.js` actuales a Componentes `.astro` aislando el Chatbot/Wizard como Islas de Javascript (Islands Architecture).
2. **OpenWiki Facade Integration:** Cómo Astro leerá el repositorio de la OpenWiki (Markdown) y utilizará Colecciones de Contenido (Content Collections) para generar el "Blog de Ingeniería".
3. **SEO & Performance Standards:** Reglas estrictas para asegurar 100/100 en Lighthouse y metadata dinámica (OpenGraph, Schema.org) inyectada en cada artículo publicado.
4. **User Stories & Acceptance Scenarios:**
   - Como usuario B2B, quiero entrar a un artículo técnico y que cargue instantáneamente (0 JS innecesario) para tener una percepción de alta calidad tecnológica.
   - Como Google Crawler, quiero poder leer todo el texto estructurado en HTML plano para indexarlo rápidamente.

**Restricciones:** 
- Mantener el diseño Premium (Dark Mode B2B).
- NO sugerir frameworks alternativos (Next.js está prohibido por ser overkill).
