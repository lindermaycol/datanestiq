# Prompt para Antigravity: Gobernanza de Costos y Observabilidad de IA (amplía la Spec 002)

Actúa como **ingeniero de plataformas de IA con foco en FinOps/observabilidad y backend PHP seguro**.

## Contexto y motivación

Claude (Sonnet 5) analizó material de referencia sobre optimización de costos de agentes de IA en producción. Las técnicas clave se contrastaron contra los FRs de la Spec 002 y contra el `chat.php` actual. Resultado: **la Spec 002 ya contempla varias de estas técnicas, pero tres controles financieros críticos NO están ni especificados ni implementados**, y otros están especificados pero sin construir. Este es un riesgo real: un agente mal configurado (o dos agentes en loop) puede drenar el presupuesto de la API de un día para otro sin que nadie se entere.

### Mapa técnica → estado actual (verificado)

| Técnica (referencia) | FR Spec 002 | Estado real en `chat.php` |
|---|---|---|
| Contador de turnos / anti-loop | FR-011 | ✅ Implementado (límite 20 mensajes) |
| Límite `max_tokens` / ratio I/O | FR-012 | ✅ Implementado (`max_tokens: 800`) |
| Router modelo barato/caro | FR-009 | ⚠️ Solo ruteo por `session_id` (se recupera en el prompt de microexperiencias); falta ruteo por **complejidad/intención** |
| Prompt caching nativo | FR-010 | ❌ Especificado, no estructurado |
| DeepSeek como capa ultrabarata | FR-013 | ❌ Especificado, no integrado |
| **Límite de gasto diario / kill-switch** | — | ❌ **NO existe ni en spec ni en código** |
| **Medición de tokens/costo/latencia por llamada** | — | ❌ **NO existe** |
| **Alerta ante gasto anómalo** | — | ❌ **NO existe** |

Los tres últimos son la brecha grave. La regla de referencia es contundente: *"Límite de gasto diario en tu API. Todos los proveedores lo permiten. Hazlo HOY."*

## ⚠️ Dependencia de orden
Este trabajo **toca `public/api/chat.php`**, igual que la Fase F del prompt `prompt-antigravity-recuperacion-microexperiencias-spec002.md`. **Ejecuta primero ese prompt de recuperación** (que reintroduce el ruteo por sesión y el split de logs) y **luego este**, que añade la capa de gobernanza financiera encima. Si se ejecuta antes, coordina para no pisar cambios.

---

## BLOQUE 1 — SDD primero: ampliar la Spec 002 (spec antes que código)

Siguiendo la Constitution (SDD mandatorio), **antes de escribir código** añade estos requisitos a `specs/002-microexperiencias-ia/spec.md` en la sección Functional Requirements, y refléjalos en `tasks.md`:

- **FR-020 (Límite de Gasto y Kill-Switch):** El proxy DEBE llevar un contador de consumo (nº de llamadas y tokens estimados) en una ventana diaria por archivo/almacén local. Al superar un umbral configurable (`DAILY_CALL_CAP`, `DAILY_TOKEN_CAP` en `.env`), DEBE dejar de llamar al LLM y responder con un mensaje de cortesía ("estamos con alta demanda, déjanos tus datos") sin gastar más presupuesto. **Fail-safe:** si el contador no se puede leer/escribir, degrada permitiendo la llamada (no bloquees todo el sitio por un error de disco), pero registra la anomalía.
- **FR-021 (Metering / Observabilidad por llamada):** El proxy DEBE registrar por cada llamada al LLM: timestamp, `session_id`, modelo usado, `prompt_tokens`/`completion_tokens`/`total_tokens` (que Groq devuelve en `usage`), y latencia en ms. Se escribe en un `usage_metrics.jsonl` **sin PII** (solo métricas). Esto permite "medir antes de optimizar".
- **FR-022 (Alerta de gasto anómalo):** DEBE existir un mecanismo que detecte un pico anómalo (ej. > N llamadas en M minutos desde una misma sesión/IP, o cruce del 80% del cap diario) y emita una alerta (log dedicado `alerts.jsonl` y, si hay SMTP/webhook configurado en `.env`, un aviso). El objetivo explícito: que un loop nocturno no "amanezca sin saldo" en silencio.

## BLOQUE 2 — Implementar los controles financieros críticos (FR-020, FR-021, FR-022)

En `public/api/chat.php` (sobre la versión ya endurecida + con ruteo de sesión del prompt de recuperación):

