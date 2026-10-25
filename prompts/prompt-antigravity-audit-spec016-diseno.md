# Auditoría del diseño Spec 016 — APROBADO con 3 correcciones al data-model antes de build

El diseño (`specs/016-analitica-conversion-loop/{spec,data-model,plan,tech_debt}.md`) está **bien estructurado
y fiel**: 5 bloques, §6/§5/§2 explícitas, `chat_metrics` en `crm.sqlite` (secure_leads), loop `learn` offline
PR-draft-only, OUT-OF-SCOPE correcto (017/GraphRAG/auto-apply). **Aprobado para pasar a build** tras corregir
3 cosas del **data-model** que verifiqué contra el schema REAL de la Spec 014 (ya en producción).

## 🔴 Corrección 1 — Enum de estados NO coincide con el real
El schema real (`init_crm_db.php` + `admin/api.php` línea 133) usa exactamente:
`nuevo, contactado, cita_solicitada, ganado, perdido, no_interesado` (**underscores**).
El data-model (Query 1) y el spec.md (WHAT) usan `'cita/diagnóstico solicitado'` y `'no-interesado'` — **no existen**
→ caerían en `ELSE 7` y el funnel/orden saldría mal.
- **Fix:** alinea TODAS las referencias de estado (spec.md WHAT + data-model Query 1 `CASE`) al enum real exacto
  (`cita_solicitada`, `no_interesado`, etc.). Sin slashes ni guiones.

## 🔴 Corrección 2 — "Conversión por Sector y Rol" NO es consultable desde `leads`
El WHAT promete desglose **por Sector y Rol**, pero la tabla `leads` **no tiene columnas `sector`/`rol`**
(solo `session_id, organizacion, status, reto, stack, ...`). Tu Query 4 agrupa por `l.organizacion` (nombre de
empresa) — **eso no es sector/rol**. El sector/rol/chips viven en las filas de `interactions` (los pasos del
`journey` que `save_wizard.php` inserta), no en `leads`.
- **Resuélvelo en el data-model (elige y decláralo):**
  - **(a) Recomendada:** añade columnas `sector` y `rol` a `leads` y que `save_wizard.php` las escriba desde el
    `journey`/contexto al crear el lead. Query 4 agrupa por `l.sector`, `l.rol`. Limpio para analítica, pero
    **requiere migración `ALTER TABLE leads ADD COLUMN` en la DB viva** (ver Corrección 3).
  - **(b) Alternativa:** deriva sector/rol de `interactions` (parseando los pasos del journey) y haz el join.
    Sin migración de columnas, pero queries más complejas.
- **No** dejes Query 4 agrupando por `organizacion` haciéndola pasar por "sector/rol" — eso sería deshonesto (§2).

## 🔴 Corrección 3 — Falta la ruta de migración de la DB de producción (ya existe con datos reales)
La Spec 014 está **live**: `crm.sqlite` ya existe en el servidor con leads reales. El `plan.md` dice "crear la
tabla en `init_crm_db.php`" pero no explica cómo aplicarlo a la DB **existente**.
- **Declara en `plan.md`:** re-correr `init_crm_db.php` en prod es seguro para `chat_metrics` (usa `CREATE TABLE
  IF NOT EXISTS`, idempotente, no toca datos). **Pero** si eliges la opción (a) de la Corrección 2, el `ALTER
  TABLE leads ADD COLUMN sector/rol` **sí modifica la tabla con datos reales** → hazlo idempotente-guarded
  (verifica si la columna existe antes de añadir), con **backup del `crm.sqlite` antes**, y como paso remoto
  gateado (dry-run/confirmación), igual que el resto del despliegue. Sin pérdida de datos.

## OK tal como está (no cambiar)
- `chat_metrics` (session_id/backend_used/latency_ms/success/tokens_est/created_at + índices) en `crm.sqlite` ✅.
- Queries 2, 3, 5 coinciden con el schema real (`appointments.status='cancelada'`, `status_history.old/new_status`, `chat_metrics`) ✅.
- Constraints §6/§5/§2 + free-tier + PR-draft-only + admin-auth ✅. Loop `learn` offline con JSON estructurado → PR draft ✅.
- OUT-OF-SCOPE (017 ops / GraphRAG / auto-apply) ✅.

## Siguiente paso
1. Corrige el data-model (C1, C2) y el plan (C3) en los `specs/016/*.md`. Doc-sync menor si aplica.
2. **Recién entonces pasa a la fase de BUILD** siguiendo tu `plan.md` (5 fases) con sus **gates humanos**:
   instrumentación `chat.php` → endpoints agregados (auth) → UI del funnel → script offline PR-draft → verificación
   (`npm run build` + `curl` + deploy gateado a `app.datanestiq.com`, con el paso de migración de la DB viva).
3. En la instrumentación, captura el backend que **realmente respondió** en el failover (Groq→DashScope→Gemini) + su latencia.

---
**Nota:** Claude (Opus 4.8) reauditará el build: enum de estados real en el funnel, sector/rol consultados de la
fuente correcta (no de `organizacion`), migración de la DB viva sin pérdida de datos, 0-LLM/§6 intactos, y el
auto-tuning solo como PR draft. Corrige el data-model y avanza a build con los gates.
