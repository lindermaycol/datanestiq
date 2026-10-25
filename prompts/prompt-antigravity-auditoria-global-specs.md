# Prompt para Antigravity: Auditoría global SDD — Specs 001, 002, 003, 004, 005, 006 y 008

Actúa como un **Director Técnico de Arquitectura, auditor de metodología SDD (Spec-Kit) y Senior Full-Stack Engineer** (Astro/React + WordPress/PHP).

Este documento **complementa** a `prompts/prompt-antigravity-auditoria-spec007-correcciones.md` (que ya cubre en detalle la Spec 007). Aquí se audita **el resto del ecosistema de especificaciones** (`specs/001` a `specs/006` y `specs/008`) contra el estado real del código, siguiendo la misma auditoría de Claude (Sonnet 5) sobre la rama `007-multi-pagina`.

El hallazgo transversal más importante: **varios `tasks.md` y `tech_debt.md` afirman que algo está "Resuelto" o "[X] completado" cuando el código real no lo respalda.** Esto viola directamente la Constitution (sección 1: SDD mandatorio, sin deriva entre especificación e implementación). Tu trabajo aquí es doble: (a) corregir el código donde falta, y (b) **corregir la documentación para que dependa de evidencia verificada, no de suposiciones**.

Trabaja sobre: `c:\xampp\htdocs\datanestiq\`. Aplica los bloques en orden. Al final de cada bloque, dejá evidencia de verificación (comando ejecutado, archivo inspeccionado) en tu respuesta.

---

## BLOQUE A: Gap de proceso SDD — Specs sin `tasks.md`

**Problema:** la Constitution exige que TODO cambio pase por `/specify → /plan → /tasks → /implement`. Sin embargo:
- `specs/001-elevacion-premium/` tiene `spec.md` (con `Status: Draft`) y `plan.md`, pero **no tiene `tasks.md`**.
- `specs/002-microexperiencias-ia/` tiene `spec.md` (con `Status: Draft`) y `plan.md`, pero **no tiene `tasks.md`**.
- `specs/003-taxonomia-servicios/` tiene `spec.md` y `plan.md`, pero **no tiene `tasks.md`**.

A pesar de esto, gran parte de lo descrito en esas specs ya está implementado en `src/` (migrado durante la Spec 006). Es decir: se implementó código sin el desglose formal de tareas que la propia Constitution declara obligatorio.

**Corrección:** para cada una de las 3 specs (001, 002, 003), genera un `tasks.md` **retroactivo**, usando `.specify/templates/tasks-template.md` como formato, con una regla estricta: **marca `[X]` únicamente lo que verificaste tú mismo en el código real** (no lo que "debería" estar). Para lo que esté implementado parcialmente o no implementado, dejalo como `[ ]` con una nota breve de qué falta. Usa como insumo los hallazgos de los Bloques B, C y D de este documento.

También actualiza el campo `Status:` en `specs/001-elevacion-premium/spec.md` y `specs/002-microexperiencias-ia/spec.md` de `Draft` a `Partially Implemented` (o el valor equivalente que exista en `.specify/templates/spec-template.md`), reflejando la realidad.

---

## BLOQUE B: Spec 001 (Elevación Premium) — gaps concretos

**Ya implementado (verificado):** animaciones GSAP con `ScrollTrigger` en `BaseLayout.astro`, estructura de Hero/TrustLayer/FAQ, SEO básico vía `SEO.astro`.

**No implementado (verificado por ausencia en el código):**
- El `spec.md` pide explícitamente un *"5to flujo genérico B2B en el chatbot"* y *"campos adicionales de lead (organización, urgencia)"*. Hoy `Chatbot.jsx` es un chat de texto libre sin flujos estructurados ni campos de lead capturados en ningún estado (`leadData` no existe en el componente).

**Corrección — añadir captura estructurada de lead en el Chatbot:**
En `src/components/islands/Chatbot.jsx`, añade un estado y una función de extracción simple (regex) que se ejecute sobre cada mensaje del usuario, para ir poblando un objeto de lead progresivamente:
```jsx
const [leadData, setLeadData] = useState({ email: null, telefono: null, organizacion: null, urgencia: null });

