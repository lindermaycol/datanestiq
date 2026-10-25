# Prompt para Antigravity — Fixes de la auditoría UX por personas (home)

Extiende **Spec 002 (microexperiencias)** y **Spec 013 (A9 — adaptación por contexto)**. No es spec nueva. Respeta la **Constitución** ([`.specify/memory/constitution.md`](../.specify/memory/constitution.md)) y `AGENTS.md`. Doc-sync a `planes/ESTADO-SPECS.md` (§11). Puedes entregar plan primero para mi revisión.

Origen: una auditoría UX por personas (CFO/CIO/CDO/CEO) confirmó que las mejoras del home funcionan, y encontró estos issues. Prioridad de arriba a abajo.

## 🔴 EXCLUSIÓN de honestidad (leer primero)
La auditoría recomienda *"agregar casos ROI anonimizados o ficticios de sector público / CDO"*. **NO lo hagas.** Prohibido fabricar casos/testimonios/clientes (Constitución §2). La credibilidad se construye con capacidades reales y modelos `[EST]`. Este ítem queda **fuera de alcance**.

---

## 🔴 Fix 1 (quick win) — Markdown crudo en el Copiloto (F-01)
**Diagnóstico:** en `src/components/islands/CopilotDemo.jsx`, `TypewriterText` solo parsea `**negrita**` y `\n` — los **links `[texto](url)` salen crudos** en el output. Irónico para una demo de IA.
**Fix:** extiende el render de `TypewriterText` para convertir markdown a HTML igual que el chatbot: **links `[texto](url)` → `<a href="…" …>`** y listas numeradas/viñetas si aparecen. **Reutiliza el patrón de `formatText` del `Chatbot.jsx`** (mismo regex de links + escape anti-XSS de `<`/`>` **antes** de inyectar). Verifica con un output que tenga links a `/soluciones/…`.

## 🔴 Fix 2 (quick win) — Feedback del buscador en el cold-start (F-02)
**Diagnóstico:** la **primera** búsqueda tarda ~23s porque descarga el modelo de embeddings (Edge AI, transformers.js). No se puede eliminar la descarga, pero hoy el feedback es pobre.
**Fix (mejorar la percepción, no la descarga):**
- **Skeleton loader** en el área de resultados mientras carga/busca (3 tarjetas placeholder animadas).
- **Progreso prominente** durante la carga del modelo: mensaje claro *"Cargando el modelo de IA por primera vez… X%"* (el estado `loading_model` ya tiene el %; hazlo visible y explícito de que es una-sola-vez).
- Que en búsquedas siguientes (modelo cacheado) el feedback sea inmediato.

## 🟠 Fix 3 (conversión) — El chatbot hereda el chip activo (F-03)
**Diagnóstico:** `Chatbot.jsx` importa solo `lastUserQuery`, **no `userContext`**. Si el usuario eligió un chip (ej. CEO) y abre el chatbot, arranca de cero preguntando el sector.
**Fix:** que el chatbot lea `userContext` (rol/sector) y **pre-cargue el estado guiado** saltando los pasos ya conocidos (si hay sector → salta a rol; si hay rol → salta a problema), sin romper el **0-LLM** (es lectura de store + set de estado local, sin `fetch`). Si no hay contexto, comportamiento actual.

## 🟠 Fix 4 (UX menor) — Pre-llenado del formulario de lead (F-04)
Mapea lo que el usuario ya dijo (sector/rol/problema/consulta) a los campos del formulario de lead (`organizacion`/`reto`/`stack`) como valores por defecto **editables** (mismo criterio que el prefill de sectores: inicializa una vez, editable/borrable). Reutiliza el pipeline seguro (`save_wizard.php`), sin PII en claro.

## Fix 5 (personalización) — Hero más fuerte por rol, para los 4 roles (F-05)
**Contexto:** hoy `HeroRoleLine` muestra una micro-línea (`goals[0]`). La auditoría pide personalización más notoria para el buyer top. **Verificado:** el chip CEO **sí** activa la micro-línea (`ceo.goals[0]='crecimiento'`) — no es que "no cambie"; es que es sutil.
**Fix (honesto, desde taxonomía):** refuerza `HeroRoleLine` a un bloque de valor por rol más sustancial pero elegante — p. ej. la **meta del rol + su primer `decisionCriteria`** en una línea destacada, aplicado **consistentemente a los 4 roles** (CFO/CIO/CDO/CEO). Todo derivado de `personas.json`; **no inventes** copy ni promesas. Progressive enhancement (sin rol, nada).

## Fix 6 (chips) — Entrada para CDO + coherencia (F-06)
**Diagnóstico/tensión de diseño:** hoy hay 3 chips que **mezclan sector y rol** ("Sector Público" = rol `cdo`+sector `publico`; "Finanzas / CFO"; "Estrategia / CEO"). Un **CDO de finanzas** no tiene entrada. 
**Fix:** añade una entrada para el **CDO orientado a datos** (ej. un 4º chip "Datos / CDO" → `handleSelect('finanzas','cdo')` o sector neutro). **Nota para mi revisión:** si crees que lo más limpio es **relabelar los chips a rol-consistentes** (en vez de mezclar sector+rol), propónlo en el plan — es una decisión de diseño que quiero revisar antes de tocar.

---

## Guardarraíles (Constitución)
- **0-LLM** del flujo guiado intacto (herencia de chip = lectura de store + estado local, sin `fetch`).
- **Honestidad §2:** cero casos/testimonios inventados (ver EXCLUSIÓN); microcopy y personalización derivan de la **taxonomía**.
- Taxonomía = fuente de verdad; progressive enhancement; `npm run build` verde; consola limpia; PII segura.

## Verificación (evidencia real)
1. **Copiloto:** un output con links `[texto](/soluciones/…)` se ve como **hipervínculos clicables**, no crudos.
2. **Buscador:** primera búsqueda muestra **skeleton + progreso "cargando modelo (primera vez) X%"**; búsquedas siguientes, inmediatas.
3. **Chatbot hereda chip:** con chip CEO activo, abrir el chatbot **salta** el/los pasos ya conocidos; Network del flujo guiado = **cero `chat.php`**.
4. **Formulario:** campos pre-llenados con lo dicho, **editables**.
5. **Hero por rol:** los 4 roles muestran el bloque reforzado (de taxonomía); sin rol, nada.
6. **Chips:** existe entrada para CDO; (si aplica) propuesta de relabel en el plan.
7. `npm run build` verde; consola limpia; `ESTADO-SPECS.md` actualizado. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: links del copiloto clicables, feedback del buscador, herencia de chip en el chatbot (0-LLM), pre-llenado editable, hero por rol desde taxonomía, y **cero contenido fabricado**.
