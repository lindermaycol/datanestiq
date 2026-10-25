# Spec 016 — Correcciones OK · LUZ VERDE para BUILD (con 1 residual + 1 nota de Fase 1)

Reaudité los artefactos: las 3 correcciones están bien aplicadas. **Apruebo el diseño y doy luz verde para
iniciar la FASE 1 de BUILD** siguiendo el `plan.md` con sus gates humanos. Antes, cierra 1 residual menor.

## ✅ Correcciones verificadas
- **C1 enum:** `cita_solicitada`/`no_interesado` (underscores reales) en `data-model.md` Query 1 (CASE) y en `spec.md` WHAT. ✅
- **C2 sector/rol:** `ALTER TABLE leads ADD COLUMN sector/rol` + Query 4 agrupa por `l.sector, l.rol` con `COALESCE(...,'no_especificado')` (ya no por `organizacion`). ✅
- **C3 migración:** idempotente vía `PRAGMA table_info(leads)` antes del `ALTER`, backup `cp crm.sqlite crm.sqlite.bak` antes de migrar en prod, ejecución con `/usr/bin/php8.2-cli`, sin pérdida de datos. ✅

## 🟡 Residual (corrige en el mismo commit del build)
- `spec.md` **línea 32** (Componente 3, loop learn) todavía dice `perdido/no-interesado` con **guion**. Cámbialo a
  `no_interesado` (underscore) para que TODAS las referencias de estado sean consistentes con el enum real.

## 🔵 Nota obligatoria para FASE 1 (para que C2 no quede vacío)
Las columnas `sector`/`rol` solo sirven si `save_wizard.php` las **puebla de verdad**. En la Fase 1:
- Verifica que el payload del chatbot (`confirmLead` → `journey`) **realmente trae** el sector y el rol (vienen del
  `userContext`/`context_inherited` de la Spec 013 y de las selecciones guiadas). Extrae sector/rol de ahí y
  escríbelos en las columnas al crear el lead.
- Si el payload NO expone sector/rol de forma fiable, **decláralo** (no dejes Query 4 devolviendo todo
  `no_especificado` haciéndolo pasar por analítica real — §2 honestidad). Ajusta el chatbot para enviarlos si hace falta.

## Fase de BUILD — procede con los gates del `plan.md`
1. **Fase 1:** `init_crm_db.php` (tabla `chat_metrics` + `PRAGMA table_info` idempotente para sector/rol) +
   `save_wizard.php` (poblar sector/rol) + `chat.php` (capturar `backend_used` real del failover + `latency_ms`).
   `php -l` tras editar cada PHP.
2. **Fase 2:** endpoints agregados en `admin/api.php`, detrás de `auth.php`.
3. **Fase 3:** UI de analítica en `/admin/` (KPI + embudo + desgloses, Vanilla, sin libs pesadas de gráficos).
4. **Fase 4:** `scripts/learn_prompt_optimizer.mjs` — **PR draft only**, free-tier, nunca auto-merge.
5. **Fase 5:** `npm run build` verde → **migración de la DB viva gateada** (backup `.bak` primero) → deploy a
   `app.datanestiq.com` (dry-run → `--confirm` del usuario). Doc-sync fila 016 a ✅.

## Guardarraíles (recordatorio)
- 0-LLM del flujo guiado intacto; §6 (chat_metrics en `secure_leads`, cero PII nueva); §2 (datos reales, sin
  `no_especificado` disfrazado); auto-tuning solo PR draft + auditoría; free-tier only; panel tras auth admin.

---
**Nota:** Claude (Opus 4.8) reauditará el build EN VIVO: `chat_metrics` poblándose con el backend real, funnel con
el enum correcto, sector/rol poblados de verdad (no todo `no_especificado`), migración sin pérdida de datos
(leads previos intactos), 0-LLM/§6 intactos, y el `learn` como PR draft. Los gates humanos del deploy se respetan.
