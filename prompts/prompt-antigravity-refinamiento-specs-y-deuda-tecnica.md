# Prompt para Antigravity: Refinamiento SDD de las 8 Specs + reconciliación de Deuda Técnica con la realidad del código

Actúa como **auditor de metodología SDD (Spec-Kit) y arquitecto de documentación técnica**.

## Objetivo

Tras múltiples ciclos de implementación (recuperación de microexperiencias, arquitectura conversacional, gobernanza de costos, fix de rutas), la documentación SDD quedó **desincronizada de la realidad del código**. Claude (Sonnet 5) auditó el estado real y detectó headers de estado obsoletos, `tech_debt.md` vacíos donde ya hay deuda real, un `tasks.md` que se contradice con su propio `tech_debt.md`, y dos inconsistencias estructurales. Este prompt hace una **pasada de higiene documental** spec por spec.

**Regla de oro:** este ciclo es **solo documentación** — NO implementes features. Donde haya pendiente de implementación, **regístralo con precisión** en el `tech_debt.md` correspondiente (estado, riesgo, decisión), pero no lo construyas. Verifica cada afirmación contra el código antes de escribirla (no marques "resuelto" lo que no verificaste).

---

## CROSS-CUTTING (resolver primero)

### C1 — Inconsistencia de carpeta en Spec 008
Existen DOS carpetas: `specs/008-headless-wordpress/` (con `spec.md` + `tech_debt.md`) y `specs/008-headless-wp/` (con un `tasks.md` huérfano). El `tasks.md` está desconectado de su spec.
- **Acción:** consolida todo en `specs/008-headless-wordpress/`. Mueve `specs/008-headless-wp/tasks.md` a `specs/008-headless-wordpress/tasks.md` (revisa su contenido; si es un placeholder inservible, reemplázalo por un tasks.md real derivado del `spec.md` de 008). Elimina la carpeta `specs/008-headless-wp/` vacía.

### C2 — Scripts de verificación son stubs falsos en `package.json`
`lint:strict`, `security:scan`, `openwiki:audit` y `audit:lighthouse` son `echo "... OK"` — no ejecutan nada real. Esto significa que TODO "criterio de éxito" que dependió de ellos (ej. Lighthouse 100/100 de Spec 007, security scan de hooks) **nunca se validó de verdad**.
- **Acción:** NO los implementes ahora, pero **documenta** esta deuda en el `tech_debt.md` de la Spec 006 (Ecosistema Astro), como "Deuda de Tooling: scripts de verificación son stubs; los SC que dependen de ellos (Lighthouse, lint, security) están sin validación automática real". Marca los criterios de éxito afectados en las specs correspondientes con una nota "(pendiente de validación automática real)".

### C3 — Credenciales en texto plano en `remote_extract.py`
`remote_extract.py` (raíz) contiene host SSH, usuario y contraseña de producción en claro (aunque está en `.gitignore`).
- **Acción:** documenta como deuda de seguridad **crítica** en el `tech_debt.md` de la Spec 008 (es infraestructura/deploy) o crea una nota de seguridad. Recomendación registrada: rotar la contraseña y migrar a `.env` (ya modelado en `.env.example`). No borres el archivo sin confirmación del usuario.

---

## Por Spec

Para CADA spec: (1) corrige el header `## Estado` si está obsoleto (fase real: la mayoría ya está en "Producción/Astro", no "Prototipo/Pendiente"); (2) reconcilia el `tech_debt.md`; (3) registra los pendientes reales.

