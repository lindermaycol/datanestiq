# Confirmación — Blog (breadcrumb + tags + formato): LUZ VERDE con 2 precisiones → ejecutar

El plan (`planes/Plan de Implementación — Blog Breadcrumb Real, Tags Funcionales y Formato Editorial Profesional.md`) es correcto y completo (breadcrumb navegable, metadata sin look de enlace + tiempo de lectura, `slugifyTag`, páginas `/blog/tag/[tag]`, tags como enlaces en post e índice, quita `glass-card`, relacionados, "Volver al Blog"). Ejecuta con estas 2 precisiones.

## 🔴 Precisión 1 (rompe el build) — DEDUPLICA tags por slug en `getStaticPaths`
Varios posts **comparten tags** (verificado: `CFO`×2, `Estrategia de Datos`×2, `Transparencia`×2, `sector-público`×2, …). Si `getStaticPaths` de `/blog/tag/[tag].astro` mapea cada par (post, tag) sin deduplicar, generará **rutas duplicadas** (`params.tag='cfo'` dos veces) → **Astro falla el build** ("duplicate slug").
- **Deriva un conjunto ÚNICO de slugs** (ej. `Set`/`Map` de `slugifyTag(tag)`) y produce **una ruta por slug**.
- **Colisiones de forma:** los datos ya son inconsistentes — conviven `"Sector Público"` y `"sector-público"`, `"IA"` e `"inteligencia artificial"`. Ambas formas de un mismo slug deben **fusionarse en UNA página** (la dedup por slug ya lo logra); el listado de esa página incluye los posts de **cualquier** forma que resuelva a ese slug. (No normalices los tags en los `.md` ahora — eso es limpieza de contenido aparte.)

## 🔴 Precisión 2 (higiene) — excluir drafts y el propio post
- La agregación de tags (para `getStaticPaths` y para el listado de cada tag) debe **excluir `draft === true`** (igual que `[slug].astro` ya filtra). Un draft no debe crear página de tag ni aparecer listado.
- **Relacionados:** excluye el **post actual** de su propia lista de relacionados y excluye drafts; máx. 3.

## Nota (opcional, no bloqueante) — JSON-LD de breadcrumb ya existe
`BaseLayout.astro` ya emite `BreadcrumbList` JSON-LD **autogenerado desde el path** (líneas 16-55). Por tanto **no agregues** otro JSON-LD de breadcrumb (evita duplicado). Detalle menor: ese JSON-LD usa el **slug** como última miga (ej. "Data ia ebitda"), no el título real — es comportamiento preexistente; si es trivial, pueden mejorar la última miga al título, pero **no** es requisito de esta tarea.

## OK tal como está
- `slugifyTag` (NFD + strip acentos + espacios→`-`) y `getTagLabelFromSlug` ✅.
- Metadata: autor en `text-gray-400` (sin look de enlace), fecha `es-PE`, tiempo de lectura ~200 wpm ✅.
- Quitar `glass-card`, ancho `max-w-3xl` (~68ch), tipografía afinada (h2/h3 con `scroll-mt`, blockquote, code, img) ✅.
- Tags como enlaces en post **e índice**; "← Volver al Blog" ✅.

## Verificación (evidencia real)
1. **Build:** `npm run build` verde y **genera una sola** `/blog/tag/{slug}` por tag único (sin error de rutas duplicadas). Cuenta de páginas de tag = nº de slugs únicos.
2. **Breadcrumb:** en un post, "Inicio › Blog › {título}" con `Inicio`→`/` y `Blog`→`/blog`; la meta `autor · fecha · X min` **no** parece enlace (gris).
3. **Tags:** clic en `#CFO` → `/blog/tag/cfo` lista **ambos** posts con ese tag; clic en `#Business Case` → `/blog/tag/business-case` (maneja espacios/acentos). Verifica que un tag compartido (CFO) tenga **una** página con **múltiples** posts.
4. **Formato:** cuerpo sin `glass-card`, ancho cómodo, tipografía editorial en claro y oscuro; relacionados (≤3, sin el post actual) y "Volver al Blog".
5. `ESTADO-SPECS.md` actualizado (Spec 007/012).

---
**Nota:** Claude (Opus 4.8) reauditará: build sin colisión de rutas de tag, una página por slug único con todos sus posts (incl. tags con espacios/acentos), breadcrumb navegable, meta sin look de enlace, y el formato editorial sin la caja glass-card.
