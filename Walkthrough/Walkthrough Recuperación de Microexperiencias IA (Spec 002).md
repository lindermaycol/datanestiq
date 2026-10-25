# Walkthrough: Recuperación de Microexperiencias IA (Spec 002)

Se ha ejecutado al 100% el plan de trabajo detallado para la recuperación de las microexperiencias de IA, resolviendo el backlog pendiente en la Spec 002.

## Cambios Implementados

### Fase A: Copilot Demo
- Se creó `src/components/islands/CopilotDemo.jsx` con diseño Glassmorphism imitando un terminal de comandos.
- Se implementaron 3 prompts C-Level de prueba, renderizados con un efecto máquina de escribir (*Typewriter effect*).
- Se configuró el componente para ruteo hacia `llama-3.3-70b-versatile` en el proxy.

### Fase B: Buscador Semántico + Transformers.js
- Se instaló la dependencia `@xenova/transformers`.
- Se creó un Web Worker (`public/worker.js`) importado mediante CDN/IndexedDB para el procesamiento local asíncrono de *embeddings*, sin bloquear el hilo principal.
- Se desarrolló `SemanticSearch.jsx` para despachar la búsqueda y actualizar el store.
- Se implementó la clase `service-card` en Astro para la animación directa vía DOM al momento de filtrar.

### Fase C & D: MultiStepWizard + Context-Aware Chaining
- Se introdujo un nuevo componente aditivo `MultiStepWizard.jsx` para el diagnóstico de madurez de datos en 3 etapas.
- Se modificó `src/store/index.ts` aislando de variables globales con **Nano Stores** para pasar `userChallenge` y `semanticHighlight`.
- Si el usuario busca semánticamente, el *Wizard* hace bypass automático del paso 1 gracias al store.
- El chat (`Chatbot.jsx`) fue migrado para guardar directamente al endpoint vía `saveLead()` en lugar de quemarse en el `localStorage`.

### Fase E: Metodología
- Se estandarizó la sección de metodología reproduciendo 3 pasos numerados (*01 Diagnóstico*, *02 Diseño*, *03 Implementación*) tal cual requería el prototipo visual, ubicándola bajo el Wizard.

### Fase F: Ruteo de Logs, BATCH y Seguridad 
- Se creó el directorio `secure_leads` y se aisló permanentemente con un `.htaccess` (Require all denied), confirmado exitosamente mediante cURL devolviendo 403 Forbidden.
- El endpoint proxy `chat.php` ahora efectúa un "doble sink": graba en los logs analíticos públicos con el `[REDACTED]` correspondiente a emails y teléfonos, mientras que guarda de forma privada y cruda en `/secure_leads/chat_raw.jsonl` solo en conversaciones con el Concierge (no Demos).
- Se desarrolló un script CLI en PHP `scripts/extraer_leads.php` que lee `chat_raw.jsonl` usando la API de Groq para convertir interacciones crudas en JSON tabular e insertarlas ordenadamente en `leads_datanestiq.csv`.

## Pruebas Realizadas
- `npm run build` ejecutado en múltiples etapas (sin errores).
- Acceso a HTTP verificado bloqueado vía `.htaccess` para datos privados.
- Dependencias actualizadas sin conflictos.

Con esto, la Deuda Técnica descrita en `tech_debt.md` referida a las microexperiencias IA (Spec 002) queda formalmente clausurada y resuelta en su totalidad.