const extractLeadSignals = (text) => {
  const emailMatch = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
  const phoneMatch = text.match(/(\+?\d[\d\s-]{7,14}\d)/);

  setLeadData((prev) => ({
    ...prev,
    email: emailMatch ? emailMatch[0] : prev.email,
    telefono: phoneMatch ? phoneMatch[0] : prev.telefono,
  }));
};
```
Llama a `extractLeadSignals(text)` dentro de `handleUserSubmit`, justo antes del `fetch`. Este `leadData` es la base que el Bloque C (persistencia) y, más adelante, la Spec 008 (endpoint real) van a consumir — no lo dejes aislado sin persistir (ver Bloque C).

---

## BLOQUE C: Spec 002 (Microexperiencias IA) — gap crítico de negocio: CERO persistencia de leads

**Problema (regresión funcional respecto al propio `tech_debt.md` de la Spec 002):** el documento de deuda técnica de la Spec 002 describe el estado *anterior* como "los leads se guardan en `localStorage` bajo la clave `datanestiq_leads`" (subóptimo, pero algo). Al auditar el código real post-migración a Astro, **no existe ningún mecanismo de persistencia de leads, ni siquiera `localStorage`**. El `SYSTEM_PROMPT` de `Chatbot.jsx` le pide al usuario su email y celular dentro de la conversación, pero esos datos nunca se extraen, guardan ni envían a ningún lado — se pierden en cuanto el usuario cierra la pestaña. Esto es más grave que la deuda técnica original: hoy **no se captura ningún lead real**, mientras Spec 008 (que resolvería esto con un backend WordPress) sigue sin empezar.

**Corrección mínima inmediata (mientras no exista la Spec 008):** usando el `leadData` del Bloque B, persiste en `localStorage` como fallback temporal, igual que documentaba el `tech_debt.md` original:
```jsx
useEffect(() => {
  if (leadData.email || leadData.telefono) {
    const existing = JSON.parse(localStorage.getItem('datanestiq_leads') || '[]');
    const updated = [...existing.filter(l => l.session !== sessionIdRef.current), { ...leadData, session: sessionIdRef.current, timestamp: new Date().toISOString() }];
    localStorage.setItem('datanestiq_leads', JSON.stringify(updated));
  }
}, [leadData]);
```
(Declara `const sessionIdRef = useRef('session_' + Date.now())` una sola vez al montar el componente y reutilízalo en el `fetch` a `chat.php` en vez del `'session_' + Date.now()` inline actual, para que todos los mensajes de una misma conversación compartan `session_id`.)

Deja un comentario `// TODO(Spec 008): reemplazar localStorage por POST a /wp-json/datanestiq/v1/leads` para que quede trazable.

**Gap arquitectónico adicional — Transformers.js nunca implementado:** la Constitution (sección 4) exige explícitamente *"Edge/Local AI: Uso de Transformers.js en el cliente para tareas ligeras (clasificación rápida, embeddings pequeños, validaciones semánticas)"*. Se verificó que **no existe ninguna referencia** a `@xenova/transformers` ni `transformers.js` en `package.json` ni en `src/`. Toda la inferencia hoy pasa por la API remota de Groq (`chat.php`), sin ninguna capa local.

Decide y ejecuta **una** de estas dos opciones (no la dejes en ambigüedad):
1. **Implementar una primera versión mínima:** usar `@xenova/transformers` en el cliente para clasificar el `customSector`/desafío que el usuario escribe en `DiagnosticWizard.jsx` contra los 10 sectores de `sectorsCorpus` (similaridad de embeddings), evitando una llamada a Groq solo para decidir a qué sector pertenece un texto libre.
2. **Posponerlo explícitamente:** si por alcance/tiempo no se implementa ahora, agrega una entrada en la sección "Complexity Tracking" de `specs/002-microexperiencias-ia/plan.md` (crea esa sección si no existe, con el mismo formato que usa `specs/007-multi-pagina/plan.md`) justificando por qué se pospone y cuándo se retomará. **No lo dejes como una desviación silenciosa de la Constitution.**

---

## BLOQUE D: Spec 003 (Taxonomía) — "Resuelto" en el papel, no implementado en el código

**Problema:** `specs/003-taxonomia-servicios/tech_debt.md` marca el ítem 1 ("Hardcoding de Corpus") como **"✅ [RESUELTO - DISEÑADO]"**, con un diseño detallado de migración a Astro Content Collections (`src/content/pillars/*.yaml`, `src/content/sectors/*.yaml`, con schema Zod). En la realidad, la taxonomía sigue siendo el archivo plano `src/data/taxonomyCorpus.json` (que el Bloque 3 del prompt de la Spec 007 ya está ampliando a los 6 pilares oficiales). "Diseñado" no equivale a "Resuelto".

**Corrección:**
1. En `specs/003-taxonomia-servicios/tech_debt.md`, cambia el estado del ítem 1 de `✅ [RESUELTO - DISEÑADO]` a `⏳ [DISEÑO APROBADO — PENDIENTE DE IMPLEMENTACIÓN]`, dejando el diseño existente intacto como referencia.
2. Evalúa con el equipo (no lo decidas unilateralmente en código) si conviene migrar ya a Content Collections ahora que el corpus ya tiene los 6 pilares completos, dado que el equipo de Marketing necesitará editar contenido sin tocar JSON crudo. Si no se migra ahora, dejar la decisión y su razón documentada en el mismo `tech_debt.md`.

---

