# FIX — Spec 022/023: el código está bien y correcto, pero NO se desplegó a producción

Reauditoría EN VIVO. **Las correcciones de diseño aterrizaron en el código** (verificado), y el build-gate/compilación
local pasaron. **Pero el backend de 022/023 NO está vivo en producción** — todos los endpoints nuevos devuelven
`{"error":"Unknown action"}`. Es el **mismo hueco de deploy** de la ronda de los buckets.

## ✅ Verificado en código (correcciones OK)
- **023 — fórmula de ahorro (§2):** ahora `ai_efficiency` usa **tokens reales × tarifa de referencia**
  (`zero_llm_count × AVG(total_tokens)/1e6 × 0.30`) con nota honesta ("basado en tokens reales; proveedores en free-tier")
  + llamadas evitadas. Ya no usa latencia. ✅
- **023 — agenda:** usa el **esquema real** de `appointments` (`requested_date`/`duration_minutes`/`type`/`status`,
  filtro `IN ('solicitada','confirmada')`, acciones confirmar/cancelar). ✅
- **022 — FK inválida removida:** `conversations` ya no declara `FOREIGN KEY … chat_metrics`. ✅
- Doc-sync consistente (build-gate PASSED, ambas filas). ✅

## 🔴 Hallazgo — NO desplegado a producción
- **Evidencia en vivo:** `GET /admin/api.php?action=ai_efficiency|agenda|conversations|usage_daily` → **todos
  `{"error":"Unknown action"}` (400)**. El `api.php` desplegado **no tiene** los casos nuevos.
- **Producción está en el deploy anterior:** `demand_signals` aún trae el campo `include_demo` (fix de buckets, ronda
  pasada) → prod = estado previo, sin 022/023.
- **El walkthrough lo confirma:** solo reporta build-gate + `npm run build` local (119 págs en `dist/`); **no hay paso de
  `deploy_ionos.py --confirm` ni migración remota**. Se construyó local pero no se desplegó.
- **Ojo con la migración:** `migrate_file_free_history` importó 227 conversaciones/209 crudos/etc. — muy probablemente
  de tu entorno **local**. En **producción** ese histórico (los `.jsonl`/`.csv` reales del servidor) **aún no se migró**;
  y como `chat.php` deja de escribir archivos, hay que migrar el histórico de prod **en el mismo deploy** para no perderlo.

## Fix (deploy real + migración remota + verificación)
1. **`npm run build`** (recompila `dist/` con el `api.php`/`chat.php`/`index.php` nuevos) → **`deploy_ionos.py --confirm`**.
2. **En producción, vía SSH (`/usr/bin/php8.2-cli`):** corre `init_crm_db.php` (crea las tablas nuevas) y
   `migrate_file_free_history.php` (importa el histórico **de prod** + archiva a `_archive/`). **Backup + `COUNT(*)`
   pre/post.**
3. **Limpia opcache** si IONOS tiene `validate_timestamps=0`.
4. **Verifica contra el endpoint real** (no solo local): `ai_efficiency`/`agenda`/`conversations`/`usage_daily` deben
   devolver **200 + datos** (no "Unknown action"). Ese "Unknown action" es el marcador de que el deploy no tomó.

## Verificación (reaudito EN VIVO tras el deploy)
- **022:** `conversations` 200 con las sesiones migradas; `usage_daily` 200 (hoy vs cap); `leads_detected` desde
  `leads_extracted`; **`chat_raw` sigue SIN endpoint** (probe → "Unknown action", correcto); y **no se generan nuevos
  `.jsonl`/`.csv`** en `secure_leads/` tras tráfico real.
- **023:** `ai_efficiency` con ahorro honesto (tokens×tarifa, `[EST]`, nota free-tier) + guard §2; **Agenda** con citas
  reales y acciones; pestaña **"Salud & Costos IA"** sin la tabla de specs/build/ESLint; embudo/dona con guard §2.

---
**Nota:** Claude (Opus 4.8) verificó que el código y las correcciones están bien, pero **el deploy de 022/023 no se
ejecutó** (endpoints "Unknown action" en vivo; prod sigue en el deploy anterior). Recompila, **despliega de verdad**,
corre la **migración en la DB de prod** (no solo local) con backup, limpia opcache, y **verifica contra el endpoint real**.
Este es el 3er caso del mismo patrón — añade a tu checklist un paso obligatorio: *"deploy ejecutado + endpoint real
responde 200"* antes de reportar verde.
