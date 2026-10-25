# Auditoría diseño Spec 019 — APROBADO con 2 precisiones · luego procede a BUILD

El diseño está **muy bien**: clasificación 100% cliente (Xenova ya cargado, 0 tokens), **doble umbral** (0.72
intención + 0.65 match FAQ) con **fallback a LLM ante cualquier duda**, FAQ **verbatim** de
`personasCorpus.json→objectionResponses` (real, §2), ruteo `cita`→`AppointmentPicker`/`guiado`→flujo guiado,
**instrumentación** `trackEvent('intent_routing', …)` (reusa 018 para medir el % 0-LLM), escape siempre visible, y
la calibración de umbrales reconocida (TD-019-06). Apruebo para build con 2 precisiones.

## 🟠 Precisión 1 (requisito de build, NO solo deuda) — modelo Xenova NO cargado → LLM inmediato, sin bloquear
Hoy el caso "modelo aún no listo" está solo como **TD-019-04 (latencia)**. Debe ser un **criterio de aceptación
explícito**, no deuda diferida: si el usuario escribe texto libre **antes** de que el modelo Xenova esté cargado
(ej. primera interacción, aún descargando), el router **NO debe bloquear** esperando la descarga → **cae al LLM
inmediatamente** (la ruta de hoy). La clasificación 0-LLM es una **optimización oportunista**: si el modelo está
listo, clasifica; si no, LLM al toque. El usuario **nunca** espera una descarga de modelo para recibir respuesta.
- **Añade a `plan.md` un criterio de aceptación:** *"texto libre con modelo no cargado → respuesta por LLM sin
  demora perceptible (no se bloquea esperando el modelo)."*

## 🟡 Precisión 2 (verificación de build) — concurrencia limpia del worker compartido
La clasificación usa el **mismo worker** que el buscador semántico (`postMessage({type:'search', id:'faq-match'})`).
TD-019-01 lo reconoce. En el build **asegura**:
- El listener rutea las respuestas **por `id`**: un resultado `faq-match` **jamás** lo consume la UI del buscador, ni
  viceversa (sin cross-wiring).
- **Reusa el caché de embeddings del corpus** (el fix de latencia de la 016): el `faqCorpus` (~14 entradas) se indexa
  **una vez**, no se re-embebe en cada consulta.

## OK tal como está (no cambiar)
- Doble umbral + fallback conservador ✅; FAQ real de la taxonomía (§2) ✅; escape a LLM siempre ✅.
- Reuso del worker Xenova (sin modelo nuevo) + `AppointmentPicker` (015) ✅; guiado 0-LLM intacto; failover de `chat.php` sin tocar ✅.
- Instrumentación de la decisión de ruteo (mide 0-LLM) ✅; umbrales como parámetros en `intents.json` (versionado) ✅.
- Deudas anticipadas honestas (TD-019-01..06), incl. corpus FAQ pequeño (~14) y calibración pendiente ✅.
- `specsStatus.json` ↔ `ESTADO-SPECS.md` consistentes (build-gate PASSED) ✅.

## Siguiente paso
1. Añade el criterio de aceptación de la Precisión 1 al `plan.md` (modelo-no-cargado → LLM inmediato).
2. **Procede al BUILD** con los gates del `plan.md`. Recordatorios: `php -l` si tocas PHP; el flujo guiado por
   botones sigue 0-LLM; nada de fabricar FAQ; deploy gateado dry-run → `--confirm` con verificación de sitio en 200.

## Evidencia que reauditaré EN VIVO
1. "¿cuánto cuesta?" → ruta **`faq`** con respuesta **de la taxonomía** + botón escape; **cero `chat.php`** (Network).
2. "quiero agendar" → abre `AppointmentPicker` (0-LLM). "¿qué servicios tienen?" → guiado (0-LLM).
3. Consulta ambigua/compleja o **baja confianza** → **`chat.php`** (LLM) — la duda cae al LLM, no a FAQ forzada.
4. **Modelo no cargado** (primera interacción) → LLM **sin bloquear** (no espera la descarga).
5. `trackEvent('intent_routing', …)` registra la decisión (intención/confianza/0llm-vs-llm) sin PII.
6. Guiado por botones 0-LLM intacto; sitio sano tras deploy; build-gate verde; fila 019 → ✅.

---
**Nota:** Claude (Opus 4.8) reauditará en vivo el ruteo 0-LLM real (faq/cita/guiado sin `chat.php`), el fallback
correcto al LLM ante duda **y ante modelo no cargado**, FAQ de taxonomía (sin inventar), y concurrencia limpia del
worker. Añade el criterio de la Precisión 1 y procede al build.