## BLOQUE E: Spec 004 (Metodología) — el pipeline de CI/CD está roto (referencia a un script inexistente)

**Problema:** `specs/004-metodologia-desarrollo-digital/tech_debt.md` marca como **"[RESUELTO]"** la automatización vía GitHub Actions. Se verificó `.github/workflows/langgraph_pipeline.yml`: el job ejecuta `python backend/langgraph_pipeline.py --mode=ci`, pero **la carpeta `backend/` no existe en el repositorio** — el script referenciado nunca fue creado. Cualquier disparo de este workflow (push a `specs/003-taxonomia-servicios/**` o `prompts/**`, o `workflow_dispatch` manual) **fallará en el paso "Run LangGraph Pipeline"**. Además, el paso final de commit (`git-auto-commit-action`) apunta a `file_pattern: 'docs/generated_copy/*.md index.html'` — `index.html` en la raíz ya no representa el sitio real (ahora es Astro, no el prototipo estático), por lo que ese target también está desactualizado.

**Corrección:**
1. Cambia el estado en `specs/004-metodologia-desarrollo-digital/tech_debt.md` de `[RESUELTO]` a `⚠️ [ROTO — script backend/langgraph_pipeline.py referenciado pero inexistente]`.
2. Decide con el usuario si este pipeline de generación automática de copy vía LangGraph sigue siendo una prioridad. Si sí: crea el andamiaje mínimo real en `backend/` (al menos un `langgraph_pipeline.py` funcional con `--mode=ci` y un `requirements.txt`) o, si no es prioridad ahora mismo, **deshabilita el workflow** (renómbralo a `.yml.disabled` o coméntalo) en vez de dejarlo activo y roto — un workflow roto que se dispara en cada push a `prompts/**` genera ruido y falsos negativos en CI.
3. Actualiza el `file_pattern` del commit final para que apunte a rutas reales del proyecto actual (por ejemplo `docs/generated_copy/*.md` solamente, quitando `index.html`).

---

## BLOQUE F: Spec 005 (OpenWiki) — validar que el pipeline automatizado corrió de verdad

**Problema:** `tasks.md` de la Spec 005 marca las 13 tareas como `[X]`, y se confirmó que existen `.github/workflows/openwiki-audit.yml`, `scripts/openwiki-local-sync.sh` y `.agents/AGENTS.md` con las reglas de jerarquía de specs y respeto a bloques `IGNORE`. Sin embargo, `src/content/openwiki/` **solo contiene `dummy.md`** — un placeholder, no documentación real generada por el agente.

**Corrección:**
1. Ejecuta manualmente `scripts/openwiki-local-sync.sh` en modo `--dry-run` y reporta el resultado real (¿corre sin errores? ¿qué genera?).
2. Si el workflow `openwiki-audit.yml` nunca se disparó en la práctica (revisa el historial de Actions si tienes acceso), dilo explícitamente en tu respuesta en vez de asumir que "estar configurado" equivale a "estar funcionando".
3. **Corrige `.agents/AGENTS.md`:** en la sección "Jerarquía y Fuente de la Verdad", la línea `**Spec 006 (Arquitectura WP):** Reglas técnicas para el despliegue y desarrollo en WordPress.` tiene el mismo error que la Constitution (Bloque de la Spec 007 ya corrigió esto en `constitution.md`, sección 6) — Spec 006 es "Ecosistema Astro", no WordPress. Corrige esa línea a:
   ```md
   - **Spec 006 (Ecosistema Astro):** Reglas técnicas de la arquitectura Astro SSG multi-página (frontend). La arquitectura WordPress Headless vive en la Spec 008.
   ```
   Y agrega una línea nueva para las specs 007 y 008 en esa misma jerarquía, con el mismo formato que las demás.

---

## BLOQUE G: Spec 006 (Ecosistema Astro) — limpieza marcada como hecha que no se hizo

**Problema:** `tasks.md` de la Spec 006, tarea T019: *"Eliminar los archivos residuales del prototipo anterior en `prototype/` para completar la migración"* — marcada `[X]`. Sin embargo, **la carpeta `prototype/` (`index.html`, `app.js`, `styles.css`) sigue existiendo en el repositorio**, y según el estado de git al momento de esta auditoría, esos archivos incluso tenían cambios sin commitear.

**Corrección — no elimines nada todavía, es una decisión que requiere confirmación humana:**
1. **No borres `prototype/` directamente.** Es una acción destructiva sobre archivos que podrían seguir siendo referencia histórica o estar en uso para comparación.
2. En su lugar, desmarca T019 en `specs/006-ecosistema-astro/tasks.md` (`[ ]`) y añade una nota: *"Pendiente: confirmar con el equipo si `prototype/` debe eliminarse o conservarse como archivo histórico antes de proceder."*
3. Reporta este hallazgo explícitamente en tu resumen final para que el usuario decida.

