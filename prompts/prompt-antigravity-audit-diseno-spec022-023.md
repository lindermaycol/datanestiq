# Auditoría DISEÑO Spec 022 + 023 — APROBADAS para BUILD con correcciones (2 de fondo en la 023)

Auditados los 8 artefactos + el doc-sync. **Coordinación bien hecha:** una sola actualización consistente de
`ESTADO-SPECS`/`specsStatus.json`/`Fases.md` con ambas filas, carpetas separadas. Apruebo el build **secuencial 022 → 023**
con las correcciones de abajo — la 023 tiene **2 de fondo** (fórmula de ahorro §2 + esquema de la agenda).

## ✅ Spec 022 (File-Free) — sólida, 1 corrección menor
Bien: `chat_raw` **privada sin endpoint** (regla §6 explícita) ✅; `leads_extracted` dedup por `session_id` **o** `email`
✅; retiro de escrituras + `migrate_file_free_history.php` con archivo a `_archive/` + `COUNT(*)` pre/post ✅; plan de
deploy **con `npm run build` antes** (caveat de dist/ incorporado) + verificación "cero archivos nuevos" ✅; retención 180d ✅.

**🟡 Corrección — FK inválida en `conversations`:** `FOREIGN KEY(session_id) REFERENCES chat_metrics(session_id)`
(data-model 1.1). `chat_metrics.session_id` **no es único** (hay una fila por llamada LLM), y `conversations` puede tener
sesiones sin fila en `chat_metrics` → FK semánticamente inválida (rompe si `PRAGMA foreign_keys=ON`). **Quita la FK**;
`session_id` es una clave de correlación blanda (como en `demand_signals`/`interaction_events`, que no la declaran FK).
- Nota (no bug): `conversations` solo captura intercambios que pasan por `chat.php` (texto libre → LLM); las
  interacciones **0-LLM** (guiado/FAQ) no llegan ahí — igual que el viejo `chat_logs.jsonl`. Está bien (el journey ya
  cubre lo 0-LLM), pero **declara** en el spec que el visor de conversaciones muestra los intercambios con LLM, no el 100%.

## 🟠 Spec 023 (Panel de Negocio) — 2 correcciones de fondo + lo demás OK
Bien: retiro de contenido dev de "Ops" (tabla de specs/build/ESLint/.htaccess → fuera del panel, SSOT intacto) ✅;
backfill sector/rol mapeando `demand_signals.role → leads.rol` correctamente ✅; gráficos **ligeros** (embudo CSS, dona
SVG `stroke-dasharray`, sin librerías) ✅; guard §2 en `ai_efficiency` ✅.

### 🔴 Corrección 1 (§2, la importante) — la fórmula de "ahorro estimado" es incorrecta y deshonesta
`data-model 1.1`:
```php
$cost_per_llm_call = AVG(latency_ms)/1000 * 0.000015;   // ← latencia ≠ costo
```
**La latencia no tiene NADA que ver con el costo.** Esto fabrica un número de dólares a partir de milisegundos — viola §2
aunque lleve `[EST]`. Además, los proveedores actuales son **free-tier (~$0 real)**, así que el "ahorro" de hoy es ~$0.
- **Fix (elige uno honesto):**
  - **(a) preferido — sin $:** muestra **"llamadas al LLM evitadas"** (un conteo: `faq+cita+guiado`) y el **% 0-LLM**.
    Es inequívoco y no requiere inventar precio. Es la métrica de eficiencia real.
  - **(b) si quieren $:** `$ [EST] = llamadas_evitadas × (avg_total_tokens_por_llamada / 1e6 × PRECIO_REF_POR_1M)`,
    usando **tokens reales** (`chat_metrics`) y un **precio de referencia declarado** ("a tarifas de $X/1M tokens; los
    proveedores actuales son free-tier"). Nunca latencia.

### 🔴 Corrección 2 (esquema real) — la query de la agenda usa columnas que NO existen
`data-model 1.2` selecciona `a.service_slug`, `a.appointment_date`, `a.start_time`, `a.end_time` y filtra
`status IN ('pending','confirmed')`. **El esquema real de `appointments` (Spec 015, `init_crm_db.php`) es:**
`id, lead_id, session_id, requested_date, duration_minutes, type, status` — y el estado real es **`'solicitada'`**
(no 'pending'/'confirmed'). La query **fallaría** (columnas inexistentes). 
- **Fix:** alinéala al esquema real: `requested_date` (fecha/hora), `duration_minutes`, `type`, `status`; y usa el enum
  real de estado. Verifica los valores de `status` en la DB antes de fijar el filtro (¿'solicitada'/'confirmada'/
  'cancelada'?). Las **acciones** confirmar/reagendar/cancelar deben escribir esos estados reales vía `update_appointment`.

### 🟡 Menores
- El endpoint `ai_efficiency` agrupa por `route`; confirma que los eventos `intent_routing` **tengan** `route` en
  `event_value` (los de rondas viejas quizá no) → los sin `route` cuéntalos como `llm`/desconocido, no los descartes silenciosamente.
- Aplica el guard §2 (muestra mínima) también a **cada gráfico** (embudo/dona/tendencias), no solo a los agregados.

## Siguiente paso
1. Aplica la corrección menor de 022 y las 2 de fondo + menores de 023.
2. **Build secuencial: 022 → 023.** Para AMBAS: `php -l`, migración idempotente con backup + `COUNT(*)` pre/post,
   **`npm run build` ANTES de `deploy_ionos.py --confirm`** (el PHP se sirve desde `dist/`), limpiar opcache, y
   **verificar contra el endpoint real** (no solo local).
3. Entrégame el reporte de cada una y **reaudito en vivo**: 022 (cero archivos nuevos, `chat_raw` sin endpoint, visores
   200), 023 (0-LLM honesto sin $-latencia, agenda real con acciones, Ops sin contenido dev, gráficos con guard §2).

---
**Nota:** Claude (Opus 4.8) auditará en vivo. 022 está casi lista (solo quita la FK inválida). En 023, lo bloqueante son
**la fórmula de ahorro** (basar en llamadas evitadas / tokens reales, nunca latencia — §2) y **la query de la agenda**
(usa el esquema real de `appointments`, no columnas inventadas). Build secuencial 022→023, recompilando antes de desplegar.
