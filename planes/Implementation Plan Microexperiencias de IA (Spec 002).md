# Implementation Plan: Microexperiencias de IA (Spec 002)

Basado en la evaluación de tu repositorio, he detectado que el "Esqueleto" de las Microexperiencias de IA (Buscador Semántico, Diagnóstico Multi-paso y Demo Copiloto) ya está implementado en la UI a un 80%, derivado del trabajo previo. 

Sin embargo, para cumplir estrictamente con los requerimientos técnicos formales (FR-004, FR-006, FR-007) de esta Especificación, necesitamos ejecutar las siguientes intervenciones de infraestructura y seguridad:

## 1. Summary
**Objetivo Principal:** Optimizar la descarga del modelo local NLP (`Transformers.js`) obligándolo a utilizar cuantización agresiva (`q8`), blindar el Proxy de IA (`chat.php`) contra abusos (Rate Limiting, Loops, Ratio I/O), conectar el Flujo de Diagnóstico (Wizard) a un webhook real, e incorporar **una arquitectura de Máquinas de Estado con Ruteo Híbrido (Deterministic vs Semantic) y un diseño Tool-Centric anti-prompt bloat.**

## 2. Technical Context & Constitution Check
- **Componentes:** El *Copilot Demo* interactivo y el *Diagnóstico Wizard* operan correctamente en el frontend (Spec 002: SC-001 y SC-003 superados visualmente).
- **Seguridad (Chat.php):** Actualmente, el backend `chat.php` está vulnerable a *DDoS* y *Prompt Injection*.
- **Performance (Transformers.js):** El `worker.js` está descargando el modelo en su tamaño completo; debemos forzar cuantización para acelerar tiempos de carga (SC-001).

## 3. Proposed Changes

### 3.1. Optimización del Local AI Worker
#### [MODIFY] [worker.js](file:///c:/xampp/htdocs/datanestiq/prototype/worker.js)
- **[MODIFY]** En la instanciación de `pipeline()`, añadiremos el parámetro `{ quantized: true, dtype: 'q8' }` para forzar que el modelo `all-MiniLM-L6-v2` se descargue en versión cuantizada (FR-006), reduciendo significativamente el impacto de red y habilitando la carga casi instantánea desde IndexedDB.

### 3.2. Seguridad Perimetral y Reducción de Costos del Proxy Backend
#### [MODIFY] [chat.php](file:///c:/xampp/htdocs/datanestiq/prototype/api/chat.php)
- **[NEW]** Añadiremos **Rate Limiting** básico basado en IP (limitando las solicitudes a 10 por IP cada 5 minutos) y una capa de **Sanitización Estricta de Inputs** (anti-XSS/SQLi) (FR-007).
- **[NEW]** **LLM Router (FR-009) y DeepSeek (FR-013)**: Evaluaremos la intención. Si es sencilla, el proxy dirigirá la llamada a un modelo ultrabarato (integrando un endpoint de DeepSeek como primary para routing, o Groq `llama-3.1-8b`). Si exige análisis complejo, la enrutaremos a un modelo mayor (`llama-3.3-70b-versatile`).
- **[NEW]** **Prompt Caching (FR-010)**: Estructuraremos los System Prompts estáticos para que el proveedor los cachee automáticamente.
- **[NEW]** **Prevención de Loops / Limits (FR-011)**: Añadiremos un contador de turnos (`Turn Counter`). Si el array de `$messages` excede las 10 réplicas (5 de usuario, 5 de sistema), el backend devolverá un mensaje de cierre forzado para evitar consumos infinitos.
- **[NEW]** **Ratio Input/Output (FR-012)**: Fijaremos estrictamente el parámetro `max_tokens` (e.g., 500) en el payload enviado al LLM. El objetivo de la demo y el bot es captar leads, no redactar ensayos gigantescos que drenen el presupuesto.
- **[NEW]** **Tool-Centric Design (FR-016)**: El backend inyectará instrucciones o payloads estructurados condicionalmente dependiendo del "estado" recibido, evitando que cada petición deba leer un System Prompt monolítico gigante.

### 3.3. Configuración de Máquina de Estados y Ruteo Híbrido en Frontend
#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- **[NEW]** **Ruteo Híbrido (Deterministic vs Semantic) (FR-014)**: Refactorizaremos el Chatbot UI para que las interacciones basadas en botones (ej. clics en "Sector Salud") actualicen el estado y rendericen respuestas locales inmediatas (cero latencia), sin malgastar tokens ni llamadas de red al LLM.
- **[NEW]** **State Machine (FR-015)**: Organizaremos `app.js` mediante una Máquina de Estados sencilla en lugar de un ruteador libre; el "Cerebro" de IA solo se activará si el usuario escribe texto natural fuera del flujo estricto determinista.
- **[MODIFY]** En la función `submitWizard()`, conectaremos el payload (Nombre, Correo, Respuestas) a un Webhook funcional para su guardado seguro (FR-004), reemplazando el placeholder actual `YOUR_FORM_ID`.

## 4. Verification Plan
- Validar mediante el *Network Tab* que la descarga de los modelos ONNX en `worker.js` use los archivos cuantizados (`_q8.onnx`).
- Intentar enviar más de 10 requests seguidas al Demo Copilot para verificar que el proxy devuelve un HTTP 429 Too Many Requests.

---

> [!IMPORTANT]
> **User Review Required:**
> Para el Flujo de Diagnóstico Multi-paso (Wizard), actualmente el código envía un payload a `https://formspree.io/f/YOUR_FORM_ID`.
> ¿Deseas que coloque tu ID real de Formspree ahora, quieres que lo redirijamos a n8n, o prefieres dejarlo apuntando a un archivo local CSV temporal (ej. `api/leads_wizard.csv`) mientras configuras tu CRM oficial?
