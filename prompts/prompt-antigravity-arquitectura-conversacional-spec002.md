# Prompt para Antigravity: Arquitectura Conversacional del Chatbot — Ruteo Híbrido, Máquina de Estados y Tool-Centric (Spec 002 FR-014/015/016)

Actúa como **arquitecto de sistemas conversacionales y Senior React/PHP Engineer**.

## Contexto: FRs ya especificados en la Spec 002 pero NO implementados (y perdidos en la migración)

Claude (Sonnet 5) analizó material de referencia sobre arquitectura de chatbots basada en State Machines (patrón "ParBot": ruteo híbrido determinista/semántico, subgrafos en vez de multi-agente, diseño tool-centric). Al contrastarlo con la Spec 002 se confirma que **estos principios YA están especificados** en:
- **FR-014 (Ruteo Híbrido Deterministic vs. Semantic):** los clics en botones/payloads de UI ejecutan transiciones de estado inmediatas con **cero latencia** (sin llamar al LLM). El LLM solo se invoca ante lenguaje natural ambiguo.
- **FR-015 (State Machine):** la lógica del chatbot debe basarse en una **máquina de estados**, no en un ruteador libre ni en múltiples agentes independientes.
- **FR-016 (Tool-Centric vs. Prompt-Bloat):** en lugar de un System Prompt monolítico gigante, inyectar instrucciones específicas por estado/herramienta, mejorando precisión y ahorrando tokens.

**Estado real verificado:** ninguno de los tres está implementado en el `src/` actual. El `prototype/app.js` original **sí los tenía** (flujo guiado `selectSector` → `selectProblem` → `getSolutionMessage` → formulario de lead, con `chatbotState.step` como máquina de estados y respuestas deterministas sin tocar el LLM). La migración a Astro reemplazó ese chatbot guiado por un **chat de texto libre que llama a Groq para absolutamente todo** (`Chatbot.jsx`), que es justo el antipatrón que la referencia advierte: *"Muchos chatbots fallan porque intentan que la IA lo resuelva todo de forma lineal."* Esto además **quema tokens innecesariamente** (cada saludo/selección dispara una llamada al LLM) y es más lento y menos predecible.

**SDD:** como los FR-014/015/016 ya existen en la spec, **no hace falta modificar `spec.md`** — solo implementar y marcar el estado real en `tasks.md`. Este prompt NO añade requisitos nuevos; recupera y construye lo ya especificado.

## Coordinación con los otros prompts (mismo `chat.php` y `Chatbot.jsx`)
Tres prompts tocan estos archivos. **Orden de ejecución recomendado:**
1. `prompt-antigravity-recuperacion-microexperiencias-spec002.md` (widgets perdidos + ruteo por sesión + split de logs en `chat.php`).
2. **Este prompt** (arquitectura conversacional del chatbot + tool-centric en `chat.php`).
3. `prompt-antigravity-gobernanza-costos-ia-spec002.md` (cap de gasto, metering, alertas sobre `chat.php`).
Si ejecutas fuera de orden, coordina para no pisar cambios. Este prompt asume que el hardening y el ruteo por sesión de `chat.php` ya existen.

---

## BLOQUE 1 — Reconstruir el Chatbot como Máquina de Estados con Ruteo Híbrido (FR-014, FR-015)

Refactoriza `src/components/islands/Chatbot.jsx` para que sea una **máquina de estados** con dos caminos, portando el flujo guiado del `prototype/app.js` a React:

1. **Estado de conversación explícito:** introduce `const [chatState, setChatState] = useState({ step: 'intro', sector: null, problem: null })`. Los pasos válidos: `intro → sector → problem → solution → lead → done` (equivalente al `chatbotState.step` del prototipo).
2. **Camino DETERMINISTA (cero latencia, sin LLM) — FR-014:**
   - En `intro`, el bot ofrece **botones** de sector (Sector Público, Salud, Finanzas, Retail, u "Otro / escribir"). Un clic ejecuta una transición local inmediata (`setChatState`) y renderiza la siguiente pregunta con respuesta **hardcodeada**, sin `fetch` a Groq.
   - En `problem`, botones de problema específicos del sector (reutiliza el mapa `getSolutionMessage(sector, problem)` del prototipo, que ya tiene las respuestas canónicas por sector/problema). El mensaje de solución sale **local, en milisegundos**.
   - En `lead`, muestra la tarjeta de confirmación de lead (email/teléfono) que ya existe (`confirmLead`).
