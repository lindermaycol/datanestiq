# Confirmación — Limpieza de vocabulario de tags: LUZ VERDE → ejecutar (con 2 correcciones de conteo)

El plan (`planes/Plan de Implementación — Limpieza del Vocabulario de Tags del Blog.md`) es correcto. Verifiqué con el `slugifyTag` real: **los 18 tags canónicos generan 18 slugs únicos, cero colisiones** (build-safe). El vocabulario (4 roles + 4 sectores + 10 temas), el mapeo de fusiones y la asignación por artículo (3–5 tags) están bien. Ejecuta la normalización tal cual, con estas correcciones menores en la **verificación** (los conteos del plan están off-by-one; no cambian la implementación):

## Correcciones de conteo (solo para no fallar la verificación con números equivocados)
1. **`/blog/tag/sector-publico`** lista **7** artículos activos (no 6): `analitica-contrataciones-estado`, `calidad-padrones-ia`, `cio-modernizacion-estado`, `dashboards-ejecutivos-gobierno`, `gobierno-dato-estado`, `ia-segura-estado`, `interoperabilidad-institucional`.
2. **`/blog/tag/gobierno-del-dato`** agrupa **5** artículos activos (no 4): `analitica-contrataciones-estado`, `calidad-padrones-ia`, `dashboards-ejecutivos-gobierno`, `gobierno-dato-estado`, `interoperabilidad-institucional`.
3. Nº de páginas de tag: las páginas se generan solo desde posts **no-draft**; el conteo real será **~16** (tags únicos usados por los 11 activos), no 14. Verifica el número **real**, no un target fijo (Salud/Educación solo están en drafts → no generan página aún).

## OK tal como está
- Vocabulario controlado de 18 tags; slugs únicos verificados ✅.
- Fusiones sensatas (ej. `Transparencia/Calidad/Trazabilidad/mdm → Gobierno del Dato`; `EBITDA/TCO/Business Case → ROI`). El caso `fhir → Compliance` es lossy pero el artículo de FHIR igual lleva `Interoperabilidad`, así que su esencia queda cubierta — OK.
- Solo edita `tags:` en el frontmatter de los 16 `.md`; no toca cuerpos, `tagUtils.ts` ni plantillas ✅.

## Verificación (evidencia real)
1. `node scripts/build-taxonomy.mjs` verde; `npm run build` verde.
2. `ls dist/blog/tag/` muestra **~16** slugs (no 45), todos consistentes (Title Case → slug), sin duplicados semánticos.
3. `/blog/tag/sector-publico` lista **7** posts; `/blog/tag/gobierno-del-dato` lista **5**.
4. Cada uno de los 11 posts activos tiene 3–5 tags coherentes; ningún post sin tags.
5. `ESTADO-SPECS.md` anota la normalización del vocabulario.

---
**Nota:** Claude (Opus 4.8) reauditará: build verde, ~16 páginas de tag sin duplicados, cobertura multi-post correcta (7 en sector-publico, 5 en gobierno-del-dato), y tags coherentes por post.

---
## ⚠️ Aparte — la implementación del chatbot
El archivo que me pasaste es este plan de **tags**, no la implementación del **fix del chatbot** (subtítulos repetidos). Cuando tengas esa implementación de Antigravity, pásamela y la audito (verificaré `php -l` limpio + respuesta de texto libre sin nombres de servicio duplicados).
