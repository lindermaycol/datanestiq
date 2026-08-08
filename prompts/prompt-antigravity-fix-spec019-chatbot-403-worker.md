# FIX URGENTE (P0) — Chatbot roto en producción: `chat.php` 403 + router 0-LLM (Spec 019) nunca inicializa

**Reauditoría EN VIVO** en `https://app.datanestiq.com/` (navegador real, chatbot abierto, pregunta "¿cuanto cuesta?").
Resultado: el chatbot respondió con el **mensaje de error de fallback** ("Ups, tuve un problema para procesar tu
mensaje…"), NO con la FAQ 0-LLM esperada. Diagnóstico de red + consola revela **DOS bugs reales, ambos de deploy/
integración** que la auditoría por código no podía ver. La Spec 019 está **no-funcional en producción**.

## Evidencia en vivo (verificada por Claude)
- **Network:** `POST https://app.datanestiq.com/api/chat.php → 403`, body `{"error":"Forbidden"}` (JSON, o sea es un
  guard de la app, NO Apache/.htaccess).
- **Console (x3):** `[intentClassifier] Failed to index intents corpus: Worker timeout: index-1 / index-2 / index-3`.
- **Console:** `Chatbot API Error: Network error` (el 403 escala al catch → mensaje de error al usuario).

---

## 🔴 Bug #1 (P0) — `chat.php` rechaza el origen del subdominio → 403 en TODO texto libre

**Causa raíz.** `public/api/chat.php` (líneas 6-16) valida el `Origin` contra una allowlist que quedó
**desactualizada tras migrar al subdominio `app.datanestiq.com`**:

```php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    $parsed_origin = parse_url($origin);
    $host = $parsed_origin['host'] ?? '';
    if ($host !== 'localhost' && $origin !== 'https://datanestiq.com') {   // ← falta app.datanestiq.com
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header("Access-Control-Allow-Origin: $origin");
}
```

El sitio se sirve desde `https://app.datanestiq.com` → el header `Origin` es `https://app.datanestiq.com` → no es
`localhost` ni el apex `https://datanestiq.com` → **403**. El chatbot LLM lleva roto desde el despliegue del subdominio.

**Fix.** Incluye el subdominio (y deja el apex para cuando el sitio migre allí). Sugerido — allowlist explícita:

```php
$allowed_origins = ['https://app.datanestiq.com', 'https://datanestiq.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    $parsed_origin = parse_url($origin);
    $host = $parsed_origin['host'] ?? '';
    if ($host !== 'localhost' && !in_array($origin, $allowed_origins, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header("Access-Control-Allow-Origin: $origin");
}
```

- Mantén la lógica fail-closed para orígenes externos (sigue rechazando POSTs de terceros — no la debilites).
- **`php -l chat.php`** tras editar.
- **Revisa el MISMO patrón en los otros endpoints PHP** por si heredaron el apex hardcodeado:
  `public/api/track_event.php` (su chequeo same-origin — Spec 018) y cualquier otro que valide `HTTP_ORIGIN`/`Referer`.
  Si alguno permite solo `datanestiq.com`, agrégale `app.datanestiq.com` igual.

---

## 🟠 Bug #2 — El router 0-LLM (Spec 019) NUNCA inicializa: el `index` del worker no devuelve `id`

**Causa raíz (colisión de las dos precisiones del diseño).** El indexado del corpus de intenciones se queda colgado:

1. `src/lib/intentClassifier.ts:159` hace `await workerRequest('index', { corpusTexts: _intentCorpusTexts })`.
   `workerRequest` (líneas 75-89) envía `{ type:'index', id:'index-N-…', corpusTexts }` y registra una promesa
   pendiente por ese `id`, con **timeout de 5s**.
2. `public/worker.js:67-80` procesa `type:'index'` y responde con `self.postMessage({ status: 'indexed' })`
   — **sin `id`**.
3. `intentClassifier.ts:94` (`handleWorkerMessage`): `if (!id) return;` → **ignora** el `{status:'indexed'}` (es la
   P2 anti-cross-wiring: descarta mensajes sin id).
4. La promesa nunca resuelve → timeout 5s → `catch` → `_classifierReady = false` **para siempre**.

Consecuencia: `isClassifierReady()` siempre `false` → el chatbot se salta el router (ruta P1) → **cero ruteo 0-LLM**;
`faq`/`cita`/`guiado` nunca se activan. El feature completo de la Spec 019 está muerto en prod.

**Fix (mínimo y no-rompedor).** El worker debe **devolver el `id`** en las respuestas de `index` (y `warmup`), y el
clasificador debe resolver ante `indexed`. Cuidado: `worker.js` es COMPARTIDO con `SemanticSearch` — **no cambies el
`status:'indexed'`** (SemanticSearch depende de él); solo **añade el `id`**.

En `public/worker.js`, handler de `index`:
```js
if (data.type === 'index') {
    try {
        const extractor = await PipelineSingleton.getInstance(x => {
            self.postMessage({ status: x.status, name: x.name, file: x.file, progress: x.progress });
        });
        if (data.corpusTexts) {
            await indexCorpus(extractor, data.corpusTexts);
        }
        self.postMessage({ status: 'indexed', id: data.id });   // ← añade id (undefined si el emisor no lo mandó)
    } catch (e) {
        self.postMessage({ status: 'error', id: data.id, error: e.message });  // ← id también en error
    }
    return;
}
```
(SemanticSearch envía el `index` **sin** `id` → `data.id` queda `undefined` → `handleWorkerMessage` lo sigue ignorando
→ su comportamiento fire-and-forget no cambia. ✅)

En `src/lib/intentClassifier.ts`, `handleWorkerMessage` (línea 98) — resolver también ante `indexed`:
```js
if (status === 'complete' || status === 'indexed') {
    pending.resolve(results);   // results será undefined para 'indexed'; initIntentClassifier no lo usa
} else if (status === 'error') {
    pending.reject(new Error(error ?? 'Worker error'));
}
```

**Robustez del timeout (secundario, incluir).** El indexado se dispara idealmente cuando el worker ya emitió `ready`
(`onWorkerReady`), así el modelo está tibio y embeber ~N frases es rápido. Verifica que el `index` del clasificador se
lance **después** del `ready` del worker (no compitiendo con la descarga fría del modelo, que puede exceder 5s). Si
puede lanzarse en frío, sube ese timeout puntual a ~15s o encadénalo al `ready`. No toques el flujo de SemanticSearch.

---

## Verificación (Claude reauditará EN VIVO, mismo método que encontró esto)
1. **Chatbot LLM funciona:** en `https://app.datanestiq.com/`, texto libre complejo → `POST /api/chat.php` **200**
   (no 403), respuesta real del LLM. Confirmar en Network.
2. **Router 0-LLM activo:** en consola, **sin** `Worker timeout: index-*`; `isClassifierReady()` llega a `true`.
3. **FAQ 0-LLM:** "¿cuánto cuesta?" → respuesta **de la taxonomía** (`objectionResponses`) **sin** llamada a
   `chat.php` (Network limpio) + botón de escape.
4. **cita / guiado 0-LLM:** "quiero agendar" → `AppointmentPicker`; "¿qué servicios tienen?" → guiado. Sin `chat.php`.
5. **Duda → LLM:** consulta ambigua/compleja → `chat.php` 200 (LLM). La duda cae al LLM, no a FAQ forzada.
6. **Sin regresión:** el buscador semántico sigue funcionando (mismo worker); flujo guiado por botones 0-LLM intacto;
   sitio sano (`app.datanestiq.com/` 200, `/admin/` 302, WordPress `datanestiq.com` 200).
7. `trackEvent('intent_routing', …)` registra la decisión (intención/confianza/0llm-vs-llm) — ahora que el router vive.

## Deploy
- `php -l` en cada PHP tocado. Build de Astro (el worker y el JS del clasificador se re-emiten).
- Deploy **dry-run → `--confirm`** del usuario (no unilateral). Verifica sitio 200 tras subir.
- Al terminar, entrégame el reporte con la evidencia de los 7 checks y **reaudito en vivo** (fue así como se detectó).

---
**Nota:** Claude (Opus 4.8) detectó ambos bugs navegando en vivo el sitio real. Mi auditoría por código validó la
*lógica* de ruteo (correcta), pero no la integración de deploy: (a) la allowlist de origen no se actualizó al migrar al
subdominio, y (b) la respuesta de indexado del worker no lleva `id`, chocando con la P2 (ignorar mensajes sin id). Los
dos son fixes quirúrgicos. Prioridad: **#1 primero** (restaura el chatbot ya), **#2** activa el 0-LLM de la 019.
