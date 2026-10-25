# Feature Specification: Microexperiencias de IA

**Feature Branch**: `[002-microexperiencias-ia]`

**Created**: 2026-07-04

**Status**: Implementado en Astro, verificado E2E

**Input**: User description: "Formulario de diagnóstico dinámico/adaptativo, Recomendador de servicios por perfil, Demo simulada de copiloto de datos, Búsqueda Semántica y Modelos en Navegador (Transformers.js)"

## Backlog y Desviaciones Documentadas
- **[DESVIACIÓN] FR-004:** Se solicitó Formspree/n8n. Se implementó un pipeline CSV soberano + batch, cuyo destino final será el endpoint WP (Spec 008).
- **[BACKLOG]:** Afinar `threshold` del Buscador Semántico en `SemanticSearch.jsx` (actualmente poco selectivo).
- **[BACKLOG]:** Revisar suscripción en `DiagnosticWizard.jsx` para el resaltado visual de tarjetas de SECTOR.
- **[BACKLOG]:** Respuestas del chatbot carecen de streaming (SSE) - Mejora futura de UX.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Demostración Técnica: IA en Acción (Priority: P1)

Como C-Level (CEO/CFO), quiero interactuar con una demostración de un "Copiloto de Datos" integrado en la web, simulando consultas corporativas complejas (ej. "Proyectar ROI del Q3 con reducción de plantilla"), para tangibilizar la capacidad analítica estratégica de Datanestiq.

**Why this priority**: Tangibiliza la oferta de mayor valor (Agentes/Copilotos) y genera un momento "Wow" que diferencia a la consultora de agencias tradicionales, hablándole directamente al tomador de decisiones.
**Independent Test**: The UI component must allow the user to click pre-defined C-level prompts and see a simulated data analysis/chart response typed out dynamically.

**Acceptance Scenarios**:
1. **Given** el usuario navega a la sección de soluciones, **When** visualiza el bloque de IA Aplicada, **Then** debe ver un entorno de demostración técnica estilo terminal/chat.
2. **Given** el componente de demostración, **When** el usuario hace clic en un prompt sugerido (ej. "Analizar riesgo crediticio del portafolio B"), **Then** el sistema utiliza la IA generativa (vía Groq) para estructurar una respuesta analítica ficticia en tiempo real.

---

### User Story 2 - Formulario de Diagnóstico Interactivo (Priority: P2)

Como CIO o Director de Operaciones, quiero responder una breve secuencia adaptativa de preguntas sobre mi infraestructura actual (Cloud, Data Warehouses), en lugar de llenar un formulario estático, para obtener una evaluación rápida de mi madurez de datos.

**Why this priority**: Reduce la fricción de captura de leads cualificados (Enterprise) en el embudo medio/bajo.
**Independent Test**: Can navigate through a multi-step form where technical questions change based on the previous architecture answer.

**Acceptance Scenarios**:
1. **Given** un botón "Realizar Diagnóstico", **When** el usuario hace clic, **Then** se abre un modal con una pregunta arquitectónica inicial (ej. "¿Tu principal fuente de datos está on-premise o en la nube?").
2. **Given** el paso 1 del formulario, **When** el usuario responde, **Then** la siguiente pregunta profundiza en su stack técnico específico (flujo dinámico).
3. **Given** el último paso, **When** completa el formulario, **Then** se captura el lead, se envía de forma segura y se le muestra un "Puntaje de Madurez" preliminar.

---

### User Story 3 - Búsqueda Semántica y Recomendador de Soluciones (Priority: P3)

Como visitante general, quiero un buscador inteligente donde pueda escribir mi problema con lenguaje natural (ej. "tengo datos desordenados en excel") para que la web entienda mi intención y me sugiera qué servicios de Datanestiq resolverán mi caso.

**Why this priority**: Mejora la navegación personalizada, retiene a visitantes confusos y demuestra inmediatamente capacidades de IA (NLP) aplicadas a la web.
**Independent Test**: Typing a natural language query in the search bar triggers a semantic matching process that outputs a specific recommended service block, regardless of exact keyword matches.

**Acceptance Scenarios**:
1. **Given** el widget "Buscador Inteligente de Soluciones", **When** escribo "pierdo mucho tiempo haciendo reportes a mano", **Then** el widget procesa la intención usando un modelo NLP y destaca "Automatización de Dashboards" y "Pipelines de Datos".

---

### User Story 4 - Encadenamiento de Contexto (Context-Aware Chaining) (Priority: P2)

