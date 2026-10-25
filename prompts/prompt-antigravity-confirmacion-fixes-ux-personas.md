# Confirmación — Fixes UX por personas: LUZ VERDE con 4 precisiones + decisión del Fix 6 → ejecutar

El plan (`planes/Plan de Implementación — Fixes de Auditoría UX por Personas (Home).md`) es sólido y respetó la **exclusión de honestidad** (nada de casos ficticios). Apruebo **Fixes 1–5** con estas precisiones, y **decido el Fix 6**.

## 🔴 Precisión 1 (Fix 4) — NO pre-llenes `organizacion` con una etiqueta de sector
El plan propone pre-llenar `organizacion` con *"Organización del sector Finanzas"*. **Eso contamina el lead:** `organizacion` es el **nombre real de la empresa** del prospecto; ponerle una etiqueta de sector mete basura al CRM (verías "Organización del sector Finanzas" en vez de la empresa real).
**Fix:** pre-llena **solo `reto`** (con la consulta/problema) y opcionalmente `stack` si el usuario lo mencionó. **Deja `organizacion` vacío** (lo llena el usuario). Editable/borrable (useRef una vez).

## 🔴 Precisión 2 (Fix 3) — Mapear `userContext.sector` → id del corpus
El chip guarda `userContext.sector` como slug (`'finanzas'`, `'publico'`, `'retail'`), pero el flujo guiado del chatbot usa ids de `sectorsCorpus` (`'sector-finanzas'`, etc.). Al heredar el contexto, **mapea** el slug al id correcto (busca el sector cuyo id/slug coincida) antes de pre-cargar el estado; si no matchea, cae al comportamiento normal (pregunta sector). No asumas que el slug == id.

## Precisión 3 (Fix 5) — El value-prop se COMPONE de campos de taxonomía, no de prosa inventada
Los ejemplos del plan ("...defendible ante directorio", "...con alta disponibilidad") suenan a copy redactado. **Compón el bloque a partir de los campos reales de la persona** (`goals`, `decisionCriteria`, `pains`) de `personas.json` — p. ej. `{goals[0]} · {decisionCriteria[0]}` — con mínimo texto conectivo. No inventes afirmaciones que no estén en la taxonomía (§2).

## Precisión 4 (Fix 2) — El modelo se cachea entre sesiones, no "1 vez por sesión"
`@xenova/transformers` cachea el modelo en el navegador (persiste entre visitas). El mensaje debe decir *"Cargando el modelo de IA (solo la primera vez)…"* — no "1 vez por sesión". Ajusta el copy.

## 🔴 Decisión del Fix 6 — Opción B: mantener los 3 chips actuales + AGREGAR "Datos / CDO"
**No** hagas la estandarización a 4 chips de rol (tu Opción A). Razones:
- Dropea el chip **"Sector Público"**, que es la entrada **estratégica de la Fase 1** (invertimos mucho ahí) — el buyer público perdería su acceso directo.
- Fuerza sectores **arbitrarios/engañosos** por rol (ej. "CIO → publico": un CIO no es necesariamente de sector público) → mis-routea contenido y el chatbot.

**Haz esto:** mantén los 3 chips actuales (`Sector Público`→cdo/publico, `Finanzas / CFO`, `Estrategia / CEO`) y **agrega un 4º chip "Datos / CDO"** → `handleSelect('finanzas', 'cdo')` (o sector neutro), para el CDO orientado a datos/finanzas. Mínima disrupción, preserva la entrada pública, cubre al CDO. Si más adelante quieres rediseñar el set completo de chips, lo vemos como decisión de producto aparte.

## Guardarraíles (Constitución) — sin cambios
0-LLM del flujo guiado intacto (herencia de chip = store + estado local, sin `fetch`); honestidad §2 (cero casos/prosa inventada; todo de taxonomía); PII segura (Fix 4 sin ensuciar `organizacion`); progressive enhancement; `npm run build` verde.

## Verificación (evidencia real)
1. **Copiloto:** links `[texto](/soluciones/…)` clicables (no crudos), anti-XSS.
2. **Buscador:** skeleton + progreso "(solo la primera vez) X%"; siguientes búsquedas inmediatas.
3. **Chatbot hereda chip:** con chip CFO activo, el bot **salta** al paso correcto (con el mapeo de sector OK) y saluda personalizado; Network = **cero `chat.php`** en el flujo guiado.
4. **Formulario:** `reto` pre-llenado y editable; **`organizacion` vacío** (no etiqueta de sector).
5. **Hero por rol:** los 4 roles muestran el bloque compuesto de `goals`/`decisionCriteria` (taxonomía); sin rol, nada.
6. **Chips:** 4 chips (los 3 actuales + "Datos / CDO"); "Sector Público" **sigue presente**.
7. `npm run build` verde; consola limpia; `ESTADO-SPECS.md` actualizado. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: links del copiloto, feedback del buscador, herencia de chip (0-LLM + mapeo de sector), formulario sin ensuciar `organizacion`, hero por rol desde taxonomía, y que el chip "Sector Público" siga existiendo.
