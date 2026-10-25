# Prompt para Antigravity: Recuperación completa de las Microexperiencias de IA perdidas (Spec 002) — portadas a la arquitectura Astro

Actúa como **Senior Astro/React Engineer, especialista en IA en el navegador (Transformers.js/Web Workers) e ingeniero backend PHP**.

## Contexto: funcionalidades perdidas cuando se vació `prototype/`

Claude (Sonnet 5) hizo una auditoría arqueológica de TODOS los walkthroughs y planes (`Walkthrough/*.md`, `planes/*.md`). Descubrió que el prototipo original (Spec 002) tenía una capa de IA rica que **se perdió al vaciarse `prototype/`**, porque los archivos clave (`worker.js`, `api/save_wizard.php`, `api/extraer_leads.php` y la lógica de `app.js`) **nunca se commitearon a git** — solo existían en disco. La migración a Astro (Spec 006/007) tampoco los portó. Hoy en `src/` solo sobrevive parcialmente el panel de sectores (grid + pitch generativo + sector on-the-fly, en `DiagnosticWizard.jsx`).

Este prompt **recupera y porta a la arquitectura Astro** (islas React + nanostores + `public/api/*.php`) las siguientes funcionalidades documentadas en los walkthroughs, respetando el hardening de seguridad ya aplicado a `chat.php`.

### Inventario de lo perdido (fuente: walkthroughs citados)
1. **Buscador Semántico con Transformers.js** — las "cajitas de texto que filtraban los servicios/sectores". Usaba `worker.js` con `all-MiniLM-L6-v2` cuantizado `q8`, un `semanticCorpus` (servicios con prefijo `service-`, sectores con `sector-`), y `renderSemanticResults` que reordenaba/difuminaba las tarjetas según la intención. *(Fuente: "Implementación Completada Panel de Sectores", "Implementation Plan Microexperiencias")*
2. **Copilot Demo** — una consola/terminal de IA con prompts C-level pre-definidos que se enrutaba al modelo pesado `llama-3.3-70b-versatile` para `unknown_session`. *(Fuente: "Walkthrough Microexperiencias de IA")*
3. **Wizard de Diagnóstico multi-paso** (máquina de estados) → guardaba vía `api/save_wizard.php` (append) en `api/leads_wizard.csv`. *(Fuente: "Walkthrough Microexperiencias de IA")*
4. **Context-Aware Chaining** — `window.DatanestiqContext` + IntersectionObserver que hacía bypass del Paso 1 del wizard e inyectaba el reto del usuario, con badge "✨ Contexto Heredado de IA". *(Fuente: "Walkthrough Context-Aware Chaining")*
5. **Extractor de Leads con IA** — `api/extraer_leads.php`, un agente `llama-3.1-8b-instant` que leía el jsonl del chat y producía `leads_datanestiq.csv` inyectando `Organizacion` y `Urgencia`. *(Fuente: "Ejecución de la Spec 001")*
6. **Estrategia de IDs / ruteo por sesión** — el `session_id` decidía: (a) a qué archivo de log ir (`unknown_session` → `other_logs.jsonl`; sesión real del concierge → `chat_logs.jsonl`), y (b) qué modelo LLM usar (demo → pesado; chatbot → ligero). *(Fuente: "Assumptions & Bridges" de spec.md y "Walkthrough Microexperiencias")*
7. **Sección de Metodología de 3 pasos** (Diagnóstico → Diseño → Implementación, numerada 01/02/03) — existía en `prototype/index.html`, no está en el Home de Astro.

### ⚠️ Conflicto crítico a resolver (lo detectó la auditoría) + naturaleza BATCH del extractor
El ciclo anterior endureció `chat.php` para **enmascarar PII** (email/teléfono) antes de escribir en `chat_logs.jsonl`. Eso significa que un extractor que leyera ese log ya no vería los correos reales (están redactados). Solución: `chat.php` debe escribir **dos sinks distintos**:
- El **log de analítica** (`chat_logs.jsonl` / `other_logs.jsonl`) sigue **redactado** (como está ahora), para métricas y depuración sin PII.
- Un **log crudo seguro NO redactado** solo para sesiones reales del concierge, guardado **fuera del webroot público** (ej. `secure_leads/chat_raw.jsonl`, protegido con `.htaccess deny` o ubicado un nivel arriba de `public/`). Este es el insumo del extractor.