---

## BLOQUE H: Spec 008 (Headless WordPress) — no implementar directo: primero completa el pipeline SDD

**Problema:** de las 8 specs, la 008 es la única que **solo tiene `spec.md`** — no tiene `plan.md` ni `tasks.md`. Según la Constitution (sección 1), está terminantemente prohibido escribir código de implementación sin haber completado antes `/plan` y `/tasks`.

**Contexto verificado del WordPress actual:** la instalación en `wp-content/` es un WordPress estándar sin ninguna pieza de la arquitectura headless descrita en `spec.md`: los plugins presentes son genéricos (`akismet`, `contact-form-7`, `wordpress-seo` (Yoast), `click-to-chat-for-whatsapp`). No existe ningún Custom Post Type `datanestiq_lead`, ningún endpoint `/wp-json/datanestiq/v1/leads`, ninguna configuración CORS ni hardening de `.htaccess`. Es decir: la Spec 008 está en cero.

**Esta spec es, además, la que finalmente resuelve el gap crítico del Bloque C** (persistencia real de leads), así que priorízala en el backlog en cuanto termines los bloques anteriores.

**Instrucción — sigue el pipeline formal, no saltes pasos:**
1. Redacta `specs/008-headless-wordpress/plan.md` siguiendo exactamente el formato de `specs/007-multi-pagina/plan.md` (Summary, Technical Context, Constitution Check, Project Structure, Complexity Tracking). Technical Context debe indicar: Language/Version PHP 8.x + WordPress actual instalado, Primary Dependencies (ACF, WPGraphQL o REST API nativa — decide y justifica cuál), Storage (MySQL vía `wp_posts`/CPT), Target Platform (Apache/XAMPP local → hosting de producción), Constraints (cero exposición del frontend público de WP, ver FR-03 de spec.md).
2. Redacta `specs/008-headless-wordpress/tasks.md` desglosando las 3 User Stories de `spec.md` (US-01 Lead Aggregation, US-02 Public Interface Hardening, US-03 Decoupled Content Publishing) en tareas concretas por fase, igual que `specs/007-multi-pagina/tasks.md`.
3. **Recién después** de tener ambos documentos, comienza la implementación: CPT `datanestiq_lead` + campos ACF, endpoint REST `POST /wp-json/datanestiq/v1/leads` con sanitización server-side, reglas `.htaccess` para bloquear `/wp-admin` y `/wp-login.php` al tráfico público no autenticado.
4. Una vez exista el endpoint real, vuelve al Bloque C de este documento y reemplaza el `localStorage` temporal por un `fetch POST` real hacia `/wp-json/datanestiq/v1/leads` desde `Chatbot.jsx`.

---

## Instrucciones de ejecución

1. Aplica el BLOQUE A primero (retroactivo de `tasks.md` para specs 001/002/003) — esto te da la base de qué está realmente hecho antes de tocar código.
2. Aplica BLOQUE B y BLOQUE C (Chatbot: campos de lead + persistencia). Verifica manualmente en el navegador que, tras escribir un email en el chat, aparezca en `localStorage.getItem('datanestiq_leads')`.
3. Aplica BLOQUE D y BLOQUE E (correcciones de estado en tech_debt.md, decisión sobre el workflow roto).
4. Aplica BLOQUE F (validar OpenWiki + corregir AGENTS.md).
5. Aplica BLOQUE G — **sin borrar nada**, solo desmarca la tarea y reporta.
6. Aplica BLOQUE H — genera `plan.md` y `tasks.md` de la Spec 008; no implementes el backend WordPress todavía si el usuario no lo ha confirmado explícitamente (es un cambio de infraestructura de mayor envergadura que amerita luz verde separada).
7. Haz commit de los cambios de documentación y código con el mensaje: `fix: sincroniza SDD (tasks.md/tech_debt.md) con estado real del codigo y agrega captura minima de leads`.

## Forma de respuesta

- Reporta bloque por bloque qué verificaste y qué cambiaste.
- En el BLOQUE H, entrega `plan.md` y `tasks.md` para revisión, pero **no toques ningún archivo dentro de `wp-admin/`, `wp-includes/` ni el core de WordPress** sin confirmación explícita adicional del usuario — esto excede el alcance de "auditoría y correcciones" y pasa a ser una feature nueva de infraestructura.
- Si algo en este documento ya no coincide con el código (por ejemplo, si el Bloque de la Spec 007 ya se ejecutó y cambió algo referenciado aquí), indícalo en vez de improvisar.
- Sección final obligatoria: **"Hallazgos adicionales"** con cualquier cosa fuera de alcance que detectes.

---

**Nota:** Claude (Sonnet 5) auditará este trabajo junto con el de `prompt-antigravity-auditoria-spec007-correcciones.md` una vez completado.
