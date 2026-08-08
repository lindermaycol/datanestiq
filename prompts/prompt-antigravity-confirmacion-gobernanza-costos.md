# Prompt para Antigravity: Confirmación del Plan de Gobernanza de Costos IA (Spec 002 FR-020/021/022 + FR-009/010/013) → ejecutar con 4 precisiones

He revisado `planes/Plan de Implementación Gobernanza de Costos de IA (Spec 002).md`. Es fiel al prompt y bien estructurado. **Tienes luz verde para ejecutar**, con estas 4 precisiones — la #1 y #2 evitan romper/contradecir lo que ya vive en `chat.php` (que ya pasó por 2 ciclos), la #3 y #4 completan detalles.

## Precisión 1 (CRÍTICA) — Respeta el ORDEN y NO elimines los controles ya existentes en `chat.php`

`chat.php` ya acumula, de los ciclos previos: (a) CORS por allowlist, (b) rate limiter por IP **fail-open** (20/10min), (c) límite de turnos (20 msgs) y de longitud (2000 chars), (d) aislamiento del system prompt + filtrado de roles `system` del cliente, (e) inyección de contexto tool-centric (BASE en índice 0, contexto en 1), (f) doble sink de logs (redactado + crudo seguro), (g) ruteo de modelo por sesión. **No borres ni reordenes nada de eso.** Inserta las capas nuevas en el punto correcto:
- **Chequeo de cap diario (FR-020) y de burst (FR-022):** DESPUÉS de los checks existentes (CORS/rate-limit/turnos/longitud) y **ANTES** del `curl` al proveedor. Si el cap se supera, retorna el mensaje de cortesía y **sal sin llamar al LLM**.
- **Metering (FR-021) e incremento del contador diario:** DESPUÉS de recibir 200 OK del proveedor (cuando `$data['usage']` existe).
- El **burst alert (15/3min)** es una capa de *aviso* distinta del rate limiter existente (20/10min, que *bloquea*). Coexisten: no reemplaces el rate limiter con el burst; el burst solo escribe en `alerts.jsonl` (+ webhook opcional).

## Precisión 2 (CRÍTICA) — Precedencia clara del ruteo de modelo (que el Copilot Demo NO se degrade)

Tu plan dice que los mensajes "simples" (<6 palabras) se enrutan al modelo barato "ahorrando el 70B de las sesiones de demostración". Cuidado: el prompt 01 estableció que las sesiones `copilot_`/`unknown_` van al modelo pesado **porque el Copilot Demo envía prompts C-level complejos que SÍ necesitan razonamiento**. Define la precedencia así, sin ambigüedad:
- **Tier PESADO (`llama-3.3-70b-versatile`):** mensajes largos/ambiguos/complejos. Los prompts predefinidos del Copilot Demo son largos → caen aquí naturalmente. No los degrades a barato solo por venir de una sesión demo.
- **Tier BARATO:** mensajes cortos/simples (saludos, "precio", "gracias", clasificación) → **DeepSeek si `DEEPSEEK_API_KEY` existe, si no `llama-3.1-8b-instant`**.
- Regla de oro: **la complejidad del mensaje decide el tier**; la sesión `copilot_` solo garantiza que un mensaje complejo use el pesado. Un saludo de 2 palabras puede ir al barato en cualquier contexto. Así el Copilot Demo real (prompts largos) sigue usando 70B y no se rompe la Fase A del ciclo 01.
- **DeepSeek:** usa su endpoint OpenAI-compatible (`https://api.deepseek.com/v1/chat/completions`, modelo `deepseek-chat`), con su propio header de Authorization. Queda **desactivado** salvo que la key esté en `.env`. Nunca pongas la key en el código.

## Precisión 3 — Fail-open total + el kill-switch no debe inflar sus propios contadores

- **Todo I/O nuevo es fail-open:** si no se puede leer el `daily_usage_*.json` → **permite** la llamada y registra la anomalía; si no se puede escribir metering/contador/alerta → no rompas la respuesta (el usuario ya tiene su respuesta), solo loguea el fallo. Un error de disco jamás debe tumbar el chat ni bloquear a usuarios legítimos.
- **Cuando el kill-switch se dispara** (cap superado → mensaje de cortesía): **NO** incrementes el contador diario ni escribas en `usage_metrics.jsonl`, porque **no hubo llamada al LLM** (no se gastaron tokens). Si no, el contador se auto-alimentaría.
- **Ubicación y rotación:** pon `daily_usage_*.json`, `usage_metrics.jsonl` y `alerts.jsonl` en un directorio **no público y escribible** (ej. `secure_leads/`, ya protegido por `.htaccess`). Añade una limpieza simple para que los `daily_usage_YYYY-MM-DD.json` viejos no se acumulen indefinidamente (ej. borrar los de >7 días en cada corrida). Verifica por `curl` que `usage_metrics.jsonl` **no** responde 200 por HTTP.

## Precisión 4 — Documenta la config con un `.env.example`

Al añadir `DAILY_CALL_CAP`, `DAILY_TOKEN_CAP`, `BURST_THRESHOLD`, `ALERT_WEBHOOK_URL`, `DEEPSEEK_API_KEY` al `.env` (que está en `.gitignore`), **crea o actualiza un `.env.example`** (versionable, **sin** valores secretos) documentando cada clave y su default sensato. Así la configuración queda descubrible para cualquiera que levante el proyecto, sin exponer secretos.

---

Con esas 4 precisiones, procede: BLOQUE 1 (spec.md — FR-020/021/022 antes de codificar), luego los controles en `chat.php`. Verifica según tu plan (kill-switch corta sin llamar a la API; metering sin PII; burst genera alerta; fail-open no tumba el chat), y adicionalmente:
- `curl` a `usage_metrics.jsonl` y `alerts.jsonl` → debe dar 403/404.
- Confirma que un prompt largo del Copilot Demo sigue yendo a `llama-3.3-70b-versatile` y un saludo corto al tier barato.

`npm run build` debe seguir dando 10 páginas. Marca `[x]` en `tasks.md` solo lo verificado. **No toques `specs/008-headless-wordpress/`.** Sección final "Hallazgos adicionales". Yo audito al terminar.
