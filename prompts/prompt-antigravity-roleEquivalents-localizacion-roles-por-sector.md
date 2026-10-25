# Prompt para Antigravity — Localización de roles por sector (`roleEquivalents`) · F-11

Extiende **Spec 011 (enriquecimiento de taxonomía)** como fuente de datos y **Spec 013 / 002** como consumidores UI. **No es spec nueva.** Respeta la Constitución (`.specify/memory/constitution.md`) y `AGENTS.md`. **Entrega un PLAN primero** para revisión de Claude. Doc-sync a `planes/ESTADO-SPECS.md` (§11).

## Problema (F-11, hallazgo real de UX)
Los roles del sitio (`cio`, `cto`, `cdo`, `cfo`, `ceo`, `coo`, `ciso`) usan **siglas corporativas anglosajonas**. Un funcionario público peruano —u otros sectores que no usan esa jerga— **no se reconoce** como "CIO/CDO" y termina eligiendo **"Otro / Detallar"** en el Concierge, perdiendo toda la personalización contextual. Hay que **localizar la etiqueta del rol según el sector activo**, sin duplicar la semántica del rol.

## Modelo de datos (el SECTOR es dueño de cómo se nombran los roles ahí)
1. **Schema de sector** (`src/lib/schemas.js` y donde valide `sectorsCorpus`): añade un campo **opcional** `roleEquivalents`: un **record** `roleId → etiqueta local (string)`.
2. **Integridad de aristas (Zod + build-taxonomy):** cada **clave** de `roleEquivalents` DEBE ser un `roleId` existente en `personas.json` (`cfo|cio|cdo|cto|coo|ciso|ceo`). Si aparece una clave que no es un rol válido → **exit 1** (como el resto del pipeline). Valores string no vacíos.
3. **Pipeline:** `scripts/build-taxonomy.mjs` debe propagar `roleEquivalents` al JSON generado de sectores (`src/data/sectorsCorpus.json` o el que consuman las islas), para que la UI lo lea **sin hardcodear** nada en `.astro`/JSX.
4. **Opt-in:** los sectores que sí usan C-level (banca/finanzas bajo SBS/Basilea, telecom corporativo, retail, seguros, logística corporativa) **omiten** el campo → la UI cae al `persona.title` global. **Nada se rompe.**

## Contenido a poblar
### Sector Público — CANÓNICO (usar exactamente estas equivalencias)
```yaml
# src/content/sectors/publico.yaml
roleEquivalents:
  cio:  "Jefe / Director de TI (OTI)"
  cto:  "Responsable de Infraestructura y Sistemas"
  cdo:  "Director de Datos / Estadística e Informática"
  cfo:  "Director de Administración y Finanzas"
  ceo:  "Director General / Titular de la entidad"
  coo:  "Secretario General"
  ciso: "Oficial de Seguridad de la Información"
```
### Otros sectores donde aplica — PROPÓN equivalencias reales (para mi audit)
Aplica a: **salud, educación, manufactura, minería** (terminología peruana de sector público/mid-market). Ejemplos orientativos (ajústalos a nomenclatura real y verificable, no inventes cargos):
- **salud:** `ceo`→"Director General / Director del Hospital", `cio`→"Jefe de la Unidad de Estadística e Informática", `cdo`→"Responsable de Gestión de la Información en Salud", `ciso`→"Oficial de Seguridad de la Información".
- **educación:** `ceo`→"Director / Titular de la institución (UGEL/DRE)", `cio`→"Jefe / Coordinador de Informática", `cdo`→"Responsable de Estadística Educativa".
- **manufactura / minería:** `cio`→"Gerente / Jefe de Sistemas (TI)", `cto`→"Gerente de Operaciones / Ingeniería", `cdo`→"Jefe de Analítica / Gestión de Datos", `coo`→"Gerente de Operaciones", `ciso`→"Jefe de Ciberseguridad".

**Regla de honestidad (§2):** usa nombres de cargo **reales y reconocibles** del Estado/empresa peruana; ante duda, prefiere una etiqueta **genérica pero exacta** ("Gerente / Jefe de Sistemas") antes que inventar un título específico. Marca en el plan las que sean tu propuesta para que las valide.

### Sectores que NO llevan el campo (justifica en el plan)
**finanzas, retail, telecomunicaciones, seguros, logística** → mantienen C-level (o mínimos). Si crees que alguno merece ajuste, propónlo.

## Consumo en UI (usar etiqueta local cuando hay SECTOR activo; acrónimo como secundario)
Formato recomendado: **`"<Etiqueta local> (<SIGLA>)"`** — ej. *"Director de Datos / Estadística e Informática (CDO)"* — así el funcionario se reconoce y el que sí sabe "CDO" también. Fallback: `persona.title` global cuando no hay sector o el rol no tiene equivalente en ese sector.
1. **Concierge / Chatbot (PRIMARIO, es el F-11):** los **botones de selección de rol** (paso posterior a elegir sector) y el **saludo heredado** deben usar la etiqueta localizada del sector activo. Mantén **"Otro / Detallar"**. **0-LLM intacto** (es lectura de datos + estado local, sin `fetch`).
2. **HeroRoleLine:** ya tiene `userContext` (rol + sector) → cuando hay sector, muestra la etiqueta localizada (con sigla secundaria); sin sector, título global.
3. **Secundario (si es limpio):** `ConsultativeCTA` / `SolutionsByRoleAndIndustry` cuando haya sector/filtro activo. Si complica, déjalo para después y dilo en el plan.
- **Solo cambia la ETIQUETA (nombre) del rol.** `goals`, `pains`, `decisionCriteria` y la lógica del flujo **no cambian** (el mapeo `slug→id` de sector y los `roleId` siguen igual).

## Guardarraíles
- **Taxonomía = fuente de verdad:** `roleEquivalents` vive en los YAML de sector; la UI lo lee del JSON generado. **Nunca** hardcodear etiquetas en `.astro`/JSX.
- **0-LLM** del flujo guiado intacto; **honestidad §2** (nomenclatura real, no inventada); progressive enhancement (fallback a título global); `npm run build` verde; `node scripts/build-taxonomy.mjs` verde (Zod + aristas, exit 1 si clave inválida).

## Verificación (evidencia real)
1. **Concierge → "Sector Público":** los botones de rol muestran *"Jefe / Director de TI (OTI)"*, *"Director de Datos / Estadística e Informática (CDO)"*, etc. (no las siglas secas). "Otro / Detallar" sigue presente.
2. **Concierge → "Finanzas y Banca"** (sin `roleEquivalents`): botones muestran los títulos C-level globales (fallback OK).
3. **HeroRoleLine:** chip "Sector Público" → muestra la etiqueta localizada del rol.
4. **0-LLM:** recorrer el flujo guiado = **cero `POST /api/chat.php`**.
5. **Pipeline:** meter una clave inválida de prueba en `roleEquivalents` hace **fallar** `build-taxonomy` (exit 1); quitarla lo deja verde. `npm run build` verde.
6. `ESTADO-SPECS.md` actualizado (Spec 011: `roleEquivalents` por sector; Spec 013/002: consumo localizado en Concierge/HeroRoleLine). Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: etiquetas localizadas en el Concierge para público (y demás sectores con el campo), fallback correcto en finanzas, HeroRoleLine localizado, 0-LLM intacto, integridad de aristas del pipeline, y **cero cargos inventados** (nomenclatura real).
