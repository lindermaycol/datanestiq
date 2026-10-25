# FIX (P0) — `getIncludeDemoFlag is not defined`: rompe Conversaciones (y otros 2 loaders del panel)

Reauditoría en vivo (Claude). Tras arreglar el anidamiento de pestañas, la pestaña **Conversaciones** muestra el
encabezado pero **la lista no se puebla** (aunque el endpoint devuelve 5 sesiones reales). Causa:

## Causa raíz (confirmada en vivo)
`loadConversations()` (index.php ~L830) hace:
```js
const data = await api('conversations', { include_demo: getIncludeDemoFlag() });
```
pero **`getIncludeDemoFlag` NO está definido** en el scope global → **`ReferenceError: getIncludeDemoFlag is not defined`**
→ la función lanza **antes** de renderizar el `#conversations-sessions-tbody` → queda vacío.
- **No es un solo caso:** `getIncludeDemoFlag` se invoca en **3 lugares** del `index.php` desplegado y **nunca se define**
  (`getIncludeDemoFlag_defined: false`, 3 referencias). Los otros dos son casi seguro la **Agenda de Citas** y la tarjeta
  de **Eficiencia 0-LLM** (por eso cargan solo el encabezado y no sus datos). El resto de cada pestaña renderiza porque su
  contenido principal no usa ese helper.

## Fix (`public/admin/index.php`) — definir el helper una vez
Agrega la definición global del helper (lee el toggle de demo → 1/0):
```js
function getIncludeDemoFlag() {
  const t = document.getElementById('journey-demo-toggle'); // el checkbox "Incluir Demo"
  return (t && t.checked) ? 1 : 0;
}
```
- Colócalo **antes** de cualquier `load*()` que lo use (o al inicio del bloque `<script>`).
- **Grep obligatorio:** busca **todas** las apariciones de `getIncludeDemoFlag` en `index.php` y confirma que las 3
  quedan resueltas (conversaciones, agenda, eficiencia 0-LLM — o las que sean). Si algún loader **no** debe depender del
  toggle demo (p. ej. conversaciones/agenda no tienen filas `demoseed`), puedes pasar `include_demo: 0` fijo ahí; pero lo
  más simple y consistente es definir el helper.

## Verificación (reaudito EN VIVO)
- **Conversaciones:** al abrir la pestaña, el `#conversations-sessions-tbody` lista las 5 sesiones reales (clic → detalle
  del diálogo redactado). Sin `ReferenceError` en consola.
- **Agenda de Citas** y **Eficiencia 0-LLM:** cargan sus **datos** (no solo el encabezado).
- Consola del panel **sin** `getIncludeDemoFlag is not defined`. Las 6 pestañas muestran contenido **con datos**.

## Deploy
- Solo `index.php` (frontend). **`npm run build` ANTES de `deploy_ionos.py --confirm`** (el sitio se sirve desde
  `dist/`), limpiar opcache, y **verificar en el panel real** que la lista de conversaciones se puebla (no solo local).

---
**Nota:** Claude (Opus 4.8) diagnosticó en vivo el `ReferenceError: getIncludeDemoFlag is not defined` (3 usos, 0
definiciones) que dejaba vacíos los loaders dinámicos de Conversaciones, Agenda y Eficiencia 0-LLM. Fix: definir el
helper (lee el checkbox `journey-demo-toggle`). Recompila antes de desplegar y confirma con datos en el panel real —
lección: "pestaña visible" no basta; hay que ver que **los datos** carguen sin errores de consola.
