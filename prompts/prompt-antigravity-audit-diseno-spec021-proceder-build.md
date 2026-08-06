# Auditoría DISEÑO Spec 021 — APROBADO para BUILD con 1 precisión de fondo + 3 refinamientos

Diseño **sólido**; las 4 precisiones de la auditoría del plan están bien incorporadas: dual-write fail-safe (chat.php →
`alerts`/`usage_metrics` + jsonl de respaldo), `journey_sessions` paginado con `session_id` como `data-session-id`
oculto, exclusión DEMO por defecto (`NOT LIKE 'demoseed%'`) + toggle `include_demo`, y `leads_detected` sin redactar
solo tras `auth.php` con dedup contra CRM. Apruebo el **build** con 1 precisión de fondo y 3 refinamientos.

## 🟠 Precisión de fondo — `usage_metrics` DUPLICA `chat_metrics` (016): reconcílialo, no dupliques
TD-021-03 reconoce el solape pero la justificación es **débil**: `chat_metrics` (016) **ya es por-invocación**
(`{session_id, backend_used, latency_ms, success, tokens_est}`, una fila por llamada al LLM). Lo **único** genuinamente
nuevo en `usage_metrics` es el **desglose prompt/completion tokens**. Crear una **tabla paralela** que `chat.php` escribe
**en cada llamada** junto a `chat_metrics` genera **doble fuente de verdad** de uso LLM → riesgo de drift + doble
escritura. Eso es exactamente la duplicación que pedí evitar.
- **Fix recomendado:** **extiende `chat_metrics`** con `prompt_tokens`/`completion_tokens` (ALTER idempotente, patrón
  016) y construye la vista de uso/costo (`usage_ops`) sobre `chat_metrics`. Una sola tabla de telemetría de LLM.
- Si insistes en tabla separada, **justifica** por qué chat_metrics no basta y garantiza que **ambas se escriben
  atómicamente** (o que una deriva de la otra) para que no divergan — y que la pestaña Ops no muestre dos cifras de uso
  distintas. Prefiero la extensión: menos escritura, cero drift.
- **Antes de construir:** confirma que `chat.php`/los APIs free-tier (Groq/DashScope/Gemini) **realmente devuelven**
  `prompt_tokens`/`completion_tokens`. Si algún backend no los da, la columna quedará en 0 → decláralo (no inventes
  tokens). `alerts` sí es tabla nueva legítima (no existe equivalente).

## 🟡 Refinamiento 1 — `journey_sessions`: incluye la 1ª consulta redactada y aclara el conteo
El `spec.md` promete "primera consulta literal redactada" y "conteo de eventos de comportamiento
(`interaction_events`)", pero el SQL del `data-model` **no** trae la 1ª consulta y usa `COUNT(ds.id)` (señales de
demanda), no `interaction_events`. La 1ª consulta es lo que hace la lista **reconocible** ("necesito un data lake…") —
inclúyela (redactada) en el resumen. Alinea `signals_count` (di si son demand_signals o eventos). Además, la lista sale
**de `demand_signals`** → una sesión anónima con solo `interaction_events` (buscó, clicó chips) y **sin** texto libre
**no aparece**. Es aceptable (enfoque en demanda), pero **decláralo** en el spec.

## 🟡 Refinamiento 2 — `leads_detected`: dedup también por EMAIL, no solo `session_id`
El `spec.md` dice "omitirá `session_id` **o correos** ya con formulario", pero el `data-model` solo dedup por
`session_id`. Un visitante puede chatear anónimo (una sesión) y llenar el formulario después (otra sesión, mismo email).
Dedup **también por email** contra `leads.email` para no mostrar como "detectado" a alguien que ya es lead del CRM.

## 🟡 Refinamiento 3 — CONSTRAINTS: aclara la contradicción aparente de PII
`CONSTRAINTS` dice "listas anónimas mantendrán PII redactada", y `leads_detected` dice "sin redactar". No son
contradictorios (son vistas distintas), pero redáctalo claro: **vistas de comportamiento/anónimas → PII redactada;
`leads_detected` (contacto comercial) → sin redactar, exclusivamente tras `auth.php`**. Y quita la mención a "visor de
logs crudos" si no está en alcance (no incluiste el visor de `chat_logs.jsonl`).

## OK tal como está (no cambiar)
- Dual-write fail-safe (try-catch autónomo) + jsonl de respaldo + retención 180d + índices ✅.
- `session_id` oculto (`data-session-id`), nunca visible ni en URL ✅; `journey_sessions` con `LIMIT/OFFSET` ✅.
- Exclusión DEMO por defecto + toggle `include_demo` + banner; `seed/purge_demand_demo.php` con prefijo `demoseed_` ✅.
- `leads_detected` sin redactar solo tras `auth.php`, cero API pública ✅; UI Vanilla ✅.
- Migración idempotente CLI con backup + COUNT(*) ✅; OUT-OF-SCOPE correcto (no CSV nuevos, no quitar jsonl, nada público) ✅.
- Doc-sync + build-gate PASSED ✅. (Nota: el "deploy" fue solo las páginas wiki; runtime no se tocó, TASKS sin marcar ✅.)

## Siguiente paso — PROCEDE AL BUILD
1. Resuelve la precisión de fondo (extiende `chat_metrics` en vez de `usage_metrics`, o justifícalo) + los 3 refinamientos.
2. `php -l` en `chat.php`/`admin/api.php`; migración idempotente con **backup + COUNT(*) pre/post** vía `/usr/bin/php8.2-cli`;
   deploy **dry-run → `--confirm`**.
3. **Esta vez prueba los ENDPOINTS DE LECTURA en vivo** (la lección del journey 500): `journey_sessions`, `alerts_ops`,
   `usage_ops`, `leads_detected` deben devolver **200 + datos** desde el panel autenticado, no solo la escritura.
4. Entrégame el reporte y **reaudito en vivo**: lista clickeable → journey sin pegar id; alertas/uso en Ops desde SQLite;
   leads detectados dedupados; toggle DEMO separando lo sembrado; cero fuga pública; guards §2.

---
**Nota:** Claude (Opus 4.8) reauditará EN VIVO. La decisión de fondo es **no crear una segunda tabla de telemetría LLM**
(`usage_metrics` ≈ `chat_metrics`): extiende `chat_metrics` con el desglose de tokens. El resto es refinamiento. Y
prueba los endpoints de lectura antes de reportar verde — es la lección que costó el 500 del journey.
