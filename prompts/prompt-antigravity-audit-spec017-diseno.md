# Auditoría diseño Spec 017 — APROBADO con 2 precisiones (SSOT + SQL de percentiles)

El diseño está bien: reutiliza `chat_metrics` (no duplica la 016), guards de honestidad (§2), cero PII (§6),
todo tras `auth.php`, sin APM ni auto-acciones. Apruebo para build tras 2 correcciones.

## 🔴 Precisión 1 — resuelve la DOBLE fuente de verdad del estado de specs
Introduces `src/data/specsStatus.json` como "SSOT", pero **`ESTADO-SPECS.md` ya es el tracker de estado
mantenido a mano** (se actualiza en cada doc-sync). Dos archivos con el estado de las specs = **drift garantizado**
(alguien actualiza uno y no el otro). Tu Risk 2 lo admite pero "validación estricta build-time" es ambiguo.
**Define la relación concreta (elige y decláralo en `data-model.md`):**
- **Preferida:** `specsStatus.json` es la SSOT de los **campos estructurados** (id/status/phase/openTechDebt) que
  consume el dashboard, y `build-specs-status.mjs` **valida en build-time que el `status` del JSON sea consistente
  con `ESTADO-SPECS.md`** (falla el build si divergen). `ESTADO-SPECS.md` sigue siendo la narrativa rica; el JSON
  no la reemplaza, pero **no pueden contradecirse** (build-gate).
- **Alternativa:** genera una de las dos desde la otra (una sola edición humana). Pero **no** dejes dos archivos
  editados a mano por separado. El objetivo: **una sola verdad del `status`; el drift rompe el build, no pasa silencioso.**

## 🟠 Precisión 2 — el SQL de p50/p95 tiene un bug de población + borde NULL
En Consulta 2, el `OFFSET (COUNT(cm.id) * 50/100)` usa el `COUNT` del grupo **completo** (éxitos + fallos), pero
la subconsulta filtra `success = 1`. Poblaciones distintas → el offset puede **exceder** las filas `success=1` y
devolver **NULL** (o un percentil incorrecto). Además, con integer-division y muestras chicas, p95 ≈ max.
- **Fix:** calcula el offset a partir del **COUNT de la MISMA población filtrada** (`success=1` de ese backend), o
  —mejor— usa **funciones de ventana** (SQLite 3.25+: `PERCENT_RANK()`/`NTILE()`/`ROW_NUMBER()`) para percentiles
  correctos. Maneja el borde (sin filas → sin percentil).
- **Honestidad (ya en tu Risk 1):** mantén el **guard de muestra mínima** (< N) → muestra "datos insuficientes para
  tendencia/percentiles", nunca un p95 engañoso con 3 requests. Mismo criterio que el `learn` de la 016.

## OK tal como está
- Reuso de `chat_metrics` (sin tablas nuevas) ✅; Consultas 1 y 3 (SLA 30d, tendencia 7d) correctas ✅.
- Pilar 2 con JSON estructurado (evita parsear markdown frágil) — buena idea, solo falta cerrar la SSOT (Precisión 1) ✅.
- CONSTRAINTS §2/§6, tras auth, Vanilla, sin APM/auto-remediación ✅. OUT-OF-SCOPE correcto (016/hardware/auto-acciones) ✅.

## Siguiente paso
1. Corrige `data-model.md` (P1 SSOT + P2 percentiles) y `plan.md`/`tech_debt.md` si aplica.
2. **Recién entonces pasa a la fase de BUILD** con los gates del `plan.md` (SSOT+validación build-time → endpoints
   `ops_telemetry`/`ops_specs_status` tras auth → UI tab → deploy gateado dry-run → `--confirm`).

---
**Nota:** Claude (Opus 4.8) reauditará el build: una sola verdad del status (build-gate ante drift), percentiles
correctos con guard de muestra mínima, reuso de `chat_metrics`, cero PII, todo tras auth. Corrige el data-model y avanza.
