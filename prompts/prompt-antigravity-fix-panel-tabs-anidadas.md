# FIX (P0 UX) — 4 pestañas del panel no muestran contenido: `view-*` mal anidadas dentro de `view-leads`

Reauditoría en vivo (Claude): **4 de 6 pestañas del `/admin/` no muestran contenido** — Conversaciones, Analítica &
Eficiencia IA, Salud & Costos IA, Engagement & Comportamiento. Leads & Citas y Demanda & Journey **sí** funcionan.

## Causa raíz (confirmada en el DOM en producción)
Las vistas están **mal anidadas**: los 4 contenedores fallidos son **hijos de `#view-leads`** en vez de hermanos.
```
view-leads         → parent BODY   ✅ (top-level)
view-conversations → parent view-leads   ❌ anidado
view-analytics     → parent view-leads   ❌ anidado
view-ops           → parent view-leads   ❌ anidado
view-behavior      → parent view-leads   ❌ anidado
view-demand        → parent BODY   ✅ (top-level)
```
`switchTab(tab)` hace bien su trabajo: pone `display:block` en la vista destino y `display:none` en `#view-leads`. Pero
como las 4 vistas viven **dentro** de `#view-leads`, al ocultar el padre se ocultan también (aunque su propio
`display` sea `block`). Por eso solo se ven Leads (el padre) y Demanda (hermano top-level). **No hay error JS; es
estructura HTML.** Los endpoints y los datos están bien — el fallo es puramente de layout, introducido al agregar las
pestañas nuevas (022/023) sin cerrar el `<div id="view-leads">`.

## Fix (`public/admin/index.php`)
- **Cierra `<div id="view-leads">`** justo después de su contenido, ANTES de que empiece `<div id="view-conversations">`.
  Asegura que los **6** `view-*` sean **hermanos** (hijos del mismo contenedor/`body`), como ya lo son `view-leads` y
  `view-demand`. Probablemente falta un `</div>` (o sobra uno que engloba) donde termina la vista de Leads y arrancan las
  nuevas de 022/023.
- Verifica el balance de `<div>`/`</div>` en toda la sección de vistas (un editor con matching de tags ayuda).

## Verificación (reaudito EN VIVO)
- En el DOM: `document.getElementById('view-conversations'|'view-analytics'|'view-ops'|'view-behavior').parentElement`
  debe ser `BODY` (o el contenedor común), **no** `view-leads`.
- Click en cada una de las 6 pestañas → **muestra su contenido** (Conversaciones: visor; Analítica: eficiencia 0-LLM +
  embudo; Salud & Costos IA: SLA/tokens/alertas; Engagement: chips/consultas; Demanda; Leads). Ninguna queda en negro.
- Sin regresión de datos (los endpoints ya devuelven 200).

## Deploy
- Solo `index.php` (frontend). **`npm run build` ANTES de `deploy_ionos.py --confirm`** (el PHP se sirve desde `dist/`),
  limpiar opcache, y **verificar en el panel real** que las 4 pestañas muestran contenido (no solo local).

---
**Nota:** Claude (Opus 4.8) diagnosticó en vivo que las vistas de las 4 pestañas están anidadas dentro de `#view-leads`
(falta el `</div>` de cierre). Fix de una línea/estructura. Recompila antes de desplegar y confirma en el panel real que
las 6 pestañas renderizan.
