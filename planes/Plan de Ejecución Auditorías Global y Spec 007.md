# Plan de Ejecución: Auditorías Global y Spec 007

Este documento detalla el plan de acción paso a paso para resolver absolutamente todos los hallazgos identificados en las auditorías de Claude Code: `prompt-antigravity-auditoria-spec007-correcciones.md` y `prompt-antigravity-auditoria-global-specs.md`.

> [!WARNING]
> ## User Review Required / Open Questions
> Hay 4 decisiones estratégicas que el usuario debe tomar antes de proceder:
> 
> 1. **Ruta `base` en Astro (Bloque 1.1 Spec 007):** Claude indica eliminar `base: '/datanestiq/dist/'` de `astro.config.mjs`. **Ojo:** Si lo elimino, el sitio compilado en la carpeta `dist/` dejará de cargar correctamente si lo ves directamente desde XAMPP (`http://localhost/datanestiq/dist/`), y volveremos a depender del servidor Node (`npm run dev`). ¿Deseas eliminarlo de todos modos para priorizar el SEO en producción, o lo mantenemos mientras pruebas en local?
> 2. **Archivos Residuales (Bloque G Global):** ¿Deseas que conservemos la carpeta antigua `prototype/` como archivo histórico, o procedemos a eliminarla por completo para limpiar el repositorio?
> 3. **Transformers.js (Bloque C Global):** ¿Deseas que posponga el uso de IA local (Transformers.js) documentando la deuda técnica, o intentamos implementar una versión mínima para clasificar los inputs del usuario?
> 4. **Pipeline LangGraph (Bloque E Global):** El script `langgraph_pipeline.py` no existe, rompiendo la CI/CD. ¿Deshabilito el workflow de GitHub Actions temporalmente, o construyo un andamiaje Python mínimo?

---

## 🛠️ Fase 1: Auditoría Spec 007 (Infraestructura, SEO, UX)

### [Componente] Configuración e Infraestructura Visual
- **[MODIFY] `astro.config.mjs`:** Eliminar propiedad `base` (pendiente de confirmación).
- **[NEW] `public/favicon.svg`, `public/grid.svg`, `public/og-image.jpg`:** Crear assets faltantes para evitar errores 404 silenciosos.
- **[MODIFY] `src/layouts/BaseLayout.astro`:** Inyectar script de Phosphor Icons y agregar soporte de accesibilidad `prefers-reduced-motion` para GSAP.
- **[MODIFY] `tailwind.config.mjs`:** Agregar tokens de color faltantes (`darker`, `brand`, `brandCyan`).
- **[MODIFY] `src/styles/global.css`:** Añadir estados `:focus-visible` a `.btn-primary` y `.glass-card` (WCAG 2.2).

### [Componente] Lógica de Componentes e Islas (Astro/React)
- **[MODIFY] `src/components/islands/Chatbot.jsx`:** Corregir typo en la clase Tailwind (`bg-brand Cyan`).
- **[MODIFY] `src/components/ui/SEO.astro`:** Corregir el JSON-LD (propiedad `description`).
- **[MODIFY] `src/components/islands/DiagnosticWizard.jsx`:** Capturar parámetro `?servicio=` de la URL y disparar evento de contexto hacia el Chatbot vía `lastUserQuery`.
- **[MODIFY] `src/components/ui/Navbar.astro`:** Cambiar anchors de `#seccion` a `/#seccion` para la navegación multi-página, y añadir `aria-label` a los botones del menú móvil.

### [Componente] Taxonomía (Spec 003)
- **[MODIFY] `src/data/taxonomyCorpus.json`:** Agregar los 4 pilares tecnológicos faltantes para completar los 6 oficiales (Hiperautomatización, Business Intelligence, Sistemas Digitales, Estrategia).
- **[MODIFY] `src/components/ui/Services.astro`:** Corregir las tarjetas 4, 5 y 6 para que coincidan con la taxonomía real.
- **[MODIFY] `src/components/ui/Footer.astro`:** Reemplazar links muertos por los enlaces reales a `/soluciones/[slug]`.

---

## 🏗️ Fase 2: Auditoría Global (Metodología, SDD y Specs 001-008)

### [Componente] Código de Microexperiencias IA (Spec 001/002)
- **[MODIFY] `src/components/islands/Chatbot.jsx`:** 
  - Añadir función `extractLeadSignals` (Regex) para identificar emails y teléfonos de la conversación.
  - Implementar persistencia temporal de leads usando `localStorage` (como fallback antes de Spec 008).

### [Componente] Documentación y SDD (Spec-Kit)
- **[NEW] `specs/001-elevacion-premium/tasks.md`**: Crear tareas retroactivas. Cambiar estado a *Partially Implemented*.
- **[NEW] `specs/002-microexperiencias-ia/tasks.md`**: Crear tareas retroactivas. Cambiar estado a *Partially Implemented*.
- **[NEW] `specs/003-taxonomia-servicios/tasks.md`**: Crear tareas retroactivas.
- **[MODIFY] `specs/003-taxonomia-servicios/tech_debt.md`**: Cambiar ítem de migracion a Astro Content Collections de *Resuelto* a *Pendiente*.
- **[MODIFY] `specs/004-metodologia-desarrollo-digital/tech_debt.md`**: Marcar CI/CD LangGraph como *ROTO*. Actualizar archivo de Actions `.github/workflows/langgraph_pipeline.yml`.
- **[MODIFY] `.agents/AGENTS.md`**: Corregir la descripción de la Spec 006 (Astro) y añadir Spec 007 y 008 a la jerarquía de verdad. Modificar igual en `.specify/memory/constitution.md`.
- **[MODIFY] `specs/006-ecosistema-astro/tasks.md`**: Desmarcar T019 (borrado de `prototype/`) y añadir nota.
- **[MODIFY] `specs/007-multi-pagina/tasks.md`**: Actualizar checklist con resultados verificados.
- **[MODIFY] `specs/007-multi-pagina/tech_debt.md`**: Añadir log de los bugs resueltos en la Fase 1.

### [Componente] Arquitectura WordPress (Spec 008)
- **[NEW] `specs/008-headless-wordpress/plan.md`**: Redactar plan arquitectónico para CPTs y REST API.
- **[NEW] `specs/008-headless-wordpress/tasks.md`**: Desglosar las User Stories en tareas de implementación backend.
*(Nota: Tal como indica la auditoría, la implementación del código PHP/WordPress de esta Spec NO se ejecutará hasta tu confirmación explícita en otro ciclo).*

---

## 🧪 Verification Plan

### Automated Tests
- Ejecutar `npm run build` para validar que `astro build` procesa las 6 rutas dinámicas generadas en `dist/soluciones/` y que no arroja errores 404 en assets.

### Manual Verification
- Validar visualmente que el JSON-LD renderiza correctamente la description.
- Validar en el navegador la persistencia de `datanestiq_leads` en el LocalStorage tras usar el Chatbot.
- Validar que al cliquear en "Inicia tu Diagnóstico" desde una subpágina, el Chatbot se abra en el Inicio con el contexto.
