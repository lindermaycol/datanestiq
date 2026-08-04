# Auditoría plan de BUILD Spec 018 — LUZ VERDE con 4 precisiones

El plan es fiel al diseño corregido (coexistencia con 016/014, `track_event.php` write-only con 405 en GET, redacción
PII solo en texto libre, beacons no-bloqueantes, retención 180d, agregados tras auth con guard §2). **Apruebo el
build** con estas 4 precisiones — la primera es de seguridad (endpoint público de escritura).

## 🔴 Precisión 1 (seguridad) — anti-abuso del endpoint público write-only
`track_event.php` es **público y de escritura**. Sin protección, **cualquiera puede hacer POST** y **floodear
`interaction_events`** → contamina la analítica (popularidad falsa), infla la DB (la retención de 180d no frena el
flood de corto plazo) y es un vector de DoS de escritura. **Añade un guard básico:**
- **Rate-limit** por IP/sesión (reutiliza el patrón de `chat.php`/`auth.php`, p. ej. N eventos/min) **y/o** un chequeo
  de **mismo-origen** (Origin/Referer de `app.datanestiq.com`) — descarta POSTs externos.
- Mantén el fallo **silencioso** (un rechazo por rate-limit no rompe la UX del beacon).
Es un endpoint que escribe a la BD del CRM; no puede quedar totalmente abierto. (Acabamos de tener 2 incidentes de
seguridad en las specs de observabilidad — no repitamos el patrón.)

## 🟠 Precisión 2 (DB viva) — migración con backup + COUNT, gateada
Añadir `interaction_events` re-corre `init_crm_db.php` en la **DB de producción** (con leads reales). Aunque
`CREATE TABLE IF NOT EXISTS` es seguro, sigue el mismo protocolo que la 016:
- **Backup** `cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak` antes.
- **`COUNT(*)` de `leads` pre/post** → prueba de cero pérdida.
- Migración remota con `/usr/bin/php8.2-cli`; deploy **dry-run → `--confirm`** del usuario (no unilateral).

## 🟠 Precisión 3 (verificación) — reincorpora los checks que faltan
Tu Verification Plan está delgado para un deploy en vivo. Agrega:
- **016 intacto:** el journey de un lead sigue viéndose **completo** en `/admin/` (leído de `interactions`, no de beacons).
- **Sin fuga pública:** ninguna URL pública expone la analítica de comportamiento (curl como en la 017; el endpoint es
  write-only, sin lecturas públicas).
- **Sitio sano post-deploy:** `app.datanestiq.com/` = 200, `/admin/` = 302, WordPress `datanestiq.com` = 200.
- **0-LLM / UX:** los beacons van a `track_event.php`, **no** a `chat.php`/LLM (Network); el flujo guiado responde igual
  de rápido; si `track_event.php` falla, la UX no se rompe (simúlalo).
- **Build-gate:** `specsStatus.json` fila 018 coincide con `ESTADO-SPECS.md` (build falla si no).

## 🟡 Precisión 4 (menores)
- **UI Vanilla:** la pestaña "Comportamiento" **sin librerías pesadas de gráficos** (consistente con 016/017 — barras/tablas Vanilla).
- **Auth real:** confirma que `ops_behavior_analytics` queda **tras el mismo `auth.php`** que el resto de acciones del panel
  (si `requireAuth()` no es el mecanismo real, usa el include de `auth.php` que ya protege `admin/api.php`).
- **Retención acoplada al view:** correr el `DELETE` de 180d en cada invocación del endpoint de analítica funciona (idempotente),
  pero decláralo; si prefieres, un pase programado es más limpio. No bloqueante.

## OK tal como está
- Coexistencia con `interactions` (016/014 intactas) ✅; `track_event.php` POST-only / 405 GET / try-catch silencioso ✅.
- Redacción PII solo en `search_query`/`chatbot_step` ✅; beacons `sendBeacon`/fetch no-bloqueante ✅; guard §2 (<20 → insuficiente) ✅.

## Siguiente paso
Implementa las 5 fases con las precisiones. `php -l` tras cada PHP. Deploy gateado. Al terminar, entrégame el reporte con
la evidencia de los checks (incl. anti-abuso probado, 016 intacto, sin fuga pública, sitio sano) y reaudito en vivo.

---
**Nota:** Claude (Opus 4.8) reauditará EN VIVO: endpoint write-only con anti-abuso (flood rechazado), journey de la 016
intacto, cero fuga pública de la analítica de comportamiento, 0-LLM/UX sin degradar, migración sin pérdida de leads, y
sitio sano. El deploy es dry-run → `--confirm` del usuario, con backup de la DB.
