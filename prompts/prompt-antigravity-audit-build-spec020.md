# Auditoría BUILD Spec 020 — APROBADO con observaciones (ya en vivo) + correcciones A/B/C aparte

Audité el build (task.md/walkthrough) **contra el código real y contra el comportamiento en vivo** en
`app.datanestiq.com` (interceptando los beacons reales del chatbot). El build está **desplegado en producción** y las
precisiones de mi auditoría de plan se honraron. Apruebo con observaciones; las 3 correcciones de datos van en el prompt
`prompt-antigravity-fix-spec020-tracking-ABC.md`.

## ✅ Verificado (código + en vivo)
- **P1 — journey con `interactions`:** `admin/api.php` `lead_journey` une `interaction_events` + `demand_signals` +
  **`interactions` por `lead_id`** + `status_history`, ordenado por `ts`. Exacto a la precisión. ✅
- **Redacción server-side:** `track_event.php:143` aplica `redactPii()` al `demand_signal.query_redacted` antes del
  INSERT; `:103` al genérico. ✅ (pero ver Observación B — cobertura incompleta).
- **Cache multi-corpus:** `worker.js` con `cachedCorpora={}` por firma. ✅ En vivo confirmé **FAQ 0-LLM sin regresión**:
  "datos sensibles a la nube" → `route:faq, faqScore:0.996, resolved:0llm`, **sin `chat.php`**.
- **Emisión no-bloqueante:** `logDemandSignal` fire-and-forget; el `demand_signal` viaja por beacon, **no** por `chat.php`. ✅
- **Clasificación de demanda en vivo:** `matched_service` + `offered` se calculan en cliente y se registran. ✅
- **Endpoints admin + guards §2:** `demand_signals` (N≥20 + top-5 ejemplos), `leakage` (N≥10), `lead_journey`. ✅
- **Doc-sync + build-gate:** `specsStatus.json`/`ESTADO-SPECS.md`/`Fases.md` 020=LIVE, antidrift SUCCESS, build 0 errores. ✅

## 🟠 Observaciones (a atender)

### Obs 1 → correcciones A/B/C (prompt aparte)
La reauditoría en vivo detectó 3 problemas de **calidad de datos** ya documentados en
`prompt-antigravity-fix-spec020-tracking-ABC.md`:
- **A (§2/integridad):** `offered:0` se registra aunque la consulta se **resolvió por FAQ** → Bucket 1 con gaps **falsos**.
- **B (privacidad):** `redactPii` **no** cubre móviles peruanos de **9 dígitos** (`987654321` se guarda crudo).
- **C (segmentación):** la herencia de contexto del chip no queda en el journey; falta `sector`/`role` en `demand_signal`.
Aplícalas.

### Obs 2 (documentar) — el umbral de catálogo cambió a 0.40; falta justificar y es bajo
El build bajó `CATALOG_MATCH_THRESHOLD` de **0.60 (diseño) → 0.40** "empírico" con `eval_demand.mjs` **6/6**. Dos cosas:
- **6 casos es muestra chica** para fijar un umbral; y **0.40 es bajo** → marca `offered=1` con similitud floja (más
  ruido en "fuga", menos en "gap"). Entrégame el **dataset y la matriz** de `eval_demand.mjs` (positivos de negocio +
  negativos tipo "pizzas/repuestos") y documenta el 0.40 en `tech_debt.md`. Con la corrección A (excluir resueltos), el
  umbral importa menos, pero igual quiero ver la evidencia.

### Obs 3 (VERIFICADO ✅) — sin regresión del buscador semántico tras el refactor del worker
El cache multi-corpus tocó `worker.js`, **compartido con `<SemanticSearch>`**. **Reauditado en vivo (2026-08-06):** el
buscador del home indexó el corpus ("IA lista para búsqueda instantánea"), y la consulta "fraude y detección de riesgo
en tiempo real" devolvió **"Encontramos 2 soluciones relevantes"** + beacon `search_query`, **simultáneamente** con la
FAQ 0-LLM del 019 (faqScore 0.996) sobre el mismo worker. **Sin regresión, sin cross-wiring.** Cerrado, no requiere acción.

### Obs 4 (proceso, menor) — desplegado antes de mi reauditoría de build
El build se desplegó a producción (`--confirm --init-crm`) **antes** de que yo auditara el reporte. Salió bien (P1 y
redacción base OK), pero el flujo ideal es build → mi auditoría → deploy gateado. Como ya está en vivo, mi QA en vivo
sirvió de reauditoría — de ahí salieron A/B/C. Mantengamos el orden en la próxima.

### Obs 5 (doc drift, menor)
El `walkthrough.md` recapitula la 019 con umbrales `0.72/0.65` (valores **viejos**; el vivo es 0.65/0.60 tras la
calibración). No afecta runtime; corrige el recap para no confundir la trazabilidad.

## Siguiente paso
1. Aplica A/B/C (prompt aparte) con ALTER TABLE idempotentes + backup + COUNT(*) + `php8.2-cli`, deploy dry-run→confirm.
2. Entrégame el dataset/matriz de `eval_demand.mjs` (Obs 2) y confirma el buscador semántico (Obs 3).
3. Reaudito EN VIVO: Bucket 1 sin gaps falsos, PII de 9 dígitos redactada, `demand_signal` con sector/rol, buscador
   semántico sano, y (cuando el usuario me pase credenciales) revisión visual del panel "Demanda & Journey".

---
**Nota:** Claude (Opus 4.8) reauditó el build **en vivo** (beacons reales). Lo estructural está bien y honra las
precisiones del plan (journey+interactions, redacción, cache multi-corpus, guards §2). Lo pendiente es **calidad de
datos** (A: gaps falsos; B: PII 9 dígitos) — corregir ya, antes de acumular registros sucios — más justificar el umbral
0.40 y confirmar que el buscador semántico no sufrió regresión por el refactor del worker.
