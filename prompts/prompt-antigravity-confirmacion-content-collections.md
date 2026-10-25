# Prompt para Antigravity: Confirmación del Plan de Content Collections (Spec 003) → ejecutar

Revisé `planes/Plan de Implementación Migración a Content Collections (Spec 003).md`. Es fiel al prompt y bien estructurado (colecciones YAML + script generador/validador + integridad referencial + páginas de sector). **Luz verde para ejecutar**, con las respuestas a tus 2 preguntas y 3 precisiones.

## Respuestas a tus Open Questions

**1. ¿Instalar `js-yaml`?** → **Sí**, como `devDependency`. Es la forma estándar y confiable de parsear YAML en un script Node fuera del contexto de Astro. Ligera y ubicua. Apruébala.

**2. ¿URLs de sector limpias (`/sectores/finanzas` sin el prefijo `sector-`)?** → **Sí**, mejor para SEO. Pero hazlo de forma robusta (ver Precisión 2): añade un campo **`slug` explícito** a cada sector en el YAML (ej. `slug: finanzas`) en lugar de recortar el string `sector-` en runtime. El **`id` interno se queda `sector-*`** (nadie lo rompe); el `slug` es solo para la URL.

## Precisión 1 — Los JSON generados: commiteados + marcados como generados, y regenerar en dev y build
Los `src/data/*.json` pasan a ser **artefactos generados** desde los YAML. Para que no haya sorpresas:
- **Mantenlos commiteados** (como red de seguridad: `npm run dev` y cualquier entorno que no corra el script igual encuentran datos válidos), pero **añade un comentario/encabezado "ARCHIVO GENERADO — no editar a mano; editar los YAML en `src/content/`"** (en JSON no hay comentarios, así que documenta esto en un `README` de `src/data/` o en el `tech_debt.md`).
- Ejecuta el script en **`prebuild` Y `predev`** (ambos hooks en `package.json`), para que tanto `npm run dev` como `npm run build` reflejen ediciones de YAML sin pasos manuales. Así el YAML es siempre la fuente y el JSON nunca queda desincronizado.

## Precisión 2 — Slug de sector consistente (no rompas consumidores por `id`)
- Añade `slug` explícito a cada sector (`sector-finanzas` → `slug: finanzas`). El `id` sigue siendo `sector-finanzas` (lo usan SemanticSearch, DiagnosticWizard, SolutionsByRoleAndIndustry, semanticHighlight).
- `getStaticPaths()` de `/sectores/[id].astro` usa el **`slug`** para la URL, no el `id`.
- Actualiza **todos** los cross-links a `/sectores/[slug]`: la pestaña "por Industria" de `SolutionsByRoleAndIndustry` (que hoy abre el buscador), y cualquier enlace en Home/Footer. Verifica que el sitemap (`@astrojs/sitemap`) incluya las nuevas rutas.
- Añadir `slug` a `sectorsCorpus.json` es **aditivo** (los consumidores usan `id`), así que no rompe nada — confírmalo.

## Precisión 3 — Verificación E2E con evidencia (no solo build)
Además de tu plan, reporta con evidencia:
1. **Diff de shape:** los JSON regenerados son **idénticos en estructura y contenido** a los actuales (sin campos perdidos: `maturityStage, standards, targetRoles, skills, copilotPrompts, keywords` en pilares; `ciiu, problems, seo/hero/contrast, keywords` en sectores; `relatedPillars` en industrias).
2. **Integridad referencial:** introduce a propósito un `relatedPillars`/`pillarsOfInterest` inválido → el script **cancela el build** (`exit 1`) con mensaje claro; luego revierte.
3. **No-regresión (Network):** chatbot guiado = **0 llamadas**; buscador semántico reordena; `SolutionsByRoleAndIndustry` (rol→resalta pilares, industria→`/sectores/[slug]`); páginas de pilar con madurez/roles.
4. **Páginas de sector:** `npm run build` genera las **10** `/sectores/[slug]` (conteo total ~22+); una landing renderiza hero/contraste/CIIU/problems + cross-linking.
5. **Editabilidad:** cambia un `headline` en un YAML → `npm run dev`/build → se refleja en la web.

## Cierre
- `specs/003-taxonomia-servicios/tech_debt.md`: deuda #1 (Content Collections) → **✅ RESUELTO**; cobertura de sectores → resuelta; corrige el `## Estado`.
- `planes/ESTADO-SPECS.md`: fila 003 → "Content Collections validadas + 10 páginas de sector".

## Forma de respuesta
- Reporta la verificación E2E con evidencia (diff de shape, build-failure por referencia inválida, 0 llamadas del chatbot, 10 páginas de sector, edición YAML reflejada).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que ningún consumidor se rompa (JSON idénticos en shape), que el script falle ante referencias inválidas, que las 10 páginas de sector se generen con URLs limpias, y que editar un YAML se refleje.
