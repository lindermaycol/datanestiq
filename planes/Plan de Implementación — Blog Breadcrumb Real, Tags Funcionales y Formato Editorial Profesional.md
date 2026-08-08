# Plan de Implementación — Blog: Breadcrumb Real, Tags Funcionales y Formato Editorial Profesional

Extiende **Spec 007 (multi-página) / Spec 012 (fábrica de contenido)**. Afecta a `src/pages/blog/[slug].astro`, `src/pages/blog/index.astro` y crea la ruta dinámica `src/pages/blog/tag/[tag].astro`.

---

## Contexto y Diagnóstico

1. **Breadcrumb inexistente y confusión visual:** En `blog/[slug].astro`, el encabezado actual muestra `autor • fecha` en color azul (`text-brand`), haciendo que "Datanestiq" o la fecha parezcan enlaces rotos. No existe navegación por migas de pan (Breadcrumbs).
2. **Tags no funcionales:** Los tags se renderizan como `<span>` decorativos sin enlaces, y no existen páginas de archivo por etiqueta.
3. **Formato encajonado (Glass-card):** El contenido del artículo `<Content />` se encuentra envuelto dentro de una tarjeta con borde y fondo semitransparente (`glass-card bg-surface/50`), lo que genera una experiencia encajonada e incómoda para la lectura de artículos extensos.

---

## User Review Required

> [!IMPORTANT]
> **🔴 Decisiones de Diseño Editorial y Navegación:**
> 
> 1. **Jerarquía Visual de Breadcrumb & Metadata:**
>    - **Breadcrumb navegable:** `Inicio › Blog › {Título del Artículo}` en la parte superior con `aria-label="breadcrumb"`. `Inicio` redirige a `/` y `Blog` redirige a `/blog`.
>    - **Metadata sin apariencia de enlace:** El autor "Datanestiq" se muestra en gris tenue (`text-gray-400`), eliminando el color azul `text-brand`. Se incluye fecha formateada en locale `es-PE` (ej: `16 jul 2026`) y el **tiempo estimado de lectura** (calculado a ~200 wpm).
> 
> 2. **Ruta de Tags (`/blog/tag/[tag].astro`):**
>    - Utilitario `slugifyTag` para normalizar etiquetas con espacios y acentos (ej: `"Business Case"` → `"business-case"`, `"Educación / DRE"` → `"educacion-dre"`).
>    - Las etiquetas en el artículo e índice pasan a ser enlaces interactivos `<a href="/blog/tag/{slug}">#{label}</a>`.
> 
> 3. **Formato Editorial Abierto (Theme-Aware):**
>    - **Remoción del `glass-card`:** El cuerpo del post se libera de la caja envolvente, ajustándose a un ancho óptimo de lectura (`max-w-3xl mx-auto`, ~68ch) sobre el lienzo oscuro principal.
>    - **Tipografía Tailwind Typography Afinada:** Ajustes en `h2/h3` con márgenes superiores y `scroll-mt-24` para anclas, blockquotes con borde lateral cyan, bloques de código con fondo oscuro y borde sutil, e imágenes `rounded-xl shadow-xl`.
>    - **Pie de Artículo:** Sección de artículos relacionados por etiqueta compartida (máx. 3), CTA de diagnóstico consultivo y enlace `← Volver al Blog`.

---

## Proposed Changes

### 1. Utilitario de Normalización de Tags

#### [NEW] [tagUtils.ts](file:///C:/xampp/htdocs/datanestiq/src/lib/tagUtils.ts)
Crear funciones auxiliares para slugify de tags y resolución de etiquetas legibles:
```typescript
export function slugifyTag(tag: string): string {
  if (!tag) return '';
  return tag
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-');
}

export function getTagLabelFromSlug(slug: string, posts: any[]): string {
  for (const post of posts) {
    const tags = post.data?.tags || [];
    for (const tag of tags) {
      if (slugifyTag(tag) === slug) return tag;
    }
  }
  return slug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}
```

---

### 2. Páginas de Archivo por Tag

#### [NEW] [tag].astro](file:///C:/xampp/htdocs/datanestiq/src/pages/blog/tag/%5Btag%5D.astro)
Ruta dinámica que genera páginas para cada tag único usando `getStaticPaths`:
- Breadcrumb: `Inicio › Blog › Tag: {tagLabel}`
- Listado de artículos filtrados por la etiqueta seleccionada, con el diseño de grilla y enlaces de tag.

---

### 3. Rediseño Editorial en Artículo de Blog

#### [MODIFY] [slug].astro](file:///C:/xampp/htdocs/datanestiq/src/pages/blog/%5Bslug%5D.astro)
- Implementar Breadcrumb visual navegable.
- Rediseñar el header con autor sin color de enlace engañoso, fecha en `es-PE` y tiempo estimado de lectura en minutos.
- Convertir las etiquetas en enlaces `<a href={`/blog/tag/${slugifyTag(tag)}`}>`.
- **Eliminar el envoltorio `glass-card`** de `<Content />` y afinar las clases Tailwind Typography.
- Agregar bloque de artículos relacionados (hasta 3 posts que compartan al menos 1 tag).
- Agregar botón de navegación `← Volver al Blog`.

---

### 4. Actualización del Índice del Blog

#### [MODIFY] [index.astro](file:///C:/xampp/htdocs/datanestiq/src/pages/blog/index.astro)
- Convertir los badges de tag en las tarjetas del listado principal en enlaces navegables a `/blog/tag/${slugifyTag(tag)}`.

---

### 5. Documentación

#### [MODIFY] [ESTADO-SPECS.md](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md)
Actualizar el registro de la Spec 007 y Spec 012 documentando las mejoras en el Blog (Breadcrumbs navegables, etiquetas dinámicas y diseño editorial libre de glass-card).

---

## Verification Plan

### Automated Verification
1. **Compilación Estática (`npm run build`):**
   - Confirmar que Astro compila limpiamente todas las páginas estáticas del blog y las nuevas rutas `/blog/tag/*`.

### Manual Verification
1. **Navegación de Breadcrumbs:**
   - Verificar en un artículo que `Inicio` redirige a `/` y `Blog` redirige a `/blog`.
2. **Metadata de Autor y Fecha:**
   - Confirmar que el autor "Datanestiq" es texto plano gris (`text-gray-400`) y no luce como un enlace azul.
   - Confirmar que se muestre la fecha en formato en español y el tiempo estimado de lectura (ej: `4 min de lectura`).
3. **Páginas de Tag:**
   - Hacer clic en etiquetas como `#CFO` o `#Business Case` y verificar la navegación hacia `/blog/tag/cfo` o `/blog/tag/business-case`.
4. **Formato Editorial:**
   - Inspeccionar el cuerpo del artículo y confirmar la ausencia de la caja `glass-card`, asegurando lectura cómoda a ancho `max-w-3xl`.
