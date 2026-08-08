# Prompt para Antigravity: Fix del Chatbot global, alineación de textura al prototipo, hardening del proxy y honestidad SDD de la Spec 002

Actúa como **Senior Astro/React Engineer, ingeniero de seguridad backend (PHP) y auditor de metodología SDD**.

Este documento resuelve 5 hallazgos de la auditoría de Claude (Sonnet 5) sobre el trabajo del ciclo anterior (estandarización de diseño + Spec 004) y sobre la **Spec 002 (Microexperiencias IA)**, cuya documentación afirma cosas que el código no respalda.

Trabaja sobre `c:\xampp\htdocs\datanestiq\`. Ejecuta los bloques en orden. Al final corre `npm run build` + `npm run preview`.

> **Alcance:** este ciclo NO construye la capa completa de IA que describe la Spec 002 (Copilot Demo, búsqueda semántica con Transformers.js, wizard adaptativo). Eso es demasiado grande para un ciclo de corrección y se planifica formalmente en el BLOQUE 5, no se implementa aquí. Igual que la Spec 008, se hace primero el pipeline SDD (`plan.md`/`tasks.md`) y la construcción va en un ciclo dedicado aparte.

---

## BLOQUE 1: BUG — el Chatbot solo existe en el Home; sus CTAs están muertos en 8 de 10 páginas

**Problema (verificado con fetch sobre el build real):** `<Chatbot client:idle />` se renderiza **únicamente en `src/pages/index.astro`**. Pero el botón "Auditoría Gratuita" del `Navbar.astro` (presente en TODAS las páginas) y los CTAs de `nosotros.astro` y `casos-de-exito.astro` despachan el evento `open-chatbot`, cuyo listener (en el script de `Navbar.astro`) busca el botón flotante del chatbot con `document.querySelector('button.fixed.bottom-6.right-6')`. Ese botón no existe fuera del Home. Resultado: en las 6 páginas `/soluciones/*`, en `/nosotros` y en `/casos-de-exito`, **ningún botón que abre el chatbot funciona** — el clic no hace nada.

**Corrección — hacer el Chatbot global vía el layout:**
1. En `src/layouts/BaseLayout.astro`, importa el island y renderízalo justo antes del `<slot />` de cierre / dentro del `<body>` (después del `<slot />`), para que aparezca en todas las páginas:
   ```astro
   ---
   import '../styles/global.css';
   import SEO from '../components/ui/SEO.astro';
   import Chatbot from '../components/islands/Chatbot.jsx';
   // ...resto igual
   ---
   <!-- ... -->
   <body class="bg-background text-white antialiased overflow-x-hidden">
     <slot />
     <Chatbot client:idle />
     <!-- ...scripts Phosphor/GSAP igual... -->
   </body>
   ```
2. En `src/pages/index.astro`, **elimina** la línea `import Chatbot ...` y la etiqueta `<Chatbot client:idle />` (para no renderizarlo dos veces en el Home).
3. Verifica que el `DiagnosticWizard` (que también vive solo en el Home vía `index.astro`) siga funcionando: su `useEffect` de `?servicio=` sigue en el Home, correcto — no lo muevas.

**Verificación:** en `npm run preview`, entra a `/nosotros`, `/casos-de-exito` y `/soluciones/ai-data-science`; confirma que el botón flotante del chatbot aparece y que "Auditoría Gratuita" del navbar lo abre.

---

## BLOQUE 2: Alinear la textura de fondo al prototipo (el usuario nota "otra textura")

**Problema:** el prototipo (`prototype/index.html`, líneas 46-48) tiene un fondo distintivo y **persistente en todo el viewport**: una malla fina de 24px + un glow radial azul en la parte superior. El sitio Astro solo pone un grid **dentro del Hero** (`Hero.astro` usa `bg-[url('/grid.svg')]` con un tile de 40px al 5%), sin glow radial y sin cubrir el resto de la página. Por eso se ve "otra textura": grid más grueso, local al Hero, y sin el resplandor azul de marca.

**Corrección — portar el fondo del prototipo a `BaseLayout.astro` (fijo, detrás de todo):**
1. En `src/layouts/BaseLayout.astro`, justo después de abrir `<body>`, añade las dos capas de fondo del prototipo (idénticas, solo actualizando el color del glow al `brand` #2563EB que ya usamos):
   ```html
   <!-- Fondo global: malla fina + glow radial de marca (portado del prototipo Fase 0) -->
   <div class="fixed inset-0 z-[-1] bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>
   <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] opacity-20 pointer-events-none z-[-1]" style="background: radial-gradient(circle, rgba(37,99,235,0.8) 0%, rgba(10,10,11,0) 70%);"></div>
   ```
2. En `src/components/ui/Hero.astro`, **elimina** la línea del grid local (`<div class="absolute inset-0 bg-[url('/grid.svg')] ...">`) — ahora el grid lo provee el fondo global del layout y no debe duplicarse ni competir con máscara. El Hero mantiene su padding y su contenido; solo se quita esa capa de textura local.
3. Revisa `SolutionHero.astro`: si también usa `bg-[url('/grid.svg')]`, quítalo igual (el fondo ahora es global).
4. `public/grid.svg` puede quedar en el repo sin uso o eliminarse; si lo eliminas, confirma que ninguna referencia quede colgando (grep `grid.svg`).

**Verificación:** en `npm run preview`, el Home y las subpáginas deben mostrar la malla fina de 24px cubriendo toda la página y el glow azul superior, igual que `http://localhost/datanestiq/prototype/index.html`.

---

## BLOQUE 3: SEGURIDAD — hardening del proxy `public/api/chat.php` (viola la Constitution sección 5)

**Problema:** `public/api/chat.php` es un proxy a Groq sin ninguna de las protecciones que exige la propia Spec 002 (FR-007 a FR-016) ni la Constitution. Hallazgos concretos verificados en el código:
- **Loggea PII en texto plano** (línea 44-58): guarda `messages` completos —que incluyen el email y teléfono que el usuario escribe— en `chat_logs.jsonl` sin sanitizar ni enmascarar. Esto **viola directamente la Constitution sección 5** ("Restricción absoluta de almacenar o loggear en texto plano cualquier PII").
- **CORS totalmente abierto:** `Access-Control-Allow-Origin: *` — cualquier sitio puede consumir el proxy y gastar tu presupuesto de Groq.
- **Sin rate limiting, sin turn counter, sin límite de longitud de input** (FR-007, FR-011, FR-012 incumplidos): un bot puede disparar llamadas infinitas.
- **System prompt del lado del cliente:** el `SYSTEM_PROMPT` viaja desde `Chatbot.jsx` en el array `messages`, así que un atacante puede sobrescribirlo (prompt injection trivial). FR-007 pide aislarlo en el servidor.

**Corrección (bounded, hazla toda):**
1. **Enmascarar PII antes de loggear:** en `logInteraction()`, antes de escribir, pasa cada `content` por una función que reemplace emails y teléfonos por `[EMAIL_REDACTED]` / `[PHONE_REDACTED]` (regex equivalentes a los de `extractLeadSignals`). El log debe servir para depurar el flujo, no para almacenar PII cruda.
2. **Restringir CORS:** cambia `Access-Control-Allow-Origin: *` por una allowlist del dominio del frontend (`https://datanestiq.com` y `http://localhost` para dev). Valida el header `Origin` de la petición contra esa lista.
3. **Límite de longitud de input:** rechaza (400) si el último mensaje del usuario supera ~2000 caracteres o si `messages` tiene más de ~20 entradas (turn counter, FR-011).
4. **Aislar el system prompt en el servidor:** deja de confiar en el `SYSTEM_PROMPT` que manda el cliente. En `chat.php`, filtra/descarta cualquier `role: 'system'` entrante y antepón un system prompt definido en el servidor (puedes copiar el texto actual del `SYSTEM_PROMPT` de `Chatbot.jsx` a una constante PHP). Ajusta `Chatbot.jsx` para dejar de enviar el mensaje `system` (el servidor lo pone).
5. **Rate limiting básico por IP:** implementa un contador simple basado en archivo/APCu (ej. máximo 20 peticiones por IP cada 10 min) que devuelva 429 al excederse. Si no hay un mecanismo de storage disponible, al menos deja el andamiaje y un `// TODO` claro.
6. **Nota de modelo:** la Spec 002 documenta `llama-3.1-8b-instant` pero `chat.php` usa `llama3-70b-8192`. Decide cuál es el correcto y alinéalos (código + doc). No los dejes contradictorios.

---

## BLOQUE 4: Honestidad SDD — la Spec 002 documenta como "implementado" lo que no existe

**Problema:** la sección "Assumptions & Bridges" de `specs/002-microexperiencias-ia/spec.md` (líneas 99-118) afirma que *"la implementación real ha logrado"* una búsqueda semántica con `Transformers.js` (`Xenova/all-MiniLM-L6-v2`) en Web Worker y un Copiloto de Datos. **Verificado: no existe ninguna referencia a Transformers.js, Web Workers, embeddings ni un Copilot Demo en `src/` ni en `prototype/`.** Igualmente, US1 (Copilot Demo, FR-001/002), US3 (Buscador Semántico, FR-005/006) y el wizard adaptativo multi-paso con badge "Contexto heredado" (US2/US4, FR-003/019) no están construidos. El `DiagnosticWizard.jsx` actual es un generador de pitch por sector, no el wizard condicional que describe la spec.

**Corrección — alinear la documentación con la realidad (no borres la visión, márcala como pendiente):**
1. En `specs/002-microexperiencias-ia/spec.md`, reescribe la sección "Assumptions & Bridges": cámbiala de afirmar implementación en pasado a describir la **arquitectura objetivo (target)**, y añade una nota explícita: *"Estado a la fecha: la búsqueda semántica (Transformers.js), el Copilot Demo y el wizard adaptativo multi-paso están ESPECIFICADOS pero NO IMPLEMENTADOS. Ver `tasks.md` para el estado real por FR."*
2. Actualiza (o crea si falta) `specs/002-microexperiencias-ia/tasks.md` con el estado **verificado** por requisito, marcando `[x]` solo lo real:
   - `[x]` Chatbot conversacional vía proxy Groq (`chat.php`) — FR-001 parcial
   - `[x]` Generación de pitch por sector en el Wizard
   - `[x]` Context-Aware Chaining parcial: `?servicio=` → Chatbot (FR-017 parcial)
   - `[x]` Extracción + confirmación visible de lead (email/teléfono) en el Chatbot
   - `[ ]` Copilot Demo con ≥3 prompts C-level (FR-001/002) — NO implementado
   - `[ ]` Buscador Semántico con Transformers.js en Web Worker + IndexedDB (FR-005/006) — NO implementado
   - `[ ]` Wizard adaptativo multi-paso condicional + Puntaje de Madurez (FR-003) — NO implementado
   - `[ ]` Badge "Contexto heredado" + bypass del Paso 1 (FR-019) — NO implementado
   - `[ ]` Hardening del proxy: rate limiting, turn counter, system prompt server-side, CORS restringido (FR-007/011/012) — se aborda parcialmente en el BLOQUE 3 de este ciclo; marca lo que quede hecho
   - `[ ]` LLM Router / DeepSeek / Prompt Caching (FR-009/010/013) — NO implementado
3. En `specs/002-microexperiencias-ia/tech_debt.md`, añade una entrada registrando esta brecha spec↔código y que la construcción se planifica en el BLOQUE 5.

---

## BLOQUE 5: Plan fasado para construir la capa de IA faltante (planificar, NO construir aquí)

Siguiendo el pipeline SDD (igual que hicimos con la Spec 008), genera el desglose formal para que la construcción real de las microexperiencias de IA sea un ciclo dedicado y priorizado, no un parche.

1. Crea `specs/002-microexperiencias-ia/plan.md` (si no existe) o añádele una sección "Roadmap de Implementación IA" con este orden de prioridad recomendado (justifica brevemente cada uno):
   - **Fase A (mayor impacto/menor costo):** Copilot Demo (US1) — es un widget de chat con 3 prompts pre-set que reusa el `chat.php` ya existente; alto efecto "wow", bajo esfuerzo incremental.
   - **Fase B:** Buscador Semántico con Transformers.js (US3) + Context-Aware Chaining completo con badge "Contexto heredado" (US4) — requiere Web Worker, modelo cuantizado y caché IndexedDB; es la pieza de mayor esfuerzo técnico (decidir modelo, tamaño de bundle, fallback sin WebGPU/WASM), coherente con la deuda de Transformers.js ya registrada en el `plan.md` de esta spec.
   - **Fase C:** Wizard adaptativo multi-paso con Puntaje de Madurez (US2) — rediseño del `DiagnosticWizard` actual hacia una máquina de estados condicional (FR-015).
2. No escribas el código de ninguna de estas fases en este ciclo. Solo el plan.

---

## Instrucciones de ejecución

1. BLOQUE 1 → verifica el chatbot en 3 subpáginas.
2. BLOQUE 2 → verifica la textura global vs el prototipo.
3. BLOQUE 3 → hardening de `chat.php` (prioritario, es una violación de la Constitution en curso).
4. BLOQUE 4 y 5 → documentación honesta + plan fasado.
5. `npm run build` + `npm run preview`, reporta el conteo de páginas (deben seguir siendo 10).
6. Commit: `fix: chatbot global + textura de fondo del prototipo + hardening proxy + honestidad SDD Spec 002`.

## Forma de respuesta
- Reporta bloque por bloque con verificación.
- Sé explícito sobre qué controles de seguridad del BLOQUE 3 quedaron implementados y cuáles requieren infraestructura adicional (ej. rate limiting persistente).
- Sección final "Hallazgos adicionales".
- **No toques `specs/008-headless-wordpress/`** (sigue en pausa) ni construyas la capa de IA del BLOQUE 5 (solo se planifica).

---

**Nota:** Claude (Sonnet 5) auditará este trabajo comparando visualmente contra `prototype/`.