**El extractor `extraer_leads.php` NO corre en tiempo real durante la conversación — es un proceso BATCH programado.** En el diseño original la extracción jsonl→CSV se ejecutaba periódicamente (ej. **cada ~5 minutos**), no en cada mensaje. Debe recuperarse así: un script invocado por **Windows Task Scheduler (o cron)** cada 5 minutos que lee el log crudo seguro acumulado, agrupa por `session_id` (ver estrategia de IDs, Fase F), clasifica `Organizacion`/`Urgencia` con IA y hace append de las filas nuevas a `leads_datanestiq.csv`. Debe ser **idempotente** (llevar registro de qué sesiones ya procesó, ej. un marcador/offset, para no duplicar filas en cada corrida de 5 min).

---

## Enfoque: fasado y secuencial (una sola entrega, con checkpoints)

Aunque es un solo prompt, ejecuta las 6 fases **en orden**, corriendo `npm run build` entre fases y deteniéndote a reportar si algo falla. Razón: la Fase B (Transformers.js/Web Worker) es la de mayor riesgo técnico (tamaño de bundle, WebGPU/WASM, IndexedDB) y no debe contaminar las fases simples. Actualiza `specs/002-microexperiencias-ia/tasks.md` marcando `[x]` cada FR conforme lo completes de verdad.

---

## FASE A — Copilot Demo (mayor impacto, menor costo; reusa `chat.php`)

Crea una isla React `src/components/islands/CopilotDemo.jsx` y colócala en el Home (`index.astro`), dentro o debajo de la sección de Servicios, con `client:visible`.
- UI estilo terminal/consola glassmorphism (coherente con el sistema de diseño actual: `brand`, `brandCyan`, `darker`, píldoras).
- **≥3 prompts C-level pre-definidos** como botones (ej: "Proyectar ROI del Q3 con reducción de plantilla", "Analizar riesgo crediticio del portafolio B", "Detectar cuellos de botella operativos en mi cadena de suministro").
- Al hacer clic: estado `loading` con typing indicator, `fetch` a `/datanestiq/public/api/chat.php` con `session_id: 'copilot_demo'` (ver Fase F para el ruteo de modelo), y render de la respuesta con efecto typewriter (reutiliza el patrón `TypewriterText` que ya existe en `DiagnosticWizard.jsx`).
- Cumple FR-001, FR-002, SC-001 (latencia percibida 500-1500ms).

## FASE B — Buscador Semántico con Transformers.js (la pieza perdida principal)

1. **[NEW] `public/worker.js`** — Web Worker que carga `@xenova/transformers` con `pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2', { quantized: true, dtype: 'q8' })`. Genera embeddings del corpus y compara por similitud coseno contra la query del usuario. (FR-006)
2. Instala la dependencia: `npm install @xenova/transformers`. Configúrala para que los pesos se sirvan/caché vía IndexedDB (comportamiento por defecto de la librería) y documenta el fallback si el navegador no soporta WASM.
3. **[NEW] `src/components/islands/SemanticSearch.jsx`** — un input de búsqueda ("Describe tu desafío…") ubicado arriba de la sección de Servicios (`client:visible`). Construye el `semanticCorpus` combinando los 6 pilares de `taxonomyCorpus.json` (prefijo `service-`) y los 10 sectores de `sectorsCorpus` (prefijo `sector-`), cada uno con `keywords`.
4. Al escribir + Enter: el worker devuelve los matches ordenados; `SemanticSearch` reordena/resalta las tarjetas de Servicios y difumina las no relevantes (equivalente al `renderSemanticResults` original). Como los componentes de Servicios son `.astro` estáticos, expón un mecanismo (ej. el island manipula el DOM de las tarjetas por `data-service-id`, o convierte `Services` en una isla) — elige la opción más limpia y explícala.
5. Guarda la query en un nanostore nuevo `userChallenge` (ver Fase D). Cumple FR-005, FR-006, US3.

