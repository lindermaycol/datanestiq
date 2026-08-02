# Spec 016 BUILD — Plan APROBADO · procede a implementar (las 4 precisiones ya incorporadas)

Reaudité el plan actualizado. **Incorporaste las 4 precisiones correctamente. Luz verde para implementar**
las 5 fases respetando los gates humanos del deploy.

## ✅ Precisiones verificadas en el plan
- **P1 fail-safe:** insert de `chat_metrics` en `try/catch (\Throwable)` → el chatbot responde siempre aunque falle el logging. ✅
- **P2 honestidad:** `Chatbot.jsx` envía `sector`/`rol` explícitos; ausentes → `NULL`/`no_especificado` (COALESCE); sin fabricar leads legados. ✅
- **P3 deploy/DB:** backup `crm.sqlite.bak` + `COUNT(*)` pre/post migración + `deploy_ionos.py` dry-run → confirmación → `--confirm`. ✅
- **P4 seguridad:** vista de mensajes por `session_id` tras `auth.php` (sin endpoint público). ✅

## 🟡 Recordatorio (incluir en el commit del build)
- Corrige el **residual del enum**: `specs/016-analitica-conversion-loop/spec.md` **línea 32** (`no-interesado` con guion → `no_interesado`). Es trivial pero cierra la consistencia.

## Recordatorios de guardarraíles (mientras construyes)
- **Solo** instrumentas el texto-libre (`chat.php`); el **flujo guiado sigue 0-LLM** (§5) — no toques los botones.
- `chat_metrics` + `sector/rol` viven en `secure_leads/crm.sqlite` (§6); los agregados de la API **sin PII cruda**.
- `learn_prompt_optimizer.mjs` = **PR draft only**, free-tier, cero auto-merge.
- `php -l` tras cada edit de PHP (`chat.php`/`save_wizard.php`/`api.php`/`index.php`/`init_crm_db.php`).
- Deploy: el `.htaccess` sigue siendo `FilesMatch` (no `Require all denied` total) — verifica `app.datanestiq.com/` = 200 tras subir.

## Evidencia que reauditaré EN VIVO (para tu reporte final)
1. **Resiliencia:** el chatbot responde aunque el insert de métricas falle (simula un fallo de DB y muéstralo).
2. **`chat_metrics`:** se puebla con el `backend_used` REAL del failover + `latency_ms` (SELECT de muestra).
3. **sector/rol reales:** un lead guiado guarda su sector/rol; un texto-libre queda `no_especificado` (sin inventar).
4. **Migración sin pérdida:** `COUNT(*)` de `leads` igual antes/después; columnas `sector`/`rol` presentes; leads previos intactos.
5. **Analítica sin PII:** `grep` de emails/teléfonos en las respuestas de los 3 endpoints = 0; endpoints tras `auth.php`.
6. **Sitio sano:** `app.datanestiq.com/` = 200, `/admin/` = 302, WordPress en `datanestiq.com` intacto.
7. **learn:** genera PR draft, NO modifica `chat.php` en prod. `npm run build` verde; `ESTADO-SPECS` fila 016 → ✅ con fecha real.

---
**Nota:** procede con la Fase 1. El deploy final es dry-run → `--confirm` del usuario, con backup de la DB viva primero.
Cuando termines, entrégame el reporte con la evidencia de los 7 puntos y reaudito en vivo.
