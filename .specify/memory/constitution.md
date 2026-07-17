# Datanestiq Constitution (Steering File)

**Rol:** Director de Arquitectura y Estratega B2B de Datanestiq.
**Ubicación:** `.specify/memory/constitution.md`

Este documento actúa como el "Cerebro Constante" del ecosistema digital de Datanestiq (Consultora de IA y Datos High-Ticket B2B). Dicta las reglas inquebrantables de diseño, tono, código y ética de negocio que TODOS los agentes de IA deben respetar obligatoriamente al planificar y programar.

---

## 1. Obligatoriedad de la Metodología (GitHub SDD)

- **Specification-Driven Development (SDD) Mandatorio:** Queda estrictamente prohibido escribir código de implementación sin antes haber completado el ciclo formal de la metodología Spec-Kit (GitHub SDD). Todo cambio o nueva característica DEBE pasar secuencialmente por:
  1. Redacción de Especificación (`/specify`)
  2. Planificación Técnica (`/plan`)
  3. Desglose de Tareas (`/tasks`)
  4. Ejecución del Código (`/implement`)
- Cualquier agente o desarrollador que intente saltarse este pipeline (por ejemplo, escribiendo código directo sin un `tasks.md` aprobado) estará violando esta constitución y su PR será rechazada.

## 2. Identidad Corporativa y Tono

- **Audiencia C-Level (Exclusividad):** Toda la comunicación, arquitectura de información y micro-copy debe dirigirse exclusivamente a tomadores de decisión ejecutivos (CEO, CTO, CDO, CIO).
- **Posicionamiento Premium B2B:** Queda estrictamente prohibido emplear lenguaje genérico, de agencia "barata", o enfoques SaaS masivos. El tono debe ser consultivo, autoritario, sofisticado, enfocado en el retorno de inversión (ROI) y en la transformación profunda de negocio. 
- **Cero Clichés:** Evitar frases vacías de marketing. Hablamos de soluciones de IA aplicadas a casos de uso empresariales complejos.

## 3. Estándares de Interfaz (UI/UX)

- **Motion Design Fluido e Intencional:** Exigencia absoluta de usar **GSAP** para animaciones, orquestando micro-interacciones, scroll effects y transiciones a 60fps estables. El diseño debe sentirse vivo y responsivo para transmitir innovación.
- **Accesibilidad Inquebrantable:** Cumplimiento estricto y auditable de las normativas **WCAG 2.2 (Nivel AA mínimo)**. Esto exige un contraste adecuado, soporte total para navegación por teclado, focus states definidos y compatibilidad con lectores de pantalla.
- **Estética High-End:** Uso de dark modes bien calibrados, paletas de colores sobrias, tipografía fluida y moderna (ej. Inter), glassmorphism controlado y jerarquía visual que guíe al ejecutivo hacia la conversión sin fricción.

## 4. Calidad de Código y Arquitectura

- **SEO Técnico Avanzado (Schema.org):** Implementación rigurosa de Datos Estructurados (Schema.org) vía JSON-LD en todo el sitio para maximizar el descubrimiento y posicionamiento en buscadores empresariales.
- **Performance Extrema y Lazy Loading:** Toda imagen, video, iframe o componente no crítico fuera del viewport inicial debe implementar carga diferida (Lazy Loading). El Critical Rendering Path debe estar optimizado para tiempos de carga sub-segundo.
- **Arquitectura de IA Eficiente:**
  - **Edge/Local AI:** Uso de `Transformers.js` en el cliente para tareas ligeras (clasificación rápida, embeddings pequeños, validaciones semánticas) reduciendo costos y latencia.
  - **Cloud/Remote AI:** Integración de **Groq** (y modelos de lenguaje de código abierto ultrarrápidos) para el razonamiento pesado y la generación conversacional avanzada.

## 5. Seguridad y Privacidad

- **Protección de Datos (PII) por Diseño:** Restricción absoluta de almacenar, registrar (loggear) o procesar en texto plano cualquier PII (Personal Identifiable Information) de los ejecutivos y prospectos. Toda información sensible debe pasar por procesos estrictos de sanitización, enmascaramiento o anonimización antes de tocar cualquier base de datos o LLM de terceros.
- **Defensa contra Prompt Injection:** Nuestras interfaces impulsadas por IA (como el AI Concierge) deben contar con validaciones de entrada robustas, sanitización, y System Prompts blindados que bloqueen intentos de Prompt Injection, Jailbreaking, o extracción de instrucciones internas del negocio.

## 6. Arquitectura de Dependencias (Specs 01 al 06)

Para asegurar la coherencia del ecosistema, todo agente debe respetar el Grafo de Dependencias Maestro:
- **La Fuente de la Verdad (Spec 003):** La taxonomía oficial (6 Pilares Tecnológicos y 10 Sectores) es inmutable. Ningún agente puede inventar servicios o sectores fuera de esta matriz sin autorización explícita.
- **La Fábrica de Copy (Spec 004):** Toda redacción orientada a ventas debe regirse por los frameworks (PAS, StoryBrand) y los 10 arquetipos de agentes de nuestra metodología de Desarrollo Digital Premium.
- **La Documentación Viva (Spec 005):** Todo cambio en el código debe ser documentado automáticamente. El agente OpenWiki auditará que el frontend no rompa la taxonomía, ni que el backend introduzca código no documentado.
- **El Frontend de Difusión (Spec 006):** El prototipo se ha migrado a Astro. Todo el frontend debe respetar la arquitectura estática (SSG) y reactividad bajo demanda (Islands).
- **La Expansión Multi-Página (Spec 007):** Estrategia de enrutamiento y SEO para la escalabilidad de páginas y servicios.
- **El Backend CMS Headless (Spec 008):** Todo diseño arquitectónico de persistencia de datos (leads, posts) debe considerar integraciones limpias vía APIs REST hacia la instancia Headless WordPress local, manteniendo el frontend (Astro) puro y desconectado del core WP.
