# Confirmación — Localización de roles por sector (`roleEquivalents`): LUZ VERDE con 2 precisiones → ejecutar

El plan (`planes/Plan de Implementación — Localización de Roles por Sector (roleEquivalents) · F-11.md`) es correcto y completo. Apruebo las equivalencias propuestas (público canónico + salud/educación/manufactura/minería con cargos reales; finanzas/retail/telecom/seguros/logística en fallback C-level). Verifiqué que **`roleEquivalents` fluye solo al `sectorsCorpus.json`** (el pipeline escribe el array de sectores ya parseado por Zod, que conserva todo campo del schema) — no hace falta cherry-pick. Ejecuta con estas 2 precisiones.

## 🔴 Precisión 1 (correctness) — HeroRoleLine debe mapear el slug del sector → id ANTES de buscar
`HeroRoleLine` lee `userContext.sector`, que es un **slug** (`'publico'`, `'finanzas'`), pero los ids de `sectorsCorpus` llevan prefijo (`'sector-publico'`). Si buscas `sectorsCorpus.find(s => s.id === userContext.sector)` **nunca matchea** → siempre caería al título global (el feature no se vería en el Hero).
- **Reutiliza el mapeo `mapSectorSlugToId`** que ya existe en `Chatbot.jsx` (búscalo por `id === slug || id === 'sector-'+slug || id.endsWith('-'+slug)`). Extráelo a un util compartido (p. ej. en el nuevo `roleLocalization.ts`) y úsalo tanto en HeroRoleLine como donde consumas `userContext.sector`.
- En el **Chatbot**, `chatState.sector` ya es el **id** (se setea en el flujo/herencia), así que ahí el lookup es directo — no cambia.

## 🔴 Precisión 2 (formato) — evita paréntesis dobles
El helper añade `" (<SIGLA>)"` salvo que la etiqueta ya incluya `(cio)`/`(CIO)`. Pero **dos etiquetas propuestas ya terminan en un paréntesis que NO es la sigla del rol**, y producirían un doble paréntesis feo:
- `publico.cio: "Jefe / Director de TI (OTI)"` → helper → *"Jefe / Director de TI (OTI) **(CIO)**"* ❌
- `educacion.ceo: "Director / Titular de la Institución (UGEL/DRE/Universidad)"` → *"…(UGEL/DRE/Universidad) **(CEO)**"* ❌

**Arréglalo dejando las etiquetas SIN paréntesis final de acrónimo**, para que el `" (<SIGLA>)"` del helper sea el único. Sugerencias:
- `publico.cio: "Jefe / Director de la OTI"` → *"Jefe / Director de la OTI (CIO)"* ✅ (OTI como parte del nombre, no paréntesis colgante).
- `educacion.ceo: "Director o Titular de la institución educativa"` → *"…educativa (CEO)"* ✅ (mueve el detalle UGEL/DRE/Universidad al cuerpo del sector, no a la etiqueta del rol).
- Revisa las demás equivalencias y aplica el mismo criterio: **una etiqueta = sin paréntesis de acrónimo al final**; el helper pone la sigla. (Opcional adicional: endurece el helper para no duplicar si detecta cualquier `(...)` final — pero la fuente de verdad son etiquetas limpias.)

## OK tal como está
- `sectorSchema`: `z.record(z.enum([...roles]), z.string().min(1)).optional()` ✅ + validación explícita de integridad en `build-taxonomy.mjs` (exit 1 si clave inválida o string vacío) ✅.
- `roleEquivalents` se propaga solo al `sectorsCorpus.json` (Zod conserva el campo) ✅.
- Consumo 0-LLM en Chatbot (botones de rol + saludo heredado, "Otro / Detallar" intacto), HeroRoleLine, y SolutionsByRoleAndIndustry (solo con sector/filtro activo; sin sector → título global) ✅.
- Solo cambia la **etiqueta**; `goals`/`pains`/`decisionCriteria` y los `roleId` no se tocan ✅.

## Verificación (evidencia real)
1. **Concierge → "Sector Público":** botones muestran *"Jefe / Director de la OTI (CIO)"*, *"Director de Datos / Estadística e Informática (CDO)"*, etc. **sin doble paréntesis**; "Otro / Detallar" presente.
2. **Concierge → "Finanzas y Banca"** (sin campo): botones en C-level global (fallback).
3. **HeroRoleLine:** chip "Sector Público" → badge con la etiqueta localizada (confirma que el **slug→id** funciona; si vieras la sigla seca, el mapeo falló — Precisión 1).
4. **0-LLM:** flujo guiado = **cero `POST /api/chat.php`**.
5. **Integridad de aristas:** meter `roleEquivalents: { rol_invalido: "X" }` de prueba → `node scripts/build-taxonomy.mjs` **exit 1**; quitarla → exit 0. `npm run build` verde.
6. `ESTADO-SPECS.md` actualizado (Spec 011: `roleEquivalents`; Spec 013/002: consumo localizado). Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: etiquetas localizadas y **sin doble paréntesis** en público, fallback en finanzas, **HeroRoleLine localizado (slug→id OK)**, 0-LLM, exit-1 del pipeline, y nomenclatura real (cero cargos inventados).
