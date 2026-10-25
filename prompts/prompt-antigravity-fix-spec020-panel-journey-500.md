# FIX (Spec 020, en vivo) — Revisión del panel: Journey Reconstructor devuelve 500 + Bucket 2 contaminado

Revisé el panel `/admin/` en vivo (con sesión iniciada) y **verifiqué los fixes A/B/C**. Buenas noticias primero, luego
2 hallazgos — el **#1 es P0** (una de las dos features estrella de la 020, el Journey, está **rota en producción**).

## ✅ Verificado OK (código + en vivo)
- **Fix B (PII 9 dígitos):** `redactPii():114` añade el patrón PE (`9\d{2}...`) tras el estándar. Probado: un móvil
  peruano de 9 dígitos se guarda `[PHONE_REDACTED]`. ✅
- **Fix C (enriquecimiento):** el `demand_signal` en vivo lleva `sector`/`role`/`score` — capturé el beacon real:
  `{matched_service:"data-engineering", offered:1, resolved_route:"llm", sector:"sector-finanzas", role:"cfo", score:0.527}`. ✅
- **Fix A (Bucket 1 limpio):** `demand_signals` endpoint excluye `resolved_route IN ('faq','cita','guiado')`. ✅
- **Migración idempotente:** PRAGMA + ALTER para `resolved_route/sector/role/score/faq_score` + índices. ✅
- **Guard §2 del panel:** la pestaña "Demanda & Journey" muestra correctamente "Datos insuficientes (N≥20/N≥10)". ✅
- **Buscador semántico y FAQ 0-LLM:** sin regresión (verificado en la ronda anterior). ✅

## 🔴 Hallazgo 1 (P0) — el `lead_journey` devuelve **500** → Journey Reconstructor roto en producción
**Evidencia en vivo:** la pestaña "Demanda & Journey" muestra "No se encontraron interacciones" para **todo** session_id
(el mío con demand_signals reales, el `test1234...` de tu reporte, y uno de diagnóstico). Consultando el endpoint
directamente autenticado:
```
GET /admin/api.php?action=lead_journey&session_id=... → HTTP 500 {"error":"Database error"}
```
La UI **enmascara el 500 como "No se encontraron interacciones"** (engañoso — parece "sin datos" cuando es un error).

**Causa raíz (prime suspect, confírmala en el log):** la query (`api.php:652-677`) reusa el named param **`:session_id`
4 veces** (una por rama del `UNION ALL`) pero hace **un solo bind** `execute([':session_id' => $session_id])`. En PDO
SQLite con *emulate-prepares* OFF, reutilizar un placeholder nombrado lanza **HY093 "Invalid parameter number"** →
PDOException → `catch` (línea 693) → 500. Es el **único** endpoint del panel con reutilización de placeholder → por eso
`leads`/`stats`/`demand_signals`/`leakage` funcionan y **solo el journey** falla. (Descarté columnas faltantes: un
INSERT de prueba en `demand_signals` con `resolved_route/sector/role/score/faq_score` **sí** persistió sin error → las
columnas existen en prod.)

**Fix:**
1. **Lee el log de prod** (`error_log`, ya se registra en `api.php:697` con `$e->getMessage()`) para confirmar el HY093
   antes de tocar nada.
2. Corrige el reuse — opción A (recomendada): **placeholders distintos por rama** (`:sid1`,`:sid2`,`:sid3`,`:sid4`) y
   pásalos todos en `execute`. Opción B: `$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true)` en `getCrmDb()` (más
   amplio; verifica que no afecte otras queries). Prefiero A (explícita, sin efectos colaterales).
3. **Esto estuvo roto desde el build inicial de la 020** (la reutilización ya existía antes del Fix C). No se detectó
   porque las pruebas fueron de **escritura** (`test_track_live.py`) y un **dump de registro**, nunca del **endpoint de
   lectura**. Añade a la verificación un E2E real del endpoint (abajo).

## 🟠 Hallazgo 2 — Fix A ensucia el Bucket 2 (fuga): `offered` forzado a 1 infla "interesados"
`Chatbot.jsx:299` fuerza `offered = 1` para toda resolución 0-LLM: `(demand.offered || ['faq','cita','guiado'].includes(resolvedRoute))`.
Pero el Bucket 2 (leakage, `api.php:628`) filtra `WHERE offered = 1` → ahora **cuenta como "sesión interesada" a quien
solo preguntó una FAQ** (interés débil) igualándolo con quien mostró interés real de servicio → **infla la fuga** y la
agrupa por `matched_service` ruidoso (TD-020-03).

**Fix:** **no fuerces `offered`.** Deja `offered` como señal **pura de catálogo** (`demand.offered`). El Bucket 1 ya
excluye lo resuelto por 0-LLM vía `resolved_route` (Fix A), así que los gaps siguen limpios **sin** tocar `offered`. Así
el Bucket 2 vuelve a medir interés real de servicio. (Si quieres medir "engagement que no convirtió", que sea una métrica
aparte y declarada, no mezclada en `offered`.)

## 🟡 Menores
- **UI del journey:** distingue **500/error** de **sin datos** — hoy ambos dicen "No se encontraron interacciones". Un
  error de backend no debe presentarse como "sin interacciones" (§2). Muestra un estado de error real.
- **Limpieza:** borra mi fila de diagnóstico: `DELETE FROM demand_signals WHERE session_id = 'diagtest000000000000000000000001'`.
- **fail-safe de `track_event.php`:** está bien que devuelva success al cliente (0 UX degradation), pero el
  `warning:'silenced_error'` que ya emites nunca se revisa — considera un contador/health-check para no volver a tener
  fallos de escritura invisibles.

## Verificación (reaudito EN VIVO)
1. `GET /admin/api.php?action=lead_journey&session_id=<uno con datos>` → **200** con el array `journey` (no 500), y el
   timeline en la pestaña muestra los eventos (behavior + demand con badges sector/rol/route + crm_interaction + status).
2. Genera una sesión real (chatbot: chip → consulta de demanda → agendar/lead) y reconstruye su journey completo.
3. Bucket 2 (cuando haya N≥10): una sesión que solo preguntó una FAQ **no** infla "interesados".
4. Sitio sano; sin regresión de A/B/C ya verificados.

## Deploy
- `php -l` en `admin/api.php` y `Chatbot.jsx` build. Deploy **dry-run → `--confirm`**. Entrégame el reporte con la
  **respuesta real del endpoint `lead_journey` (200 + JSON)** — no un dump de tabla — y reaudito en vivo.

---
**Nota:** Claude (Opus 4.8) revisó el panel en vivo. A/B/C están bien salvo el over-reach del Fix A (Bucket 2). El
bloqueante es el **500 del Journey Reconstructor** (reutilización de `:session_id`), invisible hasta ahora porque no se
probó el endpoint de lectura. Confírmalo en el log de prod, corrige con placeholders distintos, y esta vez **prueba el
endpoint que usa el usuario**, no solo la escritura.
