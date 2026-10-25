# FIX menor (Spec 021) — el toggle "Incluir Demo" no pasa `include_demo` a los BUCKETS (+ falta el banner)

Reauditoría EN VIVO. **El fix §2 core quedó CORRECTO y desplegado** (verificado): los buckets `demand_signals`/`leakage`
**excluyen demo por defecto** (total 7/3 reales → "Datos insuficientes" honesto), el endpoint ya emite el campo
`include_demo`, la PII histórica está saneada y el CLI guard es seguro. Solo queda un **hueco de frontend** (no
§2-crítico) para cerrar la ronda.

## Evidencia en vivo
Al activar el checkbox "Incluir Demo" en "Demanda & Journey":
- La **lista de sesiones** sí incluye demo (ese fetch lleva `include_demo`). ✅
- Pero la petición que dispara para los buckets es `GET …?action=demand_signals` **sin** `include_demo=1` (visto en
  Network) → los buckets recargan con demo **excluido** → el guard sigue en "Datos insuficientes" aunque el toggle esté ON.
- **`hasDemoBanner: false`** en el DOM → el banner de "modo demo" que se documentó **no está** en la UI.

Efecto: aunque marques "Incluir Demo", **no puedes previsualizar Bucket 1 (brechas) ni Bucket 2 (fugas) poblados** — que
era el objetivo original de sembrar las 20+ señales.

## Fix (solo frontend, `public/admin/index.php`)
1. En `loadDemandData()` (o la función que carga los buckets), **propaga `include_demo`** del checkbox a los fetches de
   **`demand_signals` Y `leakage`**, igual que ya se hace para `journey_sessions`. Hoy solo la lista lo recibe.
2. **Muestra el banner de modo demo** cuando el toggle está ON (ej. una barra visible arriba: "⚠️ Mostrando datos DEMO
   sembrados — no representan demanda real"), y ocúltalo con el toggle OFF. Es el salvaguarda §2 para que, al ver los
   buckets con demo, nadie los confunda con demanda real.
3. Al togglear, recarga **todo** el dashboard de demanda de forma consistente (guard §2 + Bucket 1 + Bucket 2 + lista),
   no solo la lista.

## Verificación (reaudito en vivo)
- Toggle **OFF** → buckets "Datos insuficientes" (demo excluido), sin banner. (Ya funciona.)
- Toggle **ON** → Bucket 1 (brechas) y Bucket 2 (fugas) **renderizan** con los datos sembrados, **banner DEMO visible**.
  En Network, los fetches de `demand_signals`/`leakage` llevan `include_demo=1`.
- Sin regresión del §2 (default sigue excluyendo demo) ni de los otros endpoints.

## Deploy
- Solo `index.php` (frontend). **Recuerda `npm run build` ANTES de `deploy_ionos.py --confirm`** — el backend/PHP se
  sirve desde `dist/`, así que sin recompilar se vuelve a subir la versión vieja (fue la causa raíz de la ronda anterior).
  Verifica contra la UI real, no solo local.

---
**Nota:** Claude (Opus 4.8) verificó que el §2 core está bien y vivo (demo excluido por defecto). Esto es un cierre menor:
el toggle debe pasar `include_demo` también a los **buckets** (hoy solo a la lista) y mostrar el **banner demo**, para que
puedas previsualizar Bucket 1/2 poblados sin romper la honestidad. Compila (`npm run build`) antes de desplegar.
