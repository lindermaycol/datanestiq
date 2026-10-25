# Spec 018 — diseño APROBADO (3 correcciones OK) · procede a la fase de BUILD

Reaudité el fix. **Las 3 correcciones quedaron bien:**
- **C1 Coexistencia:** `interactions` (014) sigue siendo la fuente **confiable** del journey del lead; `interaction_events`
  (018) es comportamiento best-effort agregado; el `/admin/` **lee el journey del lead desde `interactions`** (no de
  beacons) → leads históricos intactos. ✅
- **C2 Retención:** `DELETE FROM interaction_events WHERE created_at < datetime('now','-180 days')` idempotente, en plan.md/tech_debt. ✅
- **C3 PII:** redacción solo en texto libre (`search_query`/`chatbot_step`), categóricos exentos, **fail-closed** si falla; typo corregido. ✅

**Luz verde para construir**, siguiendo el `plan.md` con sus gates humanos.

## Recordatorios para el build (los que aprendimos a cuidar)
- **§5 0-LLM intacto:** los beacons **jamás** llaman a un LLM; `sendBeacon`/fetch sin `await`, **fallo silencioso**,
  **no deben degradar la UX ni la latencia** del flujo guiado.
- **NO tocar el journey de la 016/014:** la captura confiable (payload `save_wizard` → `interactions`) y la vista del
  panel **quedan igual**. `interaction_events` solo AÑADE telemetría agregada.
- **§6 PII:** redacción antes de insertar en texto libre; `interaction_events` en `secure_leads/` (privado). El
  endpoint `track_event.php` es **write-only, GET bloqueado** — y **NO expongas lecturas públicas** (recuerda la fuga
  de `/api/specs-status.json` de la 017: nada de la analítica de comportamiento se sirve público).
- **§2:** agregados con muestra suficiente; con poco → "datos insuficientes", sin inventar.
- **Migración DB viva:** backup `crm.sqlite.bak` + `CREATE TABLE IF NOT EXISTS` idempotente + `COUNT` de `leads`
  pre/post (sin pérdida). Deploy gateado dry-run → `--confirm`; `.htaccess` sigue `FilesMatch`.
- **Build-gate antidrift:** `specsStatus.json` fila 018 debe coincidir con `ESTADO-SPECS.md` (el build falla si no).

## Evidencia que reauditaré EN VIVO (para tu reporte)
1. **016 intacto:** el journey de un lead sigue viéndose completo en `/admin/` (leído de `interactions`, no de beacons);
   leads históricos sin cambios.
2. **Beacons no-bloqueantes:** el flujo guiado responde igual de rápido; si `track_event.php` falla, la UX no se rompe
   (simula un fallo). **Cero llamadas a LLM** desde los beacons (Network).
3. **`track_event.php` write-only:** un `GET` → rechazado; el endpoint **no** tiene lectura pública; `interaction_events`
   se puebla con eventos reales (un `SELECT` de muestra) y el texto libre queda **redactado**.
4. **Sin fuga pública:** ninguna URL pública expone la analítica de comportamiento (verifico como en la 017).
5. **Sitio sano:** `app.datanestiq.com/` = 200, `/admin/` = 302, WordPress intacto; migración sin pérdida de leads.
6. **Panel:** pestaña "Comportamiento" tras auth con popularidad/drop-off reales (o "datos insuficientes").
7. `ESTADO-SPECS`/`specsStatus.json`/`Fases` fila 018 → ✅ con fecha real; build-gate verde.

---
**Nota:** procede con el build de la 018. El deploy final es dry-run → `--confirm` del usuario, con backup de la DB
viva primero. Al terminar, entrégame el reporte con la evidencia de los 7 puntos y reaudito en vivo.