### Spec 001 — Elevación Premium
- **Header obsoleto:** dice "Prototipo (HTML/Tailwind/Vanilla JS) -> Preparación para Migración a Astro". Actualiza a fase real: "Implementado en Astro (producción)".
- Las 3 deudas (CSS hardcodeado, CSS monolítico, Tailwind CDN) están resueltas por la migración a Astro — pero están redactadas en marco "prototipo". Reencuádralas: en Astro ya se usa Tailwind compilado, scoping nativo y `@layer components` en `global.css`. Confírmalo y actualiza el texto.
- **Pendiente real a registrar:** `tasks.md` lista "5to flujo genérico B2B en el chatbot" como `[ ]`. Reevalúa: la máquina de estados actual (con la salida "Otro / Escribir libremente" → LLM) ya cubre el caso genérico B2B. Si lo consideras cubierto, márcalo `[x]` con nota; si falta algo concreto, especifícalo. No lo dejes ambiguo.

### Spec 002 — Microexperiencias IA
- **Header obsoleto:** "Fase actual: Prototipo" → "Implementado en Astro (producción), verificado E2E".
- **CONTRADICCIÓN CRÍTICA:** el `tech_debt.md` marca todo como RESUELTO (Transformers.js, Copilot, Wizard, gobernanza), pero el `tasks.md` todavía lista como `[ ] NO implementado`: Copilot Demo, Buscador Semántico, Wizard multi-paso, Badge Contexto Heredado, LLM Router/DeepSeek. **Esto ya SE implementó** (ciclos de recuperación y gobernanza). Reconcilia: marca esos ítems `[x]` en `tasks.md` con referencia al archivo real (`CopilotDemo.jsx`, `SemanticSearch.jsx`+`worker.js`, `MultiStepWizard.jsx`, `chat.php`). Verifica cada uno en el código antes de marcarlo.
- **Deudas menores nuevas a registrar** (detectadas en verificación E2E): (a) el umbral del buscador semántico es poco selectivo (resaltó 5/6 servicios en una prueba) — afinar `threshold`; (b) el resaltado visual de las tarjetas de SECTOR vía store `semanticHighlight` no es visible aunque el store se setea — revisar la suscripción en `DiagnosticWizard.jsx`. Regístralas como deuda "Baja / pulido de UX".
- **Desviación a documentar:** FR-004 pedía Formspree/n8n; se implementó CSV soberano (`save_wizard.php` → `secure_leads/leads_wizard.csv`) + batch. Documenta la desviación y que el destino final será el endpoint WP (Spec 008).

### Spec 003 — Taxonomía de Servicios
- La deuda #1 (migración a Astro Content Collections) sigue **correctamente marcada como PENDIENTE**. Mantenla, pero corrige el path obsoleto: ya no es `prototype/api/taxonomyCorpus.json` sino `src/data/taxonomyCorpus.json`.
- **Pendiente real:** la migración a Content Collections (Zod schema, archivos YAML por pilar/sector, endpoint agregador) es el único pendiente sustantivo de 003. Regístralo con claridad como "candidato a ciclo dedicado". Añade nota: el corpus ya tiene los 6 pilares oficiales completos, así que la migración es de arquitectura de contenido, no de datos faltantes.

### Spec 004 — Metodología / Fábrica de Agentes
- **Contradicción de header:** "Severidad: Resuelta" pero la deuda #1 dice "ROTO — desactivado". Corrige el header a "Parcial / con deuda abierta".
- **Pendientes reales a registrar** (dos):
  1. El pipeline LangGraph: `backend/langgraph_pipeline.py` no existe y el workflow está `.disabled`. Decisión pendiente del usuario: construir el andamiaje Python real o abandonar formalmente. Documenta ambas opciones.
  2. **Ejecución ordenada de los 10 agentes de copy:** se aplicaron los agentes 02, 04, 05, 06, 09, 10 (Hero, Portfolio/Casos, Servicios, Nosotros, Trust, FAQ). Los agentes **01 (blueprint), 03 (conversión), 07 (mobile optimizer), 08 (website copy system)** no se ejecutaron formalmente. Regístralo como deuda "polish de copy, prioridad baja".

