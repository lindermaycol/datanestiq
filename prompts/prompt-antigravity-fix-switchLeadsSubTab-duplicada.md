# FIX (P0) — Agenda sigue sin abrir: `switchLeadsSubTab` está DUPLICADA y la copia vieja shadowa al fix

Reauditoría en vivo (Claude, skill `verificar-en-vivo`). El fix de la rama `agenda` **sí se escribió**, pero **no surte
efecto** porque hay **dos definiciones** de `switchLeadsSubTab` en `public/admin/index.php` y **gana la vieja**.

## Causa raíz (confirmada en vivo y en código)
En JavaScript, cuando hay dos `function foo(){}` con el mismo nombre, **la última declarada gana** (hoisting). En
`index.php`:
- **Línea ~799** `function switchLeadsSubTab(sub)` → la **CORREGIDA** (referencia `leads-content-agenda`, llama `loadAgenda()`). ✅
- **Línea ~1639** `function switchLeadsSubTab(subtab)` → la **VIEJA** (solo `if (subtab === 'detected') {...} else { CRM }`,
  **sin rama `agenda`**). ❌ Al ser la última, **sobrescribe** a la corregida.

Verificación en vivo: `switchLeadsSubTab.toString()` en la consola del panel devuelve la versión **vieja** (`subtab`, sin
`agenda`) → al clic en "Agenda" cae al `else` y vuelve a CRM. `handlesAgenda: false` en el runtime, aunque el archivo
"contiene" la versión buena.

**No es lo único duplicado:** cerca de la copia vieja (línea ~1670) hay también un `loadDetectedLeads` duplicado. Parece
que se **pegó un bloque entero** de funciones de "Leads" por segunda vez sin borrar el original.

## Fix (`public/admin/index.php`)
1. **Elimina el bloque DUPLICADO y viejo** (~1639 en adelante): la segunda `switchLeadsSubTab(subtab)` **sin** rama agenda,
   y las demás funciones duplicadas de ese bloque (`loadDetectedLeads`, y cualquier otra que ya exista en su versión buena
   arriba, ~799-940). Deja **una sola** definición de cada función — la corregida.
2. **Barrido de duplicados:** busca en `index.php` **todos** los nombres de función declarados dos veces
   (`grep -n "function <nombre>"` / `async function`). Cualquier función definida dos veces es un bug latente (la segunda
   gana). Deja una sola de cada una — la que tenga la lógica correcta/nueva.

## Verificación (reaudito EN VIVO — nueva regla)
- **En el runtime, no solo en el archivo:** en la consola del panel, `switchLeadsSubTab.toString()` debe ser la versión
  **con** rama `agenda`; `handlesAgenda` = true.
- Clic en "📅 Agenda Global de Citas" → muestra `#leads-content-agenda` con las citas (`#agenda-body` con filas del endpoint
  `agenda`) y botones Confirmar/Cancelar. **No** vuelve a Formularios CRM.
- Ninguna función declarada dos veces (`grep` limpio).
- Consola sin errores; el resto de sub-pestañas siguen bien.

## Deploy
- Solo `index.php`. **`npm run build` ANTES de `deploy_ionos.py --confirm`** (el PHP se sirve desde `dist/`), limpiar
  opcache, y **verificar en el panel real** (no solo local).

---
**Nota:** Claude (Opus 4.8) diagnosticó en vivo que el fix de la agenda existe en `index.php` (~L799) pero una **copia
vieja de `switchLeadsSubTab` (~L1639) lo sobrescribe** en runtime. Lección para tu QA: verifica la función **en el
runtime** (`fn.toString()` en consola), no solo que exista en el archivo — un duplicado posterior gana silenciosamente.
Borra los duplicados, recompila y verifica en vivo.
