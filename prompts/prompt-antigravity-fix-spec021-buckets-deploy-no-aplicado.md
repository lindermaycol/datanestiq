# FIX (Spec 021) — el fix de los buckets está en el CÓDIGO pero NO se desplegó a producción

Reauditoría EN VIVO. **El código local del fix es correcto**, y la limpieza de datos aterrizó — pero el **`api.php`
corregido NO está vivo en producción**. La separación §2 de los buckets **sigue rota en el panel real**.

## Evidencia en vivo (producción, con cache-buster `no-store`)
- `GET /admin/api.php?action=demand_signals` → **total 29**, `insufficient:false`, y **NO trae el campo `include_demo`**.
- `…&include_demo=1` → **idéntico** (total 29). O sea el default **sigue incluyendo** los 22 `demoseed%` y el toggle es no-op.
- El código local **sí** emite `'include_demo' => (bool)…` en la respuesta (api.php ~L620/665) y filtra con `:inc_c/:inc_sub/
  :inc_main` (demand) y `:inc_lk/:inc_lk2` (leakage). **La respuesta de prod NO tiene ese campo → prod corre el `api.php`
  viejo, sin el fix.**

## Qué SÍ aterrizó (a nivel de datos, vía scripts SSH)
- ✅ **Limpieza PII histórica:** el teléfono `987654321` ya aparece `[PHONE_REDACTED]` en los ejemplos. `redact_historical_pii.php` corrió en prod.
- ✅ **Reseed 22:** contenido demo nuevo visible ("[DEMO] simuladores actuariales montecarlo", "computer vision frutas", etc.).
- ✅ **CLI guard en `auth.php`** (`requireAuth()` con `php_sapi_name()==='cli'`): **verificado seguro** — una petición
  web nunca es `'cli'`, no es explotable por HTTP. (Cambio no pedido pero benigno; `auth.php` es el límite de seguridad
  → márcalo explícito la próxima.)

## Causa raíz
Corriste los **scripts remotos** (`run_remote_seed.py`, `redact_historical_pii.php`) por SSH → los **datos** cambiaron.
Pero **no se re-subió el backend** (`api.php`/`index.php`) — o se subió y el **opcache** de IONOS sirve el bytecode
viejo. Por eso `journey_sessions`/`alerts_ops`/`usage_ops` (desplegados en el build previo de la 021) sí filtran demo,
pero los **buckets** (editados en esta ronda) no.

## Fix
1. **Redesplegar el backend a producción:** `api.php` y `index.php` (el toggle/banner de la UI también depende de este
   cambio) vía `deploy_ionos.py --confirm` (no solo los scripts sueltos).
2. **Limpiar opcache** si IONOS tiene `opcache.validate_timestamps=0`: subir el archivo no basta — resetea
   (`opcache_reset()` vía un script CLI, `touch` + reinicio de PHP-FPM, o el mecanismo del panel de IONOS). Confírmalo.
3. **Marcador de deploy para verificar:** la respuesta de `demand_signals`/`leakage` debe **incluir el campo
   `include_demo`** — si no está, el archivo viejo sigue sirviéndose.

## Verificación (reaudito EN VIVO)
1. `demand_signals` default → **excluye** `demoseed%`: `total` baja a solo reales (probablemente **< 20 → "datos
   insuficientes"**, que es lo honesto) y la respuesta **trae `include_demo:false`**. Con `include_demo=1` → total 29.
2. `leakage` igual (default excluye demo).
3. En la UI: toggle "Incluir Demo" recarga los buckets **y** la lista; banner DEMO visible con toggle ON; con toggle OFF
   los buckets muestran solo real.
4. Sin regresión: `journey_sessions`/`alerts_ops`/`usage_ops`/`leads_detected`/`learn_insights` siguen 200; PII histórica
   redactada se mantiene.

---
**Nota:** Claude (Opus 4.8) verificó en vivo: el **código del fix es correcto** y los **datos** (reseed + redacción PII)
aterrizaron, pero el **`api.php` corregido no está desplegado** (prod no emite el campo `include_demo`, total sigue en 29
con demo). Redespliega el backend + limpia opcache, y **verifica contra el endpoint real** (no solo local) — el campo
`include_demo` en la respuesta es el marcador de que el deploy tomó. El CLI guard de `auth.php` quedó verificado seguro.
