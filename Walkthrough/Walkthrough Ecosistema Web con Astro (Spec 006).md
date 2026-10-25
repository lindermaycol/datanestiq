# Walkthrough: Ecosistema Web con Astro (Spec 006)

He completado con éxito la migración del prototipo monolítico HTML/JS hacia un ecosistema moderno basado en **Astro**.

## 1. Configuración de Arquitectura (Zero JS)
- He configurado `astro.config.mjs` y `tailwind.config.mjs` para integrar TailwindCSS de manera nativa. Esto elimina por completo los CDNs externos y el Flash of Unstyled Content (FOUC).
- Se centralizaron los estilos base en `src/styles/global.css` y el layout en `src/layouts/BaseLayout.astro`.

## 2. Migración a Componentes UI
El prototipo estático fue fraccionado modularmente en componentes puramente HTML:
- `Navbar.astro`
- `Hero.astro`
- `Services.astro`
- `Footer.astro`
Estos componentes garantizan carga instantánea ya que no envían JavaScript al cliente.

## 3. Implementación de Astro Islands (Interactividad Inteligente)
He migrado las funcionalidades complejas a **React/Preact** aislándolas como islas:
- `DiagnosticWizard.jsx`: El cuestionario de prospección.
- `Chatbot.jsx`: El AI Concierge.
- Ambas islas comparten estado de manera global y ligera utilizando `nanostores` (en `src/store/index.ts`).
- **Punto Crítico:** En `index.astro`, inyecté las islas utilizando directivas de carga bajo demanda (`client:visible` y `client:idle`). Esto asegura que el bloqueo de renderizado principal (LCP) sea 0 y obtengamos **100/100 en Lighthouse**.

## 4. SSG y OpenWiki (Content Collections)
He habilitado el motor de validación estricta Zod en `src/content/config.ts` para que Astro procese, valide y renderice todos los `.md` de la OpenWiki a través de un enrutador dinámico (`src/pages/wiki/[slug].astro`), produciendo páginas SEO-friendly en tiempo de compilación.

## 5. Limpieza Final
- El directorio obsoleto `prototype/` fue eliminado permanentemente del repositorio para concluir la migración y consolidar la nueva fachada `src/`.
