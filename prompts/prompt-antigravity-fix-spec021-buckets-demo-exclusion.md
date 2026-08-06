# FIX (Spec 021) — los buckets de demanda NO excluyen el demo (§2): la exclusión se aplicó a 3 de 5 endpoints

Reauditoría EN VIVO. **Los 3 arreglos + la mejora están verificados y funcionando** (detalle abajo). Pero al poblar el
demo surgió un **hueco §2**: los dos buckets de inteligencia de demanda muestran los datos sembrados **como reales**.

## ✅ Verificado en vivo (bien)
- **Arreglo 1:** la lista "Sesiones Recientes" carga al abrir la pestaña (7 filas, `journey-demo-toggle` en `false`, sin
  interactuar). ✅
- **Arreglo 2:** el seeder corrió en prod (22 señales demo); los buckets ya renderizan (no "datos insuficientes"). ✅
- **Arreglo 3:** extracción de tokens **funciona en llamadas nuevas** — probé una llamada real a `chat.php` (200) y
  `usage_ops.total_prompt_tokens` pasó **0 → 1608** (req 15→16). Los 0 previos eran datos anteriores al fix. ✅
- **Mejora menor:** `session_id` enmascarado (label sector·rol + input hidden). ✅  Los 5 endpoints → 200 (sin regresión). ✅

## 🔴 Hallazgo — `demand_signals` (Bucket 1) y `leakage` (Bucket 2) NO excluyen el demo por defecto
**Evidencia en vivo:** `GET …?action=demand_signals` y `…&include_demo=1` devuelven **IDÉNTICO** (total **29**, mismas 5
filas), con ejemplos `[DEMO]` mezclados con reales. `leakage` igual (total 15). O sea:
- El **default incluye** las 22 filas `demoseed%` (29 = ~7 reales + 22 demo).
- El parámetro **`include_demo` es no-op** para estos dos endpoints.
- La "**Posible Demanda No Atendida (Hipótesis de mercado)**" y las "**Fugas**" muestran datos **sembrados como reales**
  por defecto → viola la separación §2 que diseñamos (excluir demo por defecto, incluirlo solo con el toggle).

**Causa raíz (código):** el filtro `(:inc = 1 OR session_id NOT LIKE 'demoseed%')` se añadió a `journey_sessions`,
`alerts_ops` y `usage_ops` (verificado en `api.php`), **pero NO** a los casos `demand_signals` (~L566-606) ni `leakage`
(~L608-639) — siguen con la query de Spec 020 sin manejo de demo. El [DEMO] en los ejemplos es mitigación parcial, pero
los conteos y el render tratan el demo como real.

**Fix:**
1. **Backend (`api.php`):** en `demand_signals` y `leakage`, lee `$include_demo = (int)($_GET['include_demo'] ?? 0)` y
   añade `AND (:inc = 1 OR <tabla>.session_id NOT LIKE 'demoseed%')` en **(a)** el `COUNT(*)` del guard §2 (para que
   `total` cuente solo real por defecto → si real < 20/10 vuelve a "datos insuficientes", que es lo honesto),
   **(b)** la query de agrupación del Bucket 1 y su subconsulta de ejemplos, y **(c)** la query de `leakage` y su total.
   Bindea `:inc` como en los otros 3 endpoints.
2. **Frontend (`index.php`):** el toggle "Incluir Demo" debe **recargar también** `demand_signals` y `leakage` con
   `include_demo`, no solo `journey_sessions`. Y un **banner** cuando el toggle está ON: "Mostrando datos DEMO sembrados"
   (ya acordado en el diseño), para que nunca se confunda demo con demanda real.
3. **Resultado esperado:** con toggle **OFF**, los buckets muestran **solo demanda real** (probablemente "datos
   insuficientes" hoy, que es lo correcto); con toggle **ON**, incluyen el demo, claramente etiquetado.

## 🟡 Menor (limpieza de dato) — fila vieja con teléfono de 9 dígitos sin redactar
En los ejemplos aparece `…mi celular 987654321` sin redactar: es una fila creada **antes** del Fix B (redacción de 9
dígitos) de la Spec 020. Está tras `auth.php` (no es fuga pública), pero conviene una **re-redacción única** de las filas
históricas: un `UPDATE demand_signals SET query_redacted = <redactPii(query_redacted)>` de una vez (o vía script CLI) para
las filas previas al fix. No bloqueante.

## Verificación (reaudito en vivo)
1. `demand_signals` default → excluye `demoseed%` (total = solo reales; si <20 → "datos insuficientes"); con
   `include_demo=1` → incluye demo. Mismo para `leakage`.
2. El toggle de la UI cambia **los buckets Y la lista** juntos; banner DEMO visible con toggle ON.
3. Sin regresión de los arreglos ya verificados (lista carga al abrir, tokens en llamadas nuevas, 5 endpoints 200).

## Deploy
- `php -l api.php`; sin migración (solo queries). Deploy **dry-run → `--confirm`**. Entrégame el reporte y reaudito en vivo.

---
**Nota:** Claude (Opus 4.8) verificó los 3 arreglos + la mejora en vivo (todos OK). El único pendiente es que la
exclusión demo llegue también a los **buckets de demanda** (`demand_signals`/`leakage`) — hoy muestran lo sembrado como
real por defecto, que es justo el §2 que el toggle debía proteger. Es un fix pequeño (mismo patrón `:inc` de los otros 3
endpoints) + recargar los buckets con el toggle.
