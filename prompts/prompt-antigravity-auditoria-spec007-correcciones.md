# Prompt para Antigravity: Auditoría completa post-Spec 007 → correcciones críticas, taxonomía y pulido premium

Actúa como un **Senior Astro/React Engineer, especialista en SEO técnico, arquitecto de information architecture B2B y experto en accesibilidad (WCAG 2.2 AA)**.

Este documento es el resultado de una **auditoría completa** del proyecto Datanestiq realizada por Claude (Sonnet 5) sobre el estado actual del repositorio, en la rama `007-multi-pagina`. La auditoría comparó `specs/007-multi-pagina/*.md` (spec, plan, tasks, data-model) y `.specify/memory/constitution.md` contra el código real en `src/`. Se encontraron **discrepancias entre lo que `tasks.md` marca como `[X]` completado y lo que el código realmente hace**, además de bugs concretos que rompen SEO, UX y la identidad visual "premium B2B" exigida por la Constitution.

Aplica **todas** las correcciones de este documento, en el orden indicado. Al terminar cada bloque, verifica con `npm run build` que no haya errores y que `dist/` se genere correctamente. **No hagas cambios que no estén listados aquí** — si detectas algo adicional, anótalo al final en una sección "Hallazgos adicionales" en vez de modificarlo directamente.

Trabaja sobre: `c:\xampp\htdocs\datanestiq\`

---

## BLOQUE 1: Bugs críticos de infraestructura y SEO

### 1.1 — Eliminar el `base` erróneo en `astro.config.mjs`

**Problema:** `astro.config.mjs` tiene `base: '/datanestiq/dist/'`. Esto contamina *todas* las URLs canónicas, OpenGraph y el sitemap. Verificado en `dist/index.html` generado:
```html
<link rel="canonical" href="https://datanestiq.com/datanestiq/dist/">
<meta property="og:url" content="https://datanestiq.com/datanestiq/dist/">
```
Esto viola directamente el Success Criteria #2 de la Spec 007 ("100% de las URLs generadas cuentan con... `<link rel="canonical">` que previene el contenido duplicado"). El sitio en producción vive en `https://datanestiq.com/`, no bajo un subpath `/datanestiq/dist/`.

**Corrección:**
```js
// astro.config.mjs
export default defineConfig({
  site: 'https://datanestiq.com',
  // ELIMINAR esta línea por completo:
  // base: '/datanestiq/dist/',
  integrations: [tailwind(), react(), sitemap()],
  output: 'static',
  vite: {
    server: {
      proxy: {
        '/api': {
          target: 'http://localhost/datanestiq/public',
          changeOrigin: true
        }
      }
    }
  }
});
```
Tras el cambio, reconstruye (`npm run build`) y confirma que `dist/index.html` tenga `<link rel="canonical" href="https://datanestiq.com/">` (sin el subpath).

### 1.2 — Restaurar los assets estáticos faltantes (404 silenciosos)

**Problema:** El código referencia tres archivos en `public/` que **no existen**, por lo que fallan en silencio (404):
- `src/layouts/BaseLayout.astro` → `<link rel="icon" href="/favicon.svg" />`
- `src/components/ui/Hero.astro` y `SolutionHero.astro` → `bg-[url('/grid.svg')]`
- `src/components/ui/SEO.astro` → `image = "https://datanestiq.com/og-image.jpg"` (imagen por defecto para OpenGraph/Twitter Cards — si falta, cualquier link compartido en LinkedIn/Slack se ve roto, algo inaceptable para un posicionamiento premium B2B)