Como usuario que ya interactuó con el buscador semántico o ingresó un desafío en un sector, quiero que el Formulario de Diagnóstico (Metodología) recuerde mi problema automáticamente, para no tener que repetirlo y sentir que la IA realmente me está asistiendo.

**Why this priority**: Convierte la Landing Page de un conjunto de widgets aislados a una experiencia continua (journey) personalizada, aumentando drásticamente la tasa de conversión del formulario final.
**Independent Test**: Data entered in upper sections correctly mutates the initial state of the diagnostic wizard.

**Acceptance Scenarios**:
1. **Given** que el usuario escribió 'Tengo reportes lentos en Excel' en el buscador superior, **When** hace scroll y llega al Paso 1 del Diagnóstico, **Then** el sistema omite la pregunta genérica de 'Identificación del Reto' y en su lugar muestra: 'Basado en tu reto con (reportes lentos en Excel), pasemos al diseño de solución. ¿Dónde alojas estos datos actualmente?'.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sitio MUST incluir un componente interactivo "Copilot Demo" construido con HTML/JS que se conecte al proxy de IA para generar respuestas en tiempo real.
- **FR-002**: El Copilot Demo MUST poseer al menos 3 prompts pre-configurados orientados a C-Levels que el usuario puede hacer clic para disparar la generación.
- **FR-003**: Se MUST implementar un Flujo de Diagnóstico Multi-paso (Wizard) técnico que reemplace un formulario estático estándar. El flujo debe tener al menos 3 pasos condicionales.
- **FR-004**: Al finalizar el Diagnóstico, se MUST recolectar el nombre y correo del usuario, enviando el payload de forma segura (ej. n8n o Formspree).
- **FR-005**: El sitio MUST incluir un widget "Buscador Semántico" que permita ingreso de texto natural y utilice NLP en el navegador para filtrar dinámicamente las tarjetas.
- **FR-006**: Se MUST utilizar `Transformers.js` en un Web Worker con modelos cuantizados (ej. `q8` o `fp16`) para reducir el peso de descarga, e implementar caché local mediante **IndexedDB** para garantizar carga instantánea en visitas recurrentes.
- **FR-007**: El proxy backend (`chat.php`) MUST implementar medidas de seguridad: **Rate Limiting** estricto por IP, **Sanitización de Inputs**, y **Aislamiento del System Prompt** para prevenir vulnerabilidades de *Prompt Injection*.
- **FR-008**: Todos los componentes MUST heredar el diseño premium (glassmorphism, dark mode, transiciones) implementado en la Spec 001.
- **FR-009**: El proxy backend MUST implementar un **LLM Router (Enrutador de Modelos)**. Las consultas simples (saludos, clasificación) deben dirigirse a un modelo más rápido/económico, mientras que las consultas complejas (razonamiento) usarán modelos top.
- **FR-010**: La arquitectura MUST incorporar **Prompt Caching nativo** para instrucciones y contexto repetitivo, optimizando agresivamente el uso de tokens de entrada.
- **FR-011**: El proxy MUST implementar **Prevención de Loops y Límites de Turno (Turn Counter)**. Se cortará la conversación o se denegará la petición si una sesión supera un máximo de turnos (ej. 10 interacciones), evitando que bots externos generen ciclos infinitos que agoten el presupuesto.
- **FR-012**: El proxy MUST vigilar el **Ratio Input/Output**, estableciendo un `max_tokens` estricto en la respuesta del LLM para evitar generaciones descontroladas o bugs costosos.
- **FR-013**: La arquitectura backend MUST soportar la integración de proveedores ultrabaratos como **DeepSeek** para tareas de alto volumen (clasificación, ruteo) como capa de protección financiera.
- **FR-014**: El flujo conversacional MUST incorporar un **Ruteo Híbrido (Deterministic vs. Semantic)**. Los clics en botones predefinidos (payloads de UI) ejecutarán transiciones de estado inmediatas con cero latencia (sin llamar al LLM). El LLM ("Cerebro") solo se invocará ante lenguaje natural ambiguo o respuestas abiertas.
- **FR-015**: La lógica del Chatbot MUST basarse en **State Machines (Máquinas de Estado)** en lugar de múltiples agentes independientes, garantizando agilidad y compartiendo el mismo contexto sin latencia de red innecesaria (inter-agent overhead).
- **FR-016**: El diseño MUST ser **Tool-Centric en lugar de Prompt-Bloat**. Se evitará un System Prompt monolítico gigante; en su lugar, se inyectarán instrucciones específicas dinámicamente o se estructurará la metadata como "herramientas" acotadas, mejorando la precisión del modelo y ahorrando tokens.
- **FR-017**: El frontend MUST implementar un gestor de estado global (ej. `window.DatanestiqContext = { userChallenge: null, detectedSector: null }`) para permitir **Context-Aware Chaining**.
- **FR-018**: Cada vez que el usuario interactúe (ej. presione Enter) en el Buscador Semántico o en el input de un Sector, el frontend MUST capturar ese texto y guardarlo en `userChallenge`.
- **FR-019**: Al inicializarse el componente del Wizard de Diagnóstico, este MUST verificar si `userChallenge` tiene datos. Si SÍ tiene datos, el Wizard MUST hacer un Bypass visual del Paso 1, inyectando el contexto capturado directamente en el título del Paso 2, personalizando la experiencia y mostrando un badge de 'Contexto heredado'.
- **FR-020**: (Límite de Gasto y Kill-Switch) El proxy DEBE llevar un contador de consumo (llamadas y tokens) en una ventana diaria. Al superar un umbral (`DAILY_CALL_CAP`, `DAILY_TOKEN_CAP`), DEBE dejar de llamar al LLM y responder con un mensaje de cortesía ("alta demanda") sin gastar más presupuesto, bajo un enfoque fail-open (si falla la lectura del disco, permite la llamada pero registra anomalía).
- **FR-021**: (Metering y Observabilidad) El proxy DEBE registrar métricas por llamada (timestamp, session_id, modelo usado, tokens in/out, y latencia ms) en un `usage_metrics.jsonl` sin PII.
- **FR-022**: (Alerta de Gasto Anómalo) El sistema DEBE detectar ráfagas de peticiones por sesión o cruces del 80% del umbral diario, volcando logs a un `alerts.jsonl` y emitiendo, opcionalmente, un webhook a un sistema externo si se configura.

