# Prompt para Antigravity — Blog: breadcrumb real, tags funcionales y formato editorial profesional

Extiende **Spec 007 (multi-página) / 012 (contenido)**. No es spec nueva. Afecta `src/pages/blog/[slug].astro`, el índice `src/pages/blog/index.astro` y añade páginas de tag. **Entrega PLAN primero.** Doc-sync a `ESTADO-SPECS.md`. 0-LLM (blog estático).

## Contexto (verificado en código)
`blog/[slug].astro`:
- **No hay breadcrumb.** El header solo muestra `autor • fecha` (líneas 47-51) con clase `text-brand` (azul) → "Datanestiq" **parece un enlace roto** pero es un `<span>`. De ahí la confusión del usuario. La fecha **no** es un breadcrumb.
- **Tags no funcionan:** se renderizan como `<span>` (líneas 55-58), no como enlaces; no existen páginas de tag.
- **Formato:** `prose prose-invert prose-lg` envuelto en un `glass-card` — se ve "encajonado", no editorial.

Schema del blog (`content.config.ts`): `title, description, pubDate (date), author (default 'Datanestiq'), tags[], draft`.

## Fix 1 — Breadcrumb real (visual + accesible)
- Añade un **breadcrumb visible** arriba del título: **Inicio › Blog › {título}** (los dos primeros son `<a>` a `/` y `/blog`; el último, texto actual sin enlace, truncado si es largo). Marca `aria-label="breadcrumb"`.
- **Separa la meta del breadcrumb:** la línea `autor • fecha` es **metadata**, no navegación. Quítale el look de enlace: usa gris tenue (no `text-brand`), y renderiza el autor como texto plano (no azul). La fecha en formato legible (ej. "16 jul 2026", locale es-PE) además del `<time datetime>` ISO para SEO.
- Si `BaseLayout` ya emite `BreadcrumbList` JSON-LD, mantén el breadcrumb visual **consistente** con él (no dupliques ni contradigas la jerarquía).

## Fix 2 — Tags funcionales (páginas de tag)
- Crea **`src/pages/blog/tag/[tag].astro`** con `getStaticPaths` que derive **todos los tags** de la colección `blog` (posts no-draft) y liste los posts de cada tag (reutiliza el estilo de tarjeta del índice `blog/index.astro`).
- **Slug de tag robusto:** normaliza a slug (minúsculas, sin acentos, espacios→`-`) para tags como *"Business Case"* o *"Estrategia de Datos"*; conserva el label legible para mostrar. Provee un helper de slugify reutilizable (índice, post y página de tag deben coincidir).
- En el post y en el índice, los pills de tag pasan a ser **`<a href="/blog/tag/{slug}">#{label}</a>`** con hover.
- Título/description de la página de tag: ej. *"Artículos sobre {label} · Blog Datanestiq"*. Breadcrumb: Inicio › Blog › {label}.

## Fix 3 — Formato editorial profesional
Eleva la lectura al nivel de blogs técnicos serios (Stripe/Vercel-like), **theme-aware**:
- **Quita el `glass-card` que envuelve `<Content />`** (líneas 62-64); el cuerpo va sobre el fondo, con ancho de lectura cómodo (**medida ~68ch**, ej. `max-w-2xl`/`prose` centrado) y **ritmo vertical generoso**.
- **Tipografía Tailwind Typography afinada** (`prose prose-invert` + overrides): jerarquía clara de `h2/h3` con margen superior amplio y `scroll-mt` (para anclas), párrafos legibles (`leading-relaxed`), **enlaces** en color de marca subrayado sutil, **blockquote** con barra lateral, **listas** con buen espaciado, **`code`/`pre`** con fondo oscuro y borde, **imágenes** `rounded-xl` con margen. Cuida contraste en claro y oscuro.
- **Byline mejorada:** autor + fecha legible + **tiempo de lectura estimado** (calcula ~200 wpm desde el contenido) + los tags como enlaces. Opcional: divisor sutil.
- **Pie del artículo:** conserva el CTA de diagnóstico, y añade un enlace **"← Volver al Blog"**. Opcional (si es limpio): "Artículos relacionados" por tag compartido (máx. 3).
- No metas dependencias nuevas pesadas; usa Tailwind Typography (ya presente) + utilidades.

## Guardarraíles
- **0-LLM / estático:** todo server-render de Astro; sin `fetch` en runtime del blog.
- **Honestidad §2:** no inventes datos de autor/bio; "Datanestiq" como autor org está bien.
- **Sin romper** el índice del blog ni los 11 posts; `npm run build` verde; consola limpia; enlaces sin `href="#"`.

## Verificación (evidencia real)
1. **Breadcrumb:** un post muestra "Inicio › Blog › {título}" con los dos primeros clicables; la meta `autor • fecha` ya **no** parece enlace (gris, no azul) y la fecha es legible.
2. **Tags:** clic en `#CFO` (y en uno con espacios, ej. `#Business Case`) navega a `/blog/tag/...` que lista los posts correctos; el slug maneja acentos/espacios.
3. **Formato:** el cuerpo del artículo se ve editorial (sin la "caja" glass-card), con buena tipografía, tiempo de lectura visible, y enlaces/blockquotes/código estilizados; correcto en claro y oscuro.
4. `npm run build` verde (páginas de tag generadas); `ESTADO-SPECS.md` actualizado. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: breadcrumb navegable, meta que no parece enlace, tags que enrutan a `/blog/tag/*` (incl. tags con espacios/acentos), y el formato editorial en claro/oscuro.
