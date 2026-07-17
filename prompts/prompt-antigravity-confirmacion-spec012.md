# Prompt para Antigravity: Confirmación del Plan Spec 012 (Fábrica de Contenido del Sitio) → ejecutar con correcciones

**Parte 1 (pulido) auditada y aprobada:** el ROI `[EST]` ya renderiza en las páginas de sector (verificado en el HTML construido) y Lighthouse quedó configurado (`staticDistDir`/`--no-sandbox`/`outputDir`) con el bug `EPERM` de Windows honestamente documentado + comando alternativo (`npx lighthouse`). ✓

**Parte 2 (Spec 012):** el diseño respeta los principios no negociables (contenido≠código, `draft:true`, filtro de producción, reutiliza el motor de la 010). **Luz verde**, pero con **2 correcciones que si no se hacen, la feature NO funciona**, y 3 precisiones.

## 🔴 Corrección 1 (BLOQUEANTE) — archivo de config equivocado
El plan define `pagesCollection` en **`src/content/config.ts`**. **Astro NO lee ese archivo** (no existe; se eliminó en la Spec 003). Astro lee **`src/content.config.ts`** (en la raíz de `src`). Si lo pones en `src/content/config.ts`, la colección **nunca se registra** y todo falla en silencio.
→ **Añade `pagesCollection` al `collections` existente en `src/content.config.ts`** (donde ya viven `wiki` y `blog`).

## 🔴 Corrección 2 (BLOQUEANTE) — usa el loader API, no `type: 'content'`
Las colecciones actuales (`wiki`, `blog`) usan la **API de Astro 5**: `loader: glob({ pattern: "**/*.md", base: "./src/content/<col>" })`. El plan propone `type: 'content'` (API legacy) → inconsistente y puede fallar/deprecar en esta versión.
→ Define `pagesCollection` con `loader: glob({ pattern: "**/*.md", base: "./src/content/pages" })`, espejando `wiki`/`blog`. El resto del esquema Zod (title, draft, seo, hero, sections) está bien.

## Precisión 3 — Ruta catch-all `[...slug].astro`: no romper rutas existentes
`src/pages/[...slug].astro` es catch-all. En Astro las rutas específicas ganan (sectores/soluciones/wiki/blog/nosotros siguen), pero:
- `getStaticPaths()` debe generar **solo** los slugs de la colección `pages` (no cualquier ruta), y **aplicar el filtro draft en producción** también ahí (no solo en `getCollection` de render), para que los drafts **no** generen páginas en el build de prod.
- Verifica que el build siga con las **24 páginas previas intactas** + las nuevas de `pages` (no-draft).

## Precisión 4 — El generador produce frontmatter válido
El `--target=page` escribe un `.md` cuyo **frontmatter** cumpla el esquema (`hero` objeto, `seo` objeto, `sections` array de objetos con `type` del enum). El cuerpo markdown puede ir vacío. Fuerza `draft: true`. Reutiliza `stripFrontmatter`/sellado de la Spec 010 y el **balanceo 3 keys**.

## Precisión 5 — Comando real
El plan usa `npm run generate -- ...`; ese script no existe. Usa `node scripts/docs-generator.mjs --target=page --slug=... --brief="..."` (o añade el script `generate` a `package.json`).

## Verificación (evidencia real)
1. `--dry-run` no consume API.
2. Genera **1 página de muestra** desde un brief de la taxonomía → `src/content/pages/<slug>.md` con `draft: true`, on-brand y fundamentada.
3. **En dev** la página se ve; **en `npm run build` (prod) NO se incluye** mientras sea draft (demuéstralo: build sin esa página; luego `draft:false` → aparece).
4. Build verde con las **rutas existentes intactas** (24+ páginas).
5. Diff que muestre que el agente **solo tocó la colección** (`src/content/pages/`), ningún `.astro`/lógica.
6. Reparto de proveedores en la generación.

## Forma de respuesta
- Reporta con evidencia real (draft en dev / ausente en prod build, diff de solo-contenido, build verde). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que la colección se registre en **`src/content.config.ts`** con `loader: glob()`, que el catch-all no rompa rutas ni publique drafts en prod, que el generador respete el esquema + `draft:true`, y que el agente no haya tocado código. **Pendiente del usuario:** rotar la SSH de IONOS.
