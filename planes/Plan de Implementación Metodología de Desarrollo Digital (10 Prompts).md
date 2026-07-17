# Plan de Implementación: Metodología de Desarrollo Digital (10 Prompts)

He renombrado exitosamente la especificación de Taxonomía de `004` a `003` (y su carpeta correspondiente). 

Ahora, basándome en la imagen que proporcionaste, veo un poderoso sistema de 10 *prompts/personas* de IA diseñado para construir sitios web de categoría premium ("$10K value"). Dado que acabamos de añadir el servicio de **"Desarrollo Digital Inteligente"**, esta imagen es la base perfecta para estructurar la metodología operativa interna de ese servicio.

## User Review Required

> [!IMPORTANT]
> Proponemos crear la **Spec 004: Metodología de Desarrollo Digital Premium**. Este documento oficializará cómo Datanestiq entrega sus servicios web utilizando IA, adoptando los 10 agentes especializados de tu imagen (Premium Website Builder, First Impression Engineer, Conversion Page Copywriter, etc.).

## Open Questions

> [!NOTE]
> **Modularidad de Prompts:** Crearemos **Una Especificación Principal (004)** para la orquestación, y dentro de su carpeta, un sub-directorio `/prompts` con 10 archivos `.md` individuales. Así logramos la modularidad que buscas sin fragmentar la raíz.
>
> **Librería OpenWiki (LangChain):** He analizado la imagen de OpenWiki. Es una excelente herramienta de CLI para generar y mantener documentación (Wiki) orientada específicamente para Agentes de Código (como yo). Se engancha con `AGENTS.md` y crea PRs automáticas. He añadido la creación de la **Spec 005** para documentar e implementar este sistema en tu repositorio.



## Proposed Changes

### Especificaciones Técnicas

#### [NEW] [004-metodologia-desarrollo-digital/spec.md](file:///c:/xampp/htdocs/datanestiq/specs/004-metodologia-desarrollo-digital/spec.md)
- Se creará la especificación maestra detallando el flujo de orquestación de 5 pasos (Step 1 a Step 5) indicado en la metodología.
- **Alcance Transversal:** Esta metodología NO se limitará solo al servicio de "Desarrollo Digital Inteligente". Actuará como el estándar de oro de la agencia para construir páginas de ventas, landing pages y *copywriting* persuasivo para **todos los pilares tecnológicos** (Ingeniería de Datos, RPA, IA, etc.).

#### [NEW] [004-metodologia-desarrollo-digital/prompts/](file:///c:/xampp/htdocs/datanestiq/specs/004-metodologia-desarrollo-digital/prompts/)
Se crearán 10 archivos Markdown independientes, uno para cada Agente/Prompt, para mantener el sistema modular y fácil de iterar:
  1. `01-premium-website-builder.md`
  2. `02-first-impression-engineer.md`
  3. `03-conversion-page-copywriter.md`
  4. `04-portfolio-creator.md`
  5. `05-service-page-copywriter.md`
  6. `06-about-page-storyteller.md`
  7. `07-mobile-optimizer.md`
  8. `08-website-copy-system.md`
  9. `09-trust-proof-architect.md`
  10. `10-objection-killing-faq.md`

#### [NEW] [005-openwiki-agentes/spec.md](file:///c:/xampp/htdocs/datanestiq/specs/005-openwiki-agentes/spec.md)
- Se creará una especificación para la adopción de **OpenWiki**, el sistema de documentación para agentes de IA de LangChain.
- Detallará cómo configurar el repositorio (`npm install -g openwiki`, `openwiki --init`) y cómo se enlazará con nuestros archivos de especificaciones actuales y el archivo `AGENTS.md`.
- El objetivo de esta spec es definir la infraestructura de "Documentación Viva", asegurando que un agente pueda automatizar el mantenimiento de todos los archivos MD que estamos creando.

#### [MODIFY] [003-taxonomia-servicios/spec.md](file:///c:/xampp/htdocs/datanestiq/specs/003-taxonomia-servicios/spec.md)
- Se añadirá una referencia cruzada global indicando que la entrega operativa, el diseño y el copywriting de **todos** los servicios B2B están regidos por la Spec 004 (Metodología Multi-Agente).

*(Nota sobre el Orden de Implementación: Tras tu consulta, confirmo que este es el flujo más lógico:)*
- **003 (Taxonomía):** Define QUÉ vendemos.
- **004 (Metodología):** Define CÓMO lo vendemos y construimos.
- **005 (OpenWiki):** Define CÓMO documentamos todo lo anterior para que no muera.
- **006 (WordPress):** Dónde lo desplegamos finalmente.

## Verification Plan

### Manual Verification
- Revisar que la nueva especificación capture los 10 roles y los flujos de trabajo (Step 1 a Step 5) exactos que muestra la imagen.
- Asegurar que todas las referencias numéricas de las carpetas estén correctas (Taxonomía = 003, Metodología Web = 004).