### Spec 005 — OpenWiki
- **`tech_debt.md` VACÍO (placeholder) pero `tasks.md` dice 13/13 completadas.** Contradicción: la implementación existe (workflow `openwiki-audit.yml`, `scripts/openwiki-local-sync.sh`, `AGENTS.md`), pero el contenido de `src/content/openwiki/` es solo `dummy.md` y **el workflow nunca se verificó ejecutándose de verdad** (depende de `secrets.ANTHROPIC_API_KEY`). Rellena el `tech_debt.md` con deudas reales: (a) contenido placeholder, sin documentación viva real generada; (b) workflow sin verificación de ejecución end-to-end; (c) dependencia de secret no confirmado. Marca la fase como "Andamiaje implementado, sin operación verificada".

### Spec 006 — Ecosistema Astro
- **`tech_debt.md` VACÍO pero 18/19 tareas hechas.** Rellénalo con las deudas reales resueltas y pendientes:
  - (RESUELTO) Inconsistencia de rutas assets-root vs API-subpath: resuelta con `src/lib/endpoints.js` (root-relative) + proxy Vite + `DEPLOY.md`. Verificado E2E (chat.php 200 vía proxy, worker 200, buscador semántico funcional).
  - (DECISIÓN CERRADA) T019: eliminación de `prototype/` — cancelada, se preserva como archivo histórico (con `prototype/README.md`). Cierra la tarea como decisión tomada, no como pendiente perpetuo.
  - (DEUDA, ref C2) scripts de verificación stub.
- Header: "Implementado en Astro (producción), E2E verificado".

### Spec 007 — Multi-Página
- **Header obsoleto:** "Fase actual: ⏳ Pendiente de Implementación" → "Implementado (6 páginas SSG generadas, verificado)". Las 2 deudas ya están resueltas correctamente.
- **Pendiente real a registrar:** el SC "Lighthouse 100/100 en cada URL" nunca se validó de verdad (script stub, ver C2). Regístralo como deuda de validación.

### Spec 008 — Headless WordPress
- Consolida la carpeta (ver C1).
- **`tech_debt.md` VACÍO** — es la spec con más pendiente. Regístrala con precisión: NO tiene `plan.md`; el WordPress local es estándar (plugins genéricos: akismet, contact-form-7, yoast) sin CPT `datanestiq_lead`, sin endpoint `/wp-json/datanestiq/v1/leads`, sin hardening. Todo el CSV soberano actual (`secure_leads/*.csv`) es el sink temporal **hasta** que exista este backend. Estado: "En pausa por decisión del usuario hasta validar el frontend (ya validado E2E)". Incluye la deuda de seguridad C3 (remote_extract.py).
- **Pendiente:** redactar `plan.md` + `tasks.md` (pipeline SDD) antes de implementar. Regístralo como el próximo gran hito cuando se reactive.

---

## Instrucciones de ejecución
1. Resuelve C1, C2, C3 primero.
2. Recorre las 8 specs, actualizando header + `tech_debt.md` + reconciliando `tasks.md`. **Verifica cada afirmación contra el código** (grep/lectura) antes de escribirla.
3. NO implementes ninguna feature. Este ciclo es documentación pura.
4. Al terminar, entrega un **resumen ejecutivo** (puede ir en `planes/ROADMAP.md` o un nuevo `planes/ESTADO-SPECS.md`) con una tabla: Spec | Fase real | Deuda abierta | Próximo hito.
5. Commit: `docs: refina las 8 specs y reconcilia tech_debt/tasks con el estado real del codigo (higiene SDD)`.

## Forma de respuesta
- Reporta spec por spec qué corregiste y qué verificaste en el código.
- Lista explícita de contradicciones encontradas y cómo las reconciliaste.
- Sección final "Hallazgos adicionales".
- No modifiques código de `src/`, `public/api/`, ni el core WP — solo `specs/`, `planes/`, `package.json` (si acaso comentarios), y mover/consolidar carpetas de specs.

---

**Nota:** Claude (Sonnet 5) auditará que el `tech_debt.md` de cada spec refleje la realidad verificable del código, sin optimismo ni contradicciones.
