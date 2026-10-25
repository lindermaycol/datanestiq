# Prompt de correcciones — `chat.php` sobre llm-core (resultado de la auditoría de Claude, 2026-09-26)

**Regla de Oro:** cada afirmación con su comando y salida pegada; si algo contradice este prompt, detente y repórtalo. No pegues valores de claves.

## Qué salió bien (no lo rehagas)
`php -l` limpio; `test_llm_core_parity.php` 15/15 y detecta mutaciones reales; `sync-llm-registry.php --check` detecta alteración; `chat.php` sin modelos/URLs hardcodeados; GraphRAG (Spec 025) intacto; el walkthrough reportó con honestidad el 404 de Groq; no se desplegó nada (verificado: `https://app.datanestiq.com/api/lib/providers.php`, `.json` y `llm_core.php` → 404).

## Hallazgos que debes corregir

### H1 — Espera tras el último intento (defecto de la ESPECIFICACIÓN, ya corregida en el maestro)
Con `retries=0` y un 503/error de red del primario, el cliente **duerme 1 s antes de hacer failover** (probado por Claude: `sleeps=[1]`). En el `chat.php` de antes el failover era inmediato. Causa: la espera se ejecuta también tras el último intento. **El maestro ya está corregido** (`llm_core.py`: solo se duerme si `attempt < retries`) y `parity_cases.json` ahora exige `sleeps` por caso (17 pruebas Python en verde).
1. Corrige `public/api/lib/llm_core.php` para que espere **solo entre intentos** (`if ($attempt < $retries)`).
2. Actualiza `scripts/test_llm_core_parity.php` para **registrar las esperas** del `sleep` inyectado y compararlas con `expect.sleeps` de cada caso (además de `calls`, `provider`, `key_index`, `text`, `n_failures`/`error`).
3. Demuestra con una mutación (devolver la espera tras el último intento) que el runner ahora **falla** en al menos 5 casos; revierte.

### H2 — Registro v2 (los modelos Groq del registro anterior NO existen para nuestra cuenta)
Hallazgo de Claude, confirmado por ti en el walkthrough §3.4 (404): `llama-3.1-8b-instant`, `llama-3.3-70b-versatile` y `groq/compound-mini` devuelven `model_not_found` (`GET /models` lista solo 11 modelos, incluidos `openai/gpt-oss-20b` y `openai/gpt-oss-120b`). Con esos IDs, **cada mensaje del chat hacía failover a DashScope** y registraba una alerta. El maestro pasó a **versión 2**: Groq usa `openai/gpt-oss-20b` (`chat_rapido`) y `openai/gpt-oss-120b` (`chat_demo`); ambos verificados por Claude en español (~1.4 s y ~1.6 s con `max_tokens=800`).
1. Ejecuta `php scripts/sync-llm-registry.php` para regenerar `providers.php` y `scripts/fixtures/parity_cases.json`; `--check` debe volver a OK (hoy falla a propósito: es el guard de deriva funcionando).
2. **Demuestra en vivo local** (mismo método que en tu §3.4, con la copia de respaldo del `.env` y `fc` al final): sesión normal → `backend_used='groq'`, el campo `model` del cuerpo es `openai/gpt-oss-20b` y **NO hay fila `failover` en `alerts`**; sesión `copilot_*` → `openai/gpt-oss-120b`; Groq inalcanzable (`GROQ_BASE_URL=http://127.0.0.1:9/v1` en el entorno del servidor `php -S`, sin editar `.env`) → responde DashScope y **el tiempo total no incluye la espera artificial de 1 s** (mide con `curl -w %{time_total}` antes/después de H1).
3. Los modelos `gpt-oss` son de razonamiento y devuelven un campo `reasoning` aparte del `content`. Confirma que `chat.php` entrega al frontend el `content` correcto (el cuerpo crudo del proveedor se reenvía tal cual: verifica que `Chatbot.jsx` solo lee `choices[0].message.content` y que el campo extra no rompe nada). Reporta con `grep` sobre `src/`.

### H3 — Observaciones (documenta, no cambies comportamiento)
- **`DASHSCOPE_BASE_URL` en el `.env` local** apunta a un endpoint de workspace (`…ap-southeast-1.maas.aliyuncs.com`); antes `chat.php` la ignoraba (URL fija `dashscope-intl`), ahora `llm_core` la respeta. Ambas responden 200 con nuestra clave (probado). Anótalo en el walkthrough: local y producción pueden usar endpoints distintos según su `.env` remoto.
- El chequeo "No API keys configured" ahora ocurre **después** del rate-limit y del parseo del cuerpo (antes era lo primero). Documéntalo; no lo muevas.
- `tech_debt.md` cita `$is_simple` en "líneas 287-288": hoy está en la **297**. Corrige el número.
- `.gitignore`: la línea `!public/api/lib/*.php` es necesaria y correcta; menciónala en el walkthrough.

## Fuera de alcance
Desplegar / SSH; editar `providers.json`, `llm_core.py` o `parity_cases.json` (si ves un defecto, repórtalo con un caso que falle); migrar `docs-generator.mjs`, `openwiki-llm.yaml`, `.bat`; commits.

## Reporte final
1) Salidas literales: `-l`, parity `15/15` con esperas, `--check` OK, mutación de H1 fallando. 2) Tabla de las verificaciones en vivo de H2 con evidencia (filas de SQLite, `model`, tiempos). 3) Resultado del `grep` de H2.3. 4) Lo que no pudiste verificar.

## Qué verificará Claude al re-auditar
Reproducirá parity (con `sleeps`), `--check`, la sonda de esperas con `retries=0` (`sleeps=[]`), y el failover en vivo con medición de tiempo; confirmará que `alerts` no recibe filas de failover en el camino feliz, que `.env` quedó idéntico y, tras el deploy que decida el usuario, `curl -I` sobre `providers.php`/`.json` y un mensaje real contra producción.
