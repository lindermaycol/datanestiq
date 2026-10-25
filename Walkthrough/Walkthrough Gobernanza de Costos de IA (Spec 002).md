# Walkthrough: Gobernanza de Costos de IA (Spec 002)

Se ha ejecutado íntegramente el plan de implementación para dotar al `chat.php` de controles financieros (FinOps) y ruteo semántico avanzado, cumpliendo los FR-020, FR-021, FR-022, FR-009, FR-010 y FR-013 con las estrictas precisiones dictadas por Claude.

## Cambios Implementados

### 1. Actualización SDD y Configuración
- Añadidos formalmente los requerimientos FR-020 a FR-022 en `spec.md`.
- El archivo `.env` local incorpora ahora las variables de control (`DAILY_CALL_CAP`, `DAILY_TOKEN_CAP`, `BURST_THRESHOLD`, `ALERT_WEBHOOK_URL`, `DEEPSEEK_API_KEY`).
- Se creó un archivo versionable `.env.example` proporcionando defaults sensatos sin filtrar secretos.

### 2. Kill-Switch y Límite Diario (FR-020)
- El proxy revisa un archivo rotativo en `secure_leads/daily_usage_YYYY-MM-DD.json`.
- Si las peticiones o tokens acumulados superan el cap `.env`, se retorna un código `HTTP 200 OK` con un JSON simulado que devuelve el texto predefinido ("Actualmente estamos experimentando alta demanda. Por favor, déjanos tus datos..."), deteniendo la ejecución y **ahorrando el llamado costoso a Groq**.
- Esta arquitectura es **Fail-Open**: Si los permisos del sistema fallan y el JSON no puede ser leído, la variable asume que hay saldo (0) y permite el paso.

### 3. Alertas por Burst (FR-022)
- El endpoint lleva la cuenta por sesión. Si una IP/Sesión emite, por ejemplo, >15 llamadas en 3 minutos, O si se rebasa el umbral del 80% diario, se anexa un log a `alerts.jsonl`. 
- Si `ALERT_WEBHOOK_URL` está provisto, emitirá de forma asíncrona (timeout corto) una llamada POST para notificar del exceso a Slack/Discord. 
- *Crucial:* Esta capa fue inyectada **después** del Rate-Limiter existente (que bloquea ráfagas masivas) pero **antes** del Kill-Switch, lo que garantiza que los abusos sean siempre notificados aunque el sistema ya haya denegado el servicio.

### 4. Metering y Prompt Caching (FR-010, FR-021)
- Posterior a la resolución 200 OK del proveedor, se capturan `prompt_tokens`, `completion_tokens`, `total_tokens` y `latency_ms`.
- Se vuelcan asíncronamente a `usage_metrics.jsonl` **sin PII y sin contenido**, asegurando que sean exclusivamente métricas. El contador global se incrementa correctamente. *El kill-switch NO infla este contador si se deniega la petición.*

### 5. Multi-proveedor y Ruteo por Intención (FR-009, FR-013)
- Se desarrolló una función nativa `callLLM()` capaz de enrutar *OpenAI-Compatible* para alternar entre Groq y DeepSeek.
- **Ruteo por complejidad:** Un mensaje corto (ej. "hola") se ruteará a la capa ultrabarata (`llama-3.1-8b-instant` o `deepseek-chat`), mientras que los mensajes robustos generados por el Copilot Demo (`copilot_`) conservan su acceso nativo a la capa pesada y reflexiva (`llama-3.3-70b-versatile`).

## Validaciones y Auditoría
- **Kill-Switch:** Validado ajustando manualmente los tokens excediendo el 100%. El sistema respondió instantáneamente sin hacer cURL.
- **Alertas (Burst):** Exceder el umbral escribe exitosamente a `alerts.jsonl`.
- **Fail-Open & Rotación:** El script contiene una rutina embebida para limpiar métricas viejas de > 7 días previniendo desbordamientos del disco.
- **Astro Build:** Completado sin fricciones (100% páginas renderizadas).
- **Tech Debt:** El punto 5 fue agregado y marcado como [RESUELTO] en `tech_debt.md`.
