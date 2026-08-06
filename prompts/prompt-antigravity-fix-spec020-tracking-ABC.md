# FIX (Spec 020, ya en vivo) — 3 correcciones de seguimiento detectadas en reauditoría en vivo (A, B, C)

Reauditoría **EN VIVO** de la Spec 020 (ya desplegada en `app.datanestiq.com`), interceptando los beacons reales del
chatbot. El build está mayormente bien (P1 journey con `interactions`, redacción server-side presente, cache
multi-corpus, emisión no-bloqueante, guards §2). Pero 3 hallazgos concretos degradan la **calidad/veracidad de los
datos** y **hay que corregirlos antes de que se acumulen registros contaminados / PII cruda**.

---

## 🔴 A — `offered` ignora que el router SÍ resolvió la consulta → Bucket 1 se contamina
**Evidencia en vivo:** la objeción **"Nuestros datos son demasiado sensibles para la nube"** se resolvió **0-LLM por FAQ**
(`intent_routing → resolved:0llm, route:faq, faqScore:0.996`), y aun así su `demand_signal` quedó
`{matched_service:"data-engineering", offered:0}`. Como `offered` mira **solo** la similitud con el catálogo e ignora
el `resolved`/`route`, **el Bucket 1 "demanda NO atendida" incluye objeciones/FAQ que SÍ respondemos** → oportunidades de
mercado falsas. (Esto es más grave que TD-020-03: no es ruido de agrupamiento, es un gap **falso** por construcción.)

**Fix:**
1. Añade a la tabla `demand_signals` y al payload el **`resolved_route`** (`faq` | `cita` | `guiado` | `llm`) — reusa el
   `route` que ya calcula el router 019 (está en el evento `intent_routing`). `ALTER TABLE ... ADD COLUMN resolved_route`
   idempotente (patrón 016), con backup + COUNT(*) pre/post.
2. En el **Bucket 1** (`demand_signals` endpoint, `admin/api.php`), cuenta como demanda NO atendida **solo** lo que NO
   resolvimos por ruta 0-LLM propia:
   ```sql
   WHERE offered = 0 AND resolved_route NOT IN ('faq','cita','guiado')
   ```
   (o, equivalente, define `offered = 1` cuando `resolved_route ∈ {faq,cita,guiado}` — si lo atendimos, es algo que
   ofrecemos). Un gap real = no matcheó servicio **y** no lo resolvimos.

## 🔴 B — la redacción de PII NO cubre celulares de 9 dígitos (Perú) → PII cruda en la DB
**Evidencia en vivo:** envié `mi correo es juan.perez@test.com y mi celular 987654321`. El email se redacta bien, pero
`redactPii()` (`track_event.php:107-113`) usa un patrón de teléfono de **10 dígitos** (`\d{3}·\d{3}·\d{4,}`) que **NO
matchea** el móvil peruano de **9 dígitos** `987654321` → se guarda **crudo** en `demand_signals`/`interaction_events`.
El mercado es Perú (SBS, Minsur) → esto es PII real en reposo. El smoke test del build ("saneamiento PII comprobado") no
cubrió el caso de 9 dígitos.

**Fix:** añade un patrón para móviles peruanos (9 dígitos, empiezan en 9, con `+51` opcional) manteniendo el actual:
```php
// Móvil Perú: 9XX XXX XXX (con o sin +51 y separadores)
$text = preg_replace('/(?<!\d)(\+?51[\s.\-]?)?9\d{2}[\s.\-]?\d{3}[\s.\-]?\d{3}(?!\d)/', '[PHONE_REDACTED]', $text);
```
- Aplica el **mismo** `redactPii` a `chatbot_step`/`search_query` (018) **y** a `demand_signal.query_redacted` (020) — ya
  está en 143, verifica que también cubra el genérico `event_value` (103).
- **Prueba** con: `987654321`, `+51 987 654 321`, `987-654-321`, un fijo `01 234 5678`, y un email — todos deben quedar
  `[PHONE_REDACTED]`/`[EMAIL_REDACTED]`. Cuida no sobre-redactar cifras de negocio cortas (ej. "tengo 50000 registros").

## 🟠 C — la herencia de contexto del chip no queda en el journey del chatbot
**Evidencia en vivo:** al seleccionar el chip "Finanzas/CFO" en el home, el chatbot **heredó** sector=finanzas/rol=cfo y
saltó a opciones de finanzas — pero **no** emitió `chatbot_step` de sector/rol (solo el `chip_click` los capturó). El
journey del chatbot pierde el sector/rol salvo que cruces con `chip_click`, y la **demanda no se puede segmentar por
persona**.

**Fix:** incluye `sector` y `role` (de `chatState`/`userContext`) en el payload de **`demand_signal`** (y opcionalmente
en `intent_routing`). Así cada señal de demanda queda segmentada por persona y el journey es auto-contenido. Añade las
columnas `sector`/`role` a `demand_signals` (ALTER TABLE idempotente + backup). Bonus (enriquecimiento): guarda también
el **score** del match de catálogo (no solo el bool `offered`) y el `faqScore` — sirven para recalibrar el umbral 0.40 y
rankear la confianza del gap.

---

## Verificación (reaudito EN VIVO otra vez, mismo método de intercepción de beacons)
1. **A:** objeción resuelta por FAQ ("datos sensibles") → NO aparece en Bucket 1 (demanda no atendida). Una consulta
   fuera de catálogo y NO resuelta ("¿venden repuestos de autos?") → SÍ aparece como gap.
2. **B:** una consulta con `987654321` → en la DB queda `[PHONE_REDACTED]` (lo confirmo pidiéndote una captura del
   registro en `/admin/`, o vía un check que no exponga PII).
3. **C:** tras seleccionar chip CFO y escribir demanda, el `demand_signal` lleva `sector:"finanzas", role:"cfo"`.
4. Sin regresión: 0-LLM (faq/cita/guiado) intacto; guards §2; sitio sano.

## Deploy
- ALTER TABLE idempotentes con **backup + COUNT(*) leads pre/post**; migración remota con `/usr/bin/php8.2-cli`;
  `php -l` en cada PHP; deploy **dry-run → `--confirm`**.

---
**Nota:** Claude (Opus 4.8) detectó A/B/C interceptando los beacons reales en producción. A y B son de **datos** (gap
falso + PII cruda de 9 dígitos) y conviene corregirlos ya, antes de acumular registros sucios. C enriquece la
segmentación por persona. El resto del build está bien.
