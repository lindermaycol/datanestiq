# Prompt para Antigravity — `/casos-de-exito`: migrar a `roiCases` de la taxonomía + honestidad §2

Extiende **Spec 003/011 (taxonomía)** como fuente de datos y corrige una **regresión de honestidad §2**. Reemplaza el prompt anterior `prompt-antigravity-honestidad-casos-de-exito.md` (lo absorbe). **Entrega PLAN primero.** Doc-sync a `ESTADO-SPECS.md`.

## Contexto (verificado en código)
`src/pages/casos-de-exito.astro` es una página **hardcodeada** con 3 escenarios (Financiero, Retail, Salud) — viola "taxonomía = fuente de verdad" — y tiene **problemas de honestidad §2**:
- Disclaimer engañoso (línea ~22): *"arquitecturas ilustrativas **diseñadas para proteger la confidencialidad corporativa**"* → implica clientes reales ocultos (no los hay).
- Métricas (`-85%`, `-40%`, `+22%`, `98%`, `100x`, `100%`, líneas ~47-115) en números grandes de marca **SIN `[EST]`**, como si fueran logros conseguidos.
- Intro *"escenarios representativos"* y CTA *"Su arquitectura es la siguiente historia de éxito"* refuerzan un track record ficticio.

La taxonomía ya tiene el campo **`roiCases`** en el **schema de sector** (`{ title, description, context?, metrics: [{ value, label }] }`), poblado en `finanzas.yaml` y `seguros.yaml`.

## Parte A — Migrar la página a consumir `roiCases` (taxonomía = SSOT)
1. **Reescribe `casos-de-exito.astro`** para renderizar los casos **desde `sectorsCorpus.json`**, agregando los `roiCases` de **todos** los sectores que los tengan (no hardcodees escenarios en el `.astro`). Mantén el layout visual actual (tarjetas de escenario con métricas), pero alimentado por datos.
2. **Enriquece con `roiCases` los sectores que hoy aparecen** para no perder cobertura: al menos **retail** y **salud** (además de finanzas/seguros ya existentes), y si aporta, **publico** (estratégico). Cada `roiCase` debe ser **ilustrativo y honesto** (sin nombrar clientes; `context` genérico como los existentes). Valídalo con `node scripts/build-taxonomy.mjs` (Zod + aristas).
3. Cada tarjeta muestra `title`, `context`/`description` y sus `metrics` (`value` + `label`).

## Parte B — Honestidad §2 (en la versión migrada)
1. **Disclaimer:** que NO implique clientes reales. Ej.:
   > *"Escenarios **ilustrativos** del tipo de resultado que nuestras arquitecturas pueden habilitar. **No representan clientes ni proyectos reales**; las cifras son **estimaciones de potencial `[EST]`**, no resultados medidos."*
2. **`[EST]` en cada métrica** renderizada (mismo criterio que el resto del sitio). Como los `roiCases` de la taxonomía no traen la marca en el dato, **aplícala en el render** (badge/sufijo `[EST]` junto a cada `value`).
3. **Intro:** *"escenarios ilustrativos"* (no "representativos"). **CTA:** aspiracional-honesto (ej. *"Diseñemos juntos su próxima arquitectura de valor"*), sin "la siguiente historia de éxito".

## Guardarraíles
- **Taxonomía = SSOT:** cero escenarios hardcodeados en el `.astro`; todo desde `roiCases`. No editar el JSON generado a mano.
- **§2 honestidad:** cero clientes/casos reales; nada de "confidencialidad" que insinúe clientes ocultos; toda cifra `[EST]`. No inventes logos/testimonios.
- `build-taxonomy` verde (Zod + aristas); `npm run build` verde; consola limpia.

## Verificación
1. `/casos-de-exito` renderiza los casos desde la taxonomía (quita un `roiCase` de un YAML → desaparece de la página tras rebuild; el `.astro` no contiene los textos de escenarios).
2. Disclaimer sin "confidencialidad corporativa" ni implicación de clientes reales; **cada** métrica con `[EST]`.
3. Cobertura ≥ 3 casos (finanzas/seguros/retail/salud según lo enriquecido).
4. `build-taxonomy` verde; `npm run build` verde; `ESTADO-SPECS.md` anota migración + honestidad.

---
**Nota:** Claude (Opus 4.8) reauditará: casos provenientes de `roiCases` (no hardcodeados), `[EST]` en todas las cifras, disclaimer honesto, y cero cargos/clientes inventados.
