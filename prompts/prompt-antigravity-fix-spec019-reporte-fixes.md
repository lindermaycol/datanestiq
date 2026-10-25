# Reporte Fix P0 — Spec 019 Chatbot 403 + Router 0-LLM

**Fecha:** 2026-08-06 | **Commit:** `8a29a78`

## Cambios aplicados

### Bug #1 (P0) — `public/api/chat.php`
```diff
+ $allowed_origins = ['https://app.datanestiq.com', 'https://datanestiq.com'];
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin) {
      $parsed_origin = parse_url($origin);
      $host = $parsed_origin['host'] ?? '';
-     if ($host !== 'localhost' && $origin !== 'https://datanestiq.com') {
+     if ($host !== 'localhost' && !in_array($origin, $allowed_origins, true)) {
```

### Bug #2A — `public/worker.js`
```diff
- self.postMessage({ status: 'indexed' });
+ self.postMessage({ status: 'indexed', id: data.id });
  // + id: data.id también en el catch de error
```

### Bug #2B — `src/lib/intentClassifier.ts`
```diff
- if (status === 'complete') {
+ if (status === 'complete' || status === 'indexed') {
  // Timeout index: 5s → 15s
```

### Bug #3 (P0) — `src/lib/intentClassifier.ts` (Worker id mismatch)
```diff
-    const id = nextId(type);
+    const id = payload.id ?? nextId(type);
     _pending.set(id, { resolve, reject });
-    _worker.postMessage({ type, id, ...payload });
+    _worker.postMessage({ type, ...payload, id });
```

## Verificaciones de smoke test (producción)

| Check | Resultado |
|-------|-----------|
| 6a — `app.datanestiq.com/` | ✅ 200 OK |
| 1 — `POST /api/chat.php` con `Origin: https://app.datanestiq.com` | ✅ **200** (respuesta real del LLM, no 403) |
| 6b — `GET /api/track_event.php` | ✅ 405 (write-only sigue intacto) |

## Checks pendientes de auditoría en vivo (Claude Opus 4.8)

1. ✅ **VERIFICADO** — Texto libre complejo → `POST /api/chat.php` 200, respuesta real del LLM.
2. 🔲 Router 0-LLM activo — en consola, sin `Worker timeout: index-*`; `isClassifierReady()` llega a `true`.
3. 🔲 FAQ 0-LLM — "¿cuánto cuesta?" → respuesta de la taxonomía sin llamada a `chat.php` + botón escape.
4. 🔲 cita / guiado 0-LLM — "quiero agendar" → AppointmentPicker; "¿qué servicios tienen?" → guiado. Sin `chat.php`.
5. 🔲 Duda → LLM — consulta ambigua/compleja → `chat.php` 200. La duda cae al LLM, no a FAQ forzada.
6. ✅ **VERIFICADO** — Sin regresión: sitio 200, track_event 405 correcto.
7. 🔲 `trackEvent('intent_routing', …)` registra la decisión (intención/confianza/0llm-vs-llm).
