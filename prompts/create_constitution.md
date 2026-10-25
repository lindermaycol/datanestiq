# Prompt para Generar el Steering File (Constitución)

**Rol:** Director de Arquitectura y Estratega B2B de Datanestiq.

**Contexto:**
Estamos implementando la metodología de desarrollo impulsada por especificaciones (GitHub SDD / Spec-Kit) para construir y mantener el ecosistema digital de Datanestiq, una consultora de IA y Datos de alto nivel (High-Ticket B2B). En esta metodología, existe un "Steering File" maestro llamado `constitution.md` que reside en `.specify/memory/`. Este archivo actúa como el "Cerebro Constante": dicta las reglas de diseño, tono, código y ética de negocio que TODOS los agentes de IA deben respetar obligatoriamente al planificar y programar.

**Tarea:**
Redacta el contenido fundacional para nuestro archivo `constitution.md`. 
El documento debe estar formateado en Markdown y contener reglas inquebrantables sobre:
1. **Identidad Corporativa y Tono:** Obligación de dirigirse a C-Levels (CEO, CTO, CDO) y nunca sonar como una agencia "barata" o genérica.
2. **Estándares de Interfaz (UI/UX):** Exigencia absoluta de Motion Design fluido (GSAP, 60fps) y accesibilidad estricta WCAG 2.2.
3. **Calidad de Código y Arquitectura:** Directrices para el uso de Datos Estructurados (Schema.org), carga diferida (Lazy Loading), y uso eficiente de LLMs locales (Transformers.js) y remotos (vía Groq).
4. **Seguridad y Privacidad:** Restricciones de no almacenar PII (Personal Identifiable Information) de ejecutivos sin sanitización, y prevención de Prompt Injection en nuestras interfaces AI.

**Formato de Salida:**
Devuelve únicamente el código Markdown estructurado, listo para ser copiado y guardado como nuestro `constitution.md`.
