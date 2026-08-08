# Plan de Ejecución: Recuperación de Microexperiencias de IA (Spec 002)

Este plan detalla el proceso en fases para recuperar y portar a Astro las funcionalidades avanzadas de IA detalladas en la Spec 002 (Buscador Semántico, Copilot Demo, Wizard Multi-paso, Context Chaining y Batch Lead Extractor).

## User Review Required

> [!IMPORTANT]
> Revisa este plan cuidadosamente. La ejecución se realizará fase por fase en orden secuencial como lo solicitaste, garantizando que el entorno local de Transformers.js (WebGPU/WASM) no contamine el flujo. Además, revisa el rediseño propuesto de cómo `chat.php` manejará los logs con y sin redacción.

## Proposed Changes

### FASE A: Copilot Demo
Implementación del widget demostrativo de IA conectado a LLMs de razonamiento.
- **[NEW] `src/components/islands/CopilotDemo.jsx`**: Widget interactivo estilo terminal/glassmorphism con al menos 3 prompts pre-configurados para C-Levels. Usará el efecto typewriter y hará peticiones con el ID `copilot_demo`.
- **[MODIFY] `src/pages/index.astro`**: Integración del componente `<CopilotDemo client:visible />` en el Home (debajo de la sección de servicios).

### FASE B: Buscador Semántico con Transformers.js
- **[NEW] `public/worker.js`**: Web Worker que cargará el modelo `Xenova/all-MiniLM-L6-v2` cuantizado en `q8` mediante IndexedDB para vectorizar textos y realizar similitud coseno sin latencia de red.
- **[NEW] `src/components/islands/SemanticSearch.jsx`**: Barra de búsqueda NLP. Empaquetará los títulos, descripciones de Servicios y Sectores para vectorización local, e interactuará con el DOM renderizado de Astro (via `querySelectorAll` filtrando elementos `.service-card`, `.sector-card`) para destacar los _matches_ y difuminar los irrelevantes.
- **[MODIFY] `src/pages/index.astro`**: Inclusión de `<SemanticSearch client:visible />` encima del grid de Servicios y Sectores.
- **Dependency**: Se ejecutará `npm install @xenova/transformers`.

### FASE C: Wizard de Diagnóstico Multi-paso + CSV
- **[NEW] `src/components/islands/MultiStepWizard.jsx`**: Un componente React estructurado como máquina de estados. 
  - Paso 1: Reto general.
  - Paso 2: Pregunta técnica condicional según selección previa.
  - Paso 3: Captura de lead (Nombre/Email) y muestra de Puntaje.
- **[MODIFY] `src/pages/index.astro`**: Sustitución del formulario/wizard base con este nuevo componente en la parte inferior de la página.
- **[NEW] `public/api/save_wizard.php`**: Endpoint que recibirá peticiones POST (validando CORS y longitud) y hará **append** a un archivo CSV (`leads_wizard.csv`).
- **[MODIFY] `src/components/islands/Chatbot.jsx`**: Modificar la función `confirmLead` para que, en vez de usar `localStorage`, envíe los datos al mismo `save_wizard.php`.

### FASE D: Context-Aware Chaining
- **[MODIFY] `src/store/index.ts`**: Añadir la exportación de un nanostore atomizado: `export const userChallenge = atom('');`.
- **[MODIFY] `src/components/islands/SemanticSearch.jsx`**: Escribir en `userChallenge` cada vez que el usuario presiona Enter.
- **[MODIFY] `src/components/islands/MultiStepWizard.jsx`**: Suscribirse a `userChallenge` al montarse; si contiene texto, efectuar _bypass_ del Paso 1 e iniciar en el Paso 2 con el badge de "Contexto Heredado de IA".

### FASE E: Metodología
- **[NEW] `src/components/ui/Methodology.astro`**: Componente visual de 3 pasos (01 Diagnóstico, 02 Diseño, 03 Implementación) adaptado al styling premium actual (sin los gradientes desfasados del prototipo viejo).
- **[MODIFY] `src/pages/index.astro`**: Añadir el componente antes de las FAQs.

### FASE F: Ruteo de Logs y Extraction Job (BATCH)
- **[MODIFY] `public/api/chat.php`**:
  - Modificar el enrutador de modelos para usar `llama-3.3-70b-versatile` si `session_id` comienza con `copilot_` o `unknown_`.
  - Crear la lógica de "doble sink" de logs:
    1. `chat_logs.jsonl` y `other_logs.jsonl` seguirán redactando PII.
    2. Se escribirá el mensaje íntegro (crudo, NO redactado) en `../../secure_leads/chat_raw.jsonl` (fuera del webroot) solo para las sesiones válidas de lead capture (no bots ni demos rápidas repetitivas, aunque esto se validará mejor).
- **[NEW] `secure_leads/.htaccess`**: `Deny from all` (si estuviera expuesto).
- **[NEW] `scripts/extraer_leads.php`**: Script batch diseñado para cron. Leerá de `secure_leads/chat_raw.jsonl`, usará LLM para deducir `Organizacion` y `Urgencia`, validará el offset procesado (idempotencia guardada en un `marker.txt`), y anexará en `leads_datanestiq.csv`.

### Actualización de Documentación
- **[MODIFY] `specs/002-microexperiencias-ia/spec.md`**: Actualizar la arquitectura objetivo para marcar como verdaderamente implementadas estas piezas.
- **[MODIFY] `specs/002-microexperiencias-ia/tasks.md`**: Marcar todas las tareas relevantes con `[x]`.
- **[MODIFY] `specs/002-microexperiencias-ia/tech_debt.md`**: Explicar la mitigación del conflicto de PII vs Captura de Leads (Doble Log / Proceso Batch).

## Verification Plan
1. `npm run build` en cada fase.
2. Confirmar vía `Network Tab` la descarga de los modelos cuantizados de `.onnx` para el web worker.
3. Simular llenado de formulario del Copilot y Wizard.
4. Ejecutar manualmente `php scripts/extraer_leads.php` en la terminal para confirmar que extrae del JSONL hacia el CSV.