## FASE C — Wizard de Diagnóstico multi-paso + persistencia CSV

1. Evoluciona `DiagnosticWizard.jsx` (o crea un componente hermano) hacia un **flujo condicional de ≥3 pasos** (máquina de estados), no el one-shot actual: Paso 1 identificación del reto → Paso 2 stack técnico (pregunta que cambia según respuesta previa) → Paso 3 captura de Nombre/Email/Organización + "Puntaje de Madurez" preliminar. (FR-003, US2)
2. **[NEW] `public/api/save_wizard.php`** — endpoint que recibe el payload del wizard y hace **append** a `public/api/leads_wizard.csv` (sin sobrescribir). Aplica la misma disciplina de seguridad de `chat.php`: valida Origin (CORS), sanitiza inputs (`strip_tags`/`htmlspecialchars`), limita longitud. **Este CSV es un sink NO redactado** (es captura de lead consentida, no un log) — protégelo con un comentario claro y, si es viable, colócalo fuera del webroot público o con un `.htaccess` deny. (FR-004)
3. Ajusta el `confirmLead` del `Chatbot.jsx` para que también escriba al mismo `save_wizard.php` (o un `save_lead.php` compartido), de modo que TODO lead consentido vaya al CSV soberano — reemplazando el `localStorage` temporal como fuente de verdad de leads.

## FASE D — Context-Aware Chaining (portado a nanostores)

1. **[MODIFY] `src/store/index.ts`** — añade `export const userChallenge = atom('')` (y `detectedSector` si aplica). Esto reemplaza el `window.DatanestiqContext` global del prototipo por el patrón nanostores ya usado en Astro.
2. La Fase B (SemanticSearch) y los inputs de sector del Wizard escriben en `userChallenge` al dar Enter. (FR-017, FR-018)
3. En el Wizard multi-paso (Fase C): al montarse, si `userChallenge` tiene contenido, **hace bypass del Paso 1** y arranca en el Paso 2 con el título inyectado (*"Basado en tu reto con '[userChallenge]', pasemos al diseño de solución…"*) y un badge visible **"✨ Contexto Heredado de IA"**. (FR-019, US4). Nota: en Astro/React usa el ciclo de vida del componente + suscripción al store, no un IntersectionObserver sobre `#diagnostic-wizard` (el prototipo usaba eso por ser vanilla; en React es innecesario).

## FASE E — Sección de Metodología de 3 pasos (recuperar del prototipo)

Crea `src/components/ui/Methodology.astro` replicando la sección del prototipo (`prototype/index.html`, bloque `#metodologia`): 3 pasos numerados 01/02/03 (Diagnóstico → Diseño de solución → Implementación ágil) con el mismo copy. Insértala en `index.astro` entre el Wizard y el FAQ. Usa el sistema de diseño actual (sin gradiente azul-morado).

## FASE F — Ruteo por sesión en `chat.php` (estrategia de IDs) + extractor de leads

1. **[MODIFY] `public/api/chat.php`** — reintroduce el **ruteo por `session_id` SIN romper el hardening actual**:
   - **Modelo:** si `session_id === 'copilot_demo'` (o empieza con `copilot_`/`unknown_`), usa `llama-3.3-70b-versatile` (razonamiento pesado); en cualquier otro caso mantén `llama-3.1-8b-instant`. (FR-009)
   - **Log split:** las sesiones de demo/`unknown_session` se loggean en `other_logs.jsonl`; las del AI Concierge en `chat_logs.jsonl`. **Ambos siguen aplicando el enmascaramiento de PII ya implementado** (no lo quites).
