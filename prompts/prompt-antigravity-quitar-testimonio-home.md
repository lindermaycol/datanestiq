# Prompt para Antigravity — Quitar el testimonio del home (Opción 1)

El usuario eligió **Opción 1**: **eliminar el bloque de testimonio** del home hasta que existan casos reales. (La fila de logos ya quedó bien como "Tecnologías que dominamos" — **no la toques**.)

## Cambios (mínimos, solo 2–3 archivos)
1. **`src/content/pages/home.md`** — elimina la sección `- type: "testimonials"` (quote/author/role). Deja intacta la sección `logos`.
2. **`src/components/ui/TrustLayer.astro`** — haz que el bloque de testimonio **no se renderice cuando no hay cita**, y elimina los defaults de borrador:
   - Envuelve el `<!-- Testimonial -->` en un condicional: `{testimonialQuote && ( … )}` para que reaparezca fácil cuando haya un testimonio **real**.
   - Borra los valores por defecto de borrador (`testimonialQuote`, `testimonialAuthor = 'Datanestiq'`, `testimonialRole`, `testimonialLabel = 'DN'`) o déjalos `undefined`/vacíos, de modo que **por defecto NO se muestre nada**.
3. **`src/pages/index.astro`** — si al quitar el testimonio quedan props de testimonio pasadas a `<TrustLayer>` que ya no aplican, puedes dejarlas (serán `undefined` y el condicional las oculta) o quitarlas; lo importante es que **no se renderice ningún testimonio**.

## Guardarraíles
- **No** inventes ni dejes ningún testimonio/persona/empresa. Cero rastros de borrador (`[BORRADOR…]`, `Datanestiq / Firma Consultora B2B`, `DN`).
- No rompas el layout: verifica que la sección de logos/tecnologías y el espaciado se vean bien sin el testimonio.
- `npm run build` verde; consola limpia.

## Verificación (evidencia)
- `grep` en `dist/index.html`: **cero** ocurrencias de `BORRADOR`, `testimonialQuote`, `Directora de Operaciones`, `Datanestiq` como autor de testimonio, ni comillas de cita huérfanas.
- La fila "Tecnologías que dominamos" (AWS/Databricks/…) **sigue** presente.
- `npm run build` verde. Reporta el diff.

---
**Nota:** Claude (Opus 4.8) reauditará en `dist/` que no quede ningún testimonio (real ni borrador) y que el layout del home siga correcto.
