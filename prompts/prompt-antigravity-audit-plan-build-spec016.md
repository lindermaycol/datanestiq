# Auditoría del plan de BUILD Spec 016 — LUZ VERDE con 4 precisiones

El plan (`planes/Plan de Implementación — Spec 016 (BUILD…)`) es correcto y respeta el diseño auditado
(chat_metrics, endpoints tras auth, UI Vanilla, learn=PR-draft, migración con backup+PRAGMA). **Apruebo el
build** siguiendo las fases, con estas 4 precisiones — dos evitan un "verde pero roto/deshonesto".

## 🔴 Precisión 1 (crítica) — el insert de `chat_metrics` debe ser FAIL-SAFE
`chat.php` va a abrir `crm.sqlite` e insertar métricas en **cada** request. Si ese insert falla (DB locked, path,
disco), **NO puede romper la respuesta del chatbot**. Envuélvelo en `try/catch` que registre el error y **siga**;
la métrica es best-effort (como el `@file_put_contents` existente). **El chat responde SIEMPRE, con o sin métrica.**
- `php -l public/api/chat.php` tras el edit (una comilla suelta ya rompió el endpoint antes).

## 🟠 Precisión 2 (honestidad §2) — sector/rol reales, sin fabricar
Las columnas `sector`/`rol` solo valen si se pueblan de verdad. En `save_wizard.php`:
- Extrae sector/rol del `journey`/`userContext` que envía el chatbot. **Verifica que el payload REALMENTE los trae**
  (el paso `context_inherited` o las selecciones guiadas de Spec 013). Si el payload no los expone de forma fiable,
  **modifica `Chatbot.jsx`** para enviarlos explícitos, o parsea el journey robustamente.
- **Caso texto-libre / leads legados:** si no hay sector/rol, quedan `NULL` → `no_especificado`. **Eso es correcto y
  honesto — NO inventes ni backfillees** sector/rol para los leads preexistentes en prod. La analítica muestra
  `no_especificado` con transparencia. (Query 4 ya usa `COALESCE`.)

## 🟠 Precisión 3 (deploy gateado + DB viva) — dry-run → confirmación del usuario, no `--confirm` unilateral
El plan pone directo `deploy_ionos.py --confirm`. Mantén el flujo gateado del despliegue:
- **Migración primero:** `cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak` (backup) → `PRAGMA table_info`
  idempotente → `ALTER TABLE` con `/usr/bin/php8.2-cli` → verifica que los leads previos **siguen intactos** (COUNT antes/después).
- **Deploy:** `deploy_ionos.py` **dry-run** → muestra el plan → **espera el "sí" explícito del usuario** → `--confirm`.
  No dispares `--confirm` unilateral.
- Recuerda R1/R2 ya resueltos: el `.htaccess` `FilesMatch` no debe volver a caer en `Require all denied` total
  (verifica `app.datanestiq.com/` = 200 tras el deploy).

## 🟡 Precisión 4 (residual + fuente de la vista de mensajes)
- **Residual del enum:** corrige `spec.md` **línea 32** (`no-interesado` con guion → `no_interesado`) en este mismo commit.
- **Vista de mensajes por `session_id`** en el detalle del lead: lee desde la ubicación **segura** (`secure_leads/`
  chat logs, tras `auth.php`). **No** crees un endpoint público que exponga contenido de chat.

## OK tal como está
- 3 endpoints de analítica tras `auth.php`, agregados sin PII; UI Vanilla (sin libs de gráficos); `learn_prompt_optimizer.mjs`
  offline free-tier PR-draft-only; migración idempotente con backup. ✅
- Instrumentar **solo** el texto-libre (`chat.php`); el **flujo guiado sigue 0-LLM** (§5) — no lo toques.

## Verificación (evidencia real que reauditaré en vivo)
1. Chatbot responde aunque falle el insert de métricas (simula un fallo → el chat sigue).
2. `chat_metrics` se puebla con el `backend_used` REAL del failover + `latency_ms`.
3. Funnel con el enum real; sector/rol poblados **de verdad** (no todo `no_especificado` si vino guiado); leads previos intactos tras la migración (COUNT igual + columnas nuevas).
4. Endpoints de analítica tras auth, agregados sin PII (grep de emails/teléfonos = 0 en las respuestas).
5. `app.datanestiq.com/` = 200 y `/admin/` = 302 tras el deploy; WP intacto.
6. `learn` = PR draft (no toca `chat.php` en prod). `npm run build` verde; `docs:sync` (wiki) OK; `ESTADO-SPECS` fila 016 → ✅.

---
**Nota:** Claude (Opus 4.8) reauditará en vivo: chatbot resiliente a fallo de métricas, `chat_metrics` con backend real,
sector/rol honestos, migración sin pérdida de datos, deploy gateado con el sitio en 200, y `learn` como PR draft.
Procede con el build respetando los gates humanos del deploy.