3. **Camino SEMÁNTICO (LLM) — FR-014:** el input de texto libre sigue disponible en todo momento. Si el usuario **escribe** en lugar de pulsar un botón (lenguaje natural ambiguo), *ahí sí* se llama a `chat.php` (como hoy). Es decir: botones = determinista/gratis; texto libre = LLM.
4. **Regla clave:** ningún clic en botón del flujo guiado debe generar una llamada al LLM. Verifica en la pestaña Network que seleccionar sector→problema→solución produce **cero** requests a `chat.php`.
5. Conserva todo lo ya bueno del `Chatbot.jsx` actual: apertura global vía `chatbotOpen`, contexto heredado (`lastUserQuery`/`?servicio=`), extracción y confirmación de lead, formateo de texto. La máquina de estados envuelve eso, no lo elimina.

## BLOQUE 2 — Diseño Tool-Centric en el proxy (FR-016)

En `public/api/chat.php`, reemplaza el **System Prompt monolítico** por instrucciones inyectadas según el estado/intención:
1. El frontend envía, junto al mensaje, un campo `context` liviano (ej. `{ step, sector }`) cuando invoca el camino semántico.
2. `chat.php` compone el system prompt de forma **modular**: una base mínima estable (identidad + reglas de captura de lead + anti prompt-injection) **+** un fragmento específico del estado/sector solo cuando aplica. Evita el bloque gigante único.
3. Mantén el prompt base **estable y primero** en el array (para aprovechar el prompt caching — se coordina con FR-010 del prompt de gobernanza de costos). No metas datos volátiles en el prefijo cacheable.
4. Sigue descartando cualquier `role: 'system'` que venga del cliente (el servidor manda el system prompt). No debilites el aislamiento ya implementado.

## BLOQUE 3 — Documentación SDD

- Marca `[x]` FR-014, FR-015, FR-016 en `specs/002-microexperiencias-ia/tasks.md` **solo** tras verificarlos funcionando (Network sin requests en el flujo guiado; system prompt modular).
- En `specs/002-microexperiencias-ia/tech_debt.md`, registra que el chatbot pasó de free-text puro a máquina de estados híbrida, y la reducción esperada de llamadas al LLM.
- **Nota sobre LangGraph/Python (NO ejecutar aquí):** la referencia usa un stack Python/LangGraph/Pydantic/Redis. Nuestro chatbot vive como isla React + proxy PHP, y la máquina de estados se implementa en ese stack (no se justifica reescribir a Python ahora). La `backend/langgraph_pipeline.py` de la Spec 004 (hoy deshabilitada) es un tema separado — el pipeline de generación de copy, no el chatbot en vivo. Si en el futuro se migra el "cerebro" a LangGraph, esta máquina de estados es el contrato a portar. Deja esto anotado en el `tech_debt.md`, no lo construyas.

---

## Instrucciones de ejecución
1. BLOQUE 1 (máquina de estados + ruteo híbrido en `Chatbot.jsx`).
2. BLOQUE 2 (tool-centric en `chat.php`).
3. `npm run build` (deben seguir siendo 10 páginas) + `npm run preview`.
4. Verificación crítica (Network): abrir el chatbot, pulsar Sector → Problema → ver solución → **cero requests a `chat.php`**; luego escribir texto libre → **una** request a `chat.php`.
5. BLOQUE 3 (documentación).
6. Commit: `feat: chatbot como maquina de estados con ruteo hibrido y system prompt tool-centric (Spec 002 FR-014/015/016)`.

## Forma de respuesta
- Reporta con la evidencia de Network (guiado = 0 llamadas, texto libre = 1 llamada).
- **No toques `specs/008-headless-wordpress/`** ni reescribas nada a Python/LangGraph.
- Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará comparando el flujo guiado contra el del `prototype/app.js` original y verificando que el flujo determinista no genere tráfico al LLM.
