# Auditoría diseño Spec 018 — APROBADO con 3 correcciones (no degradar el journey live de la 016)

El diseño está bien: `interaction_events` limpio, beacons **fire-and-forget** (`sendBeacon`, no bloquea, fallo
silencioso), **redacción PII** en `track_event.php`, anonimización por `session_id`, **sin trackers de terceros**,
guards de honestidad §2, retención 180d, y consistencia `specsStatus.json` ↔ `ESTADO-SPECS.md` (build-gate PASSED).
Apruebo para build tras 3 correcciones del `data-model.md`.

## 🔴 Corrección 1 (la importante) — NO refactorices el journey LIVE de la 016 a beacons best-effort
El data-model propone "desacoplar" la 016: que el journey del lead **ya no se almacene** y se **derive** de
`interaction_events` por `session_id` (Sección 2). **Eso degrada una feature que ya está en producción y funciona.**
Tres problemas concretos:
1. **Leads históricos rotos:** los leads capturados **antes** de la 018 **no tienen** filas en `interaction_events`
   → su "journey" en el panel quedaría **vacío**. Pérdida de continuidad de datos en prod.
2. **Fiabilidad degradada:** hoy el journey del lead viene del **payload de confirmación** (`save_wizard`), que es
   **confiable**. Los beacons de la 018 son **best-effort** (uno puede caerse en silencio) → derivar el journey del
   lead de eventos best-effort lo vuelve **incompleto/no confiable**.
3. **Ignora la tabla `interactions` (Spec 014) que YA existe** — donde hoy se guardan los pasos del journey del lead.
   El data-model ni la menciona; la dejaría huérfana o en conflicto.

**Fix (coexistencia, no reemplazo):**
- `interaction_events` (018) = telemetría **agregada de comportamiento** de **todos** los usuarios (best-effort OK
  para agregados: unos pocos eventos perdidos no sesgan un ranking).
- **La 016/014 conservan su captura confiable** del journey individual del lead (`interactions` / payload de
  confirmación). **No** cambies eso.
- Se acepta **solapamiento menor** (un clic puede estar en ambos): es preferible a romper el journey confiable y los
  datos históricos. Declara explícitamente en el data-model la **relación entre `interactions` (014, journey confiable
  del lead) e `interaction_events` (018, comportamiento agregado)** — coexisten, no se reemplazan.

## 🟡 Corrección 2 — especifica CÓMO se aplica la retención de 180 días
El `tech_debt` dice "política de retención de 180 días" pero no el mecanismo. Defínelo: un script de limpieza
(`DELETE FROM interaction_events WHERE created_at < datetime('now','-180 days')`) corrido periódicamente (cron/manual),
idempotente. Sin eso, la tabla crece sin límite. Anótalo en `plan.md`.

## 🟡 Corrección 3 — robustez de la redacción PII (+ typo)
- Corrige el typo: *"**Gato** de seguridad"* → "Gate/Guardarraíl de seguridad".
- Confirma en el diseño que la redacción se aplica **antes de insertar** a `event_value` **solo en los tipos de
  texto libre** (`search_query`, `chatbot_step` con mensaje), y que si la redacción falla → se guarda vacío/ignora
  (fail-safe, ya lo dice). Los eventos categóricos (`chip_click` = 'finanzas') no necesitan redacción.

## OK tal como está (no cambiar)
- `interaction_events` + índices ✅; `sendBeacon` fire-and-forget (§5: **0-LLM intacto**, beacons ≠ LLM) ✅.
- Endpoint **write-only** con GET bloqueado ✅; en `secure_leads/` privado ✅; sin trackers de terceros (§6) ✅.
- Queries de popularidad/drop-off del wizard ✅; migración idempotente con backup `.bak` (F1) ✅.
- OUT-OF-SCOPE (A/B, fingerprinting, personalización runtime) ✅.

## Siguiente paso
1. Corrige el `data-model.md` (C1 coexistencia + relación con `interactions`) y `plan.md`/`tech_debt.md` (C2 retención, C3 redacción).
2. **Recién entonces pasa a build** con los gates del `plan.md` (migración con backup → `track_event.php` write-only →
   beacons en las islas → pestaña Comportamiento tras auth → deploy gateado dry-run → `--confirm`).

---
**Nota:** Claude (Opus 4.8) reauditará el build EN VIVO: que **el journey del lead de la 016 siga confiable e intacto**
(no vaciado ni dependiente de beacons), beacons que no degraden la UX ni el 0-LLM, PII redactada, endpoint write-only
(sin lecturas públicas — recuerda la fuga de la 017), y agregados honestos con muestra escasa. Corrige y avanza.
