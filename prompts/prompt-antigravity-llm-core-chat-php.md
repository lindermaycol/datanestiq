# Prompt para Antigravity: `chat.php` sobre el registro único de proveedores LLM (llm-core)

**Regla de Oro (anti-fabricación):** todo lo que afirmes debe estar respaldado por un comando que ejecutaste y cuya salida pegas. Si algo de este prompt contradice lo que ves en el repo, **detente en ese punto, repórtalo y no improvises**. No pegues valores de claves en ningún lado (respuesta, logs, walkthrough): el vault importa estos chats y solo enmascara formatos conocidos.

## 0. Contexto (léelo antes de tocar nada)
Existe un registro único de proveedores LLM fuera de este repo, en `C:\Users\Lisbeth\llm-core\`:
- `providers.json` — proveedores (`base_url`, `key_env`) y **perfiles** (cadenas ordenadas de proveedor+modelo). **Es la fuente de verdad.**
- `llm_core.py` — implementación de referencia en Python (léela completa: es la especificación).
- `parity_cases.json` — **15 casos de conformidad** que toda implementación (Python, PHP) debe reproducir exactamente.
- `test_llm_core.py` — pruebas de la versión Python (16 pruebas, verdes al 2026-09-26; verifícalo con `python -m unittest test_llm_core` dentro de esa carpeta).
- Nota del vault: `C:\Users\Lisbeth\vault\shared\domain\registro-unico-proveedores-llm.md`.

Problema que resuelve: la cadena Groq → DashScope → Gemini y sus modelos están hardcodeados en `public/api/chat.php` (líneas ~330-352 hoy), `scripts/docs-generator.mjs`, `scripts/openwiki-llm.yaml` y `scripts/openwiki-camino-b.bat`, con divergencias. Agregar un proveedor exige tocar ~10 sitios.

**Alcance de este prompt: SOLO `chat.php`** (más el cliente PHP, su sincronización y sus pruebas). `docs-generator.mjs`, `openwiki-llm.yaml` y el `.bat` quedan para un prompt posterior.

## 1. Paso 0 — bloqueante: estado de `chat.php`
`git status` y `git diff -- public/api/chat.php`. Al 2026-09-26 `chat.php` tiene **cambios sin commitear de la Spec 025 (GraphRAG, ~21 líneas de `subgraph_context`)**. Esos cambios **no son tuyos en este prompt**: no los reviertas, no los reformatees y no hagas commit. Repórtalos en tu primer mensaje y sigue trabajando encima. Si el diff contiene algo más que GraphRAG, o si `chat.php` cambió respecto de lo descrito aquí, **detente y avisa**.

## 2. Qué construir

### 2.1 `public/api/lib/llm_core.php` — cliente PHP
API: `llm_complete(string $profile, array $messages, array $opts = []): array`.
- **Retorna**: `['ok'=>bool, 'http'=>int, 'body'=>string /*cuerpo JSON crudo de la respuesta ganadora*/, 'text'=>string, 'provider'=>string, 'model'=>string, 'key_index'=>int, 'latency_ms'=>int, 'failures'=>[['provider','model','key_index','http'], ...]]`. Si todos fallan: `ok=false`, `provider=''`, `failures` con cada intento fallido. Si ningún proveedor tiene clave: `ok=false` y `error='sin_claves'`. **No lanza excepciones hacia `chat.php`.**
- **Opciones**: `temperature` (por defecto `defaults.temperature` del perfil, si no 0.7), `max_tokens` (perfil, si no 800), `timeout` (perfil, si no 30), `retries` (por defecto 2), `registry` (ruta o array; por defecto el generado, ver 2.2), `transport` (callable inyectable para pruebas; por defecto curl), `sleep` (callable inyectable; por defecto `usleep`).
- **Semántica exacta** (es la de `llm_core.py` y `parity_cases.json`; léelos, no te fíes de este resumen): se omite el proveedor sin clave (`key_env` es una lista; varias claves del mismo proveedor se rotan); `base_url` se puede sobrescribir con la variable `<NOMBRE_EN_MAYÚSCULAS>_BASE_URL`; hasta `retries+1` intentos por clave para estados 0/429/500/502/503/504 con espera `min(2^intento, 8)` s; 401/403/429 agotados **rotan a la siguiente clave del mismo proveedor**; cualquier otro no-2xx, 5xx/0 agotados o **contenido vacío** pasa al **siguiente proveedor**; se elimina `<think>…</think>` y, si tras eso el texto queda vacío, cuenta como fallo.
- Lee las claves con `getenv()` (chat.php ya carga `.env` con `putenv`). **Nunca** escribas ni registres una clave.
- Función auxiliar `llm_has_any_key(string $profile, $registry=null): bool` para el chequeo temprano de `chat.php`.

### 2.2 El registro dentro de `public/`: NO como JSON
**Hecho verificado por Claude:** no existe ningún `.htaccess` bajo `public/`, así que un `public/api/lib/providers.json` sería descargable por URL. Solución acordada: el JSON maestro NO se copia a `public/`. En su lugar:
- `scripts/sync-llm-registry.php` **genera** `public/api/lib/providers.php` (`<?php return [...];`, ejecutarlo por HTTP no devuelve nada útil) a partir de `C:\Users\Lisbeth\llm-core\providers.json` (ruta sobrescribible con la variable `LLM_CORE_DIR`), con una cabecera de comentario `GENERATED — sha256 de providers.json = <hash> — no editar a mano`.
- `php scripts/sync-llm-registry.php --check` recalcula el hash del maestro y falla (exit 1) si el archivo generado no corresponde. Este `--check` debe poder correr **antes de cada deploy**; **no modifiques `scripts/deploy/deploy_ionos.py`**, pero indica en tu reporte en qué punto se acoplaría.
- `scripts/fixtures/parity_cases.json` = copia de `parity_cases.json` con el mismo mecanismo de verificación por hash (no va a `public/`).

### 2.3 Cambios en `public/api/chat.php` (comportamiento idéntico salvo lo listado en 2.4)
1. `require_once __DIR__ . '/lib/llm_core.php';` y el registro generado.
2. Elimina las tres variables `$groq_api_key/$dashscope_api_key/$gemini_api_key`, el array `$providers` y la función `callOpenAICompatible`. El chequeo "sin claves" → `llm_has_any_key($profile)`, **con el mismo mensaje y HTTP 500 de hoy**.
3. Perfil: `$profile = $is_demo_run ? 'chat_demo' : 'chat_rapido';` (sustituye a `$target_model`). Verifica en `providers.json` que las cadenas de esos dos perfiles coinciden con el comportamiento actual (Groq con `llama-3.3-70b-versatile` en demo y `llama-3.1-8b-instant` si no; luego DashScope `qwen-plus`; luego Gemini `gemini-2.5-flash`). Si no coinciden, repórtalo: **no cambies `providers.json`** (es del registro compartido).
4. Llamada: `llm_complete($profile, $messages, ['max_tokens'=>800, 'retries'=>0, 'timeout'=>20])`. `retries=0` preserva el comportamiento actual (hoy no hay reintentos); `timeout=20` es nuevo (hoy curl no tiene tope) — justifícalo en el reporte.
5. Conserva **intacto**: el registro de alertas `failover` (`$record_alert($sessionId,'failover',…)` una vez por cada elemento de `failures`, con el mismo texto), `logInteraction`, `chat_metrics` (`backend_used = strtolower(provider)`, latencias, tokens con el mismo *fallback* a `usageMetadata` de Gemini), `usage_daily`, los límites diarios/kill-switch, el mensaje de "procesando muchas solicitudes" cuando todo falla, y `echo $response` con el **cuerpo crudo** del proveedor ganador (la forma de respuesta hacia el frontend no puede cambiar).
6. **No toques** `$cheap_llm_*` ni `$is_simple`: en el `chat.php` actual se leen/calculan pero no se usan (código muerto que quedó del ruteo por costo de la Spec 002). Solo repórtalo con líneas.

### 2.4 Cambios de comportamiento aceptados (repórtalos explícitamente)
(a) contenido vacío 200 → ahora failover al siguiente proveedor (antes se devolvía vacío); (b) timeout de 20 s; (c) `<think>` filtrado. Todo lo demás debe ser idéntico. **No agregues NVIDIA** ni otros proveedores a los perfiles de chat.

## 3. Pruebas (todas deben ser reproducibles por Claude)
- `C:\xampp\php\php.exe -l` en cada PHP modificado/creado (pega la salida).
- `scripts/test_llm_core_parity.php`: ejecuta **los 15 casos** de `scripts/fixtures/parity_cases.json` con `transport` y `sleep` simulados (sin red), compara `provider`, `key_index`, `text`, `calls` (y `n_failures`/`error` donde aplique) y sale con código ≠ 0 si falla alguno. Debe imprimir `15/15`. Respeta `retries` por caso (default 2).
- `php scripts/sync-llm-registry.php --check` → OK; y demuestra que falla si alteras un byte del generado (revierte después).

## 4. Verificación en vivo local (criterios de la skill `verificar-en-vivo`: "verde" sin evidencia no cuenta)
Levanta el backend con `C:\xampp\php\php.exe -S localhost:8081 -t public` (si el proyecto ya documenta otra forma, úsala y cítala). Con el `.env` real, **haz una copia de respaldo del `.env` antes de tocarlo y restáuralo al final; demuestra con `fc`/`git diff --no-index` que quedó idéntico**:
1. Mensaje normal → HTTP 200 real; en `secure_leads/crm.sqlite`, `chat_metrics.backend_used='groq'` y `usage_daily` incrementado.
2. `session_id` que empieza con `copilot_` → modelo `llama-3.3-70b-versatile` en la métrica.
3. **Failover real**: invalida temporalmente la clave de Groq → responde DashScope, hay fila `alerts` con `alert_type='failover'` y `backend_used='dashscope'`. Luego invalida Groq y DashScope → responde Gemini.
4. Invalida las tres → HTTP 200 con el mensaje de contingencia y `backend_used='none'`.
5. Comparación de forma: el JSON devuelto en (1) tiene las mismas claves de primer nivel que antes del cambio (`git stash`/versión previa de `chat.php` contra la misma petición, mismo `session_id` de demo).
Muestra las filas de SQLite consultadas (sin datos personales).

## 5. Fuera de alcance (no lo hagas)
Desplegar a IONOS o ejecutar SSH; modificar `docs-generator.mjs`, `openwiki-llm.yaml`, `openwiki-camino-b.bat`; editar `providers.json`, `llm_core.py` o `parity_cases.json` (si hallas un bug ahí, repórtalo con un caso que falle); tocar el frontend; agregar proveedores; commits.

## 6. Documentación
Un walkthrough en `Walkthrough/` con el patrón existente, y una nota corta en `specs/002-microexperiencias-ia/tech_debt.md` (o el archivo de deuda que corresponda) sobre el código muerto de `$cheap_llm_*`/`$is_simple` y la migración pendiente de los otros tres archivos.

## 7. Reporte final (formato)
1) Confirmación del Paso 0 (estado de `chat.php` y de la Spec 025). 2) Lista de archivos creados/modificados con `git diff --stat`. 3) Salida literal de `-l`, `15/15`, `--check`. 4) Tabla de las verificaciones en vivo (§4) con la evidencia. 5) Cambios de comportamiento (§2.4) y decisiones que tomaste. 6) Lo que **no** pudiste verificar.

## Qué verificará Claude al auditar
Reproducirá `php scripts/test_llm_core_parity.php` (15/15) y `--check`; leerá el diff de `chat.php` buscando que no queden modelos ni URLs hardcodeados (`grep -n "llama-3\|qwen-\|gemini-2\|api.groq\|dashscope-intl" public/api/chat.php` debe salir vacío) y que el bloque de GraphRAG siga intacto; repetirá el failover en vivo; comprobará que `.env` quedó idéntico y que no hay claves en el walkthrough ni en la salida; y, tras el deploy (que decide el usuario), `curl -I https://<dominio>/api/lib/providers.json` y `.../providers.php`.