2. **[NEW] `extraer_leads.php` (proceso BATCH, NO en tiempo real)** — recupera el extractor como un **job programado cada ~5 minutos** (Windows Task Scheduler / cron), no como endpoint que se llame en cada mensaje. Lee el **log crudo seguro no redactado** (`secure_leads/chat_raw.jsonl`, fuera del webroot), agrupa las conversaciones por `session_id`, y para cada sesión nueva usa un agente `llama-3.1-8b-instant` (mismo patrón de `chat.php`) para clasificar/normalizar `Organizacion` y `Urgencia` (Alta/Media/Baja). Hace **append** de las filas nuevas a `leads_datanestiq.csv`. Requisitos:
   - **Idempotencia:** mantén un marcador de las `session_id` ya procesadas (ej. un `.processed` o un offset de byte) para que las corridas cada 5 min no dupliquen filas.
   - **Degradación:** si el LLM no está disponible, copia los campos crudos sin clasificar (no romper el batch).
   - **Entrega:** incluye el comando/instrucción para registrar la tarea en Windows Task Scheduler (frecuencia 5 min) y un `// TODO(Spec 008)` indicando que este CSV será reemplazado por el CPT `datanestiq_lead` de WordPress.
   - Los leads consentidos vía `confirmLead`/`save_wizard.php` (Fase C) siguen yendo directo al CSV soberano; el extractor batch es la vía para enriquecer las conversaciones del concierge que dejaron datos sin pasar por el formulario.

---

## Actualización de documentación (obligatoria)

- Marca en `specs/002-microexperiencias-ia/tasks.md` los FR que quedaron realmente implementados (`[x]`) vs los que no.
- En `specs/002-microexperiencias-ia/spec.md`, la sección "Assumptions & Bridges" ahora sí puede describir estas piezas como implementadas — pero **solo las que verifiques funcionando**. No repitas el error anterior de documentar como hecho lo que no lo está.
- En `specs/002-microexperiencias-ia/tech_debt.md`, registra el conflicto PII-redaction vs lead-extraction y cómo se resolvió (canal de leads dedicado y no redactado).

## Instrucciones de ejecución

1. Ejecuta las fases A→F en orden, con `npm run build` entre cada una.
2. Verifica manualmente (vía XAMPP para lo que toque PHP, `npm run preview` para lo estático):
   - Copilot Demo responde a los 3 prompts.
   - Buscador Semántico reordena tarjetas al escribir "problemas en cadena de suministro" (debe destacar Logística) — valida en Network que el worker descargue los `.onnx` cuantizados.
   - Wizard multi-paso guarda una fila en `leads_wizard.csv`.
   - Context chaining: escribe en el buscador, ve al wizard, confirma el badge "Contexto Heredado".
   - `extraer_leads.php` produce `leads_datanestiq.csv` con Organizacion/Urgencia.
3. Reporta conteo de páginas del build y cualquier impacto de bundle de Transformers.js (tamaño, tiempo de primera carga).
4. Commit: `feat: recupera microexperiencias de IA perdidas (buscador semantico, copilot demo, wizard multipaso, context chaining, pipeline de leads) portadas a Astro`.

## Forma de respuesta
- Reporta fase por fase con verificación real (no asumida).
- Sé explícito sobre el bundle de Transformers.js y el fallback sin WASM.
- **No toques `specs/008-headless-wordpress/`** (sigue en pausa; el CSV es el sink temporal hasta que exista el endpoint WP).
- Sección final "Hallazgos adicionales".

---

**Nota de seguridad colateral (para el usuario, no para ejecutar aquí):** `remote_extract.py` en la raíz contiene credenciales SSH y contraseña de producción en texto plano. Está en `.gitignore` (no se versiona), pero sigue en disco sin cifrar. Recomiendo rotar esa contraseña y borrar el archivo — pero eso lo decide el usuario, no se toca en este ciclo.

**Nota:** Claude (Sonnet 5) auditará este trabajo comparando contra los walkthroughs originales.
