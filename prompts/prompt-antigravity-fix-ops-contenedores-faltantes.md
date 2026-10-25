# FIX (P1) — "Salud & Costos IA" (view-ops): crash por 2 contenedores faltantes aborta media pestaña

Hallazgo en vivo (Claude, skill `verificar-en-vivo`, click-through completo del panel en producción). La pestaña **Salud
& Costos IA** renderiza solo su parte superior (KPIs + tabla de proveedores) y **deja vacías** las secciones inferiores.
Causa: un `TypeError` en runtime.

## Causa raíz (confirmada en vivo, DOM real)
`loadOpsTelemetry()` en `public/admin/index.php` escribe `innerHTML` en dos contenedores que **no existen** en el HTML de
`#view-ops`:
- Línea ~1028: `document.getElementById('ops-system-info').innerHTML = ...`  → **`ops-system-info` no existe** → `Cannot set
  properties of null (setting 'innerHTML')`.
- Línea ~1037: `document.getElementById('ops-specs-body')`  → **`ops-specs-body` tampoco existe**.

Consola en vivo: `Uncaught (in promise) TypeError: Cannot set properties of null (setting 'innerHTML')`. El `grep` confirma
que el HTML de `#view-ops` (líneas ~340-419) solo define `ops-kpi-grid` (342) y `ops-providers-body` (353); **faltan**
`ops-system-info` y `ops-specs-body`. Verificación DOM: `getElementById('ops-system-info') === null` y
`getElementById('ops-specs-body') === null`.

**Impacto (el crash aborta la función a mitad):** como el throw ocurre en la línea 1028, **nada de lo que sigue en
`loadOpsTelemetry` se ejecuta**:
- ❌ Estado del Sistema (`ops-system-info`) — no existe / no renderiza
- ❌ Portafolio de Specs SSOT (`ops-specs-body`) — no renderiza
- ❌ Tendencia Diaria 7d (`ops-trend-body`) — **0 filas** (verificado)
- ❌ Alertas de Infraestructura SQLite (`ops-alerts-body`) — **0 filas** (verificado)
- ❌ Consumo/Desglose de Tokens LLM (`ops-tokens-usage-container`) — vacío (verificado)
- ✅ KPIs (`ops-kpi-grid`) y Proveedores (`ops-providers-body`) — sí renderizan (van **antes** del crash)

Es un caso de **"visible ≠ funciona"**: se ve la parte de arriba y parece OK, pero la mitad inferior está muerta.

## Fix (`public/admin/index.php`)
1. **Restaurar los 2 contenedores faltantes en `#view-ops`** (los loaders producen contenido real, así que deben existir):
   - **Estado del Sistema:** una tarjeta con `<div id="ops-system-info"></div>` (el JS L1028 inyecta último build, páginas
     estáticas, total specs, subdominio, protección .htaccess).
   - **Portafolio de Specs (SSOT):** una tarjeta con una tabla cuyo `<tbody id="ops-specs-body"></tbody>` — el `map` (L1043-1050)
     emite **6 columnas**: `ID`, `Nombre`, `Estado`, `Fase`, `Deuda Técnica Abierta`, `Última Auditoría`. Encabezado acorde.
   - Colócalos dentro de `#view-ops` (p. ej. tras la grilla de alertas/tokens, o donde tenga sentido visual), de forma que
     **sean hijos de `#view-ops`** (no anidados en otra vista) — misma regla que ya aplicamos a las vistas hermanas.
2. **Endurecer `loadOpsTelemetry` con guardas anti-null** para que un contenedor ausente **no vuelva a abortar** el resto:
   envuelve cada bloque en `const el = document.getElementById(id); if (el) { el.innerHTML = ... }` (o un helper
   `setHTML(id, html)` que haga el null-check). Así, si mañana falta un contenedor, se degrada esa sección sin matar
   Tendencia/Alertas/Tokens.
3. **Barrido preventivo:** revisa que **todos** los `getElementById(...).innerHTML` de `loadOpsTelemetry` (y de otros
   loaders de `view-ops`) tengan su contenedor correspondiente en el HTML (`ops-trend-body`, `ops-alerts-body`,
   `ops-tokens-usage-container`, `ops-daily-*` ya existen; confirma que no haya otro huérfano).

## Verificación (reaudito EN VIVO — click-through con DATOS)
- Consola del panel **sin** `TypeError: Cannot set properties of null` al abrir "Salud & Costos IA".
- Scroll completo de la pestaña: **Estado del Sistema** con datos, **Portafolio de Specs** con filas (una por spec del
  SSOT), **Tendencia 7d**, **Alertas SQLite** y **Consumo de Tokens** ya con contenido (o su estado vacío honesto §2, no
  en blanco por crash). Verificado por DOM: `ops-trend-body`/`ops-alerts-body` con filas > 0 cuando hay datos.

## Deploy
- Solo `index.php`. **`npm run build` ANTES de `deploy_ionos.py --confirm`** (PHP se sirve desde `dist/`), limpiar opcache,
  y **click-through en el panel real** (no solo local ni "SELF-GUARD PASSED" — el auto-verify no ejerce el DOM del panel).

---
**Nota:** Claude (Opus 4.8) lo cazó con click-through completo del panel (no solo "la pestaña abre"). El fix de **Agenda
Global de Citas** quedó ✅ verificado en vivo (carga 1 cita real con Confirmar/Cancelar). Este bug de `view-ops` es
**independiente** y preexistente: los KPIs enmascaraban que la mitad inferior estaba muerta por el crash. Lección reforzada:
"visible ≠ funciona" — hay que **recorrer cada sección** y mirar la consola, no solo el encabezado.
