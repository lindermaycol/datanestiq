# Plan de Implementación: Gobernanza de Costos de IA (Spec 002)

Este documento detalla la implementación de controles de costos y observabilidad para el proxy `chat.php`, cumpliendo con los requerimientos FR-009, FR-010, FR-013, FR-020, FR-021, y FR-022.

## Modificaciones a Especificaciones

### [MODIFY] [specs/002-microexperiencias-ia/spec.md](file:///c:/xampp/htdocs/datanestiq/specs/002-microexperiencias-ia/spec.md)
Se añadirán los siguientes requerimientos formales:
- **FR-020 (Límite de Gasto y Kill-Switch)**: Contador de consumo diario (`calls`, `tokens`) usando `daily_usage_YYYY-MM-DD.json`. Si se superan los límites `.env`, responder con cortesía (fail-open si falla disco).
- **FR-021 (Metering / Observabilidad)**: Registro de `usage_metrics.jsonl` (tokens, latencia, modelo) por cada llamada sin incluir PII ni contenido.
- **FR-022 (Alerta de gasto anómalo)**: Detección de bursts (ráfagas) por sesión y umbrales del 80% diario, volcando a `alerts.jsonl` y ejecutando webhook opcional si configurado.

## Implementación Técnica Backend

### [MODIFY] [.env](file:///c:/xampp/htdocs/datanestiq/.env)
- Añadir las variables de control: `DAILY_CALL_CAP`, `DAILY_TOKEN_CAP`, `BURST_THRESHOLD`, `ALERT_WEBHOOK_URL`, `DEEPSEEK_API_KEY`. Se dejarán en blanco u omitidas de forma predeterminada para que tomen los fallbacks configurados en código.

### [MODIFY] [public/api/chat.php](file:///c:/xampp/htdocs/datanestiq/public/api/chat.php)
1. **Ruteo de Modelos (FR-009, FR-013)**: 
   - Definir función `callLLM($provider, $model, $messages, $max_tokens)`.
   - Incorporar lógica para verificar si el último mensaje es "simple" (< 6 palabras y contiene patrones básicos). Si es simple, se enrutará a DeepSeek (si la llave existe) o a Llama 3.1 8B instant, ahorrando el modelo pesado de 70B de las sesiones de demostración.
2. **Control de Presupuesto (FR-020, FR-022)**:
   - Al inicio del flujo LLM, cargar `daily_usage_YYYY-MM-DD.json`.
   - Validar ráfagas (`BURST_THRESHOLD`) y umbrales globales. Emitir logs a `alerts.jsonl` si es necesario.
   - Si se supera el límite global (`DAILY_CALL_CAP` o `DAILY_TOKEN_CAP`), retornar inmediatamente un JSON con mensaje predefinido ("alta demanda"), evitando ejecutar el cURL a Groq/DeepSeek.
3. **Métricas y Cache (FR-010, FR-021)**:
   - Al recibir 200 OK del proveedor, extraer `$latency_ms` y `$data['usage']`.
   - Volcar métricas a `usage_metrics.jsonl`.
   - Sumar al archivo `daily_usage`.
   - Se validará y dejará documentado en código que el diseño de inyección de array preserva la política de Prompt Caching de Groq (el prefijo inmutable va primero).

## Plan de Verificación

### Pruebas Manuales
1. **Kill-Switch**: Configurar temporalmente `DAILY_CALL_CAP=2` en el script, emitir 3 mensajes desde el chatbot y confirmar que el tercero devuelve la respuesta hardcodeada ("alta demanda") sin llamar a la API externa (verificable viendo 0 latencia en devtools y logs).
2. **Observabilidad**: Inspeccionar `/secure_leads/usage_metrics.jsonl` para validar el registro de latencia y desglose de tokens sin fugas de texto de la conversación.
3. **Alertas por Burst**: Emitir 15 mensajes repetitivos rápido desde la misma sesión y verificar la generación del archivo `/secure_leads/alerts.jsonl`.
4. **Fail-open (Tolerancia a fallos)**: Bloquear los permisos (simular disco inaccesible) al archivo de uso diario y confirmar que el endpoint sigue resolviendo los mensajes a través de Groq sin tumbar la aplicación.
