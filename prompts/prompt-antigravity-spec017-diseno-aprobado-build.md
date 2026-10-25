# Spec 017 — diseño APROBADO (precisiones resueltas) · procede a la fase de BUILD

Reaudité el fix del diseño. **Las 2 precisiones quedaron bien resueltas:**
- **P1 SSOT:** `specsStatus.json` como SSOT de campos estructurados + `build-specs-status.mjs` con **build-gate
  antidrift** (falla el build si el `status` no coincide con `ESTADO-SPECS.md`). ✅
- **P2 percentiles:** CTE + window functions (`ROW_NUMBER`/`COUNT() OVER`) sobre la población `success=1`, con guard
  de muestra mínima (`< 10` → NULL + "datos insuficientes"). ✅

**Luz verde para construir**, siguiendo el `plan.md` (4 fases) con sus gates humanos. Cuando entregues el plan de
build detallado o la implementación, Claude lo audita.

## Recordatorios para el build
- **Reutiliza `chat_metrics`** (sin tablas nuevas); todo tras `auth.php`; Vanilla, sin libs pesadas ni APM.
- **§2:** percentiles/tendencias solo con muestra suficiente; si no, "datos insuficientes" (nunca cifras engañosas).
- **§6:** métricas de sistema, **cero PII** de leads; el status de specs viene de `specsStatus.json` (no de `leads`).
- **Build-gate del SSOT:** `build-specs-status.mjs` debe correr en `npm run build` y **fallar** ante drift status ↔ `ESTADO-SPECS.md`.
- **Deploy gateado:** `deploy_ionos.py` dry-run → confirmación del usuario → `--confirm`. Verifica `app.datanestiq.com/` = 200
  y `/admin/` = 302 tras subir, y que el `.htaccess` sigue siendo `FilesMatch` (no deny total).
- **Sin dispersión:** la nueva vista va como **pestaña "Observabilidad"** dentro de `/admin/`, junto a la analítica de la 016
  (idealmente una sección "Analítica" con pestañas Conversión / Ops), no un panel suelto.

## Evidencia que reauditaré en vivo (para tu reporte)
1. Build-gate del SSOT: introduce una discrepancia de prueba status↔ESTADO-SPECS → el build **falla**; corrígela → verde.
2. Percentiles correctos con datos; con < 10 éxitos → "datos insuficientes" (no p95 engañoso).
3. Endpoints `ops_telemetry`/`ops_specs_status` tras `auth.php`; agregados sin PII (grep emails/teléfonos = 0).
4. Sitio sano tras deploy (`app.datanestiq.com/` = 200, `/admin/` = 302, WP intacto).
5. `ESTADO-SPECS`/`Fases` fila 017 → ✅ con fecha real.

---
**Nota:** procede con el build de la 017. El deploy final es dry-run → `--confirm` del usuario. Al terminar,
entrégame el reporte con la evidencia de los 5 puntos y reaudito en vivo.
