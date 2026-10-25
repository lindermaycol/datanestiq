# Prompt para Antigravity — Limpieza del vocabulario de tags del blog (vocabulario controlado)

Higiene de contenido del **blog** (Spec 007/012). Solo toca el **frontmatter `tags`** de los `.md` en `src/content/blog/` — **no** cambia cuerpos de artículos ni el código de tags (ya funciona). **Entrega PLAN primero** con el vocabulario propuesto para mi revisión. Doc-sync a `ESTADO-SPECS.md`.

## Problema (verificado)
Los tags están **inconsistentes** en los `.md`: mezcla de Title Case y kebab-case, y **duplicados semánticos**. Hoy generan **45 páginas de tag** para solo 11 posts (páginas "delgadas"/casi duplicadas → ruido SEO). Ejemplos de duplicados a fusionar:
- `gobernanza-datos` / `gobernanza-de-datos` / `Gobierno del Dato`
- `estrategia` / `estrategia-de-datos` / `estrategia-datos-ia` / `Estrategia de Datos`
- `IA` / `inteligencia artificial` / `Inteligencia Artificial`
- `sector-publico` / `Sector Público` / `sector-gobierno` / `Administración Pública`
- `data-engineering` / `Ingeniería de Datos`, `transparencia` / `Transparencia`, etc.

## Objetivo
Definir un **vocabulario controlado** (canónico, consistente) y **normalizar** los tags de los 11 posts a ese set, fusionando duplicados. Meta: **~12–18 tags canónicos**, cada uno con ≥2 posts idealmente, y **3–6 tags por post**.

## Requisitos de diseño (proponlos en el PLAN para revisión)
1. **Una sola convención de escritura:** usa **Title Case legible en español** (ej. `"Gobierno del Dato"`, `"Estrategia de Datos"`, `"Sector Público"`). El `slugifyTag` ya convierte a URL (`gobierno-del-dato`), así que el `.md` lleva la forma legible.
2. **Sin colisiones de slug** entre dos tags canónicos distintos (verifícalo con `slugifyTag`).
3. **Categorías sugeridas** (ajústalas en el plan):
   - **Roles C-Level:** `CFO`, `CIO`, `CDO`, `CEO` (siglas, útiles como filtro de persona).
   - **Sector:** `Sector Público`, `Finanzas y Banca` (solo si aplican al post).
   - **Temas:** `Gobierno del Dato`, `Estrategia de Datos`, `Ingeniería de Datos`, `Business Intelligence`, `Hiperautomatización`, `Inteligencia Artificial`, `Interoperabilidad`, `Compliance`, `ROI`, `Soberanía de Datos`.
4. **Tabla de mapeo** en el plan: `tag actual → tag canónico (o eliminar)`, para que la apruebe antes de tocar los `.md`.
5. Los tags deben **describir realmente** el contenido del post (honestidad; no metas tags irrelevantes solo para inflar).

## Guardarraíles
- Solo edita `tags:` en el frontmatter de `src/content/blog/*.md`. **No** cambies `title/description/pubDate/cuerpo**, ni `tagUtils.ts`, ni las plantillas.
- `npm run build` verde; el nº de páginas `/blog/tag/*` debe **bajar** notablemente (de 45 a ~12–18) y cada página debe listar sus posts.
- Ningún post debe quedar sin tags (mínimo 3).

## Verificación (evidencia real)
1. `npm run build` verde; `ls dist/blog/tag/` muestra **~12–18** slugs (no 45), sin duplicados semánticos.
2. Un tag canónico compartido (ej. `sector-publico`) lista **todos** los posts públicos relevantes en una sola página.
3. Cada post tiene 3–6 tags coherentes con su contenido; sin tags huérfanos casi-duplicados.
4. `ESTADO-SPECS.md` anota la normalización del vocabulario de tags.

---
**Nota:** Claude (Opus 4.8) revisará primero la **tabla de mapeo** propuesta (vocabulario canónico) y luego reauditará: build verde, ~12–18 páginas de tag sin duplicados, y tags coherentes/honestos por post.