### Key Entities

- **Diagnosis Session**: Representa las respuestas de madurez dadas por el usuario.
- **Copilot Simulation**: Estado del widget de demo (idle, typing, complete).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El Copilot Demo puede ejecutarse de principio a fin sin errores en consola y con una percepción de latencia realista (500-1500ms).
- **SC-002**: El form de diagnóstico envía correctamente el payload de datos a Formspree.
- **SC-003**: El widget recomendador oculta/muestra contenido de forma fluida (CSS transitions) en menos de 100ms tras la selección.

## Assumptions & Bridges (Arquitectura Objetivo)

A diferencia de las suposiciones iniciales (donde se planeaba usar respuestas "mockeadas" para la demo), la arquitectura real apunta a un sistema híbrido avanzado (Bridges) que integra procesamiento local en el navegador y un proxy en el servidor.

> **Estado a la fecha:** La búsqueda semántica (Transformers.js), el Copilot Demo y el wizard adaptativo multi-paso están ESPECIFICADOS pero NO IMPLEMENTADOS. Ver `tasks.md` para el estado real por FR.

1. **Buscador Semántico (Client-Side AI) - IMPLEMENTADO:**
   - Utilizamos `Transformers.js` con el modelo `paraphrase-multilingual-MiniLM-L12-v2`.
   - Se ejecuta 100% en el navegador (Web Worker) con pre-carga en background.
   - Genera los embeddings del catálogo de soluciones y sectores enriquecidos (incluyendo KPIs, regulaciones, subsectores y objeciones de la Spec 011) en memoria, comparando la intención del usuario localmente para resaltar los matches sin latencia de red.

2. **Generación LLM (Server-Side Proxy) - IMPLEMENTADO PARCIALMENTE:**
   - En lugar de exponer credenciales en `app.js`, se implementó `api/chat.php` como un backend proxy seguro con hardening (límites, enmascaramiento PII).
   - Tanto el **Copiloto de Datos** (pendiente) como el **Chatbot** (implementado) se conectan vía `fetch()` a este proxy.
   - El proxy `chat.php` utiliza el modelo de clase mundial `llama-3.1-8b-instant` (vía Groq) para procesar los prompts y generar respuestas en tiempo real.

3. **Enrutamiento de Logs (Higienización de Datos) - IMPLEMENTADO:**
   - El proxy enmascara PII y protege el servidor de abusos.
   - Si proviene del AI Concierge de captación de leads, se guarda de manera persistente en `chat_logs.jsonl` para el sistema de CRM.
