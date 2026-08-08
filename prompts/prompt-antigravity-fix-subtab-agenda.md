# FIX (P0 UX) — la sub-pestaña "Agenda Global de Citas" no abre (falta el branch en `switchLeadsSubTab`)

Reauditoría en vivo (Claude). **Conversaciones quedó ARREGLADA** (`getIncludeDemoFlag` definido; la lista muestra las 5
sesiones ✅). Pero la sub-pestaña **"📅 Agenda Global de Citas"** (dentro de "Leads & Citas") **no abre**: al hacer clic
vuelve a "Formularios CRM" / no muestra nada.

## Causa raíz (confirmada en vivo)
El botón llama `switchLeadsSubTab('agenda')`, pero la función **solo maneja `'detected'` y, en su `else`, `'crm'`** — **no
tiene rama para `'agenda'`**:
```js
function switchLeadsSubTab(subtab) {
    const crmTab = ...; const detTab = ...;
    const crmContent = ...; const detContent = ...;   // ← nunca referencia leads-content-agenda
    if (subtab === 'detected') { ...mostrar detected... loadDetectedLeads(); }
    else { ...mostrar CRM... loadLeads(currentPage); }   // ← 'agenda' cae aquí → vuelve a CRM
}
```
Todo lo demás ya existe: el contenedor **`#leads-content-agenda`**, el tbody **`#agenda-body`**, la función
**`loadAgenda()`** (definida) y el endpoint **`agenda`** (devuelve la cita real, `total:1`). **Solo falta el branch.**

## Fix (`public/admin/index.php`)
Agrega una rama `agenda` en `switchLeadsSubTab` (y resetea/oculta las otras dos). Ejemplo:
```js
const agendaTab = document.getElementById('leads-subtab-agenda');
const agendaContent = document.getElementById('leads-content-agenda');

if (subtab === 'detected') {
    // ...igual que hoy... crmContent.display='none'; detContent.display='block'; agendaContent.style.display='none';
    loadDetectedLeads();
} else if (subtab === 'agenda') {
    // resaltar agendaTab, apagar crmTab/detTab
    agendaTab.style.background = '#22d3ee'; agendaTab.style.color = '#000'; agendaTab.style.fontWeight = '700';
    crmTab.style.background='rgba(255,255,255,0.05)'; crmTab.style.color='#fff'; crmTab.style.fontWeight='normal';
    detTab.style.background='rgba(255,255,255,0.05)'; detTab.style.color='#fff'; detTab.style.fontWeight='normal';
    crmContent.style.display = 'none';
    detContent.style.display = 'none';
    agendaContent.style.display = 'block';
    loadAgenda();
} else { // 'crm'
    // ...igual que hoy... + agendaContent.style.display='none'; agendaTab reset
    crmContent.style.display='block'; detContent.style.display='none';
    loadLeads(currentPage);
}
```
- Asegura que en **cada** rama las **tres** vistas se muestran/ocultan correctamente (incluye ocultar `agendaContent` en
  crm/detected, cosa que hoy no se hace).

## Verificación (reaudito EN VIVO)
- Clic en "📅 Agenda Global de Citas" → muestra `#leads-content-agenda` con las citas (`#agenda-body` con filas del
  endpoint `agenda`), y los botones Confirmar/Cancelar. **No** vuelve a Formularios CRM.
- Clic en "Formularios CRM" / "Detectados en Chat" → alternan bien y **ocultan** la agenda.
- Sin errores de consola.

## Deploy
- Solo `index.php`. **`npm run build` ANTES de `deploy_ionos.py --confirm`**, limpiar opcache, verificar en el panel real.

## Sugerencia (para cerrar esta racha de bugs de navegación)
Han aparecido 3 bugs de wiring de UI seguidos (pestañas anidadas, `getIncludeDemoFlag` indefinido, y este sub-tab sin
branch). Todos se cazan con **un click-through completo**: abre **cada pestaña y cada sub-pestaña**, confirma que su
**contenido con datos** carga (no solo el encabezado) y que la **consola no tiene errores**. Hazlo como paso final antes
de reportar verde — evita encontrarlos de a uno.

---
**Nota:** Claude (Opus 4.8) verificó Conversaciones OK (5 sesiones) y diagnosticó que `switchLeadsSubTab` no tiene rama
`'agenda'` (por eso vuelve a CRM). `loadAgenda`, `#leads-content-agenda`, `#agenda-body` y el endpoint ya existen — solo
falta el branch. Recompila antes de desplegar y haz un click-through de todo el panel.
