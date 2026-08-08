# Plan de Implementación: Profundidad de Páginas y Navegación (SDD)

## 1. Overview
**Objetivo:** Consumir y renderizar en la interfaz (`.astro`) la riqueza de datos añadida a la taxonomía durante la Fase 3, para dotar de profundidad técnica y comercial a las páginas de soluciones y sectores. Además, resolver la ausencia de páginas hub (`/soluciones`, `/sectores`), enlaces legales rotos y generar contenido de valor en el blog para el Sector Público.
**Entregables:** Actualizaciones en UI (`[id].astro`, `[slug].astro`), creación de Hubs (`/soluciones/index.astro`, `/sectores/index.astro`), ajuste en `Navbar.astro` y Footer, páginas legales (`/privacidad`, `/terminos`), y 3 posts de blog.

## 2. Impacto en Specs
Las siguientes especificaciones recibirán actualizaciones conceptuales que guiarán el desarrollo futuro:
- **Spec 003 (Taxonomía de Servicios):** Se define formalmente que la capa de UI (`/soluciones/*` y `/sectores/*`) es responsable de renderizar explícitamente los campos enriquecidos (incluyendo `objectionResponses`, `deploymentModels` y `engagementModels`), garantizando que la riqueza de la taxonomía alcance al usuario final y no solo al chatbot.
- **Spec 007 (Expansión Multi-Página):** Se consolida la arquitectura de enrutamiento estático incluyendo: (1) Páginas Hub (índices dinámicos para `/soluciones` y `/sectores`), (2) Accesibilidad desde la barra de navegación principal, y (3) Existencia obligatoria de páginas legales (`/privacidad`, `/terminos`) para construir confianza institucional B2B.
- **Spec 012 (Fábrica de Contenido):** Se estipula la generación y despliegue de 2 a 3 artículos de blog enfocados en el Sector Público, abordando temas de interoperabilidad, gobierno de datos institucional y cumplimiento normativo.

## 3. Diseño de páginas de solución (`/soluciones/*`)
El archivo `src/pages/soluciones/[id].astro` será ampliado para renderizar:
- **Bloque "Resolvemos tus dudas":** Mapeo de `relatedRoles` iterando sobre sus `objectionResponses`. Se mostrará el rol (ej. CIO), su duda (objeción) y nuestra solución real (respuesta), construyendo un FAQ consultivo.
- **Validación visual de `[EST]`:** Los `proofPoints` ya se renderizan, pero me aseguraré de que la insignia `[EST]` se resalte visualmente con estilos si es necesario para reforzar la honestidad del dato.
- *Nota:* `techStack` y `competitivePositioning` ya están programados en la UI, por lo que el enriquecimiento previo fluirá naturalmente.

## 4. Diseño de páginas de sector (`/sectores/*`)
El archivo `src/pages/sectores/[slug].astro` será ampliado para renderizar:
- **Modelos de Despliegue y Contratación:** Si existen `deploymentModels` y `engagementModels` (añadidos en Público), se crearán nuevas secciones visuales para mostrarlos (ej. "Cómo desplegamos y operamos" y "Modalidades de trabajo").
- **Bloque "Resolvemos tus dudas":** Al igual que en soluciones, consumiremos los `relevantPersonas` del sector y expondremos sus `objectionResponses` para atajar fricciones de compra específicas del nicho.
- **KPIs (ya existentes):** Comprobación visual de que el texto con `[EST]` fluye correctamente.

## 5. Hubs y navegación
- **Hub `/soluciones/index.astro`:** Grilla dinámica iterando sobre `taxonomyCorpus.json`. Muestra nombre, extracto (description) y link a `/soluciones/[slug]`.
- **Hub `/sectores/index.astro`:** Grilla dinámica iterando sobre `sectorsCorpus.json`. Muestra ícono, título y link a `/sectores/[slug]`.
- **`Navbar.astro`:** Reemplazar o complementar los enlaces `/#servicios` con accesos directos a "Soluciones" (`/soluciones`) y "Sectores" (`/sectores`). 
- **Sitemap:** Astro detectará automáticamente los `index.astro` y los inyectará en `sitemap-index.xml`.

## 6. Páginas legales
- Creación de `src/pages/privacidad.astro` y `src/pages/terminos.astro` con diseños limpios basados en `BaseLayout` y contenido de borrador profesional.
- **Footer.astro:** Actualización de las anclas `href="#"` para que apunten a `/privacidad` y `/terminos`.

## 7. Consumo de taxonomía
- `/soluciones/[id].astro` consume: `techStack`, `competitivePositioning`, `proofPoints` (desde Pilar), y `objectionResponses` (desde Persona).
- `/sectores/[slug].astro` consume: `subSectors`, `kpis`, `regulations` (desde Sector), `deploymentModels`, `engagementModels` (desde Sector), y `objectionResponses` (desde Persona).
- *Gaps detectados:* Ninguno aparente. La F3 dejó el corpus plenamente poblado. Todo se obtendrá de `taxonomyCorpus.json`, `sectorsCorpus.json` y `personas.json`.

## 8. Riesgos y mitigaciones
- **Riesgo:** Duplicación de rutas si un Hub colisiona con el catch-all `[...slug]`.
  - **Mitigación:** Usaré carpetas estructuradas y `index.astro` bajo `/soluciones/` y `/sectores/`.
- **Riesgo:** Fallos en renderizado por propiedades `undefined` (`engagementModels` solo existe en Público).
  - **Mitigación:** Uso estricto de optional chaining y condicionales `{sector.engagementModels && ... }` en JSX/Astro.

## 9. Tareas (Preliminar)
1. Escribir `/soluciones/index.astro` y `/sectores/index.astro`.
2. Actualizar `Navbar.astro` y `Footer.astro`.
3. Crear `/privacidad.astro` y `/terminos.astro`.
4. Extender UI de `[id].astro` (Soluciones) para `objectionResponses`.
5. Extender UI de `[slug].astro` (Sectores) para `deploymentModels`, `engagementModels` y `objectionResponses`.
6. Generar 2-3 artículos de blog con `node scripts/docs-generator.mjs`.
7. Ejecutar `npm run build` y testear.

> [!IMPORTANT]
> **Revisión Requerida:** Por favor revisa este plan. Estoy posicionado estrictamente como consumidor (renderizando la data generada en la Fase 3) y no modificaré ni un solo dato nativo de la taxonomía. Haz clic en **Proceed** si estás de acuerdo para comenzar la implementación de la interfaz.
