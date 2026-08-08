# Plan de Ejecución: Estandarización de Diseño & Ejecución de Agentes (Spec 004)

Este plan detalla las acciones técnicas requeridas para alinear el ecosistema Astro (Spec 006/007) con el estándar estético sobrio del prototipo original (Spec 001), y para aplicar los frameworks de copywriting (Agentes de la Spec 004) en las nuevas páginas y componentes.

## Bloque 1: Consolidación del Sistema de Color y UI

### `tailwind.config.mjs`
- **Reemplazar** la paleta `colors` para eliminar los tokens duplicados (`primary`/`brand`) y el morado (`accent`).
- **Nueva paleta:** `background`, `surface`, `darker`, `brand` (#2563EB), `brandHover`, `brandCyan`, y `secondary`.

### Global CSS y Componentes (9 archivos afectados)
- **Refactorización de Nombres:** Reemplazar `primary` por `brand` y `accent` por `brandCyan` en:
  - `Footer.astro`, `Services.astro`, `Navbar.astro`, `global.css`, `Hero.astro`, `Faq.astro`, `[slug].astro`, `SolutionContrast.astro`, `SolutionHero.astro`.
- **Eliminación del Gradiente Morado (Exceptions):**
  - En `Navbar.astro` (Logo): Usaremos `text-white` o un gradiente `from-white to-gray-400`.
  - En `Hero.astro` (H1 Highlight): Usaremos `text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500`.
  - Revisar y aplicar la misma lógica en `SolutionHero.astro` si existe.
- **Botones Píldora:** En `global.css`, actualizar `.btn-primary` a `rounded-full` (manteniendo `rounded-2xl` en las tarjetas `glass-card`).

## Bloque 2: Captura de Lead Visible (Chatbot)

### `src/components/islands/Chatbot.jsx`
- **Componente de Confirmación:** Implementar estado `showLeadCard` y renderizar un formulario in-chat con los campos `email` y `telefono` pre-rellenados, y un botón "Confirmar y agendar diagnóstico".
- **Lógica de Persistencia:** Mover el guardado en `localStorage` (o el log final) al evento de click en el botón de confirmación, desactivando el guardado "silencioso" por regex.

## Bloque 3: Ejecución de Agentes Pendientes (Spec 004)

### `src/pages/nosotros.astro` (Agente 06)
- Crear nueva página con `BaseLayout`, `Navbar` y `Footer`.
- Copy: Narrativa consultiva C-Level sobre la misión de Datanestiq y su equipo/principios de trabajo.

### `src/pages/casos-de-exito.astro` (Agente 04)
- Crear nueva página estática para casos ilustrativos.
- **[IMPORTANT] Restricción Ética:** Redactar 3-4 "escenarios de aplicación" utilizando arquetipos de industria (sin mencionar marcas reales falsas).
- Actualizar explícitamente `specs/004-metodologia-desarrollo-digital/tech_debt.md` registrando la necesidad futura de incorporar testimonios reales.

### `src/data/taxonomyCorpus.json` (Agente 05)
- Refinar el copy de `hero.headline`, `hero.subheadline`, `contrast.problem` y `contrast.solution` para los 6 pilares, enfocándolo más a ROI y ejecutivos. Mantendremos inmutables los IDs, SEO y features.

### `src/components/ui/Faq.astro` (Agente 10)
- Expandir de 3 a 6-7 objeciones reales B2B (costos, tiempos, seguridad de datos, garantía de ROI).
- Asegurar mantenimiento de atributos de accesibilidad (`aria-expanded`, `aria-controls`).

### `src/components/ui/Navbar.astro` & `Footer.astro`
- Agregar enlaces hacia `/nosotros` y `/casos-de-exito`.

## Trazabilidad y Verificación

### Documentación
- Crear o actualizar `specs/004-metodologia-desarrollo-digital/tasks.md` registrando el estado retroactivo y actual de los agentes (01 al 10).

### Validación Final
- Ejecutar `npm run build` y `npm run preview`.
- Validar LCP visual, accesibilidad en Lighthouse y ausencia total de gradientes `blue-purple`.