**Corrección:**
1. Crea `public/favicon.svg`: un ícono SVG simple y minimalista coherente con la identidad de marca (monograma "D" o ícono geométrico), usando la paleta `primary` (#3B82F6) / `accent` (#8B5CF6) del `tailwind.config.mjs`.
2. Crea `public/grid.svg`: un patrón de grid/malla sutil (líneas finas, `stroke-opacity` bajo) en `currentColor` o blanco con baja opacidad, pensado para usarse como textura de fondo detrás del Hero (ya se le aplica un `mask-image` de degradado en el CSS, así que el SVG debe ser tileable).
3. Crea `public/og-image.jpg` (1200x630px): una imagen de marca para OpenGraph con el logotipo/wordmark "DATANESTIQ" y el tagline "Transformamos Datos en Ventaja Asimétrica" sobre el fondo `background` (#0B0F19), en estética "high-end dark mode" acorde a la Constitution. Si no puedes generar una imagen rasterizada, genera un SVG equivalente y expórtalo a JPG/PNG con las herramientas disponibles; si tampoco es posible, dilo explícitamente al final en "Hallazgos adicionales" en vez de dejar el `<meta>` apuntando a un archivo inexistente.

### 1.3 — Cargar Phosphor Icons (los íconos `ph ph-*` están rotos en todo el sitio)

**Problema:** `DiagnosticWizard.jsx`, `Chatbot.jsx` y `TrustLayer.astro` usan masivamente clases `<i class="ph ph-robot">`, `<i class="ph ph-bank">`, etc. (la librería [Phosphor Icons](https://phosphoricons.com/)), pero **la librería nunca se carga** en `BaseLayout.astro`. Resultado: todos esos íconos son invisibles/vacíos en producción.

**Corrección:** en `src/layouts/BaseLayout.astro`, dentro del `<head>`, junto a las fuentes:
```html
<!-- Phosphor Icons -->
<script src="https://unpkg.com/@phosphor-icons/web" defer></script>
```

### 1.4 — Definir los tokens de color faltantes en Tailwind (`brand`, `brandCyan`, `darker`)

**Problema:** `tailwind.config.mjs` solo define `background`, `surface`, `primary`, `secondary`, `accent`. Sin embargo, `DiagnosticWizard.jsx`, `Chatbot.jsx` y `TrustLayer.astro` usan constantemente clases como `bg-brand/20`, `text-brandCyan`, `border-brand/30`, `bg-darker` — clases que Tailwind **no genera** porque esos colores no existen en la config. El resultado es que el widget de chat y el wizard de diagnóstico se renderizan sin fondo, sin bordes de color ni acentos de marca (se ven "planos"/rotos comparados con el resto del sitio).

**Corrección:** añade los tokens faltantes en `tailwind.config.mjs`, coherentes con la paleta ya establecida (mantén `primary`/`accent` como están, no los reemplaces):
```js
// tailwind.config.mjs
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
  theme: {
    extend: {
      colors: {
        background: '#0B0F19',
        surface: '#111827',
        darker: '#05070C',
        primary: '#3B82F6',
        secondary: '#10B981',
        accent: '#8B5CF6',
        brand: '#3B82F6',
        brandCyan: '#22D3EE'
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
```
Nota: `brand` se alinea a `primary` (mismo azul) para no introducir una tercera identidad de color; `brandCyan` es el acento cian ya usado visualmente en los iconos de "Cloud/DevOps" (`text-cyan-400`) de `Services.astro`, así se mantiene consistente el sistema.

### 1.5 — Corregir el typo de clase en `Chatbot.jsx`

**Problema:** línea 98 de `src/components/islands/Chatbot.jsx`:
```jsx
className="fixed bottom-6 right-6 w-14 h-14 bg-brand Cyan text-white rounded-full shadow-lg flex items-center justify-center hover:bg-cyan-600 transition-colors z-50 ring-2 ring-brandCyan/50"
```
Hay un espacio suelto dentro de `bg-brand Cyan` que rompe la clase (Tailwind la lee como dos clases separadas: `bg-brand` y `Cyan`, esta última inválida).

**Corrección:**
```jsx
className="fixed bottom-6 right-6 w-14 h-14 bg-brandCyan text-white rounded-full shadow-lg flex items-center justify-center hover:bg-cyan-600 transition-colors z-50 ring-2 ring-brandCyan/50"
```

### 1.6 — Corregir el JSON-LD roto en `SEO.astro` (`serviceData.description` es `undefined`)

**Problema:** `src/components/ui/SEO.astro` construye el `Service` de Schema.org así:
```js
"description": serviceData.description
```
Pero los objetos de `taxonomyCorpus.json` **no tienen** un campo `description` en la raíz (solo `seo.description`, anidado). El resultado es que el JSON-LD generado en cada `/soluciones/[id]` tiene `"description": undefined` literal, lo que puede invalidar el structured data ante los validadores de Google.

**Corrección:** en `src/layouts/BaseLayout.astro`, donde se invoca `<SEO ... serviceData={serviceData} />`, ya se recibe `description` como prop separada (que sí viene correctamente poblada desde `service.seo.description` en `[id].astro`). Simplifica `SEO.astro` para que el JSON-LD reutilice esa misma variable en vez de buscar un campo inexistente en `serviceData`:
```astro
---
interface Props {
  title: string;
  description: string;
  url?: string;
  image?: string;
  serviceData?: {
    name: string;
  };
}

const { title, description, url = "https://datanestiq.com", image = "https://datanestiq.com/og-image.jpg", serviceData } = Astro.props;
---
<!-- ... resto igual ... -->
{serviceData && (
  <script type="application/ld+json" set:html={JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Service",
    "name": serviceData.name,
    "provider": {
      "@type": "Organization",
      "name": "Datanestiq"
    },
    "description": description
  })} />
)}
```

---

## BLOQUE 2: Reparar la Continuidad de Contexto (User Story 3 de la Spec 007 — actualmente NO funciona pese a estar marcada `[X]` en `tasks.md`)

**Problema:** La Spec 007 exige (Success Criteria #4): *"al hacer clic en el CTA de cualquier solución, el Wizard de destino inicializa el diálogo mencionando... el servicio de origen"*. `tasks.md` marca T009, T010 y T011 como completadas, pero al auditar el código:
- `SolutionHero.astro` sí genera el link `/?servicio=${serviceId}` (T009 ✅ correcto).
- `DiagnosticWizard.jsx` **no importa** `nanostores`, **no tiene ningún `useEffect`** que lea `URLSearchParams`, y **nunca llama** a `lastUserQuery.set(...)` (T010 y T011 ❌ no implementados).
- `Chatbot.jsx` sí está preparado para reaccionar (líneas 38-44: si `lastUserQuery` cambia, abre el chat y envía el mensaje), pero nada lo alimenta.

Resultado: hoy, al hacer clic en "Inicia tu Diagnóstico" desde cualquier `/soluciones/[id]`, el usuario aterriza en el home con un query param en la URL que **no hace absolutamente nada**.

### 2.1 — Capturar `?servicio=` en `DiagnosticWizard.jsx` y disparar el contexto

Añade en `src/components/islands/DiagnosticWizard.jsx`:
```jsx
import React, { useState, useEffect } from 'react';
import { lastUserQuery, chatbotOpen } from '../../store/index';
import corpus from '../../data/taxonomyCorpus.json';

// ... (mantener sectorsCorpus y el resto del componente igual)

export default function DiagnosticWizard() {
  // ... estados existentes (sectors, customSector, inputs, pitches, loadingIds) ...

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const servicioSlug = params.get('servicio');
    if (!servicioSlug) return;

    const service = corpus.find((s) => s.slug === servicioSlug);
    if (!service) return;

    lastUserQuery.set(
      `Vengo desde la página de "${service.name}". Quiero entender cómo Datanestiq puede ayudarme concretamente con este servicio.`
    );
    chatbotOpen.set(true);

    // Limpia el query param de la URL sin recargar la página
    const url = new URL(window.location.href);
    url.searchParams.delete('servicio');
    window.history.replaceState({}, '', url);
  }, []);

  // ... resto del componente sin cambios ...
}
```

**Verificación:** navega a `/soluciones/ai-data-science`, haz clic en "Inicia tu Diagnóstico", confirma que en `/` el Chatbot se abre automáticamente con un mensaje que menciona "Inteligencia Artificial & Data Science" (el `name` real del servicio, no el slug).

### 2.2 — Corregir los anchors del Navbar para que funcionen desde subpáginas

**Problema:** `src/components/ui/Navbar.astro` (usado también en `/soluciones/[id].astro`) tiene enlaces `href="#servicios"`, `href="#faq"`, `href="#roi"`. Estas secciones **solo existen en el home** (`index.astro`). Al hacer clic en "Servicios" desde `/soluciones/ai-data-science`, el navegador intenta hacer scroll a un elemento `#servicios` que no existe en esa página → no pasa nada.

**Corrección:** cambia los 6 enlaces (3 en el menú desktop, 3 en el menú mobile) de:
```html
<a href="#servicios" ...>Servicios</a>
<a href="#faq" ...>Metodología</a>
<a href="#roi" ...>Casos ROI</a>
```
a:
```html
<a href="/#servicios" ...>Servicios</a>
<a href="/#faq" ...>Metodología</a>
<a href="/#roi" ...>Casos ROI</a>
```
Así, desde cualquier subpágina el navegador primero navega al home y luego hace scroll a la sección.

---

## BLOQUE 3: Completar la Taxonomía Oficial (Spec 003) — actualmente solo 2 de 6 pilares existen

**Problema:** la Spec 003 (fuente de verdad inmutable, según la Constitution sección 6) define **6 Pilares Tecnológicos** oficiales. `src/data/taxonomyCorpus.json` solo contiene 2 (`ai-data-science`, `data-engineering`). Mientras tanto, `src/components/ui/Services.astro` en el home ya muestra **6 tarjetas**, pero 4 de ellas enlazan a slugs que **no existen** en el corpus (`business-intelligence`, `cloud-devops`, `automatizacion`, `estrategia` → 404 reales en producción hoy). Peor aún, **"Cloud & DevOps" no es uno de los 6 Pilares oficiales** de la Spec 003 — es un servicio inventado que viola la regla de taxonomía inmutable de la Constitution.

### 3.1 — Añadir los 4 pilares faltantes a `src/data/taxonomyCorpus.json`

Añade estos 4 objetos al array existente (mantén los 2 actuales sin cambios), respetando exactamente el schema de `specs/007-multi-pagina/data-model.md`:

```json
{
  "id": "hiperautomatizacion",
  "name": "Hiperautomatización Inteligente",
  "slug": "hiperautomatizacion",
  "seo": {
    "title": "Hiperautomatización RPA + IA para Empresas | Datanestiq",
    "description": "Orquestamos RPA, IDP y ecosistemas API para automatizar procesos críticos de extremo a extremo con precisión milimétrica."
  },
  "hero": {
    "headline": "Erradica la Fricción Operativa a Escala",
    "subheadline": "Orquestamos flujos de trabajo autónomos que combinan RPA y IA cognitiva para ejecutar procesos complejos sin intervención humana."
  },
  "contrast": {
    "problem": "Procesos manuales repetitivos que consumen horas-hombre críticas y generan errores costosos en operaciones de alto volumen.",
    "solution": "Robots de software y agentes cognitivos que ejecutan, validan y escalan procesos de extremo a extremo, 24/7 y sin fricción."
  },
  "features": [
    "Hiperautomatización de Procesos Core (RPA + Cognitive AI)",
    "Procesamiento Inteligente de Documentos (IDP / OCR Avanzado)",
    "Orquestación Serverless e Integración de Ecosistemas API"
  ]
},
{
  "id": "business-intelligence",
  "name": "Análisis de Datos, BI & Dashboards",
  "slug": "business-intelligence",
  "seo": {
    "title": "Business Intelligence y Dashboards Ejecutivos | Datanestiq",
    "description": "Dashboards ejecutivos de alta densidad y Process Mining para traducir terabytes de datos en decisiones estratégicas."
  },
  "hero": {
    "headline": "Convierte Datos Crudos en Narrativas Ejecutivas",
    "subheadline": "Diseñamos tableros de control de alto impacto que dan visibilidad absoluta sobre el rendimiento del negocio en tiempo real."
  },
  "contrast": {
    "problem": "Reportes estáticos y desactualizados que llegan tarde para influir en decisiones críticas del negocio.",
    "solution": "Dashboards ejecutivos en tiempo real con IA generativa que narra insights antes de que los pidas."
  },
  "features": [
    "Dashboards Ejecutivos de Alta Densidad (Power BI, Tableau, Looker)",
    "Minería de Procesos (Process Mining)",
    "Monitoreo en Tiempo Real de KPIs/OKRs Estratégicos"
  ]
},
{
  "id": "sistemas-digitales",
  "name": "Desarrollo de Sistemas Digitales Premium",
  "slug": "sistemas-digitales",
  "seo": {
    "title": "Desarrollo de Software y Plataformas AI-First | Datanestiq",
    "description": "Plataformas corporativas y portales B2B de clase mundial, concebidos desde su génesis para integrar capacidades cognitivas."
  },
  "hero": {
    "headline": "Sistemas Digitales Diseñados para Escalar",
    "subheadline": "Construimos plataformas corporativas AI-first con interfaces de élite, optimizadas rigurosamente para la conversión."
  },
  "contrast": {
    "problem": "Software legado o genérico que no se adapta a la complejidad de tus procesos ni a la velocidad de tu negocio.",
    "solution": "Plataformas a medida con arquitecturas headless y modulares, diseñadas para crecer contigo."
  },
  "features": [
    "Desarrollo de Sistemas a Medida y Plataformas Corporativas AI-First",
    "Aplicaciones Web y Portales B2B de Alta Conversión",
    "Arquitecturas Headless, Microservicios y Sistemas Modulares"
  ]
},
{
  "id": "estrategia-datos-ia",
  "name": "Consultoría Estratégica en Datos & IA",
  "slug": "estrategia-datos-ia",
  "seo": {
    "title": "Consultoría Estratégica en IA y Datos para C-Level | Datanestiq",
    "description": "Auditorías de madurez, roadmaps de alto ROI y gobernanza de IA para directorios que navegan la disrupción tecnológica."
  },
  "hero": {
    "headline": "El Roadmap Hacia el ROI Asimétrico",
    "subheadline": "Asesoramos a tu directorio en la adopción tecnológica, mitigando riesgos y priorizando casos de uso de alto retorno."
  },
  "contrast": {
    "problem": "Inversión dispersa en iniciativas de IA sin una hoja de ruta clara ni gobernanza que mitigue el riesgo regulatorio.",
    "solution": "Auditoría de madurez y roadmap priorizado que convierte cada iniciativa de IA en una apuesta de ROI medible."
  },
  "features": [
    "Auditoría Integral de Madurez de Datos e IA",
    "Roadmaps Estratégicos y Priorización de Casos de Uso de Alto ROI",
    "CTO as a Service y Asesoramiento Arquitectónico Experto"
  ]
}
```

### 3.2 — Corregir los enlaces de `src/components/ui/Services.astro` para que coincidan con la taxonomía real

Las tarjetas 3, 4, 5 y 6 actualmente apuntan a slugs que no existen. Corrígelas así (mantén el ícono SVG de cada tarjeta, solo corrige `href`, `<h3>` y el párrafo de descripción):

- Tarjeta 3 (Business Intelligence): el `href` ya es correcto (`/soluciones/business-intelligence`), no cambies nada — ahora existirá en el corpus.
- Tarjeta 4 ("Cloud & DevOps" → reemplazar por el pilar real "Desarrollo de Sistemas Digitales Premium"):
  ```html
  <a href="/soluciones/sistemas-digitales" class="glass-card hover:-translate-y-2 block" data-animate="fade-up" style="opacity: 0">
    <div class="w-12 h-12 bg-cyan-500/20 rounded-lg flex items-center justify-center mb-6">
      <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
    </div>
    <h3 class="text-xl font-bold mb-3">Sistemas Digitales Premium</h3>
    <p class="text-gray-400">Plataformas corporativas AI-first, portales B2B y arquitecturas headless a medida.</p>
  </a>
  ```
- Tarjeta 5 (slug `automatizacion` → corregir a `hiperautomatizacion`):
  ```html
  <a href="/soluciones/hiperautomatizacion" class="glass-card hover:-translate-y-2 block" data-animate="fade-up" style="opacity: 0">
    <!-- mantener el mismo ícono SVG -->
    <h3 class="text-xl font-bold mb-3">Hiperautomatización Inteligente</h3>
    <p class="text-gray-400">RPA + IA cognitiva para ejecutar procesos críticos de extremo a extremo, sin fricción.</p>
  </a>
  ```
- Tarjeta 6 (slug `estrategia` → corregir a `estrategia-datos-ia`):
  ```html
  <a href="/soluciones/estrategia-datos-ia" class="glass-card hover:-translate-y-2 block" data-animate="fade-up" style="opacity: 0">
    <!-- mantener el mismo ícono SVG -->
    <h3 class="text-xl font-bold mb-3">Consultoría Estratégica en Datos & IA</h3>
    <p class="text-gray-400">Auditoría de madurez, roadmaps de alto ROI y gobernanza de IA para tu directorio.</p>
  </a>
  ```

### 3.3 — Actualizar el Footer con los mismos enlaces reales

`src/components/ui/Footer.astro` tiene una lista "Servicios" con `href="#"` (sin destino real) para 3 de los 6 pilares. Reemplázalos por enlaces reales a las 6 páginas de `/soluciones/[slug]` usando los `name` y `slug` definidos en `taxonomyCorpus.json` (puedes iterar sobre el corpus con un `import` igual que en `[id].astro`, en vez de hardcodear la lista, para que el footer nunca vuelva a desincronizarse de la taxonomía).

---

## BLOQUE 4: Pulido de diseño y accesibilidad (WCAG 2.2 AA, exigido por la Constitution sección 3)

### 4.1 — Respetar `prefers-reduced-motion` en las animaciones GSAP

**Problema:** el script inline de GSAP en `BaseLayout.astro` anima siempre todos los elementos `[data-animate]`, sin importar si el usuario tiene activado "reducir movimiento" en su sistema operativo — esto viola WCAG 2.2 (criterio 2.3.3, Animation from Interactions) y la exigencia de accesibilidad "inquebrantable" de la Constitution.

**Corrección:** en `src/layouts/BaseLayout.astro`, envuelve la lógica de animación con una comprobación de `matchMedia`:
```js
document.addEventListener('DOMContentLoaded', () => {
  gsap.registerPlugin(ScrollTrigger);

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReducedMotion) {
    // Mostrar todo instantáneamente, sin animación
    document.querySelectorAll('[data-animate]').forEach((el) => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  // ... resto del código GSAP existente sin cambios ...
});
```

### 4.2 — Añadir `aria-label` al botón de menú móvil

En `src/components/ui/Navbar.astro`, el botón `#mobile-menu-btn` tiene `aria-expanded` y `aria-controls` pero no `aria-label` (un lector de pantalla anunciaría un botón sin nombre accesible). Añade:
```html
<button id="mobile-menu-btn" aria-label="Abrir menú de navegación" class="text-gray-300 hover:text-white focus:outline-none" aria-expanded="false" aria-controls="mobile-menu">
```
Y en `#close-menu-btn`:
```html
<button id="close-menu-btn" aria-label="Cerrar menú de navegación" class="text-gray-400 hover:text-white focus:outline-none">
```

### 4.3 — Estados de foco visibles en todos los elementos interactivos

Revisa `glass-card` (usado como `<a>` clicable en `Services.astro` y como contenedor en `Faq.astro`) y los botones `btn-primary`: ninguno define un estado `:focus-visible` explícito más allá del `focus:outline-none` del navbar. Añade en `src/styles/global.css`, dentro de `@layer components`:
```css
.btn-primary {
  @apply bg-primary hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background;
}

.glass-card {
  @apply bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background;
}
```

---

## BLOQUE 5: Sincronizar la documentación SDD con el estado real del código

**Problema:** la Constitution (`.specify/memory/constitution.md`, sección 6) describe la "Spec 006" como *"Las bases del prototipo se están portando a WordPress"*, pero la Spec 006 real (carpeta `specs/006-ecosistema-astro/`) es la migración a **Astro**, y la migración a WordPress headless es la **Spec 008** (`specs/008-headless-wordpress/`, estado "Pendiente de Implementación"). Este es exactamente el tipo de deriva spec↔realidad que la propia Constitution prohíbe en su sección 1.

### 5.1 — Corregir la sección 6 de la Constitution

Reemplaza el último punto de la sección "6. Arquitectura de Dependencias (Specs 01 al 06)" en `.specify/memory/constitution.md`:
```md
<!-- ANTES -->
- **La Migración Core (Spec 006):** Las bases del prototipo se están portando a WordPress. Todo diseño arquitectónico futuro debe considerar integraciones limpias vía APIs REST, arquitectura modular (plugins custom) o estrategias Headless que conecten el backend WP con nuestro frontend hiper-optimizado (Spec 001).

<!-- DESPUÉS -->
- **El Ecosistema Astro (Spec 006):** El frontend se reconstruyó como una aplicación Astro SSG multi-página (ver Spec 007), reemplazando el prototipo monolítico. Todo diseño arquitectónico futuro debe considerar integraciones limpias vía APIs REST, arquitectura modular o estrategias Headless que conecten un backend WordPress aislado (Spec 008, en curso) con este frontend hiper-optimizado.
```
Actualiza también el título de la sección de "(Specs 01 al 06)" a "(Specs 01 al 08)" ya que existen 8 specs, y añade una línea final referenciando la Spec 007 (multi-página) y la Spec 008 (headless WordPress) en la lista de dependencias, con el mismo formato que las demás.

### 5.2 — Corregir `specs/007-multi-pagina/tasks.md`

Las tareas T010 y T011 estaban marcadas `[X]` sin estar implementadas. Ahora que el BLOQUE 2 de este prompt las implementó de verdad, **verifica manualmente** (no asumas) que:
- T010 (capturar `?servicio` en el Wizard vía `useEffect`/`URLSearchParams`) — confirmado en el código.
- T011 (inicializar `lastUserQuery` en Nano Stores si detecta un servicio) — confirmado en el código.

Si por algún motivo no pudiste completar el BLOQUE 2 exactamente como se describe, **desmarca** esas tareas (`[ ]`) en `tasks.md` y añade una nota explicando el motivo, en vez de dejarlas marcadas como completas sin estarlo. Aplica el mismo criterio a T012-T015 (sitemap, build de `dist/soluciones/`, Lighthouse): confirma que siguen siendo ciertas tras añadir 4 pilares nuevos al corpus (ahora deben generarse 6 páginas en `dist/soluciones/`, no 2).

### 5.3 — Actualizar `specs/007-multi-pagina/tech_debt.md`

Reemplaza el placeholder actual (*"Este documento se actualizará conforme avancemos..."*) con una entrada real documentando lo encontrado en esta auditoría, siguiendo el mismo formato que `specs/002-microexperiencias-ia/tech_debt.md` (Estado / Impacto / Severidad / Lista de Deuda Técnica con "Estado: Resuelto" o "Pendiente" por ítem), listando como resueltos los bugs de este prompt.

---

## Instrucciones de ejecución

1. Aplica el BLOQUE 1 completo (infraestructura/SEO) y ejecuta `npm run build`. Confirma manualmente en `dist/index.html` y `dist/soluciones/*/index.html` que el canonical ya no tiene `/datanestiq/dist/`.
2. Aplica el BLOQUE 2 (continuidad de contexto) y verifica manualmente en el navegador (`npm run dev`): entra a `/soluciones/ai-data-science`, haz clic en "Inicia tu Diagnóstico", confirma que el chatbot se abre solo en el home con un mensaje contextual.
3. Aplica el BLOQUE 3 (taxonomía) y ejecuta `npm run build` de nuevo. Confirma que `dist/soluciones/` contiene **6** subcarpetas (una por pilar), no 2.
4. Aplica el BLOQUE 4 (accesibilidad/diseño).
5. Aplica el BLOQUE 5 (documentación SDD).
6. Ejecuta `npm run build` una última vez de punta a punta y confirma que no hay errores ni warnings nuevos.
7. Haz commit de los cambios con el mensaje: `fix: corrige SEO, contexto Wizard-Chatbot y completa taxonomia de 6 pilares (post-auditoria Spec 007)`.

## Forma de respuesta

- Aplica cada bloque en el orden indicado; no te saltes bloques ni cambies el orden.
- Al terminar, entrega un resumen por bloque: qué archivos tocaste y qué verificaste (build exitoso, 6 páginas generadas, canonical correcto, etc.).
- Si algo de este prompt entra en conflicto con el código real que encuentres (por ejemplo, si algún archivo ya cambió desde que se escribió esta auditoría), indícalo explícitamente en vez de improvisar una solución distinta.
- Incluye al final una sección **"Hallazgos adicionales"** con cualquier problema que detectes durante la ejecución y que no esté cubierto por este documento (no lo corrijas sin permiso, solo repórtalo).
- No hagas cambios en `wp-admin/`, `wp-includes/` ni ningún archivo del núcleo de WordPress — esta auditoría es exclusivamente sobre el frontend Astro (`src/`, `astro.config.mjs`, `tailwind.config.mjs`, `public/`) y la documentación SDD (`specs/`, `.specify/`).

---

**Nota:** Claude (Sonnet 5) auditará este trabajo una vez completado, verificando cada bloque contra el código resultante antes de darlo por cerrado.