1. **Metering (FR-021):** tras recibir la respuesta de Groq, extrae `data['usage']` (`prompt_tokens`, `completion_tokens`, `total_tokens`) y el tiempo transcurrido; haz append a `usage_metrics.jsonl` con `{timestamp, session_id, model, prompt_tokens, completion_tokens, total_tokens, latency_ms}`. **Nunca** incluyas el contenido de los mensajes aquí (es un log de métricas, no de conversación).
2. **Cap diario + kill-switch (FR-020):** antes de llamar a Groq, lee un contador diario (archivo `daily_usage_YYYY-MM-DD.json` en un dir con rotación) con `{calls, total_tokens}`. Si `calls >= DAILY_CALL_CAP` o `total_tokens >= DAILY_TOKEN_CAP` (valores desde `.env`, con defaults sensatos, ej. 5000 llamadas / 2M tokens), NO llames al LLM: responde 200 con un mensaje de cortesía que invite a dejar los datos de contacto. Tras cada llamada exitosa, incrementa el contador. Aplica el mismo criterio **fail-open** que el rate limiter (si el storage falla, permite y registra).
3. **Alerta (FR-022):** si una sesión/IP supera un `BURST_THRESHOLD` (ej. 15 llamadas en 3 min) o el consumo diario cruza el 80% del cap, escribe una entrada en `alerts.jsonl` y, si `ALERT_WEBHOOK_URL` está definido en `.env`, dispara un `POST` (con timeout corto y fail-open). Documenta que sin webhook configurado, la alerta queda en el log local.
4. Añade a `.env` (y a un `.env.example` si existe, sin secretos) las claves nuevas: `DAILY_CALL_CAP`, `DAILY_TOKEN_CAP`, `BURST_THRESHOLD`, `ALERT_WEBHOOK_URL` (opcional).

## BLOQUE 3 — Cerrar los FRs especificados-pero-no-construidos (dentro de lo razonable)

1. **FR-010 (Prompt Caching):** estructura el payload para maximizar el cache del proveedor — el `system` prompt (ya aislado en servidor) y cualquier contexto estático deben ir **primero y estables** en el array de mensajes, sin variar entre llamadas de una misma sesión. Documenta en comentario que Groq/OpenAI-compatibles cachean el prefijo repetido. Verifica en `usage_metrics.jsonl` que en llamadas sucesivas de una sesión los `prompt_tokens` facturados no crecen linealmente con todo el historial repetido.
2. **FR-009 (Ruteo por intención, además de por sesión):** añade una heurística barata **antes** de elegir modelo — si el último mensaje del usuario es corto y coincide con patrones simples (saludo, "precio", "estado", "gracias", < ~6 palabras), rutea al modelo ligero aunque no sea demo; reserva el modelo pesado para lenguaje natural largo/ambiguo. Esto materializa el hallazgo de la referencia ("el 70% eran variaciones de preguntas simples"). Mantén el ruteo por `session_id` de la Fase F como capa superior.
3. **FR-013 (DeepSeek):** crea la **abstracción de proveedor** (una función `callLLM($provider, $model, $messages, $max_tokens)` que soporte Groq y DeepSeek vía sus endpoints OpenAI-compatibles). Deja DeepSeek **configurable pero desactivado por defecto** (requiere `DEEPSEEK_API_KEY` en `.env`, que el usuario proveerá). Documenta que DeepSeek es la capa ultrabarata para clasificación/routing de alto volumen. No pongas ninguna API key en el código.

---

## Instrucciones de ejecución
1. BLOQUE 1 (documentación SDD) primero — no escribas código de los FR nuevos sin haberlos añadido a la spec.
2. BLOQUES 2 y 3 sobre `chat.php` (asumiendo que el prompt de recuperación ya corrió; si no, coordínalo).
3. Verificación:
   - Simula superar `DAILY_CALL_CAP` bajándolo temporalmente a 2 y confirma que la 3ª llamada devuelve el mensaje de cortesía sin pegarle a Groq.
   - Confirma que `usage_metrics.jsonl` registra tokens y latencia por llamada, sin contenido de mensajes.
   - Confirma que un burst dispara una entrada en `alerts.jsonl`.
   - Confirma el fail-open: renombra/bloquea el dir de contadores y verifica que el chat sigue respondiendo (con la anomalía registrada).
4. Actualiza `specs/002-microexperiencias-ia/tasks.md` y `tech_debt.md` con el estado real.
5. Commit: `feat: gobernanza de costos IA (cap diario, metering, alertas) + ruteo por intencion y abstraccion multi-proveedor (Spec 002 FR-020..022, FR-009/010/013)`.

## Forma de respuesta
- Reporta bloque por bloque con la verificación real (incluida la prueba del cap y del fail-open).
- Sé explícito sobre qué quedó configurable-pero-desactivado (DeepSeek, webhook de alertas) por requerir credenciales del usuario.
- **No toques `specs/008-headless-wordpress/`**.
- Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará este trabajo. Estos controles son de seguridad financiera: se revisará que el kill-switch realmente corte el gasto y que ningún control quede fail-closed (bloqueando a usuarios legítimos por un error de storage).
