# Prompt para Antigravity — Corrección de honestidad §2 en `/casos-de-exito`

Corrige una **regresión de honestidad radical (Constitución §2)** en `src/pages/casos-de-exito.astro`. No es spec nueva; alinea la página con la política `[EST]` que ya rige el resto del sitio (Spec 013 / BusinessCaseEstimator / taxonomía). Doc-sync a `ESTADO-SPECS.md`.

## Problema (verificado en el código)
La página presenta escenarios **hipotéticos** como si fueran **casos reales de clientes anonimizados**, y muestra métricas **sin marca `[EST]`**:
- **Disclaimer engañoso (línea ~22):** *"…arquitecturas ilustrativas **diseñadas para proteger la confidencialidad corporativa**."* → "proteger la confidencialidad" **implica clientes reales ocultos**. No los hay.
- **Intro (línea ~19):** *"escenarios **representativos** y arquitecturas de referencia"* → "representativos" sugiere que representan casos reales.
- **Métricas sin `[EST]` (líneas ~47, 51, 79, 83, 111, 115):** `-85%`, `-40%`, `+22%`, `98%`, `100x`, `100%` en números grandes de marca, **presentadas como logros conseguidos**. En el resto del sitio toda métrica de negocio lleva `[EST]`.
- **CTA (línea ~125):** *"Su arquitectura es la siguiente historia de éxito"* refuerza la impresión de track record real.

Esto es exactamente lo que §2 prohíbe: **prueba social/casos fabricados**. Hay que dejar claro que son **ilustrativos/potenciales**, no clientes reales.

## Fix (solo copy + etiquetado; conserva los escenarios como ejemplos)
1. **Disclaimer:** reescríbelo para que NO implique clientes reales. Ej.:
   > *"Escenarios **ilustrativos** que muestran el tipo de resultado que nuestras arquitecturas pueden habilitar. **No representan clientes ni proyectos reales**; las cifras son **estimaciones de potencial `[EST]`**, no resultados medidos."*
2. **Intro:** cambia *"escenarios representativos"* → *"escenarios ilustrativos"* (elimina la connotación de "representan casos reales").
3. **Métricas:** añade la marca **`[EST]`** visible junto a **cada** cifra (mismo criterio que el resto del sitio: badge o sufijo `[EST]`). Que quede claro que son estimaciones ilustrativas de potencial, no resultados obtenidos.
4. **CTA (línea 125):** suaviza para que sea aspiracional-honesto sin implicar historial real. Ej. *"Diseñemos juntos su próxima arquitectura de valor"* (evita "la siguiente historia de éxito" tras casos que no son reales).
5. Mantén los 3 escenarios (Financiero / Retail / Salud) y sus arquitecturas como **ejemplos ilustrativos** — solo cambia el framing y el etiquetado.

## Guardarraíles
- **§2 honestidad:** cero afirmación de clientes/casos reales; nada de "confidencialidad" que insinúe clientes ocultos; toda cifra `[EST]`.
- No inventes clientes, logos ni testimonios. No cambies la lógica; es una corrección de contenido en `casos-de-exito.astro`.
- `npm run build` verde; consola limpia.

## Verificación
1. `/casos-de-exito`: el disclaimer **no** menciona "confidencialidad corporativa" ni implica clientes reales; dice explícitamente que son ilustrativos y que las cifras son `[EST]`.
2. **Cada** métrica (`-85%`, `-40%`, `+22%`, `98%`, `100x`, `100%`) muestra `[EST]` visible.
3. La intro y el CTA no afirman track record real.
4. `npm run build` verde; `ESTADO-SPECS.md` anota la corrección de honestidad en `/casos-de-exito`.

---
**Nota:** Claude (Opus 4.8) reauditará en navegador la página `/casos-de-exito`: disclaimer sin framing de confidencialidad, `[EST]` en todas las cifras, y copy sin implicar clientes reales.
