# Spec 016 (DISEÑO) — Analítica de Conversión + Loop `chat→lead→learn`

**Esta tarea es de DISEÑO SDD, NO de implementación.** Escribe los artefactos de la nueva **Spec 016**
(`specs/016-analitica-conversion-loop/`) siguiendo los **5 bloques** de
`spec-driven-development-cinco-bloques` (WHY / WHAT / CONSTRAINTS / OUT-OF-SCOPE / TASKS) + `data-model.md`
+ `plan.md`. **Entrégalos para que Claude los audite ANTES de implementar.** No toques código de runtime todavía.

## Contexto (verificado en el código)
- El CRM (Spec 014) ya tiene el modelo: tablas `leads` (con `session_id`, `journey`, `status`), `interactions`,
  `status_history`, `appointments`. `admin/api.php` ya hace CRUD + filtro por status + detalle por lead.
- `chat.php > logInteraction()` ya loguea a `chat_logs.jsonl` (redactado), `secure_leads/chat_raw.jsonl`
  (raw, seguro) y `alerts.jsonl`, con `session_id`. **Falta: `backend_used` + `latency_ms` + outcome por request.**
- El deploy 014/015 está **live** en `app.datanestiq.com` (esta pieza ya opera en prod).

## WHAT (alcance de la Spec 016 — 3 piezas)
1. **Instrumentación de `chat.php`:** por cada request de texto libre, registrar `{session_id, backend_used
   (groq|dashscope|gemini), latency_ms, success, ts}` (además de lo ya logueado). Persistir en una tabla
   nueva del **mismo `crm.sqlite`** (ej. `chat_metrics`) o en jsonl seguro — decídelo en `data-model.md`.
   **Sin PII nueva** (reusa la redacción existente).
2. **Analítica de conversión en el panel CRM (absorbe la capa analítica de Specs 013/014/015):**
   - **Funnel agregado:** COUNT de leads por `status`, tasas lead→cita→cliente (join `leads`↔`appointments.status`),
     tiempo en cada etapa (de `status_history`).
   - **Vista de journey por lead:** enlazar el `journey` del lead (chips/rol/sector/búsqueda de Spec 013) + su
     sesión de chat (`chat_logs` por `session_id`) → ver el camino que convirtió.
   - **Qué convierte:** desglose por sector/rol/journey.
   - Endpoints nuevos en `admin/api.php` (queries agregadas) + vista en el panel `/admin/`. **Detrás del auth admin.**
3. **Loop `learn` (offline, 0 tokens de Claude):** un script (corre con las APIs free-tier Groq/Gemini o
   determinista, **nunca Claude comercial en runtime**) que semanalmente analiza qué patrones del `SYSTEM_PROMPT`
   y qué journeys correlacionan con conversión, y **propone edits al `SYSTEM_PROMPT` como PR draft**
   (estilo `content-pr.yml`) — **NUNCA auto-aplicado**; Claude audita el PR. El chatbot es safety-relevant.

## CONSTRAINTS (heredadas de la Constitución — el spec debe declararlas explícitas)
- **§6 PII:** cero canales nuevos de PII; la analítica usa data ya capturada; `chat_metrics`/logs viven en
  `secure_leads/` (fuera del webroot, protegido). Los agregados del funnel **no** exponen PII cruda.
- **§5 0-LLM:** el flujo guiado del chatbot **sigue siendo 0-LLM**; la instrumentación solo loguea; la
  destilación es **offline**, no runtime.
- **§2 Honestidad:** el funnel muestra **datos reales** (sin métricas fabricadas; `[EST]` no aplica — es data interna real).
- **Auto-tuning del `SYSTEM_PROMPT` = PR draft + auditoría de Claude, jamás auto-merge.** Un cambio al prompt
  del chatbot pasa por `php -l` y por mí.
- **Solo APIs free-tier en runtime/scripts; Claude solo audita** (`AGENTS.md`).
- Panel de analítica **detrás del auth admin** existente (password-only, ver `tech_debt.md` 014).

## OUT-OF-SCOPE (declararlo en el spec)
- **NO** el panel de observabilidad de ops (será **Spec 017**, aparte — salud de backends/perf/status de specs).
- **NO** GraphRAG, MCP endpoint, buscador sobre blog, router ampliado (backlog).
- **NO** auto-aplicar cambios al `SYSTEM_PROMPT`. **NO** exponer PII en el funnel.

## Entregables de esta tarea (solo diseño)
- `specs/016-analitica-conversion-loop/spec.md` (5 bloques), `data-model.md` (tabla `chat_metrics` + queries
  del funnel + join sesión↔lead↔conversión), `plan.md` (fases + gates), `tech_debt.md` (vacío o riesgos previstos).
- Doc-sync: fila 016 en `planes/ESTADO-SPECS.md` (en 🟠 "diseñada, pendiente de implementación") + `Fases.md`.
- **NO implementes runtime.** Entrega los artefactos y **espera la auditoría de Claude** antes de la fase de build.

---
**Nota:** Claude (Opus 4.8) auditará el **spec.md + data-model + plan**: que respete 0-LLM/§6 PII/honestidad, que
la analítica reuse el modelo del CRM existente (no duplique), que el auto-tuning sea PR-draft-only, y que el
alcance no invada la Spec 017 (ops). Recién tras mi visto bueno se pasa a implementar.
