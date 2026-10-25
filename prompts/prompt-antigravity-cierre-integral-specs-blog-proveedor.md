# Prompt para Antigravity: Cierre Integral — Higiene SDD de las 8 Specs + Blog en Astro + Proveedor LLM configurable

Actúa como **auditor SDD, arquitecto de contenido Astro y backend PHP**.

## Contexto y decisiones tomadas (por el usuario)
Tras múltiples ciclos, la documentación SDD quedó desincronizada del código y hay features que cerrar. Claude (Sonnet 5) auditó el estado real. El usuario decidió:
- **Construir un Blog** en Astro (markdown→HTML estático).
- **Proveedor del tier económico configurable** (DeepSeek / Alibaba-Qwen / cualquier OpenAI-compatible vía `.env`), no hardcodear DeepSeek.
- **LangGraph:** documentar y posponer (no construir).
- **Resto de mejoras:** registrarlas como backlog en `tech_debt.md`, no construirlas en este ciclo.

Este prompt tiene 4 partes: **A** (cross-cutting), **B** (higiene SDD por spec), **C** (Blog — código), **D** (proveedor configurable — código). Ejecuta en orden. Verifica cada afirmación documental contra el código antes de escribirla.

---

## PARTE A — Cross-cutting (primero)

**A1 — Carpeta 008 partida.** Existen `specs/008-headless-wordpress/` (spec+tech_debt) y `specs/008-headless-wp/` (tasks.md huérfano). Consolida en `specs/008-headless-wordpress/`: mueve el tasks.md ahí (si es placeholder inservible, reemplázalo por uno real derivado del spec.md), elimina la carpeta `008-headless-wp/`.

**A2 — Scripts stub en `package.json`.** `lint:strict`, `security:scan`, `openwiki:audit`, `audit:lighthouse` son `echo "...OK"` (no validan nada real). Documenta esta deuda en el `tech_debt.md` de la Spec 006 ("scripts de verificación son stubs; los SC que dependen de ellos —Lighthouse 100/100, lint, security— no tienen validación automática real"). No los implementes; solo regístralo y anota en las specs afectadas "(pendiente de validación automática real)".

**A3 — Credenciales en claro.** `remote_extract.py` (raíz) tiene host/usuario/contraseña SSH de producción en texto plano. Documenta como deuda de seguridad CRÍTICA en el `tech_debt.md` de la Spec 008. Recomendación: rotar contraseña y migrar a `.env` (ya modelado en `.env.example`). No borres el archivo sin confirmación.

---

## PARTE B — Higiene SDD por spec (documentación)

Para cada spec: corrige el header `## Estado` si está obsoleto; reconcilia `tech_debt.md` y `tasks.md` con la realidad; registra el backlog de mejoras. **Solo documentación, no código.**

### Spec 001 — Elevación Premium
- Header obsoleto ("Prototipo... Preparación para Migración") → "Implementado en Astro (producción)". Reencuadra las 3 deudas (ya resueltas por la migración: Tailwind compilado, scoping nativo, `@layer components`).
- Reevalúa el `[ ]` "5to flujo genérico B2B en chatbot": la máquina de estados con salida "Otro/Escribir libremente"→LLM ya lo cubre; márcalo `[x]` con nota o especifica qué falta.
- **Backlog a registrar:** componentes reutilizables `Button.astro`/`Card.astro` (mencionados pero nunca creados); View Transitions API para navegación fluida; validación Lighthouse real.

### Spec 002 — Microexperiencias IA
- Header "Prototipo" → "Implementado en Astro, verificado E2E".
- **RECONCILIAR CONTRADICCIÓN:** el `tasks.md` aún lista como `[ ] NO implementado`: Copilot Demo, Buscador Semántico (Transformers.js), Wizard multi-paso, Badge Contexto Heredado, LLM Router/DeepSeek. **Todo eso YA se implementó.** Márcalos `[x]` con el archivo real (`CopilotDemo.jsx`, `SemanticSearch.jsx`+`worker.js` con modelo cuantizado q8, `MultiStepWizard.jsx`, `chat.php`). Verifica cada uno antes de marcar.
- **Backlog a registrar (deuda baja/pulido):** (a) umbral del buscador semántico poco selectivo (resaltó 5/6 servicios) — afinar `threshold` en `SemanticSearch.jsx`; (b) resaltado visual de tarjetas de SECTOR vía store `semanticHighlight` no visible — revisar suscripción en `DiagnosticWizard.jsx`; (c) sin failover real de proveedor (parcialmente resuelto en Parte D); (d) respuestas del chatbot no usan streaming (SSE) — mejora futura de UX.
- **Desviación a documentar:** FR-004 pedía Formspree/n8n; se usó CSV soberano + batch (destino final: endpoint WP, Spec 008).

### Spec 003 — Taxonomía
- Deuda #1 (migración a Content Collections) sigue PENDIENTE — mantén, pero corrige path: `src/data/taxonomyCorpus.json` (no `prototype/api/...`).
- **Backlog a registrar:** (a) migración a Content Collections (Zod + YAML por pilar/sector + endpoint agregador) — candidato a ciclo dedicado; (b) **páginas de sector `/sectores/[id]`**: hoy solo existen las 6 de pilares; generar landing por cada uno de los 10 sectores daría cobertura SEO long-tail; (c) iconos/imágenes dedicadas por servicio.

### Spec 004 — Metodología
- Corrige header contradictorio ("Severidad: Resuelta" vs deuda "ROTO") → "Parcial / deuda abierta".
- **LangGraph:** documenta la decisión tomada — POSPUESTO. Registra en tech_debt: el workflow sigue `.disabled`; su único rol sería el pipeline offline de generación de copy (NO el chatbot en vivo, que ya usa la máquina de estados React+PHP, superior en costo/latencia). Dos caminos futuros: construir `backend/langgraph_pipeline.py` real, o abandonar. Sin acción de código ahora.
- **Backlog a registrar:** agentes de copy 01 (blueprint), 03 (conversión), 07 (mobile optimizer), 08 (website copy system) no ejecutados formalmente — polish, prioridad baja.

### Spec 005 — OpenWiki (rellenar tech_debt vacío)
- Estado real: "Andamiaje implementado, SIN operación verificada". Registra deudas reales: (a) contenido es solo `dummy.md`, sin documentación viva real; (b) el workflow `openwiki-audit.yml` depende de `npm install -g openwiki` — **paquete/CLI de existencia y funcionalidad NO verificadas** (probablemente no hace lo que el YAML asume); (c) usa un ID de modelo obsoleto (`claude-3-5-sonnet-20240620`) y un secret `ANTHROPIC_API_KEY` no confirmado; (d) nunca se ejecutó end-to-end. **Conclusión a documentar: la "documentación viva automática" NO está operativa hoy.** Backlog: validar o reemplazar el motor OpenWiki por un script real; actualizar el modelo; generar documentación inicial real.

### Spec 006 — Ecosistema Astro (rellenar tech_debt vacío)
- Header → "Implementado en Astro (producción), E2E verificado".
- Registra: (RESUELTO) inconsistencia de rutas assets-root vs API-subpath → resuelta con `src/lib/endpoints.js` root-relative + proxy Vite + `DEPLOY.md`, verificado E2E (chat.php 200 vía proxy, worker 200, buscador semántico funcional). (DECISIÓN CERRADA) T019 eliminación de `prototype/` cancelada (se preserva como histórico con README) — ciérrala, no la dejes pendiente perpetua. (DEUDA ref A2) scripts de verificación stub. (BACKLOG) optimización de imágenes con `astro:assets`.

### Spec 007 — Multi-Página
- Header "Pendiente de Implementación" → "Implementado (6 páginas SSG, verificado)". Las 2 deudas ya están resueltas.
- **Backlog a registrar:** SC "Lighthouse 100/100" nunca validado de verdad (ref A2); breadcrumb schema y cross-linking de servicios relacionados; páginas de sector (ref Spec 003).

### Spec 008 — Headless WordPress (consolidar y rellenar)
- Tras A1, rellena el tech_debt: NO tiene `plan.md`; WP local es estándar (akismet, contact-form-7, yoast) sin CPT `datanestiq_lead`, sin endpoint `/wp-json/datanestiq/v1/leads`, sin hardening. El CSV soberano actual es el sink temporal hasta que exista este backend. Estado: "En pausa por decisión del usuario hasta validar el frontend (ya validado E2E)". Incluye la deuda A3. **Ángulo a registrar:** el headless WP podría servir como **UI de edición para el Blog** (Parte C) — editores no técnicos escriben en WP, el contenido alimenta las colecciones Astro. Próximo hito: redactar `plan.md`+`tasks.md`.

---

## PARTE C — Construir el Blog (Astro Content Collections, markdown→HTML estático)

Objetivo: blog estático de alto rendimiento (SEO/Lighthouse), coherente con el sistema de diseño y tono premium B2B de la Constitution.

### C1 — Colección `blog`
En `src/content.config.ts`, añade (sin tocar `openwiki`):
```ts
const blogCollection = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/blog" }),
  schema: z.object({
    title: z.string().max(120),
    description: z.string().max(200),
    pubDate: z.date(),
    author: z.string().default('Datanestiq'),
    tags: z.array(z.string()).default([]),
    draft: z.boolean().default(false)
  })
});
export const collections = { 'openwiki': openwikiCollection, 'blog': blogCollection };
```

### C2 — Posts de ejemplo
Crea `src/content/blog/` con **2 posts** en markdown, con tono consultivo C-level (nada de clichés, alineado a la Constitution). Temas sugeridos: "Por qué el 70% de las iniciativas de IA corporativa no llegan a producción (y cómo evitarlo)" y "Data Mesh vs. Data Lakehouse: qué arquitectura elige un CDO en 2026". Frontmatter completo (title, description, pubDate, author, tags). Márcalos como contenido editorial de la firma (no inventes datos/clientes reales como verificados).

### C3 — Listado `src/pages/blog/index.astro`
- Usa `BaseLayout` + `Navbar` + `Footer`. Lee `getCollection('blog')`, **filtra `draft: true`**, ordena por `pubDate` desc.
- Grid de tarjetas (reutiliza `.glass-card`), cada una enlaza a `/blog/[slug]`, muestra título, fecha, tags, descripción.
- SEO: title/description propios vía `SEO.astro`.

### C4 — Post `src/pages/blog/[slug].astro`
- `getStaticPaths()` sobre `getCollection('blog')` (filtra drafts). `render(entry)` para el `<Content />`.
- Tipografía de lectura (`prose prose-invert`), fecha, autor, tags, y un CTA final al chatbot/diagnóstico.
- SEO por post (title/description del frontmatter) + **JSON-LD `BlogPosting`** (headline, datePublished, author, publisher Datanestiq) — la Constitution exige Schema.org.

### C5 — Navegación
Añade enlace **"Blog"** en `Navbar.astro` (desktop + mobile) y en `Footer.astro`. El sitemap (`@astrojs/sitemap`) los incluye automáticamente en el build.

### C6 — Verificación
`npm run build` debe generar `dist/blog/index.html` y un `dist/blog/<slug>/index.html` por post no-draft. Confirma que un post con `draft: true` NO se genera.

---

## PARTE D — Proveedor LLM del tier económico configurable (OpenAI-compatible)

En `public/api/chat.php`, generaliza el tier económico para soportar DeepSeek, Alibaba-Qwen o cualquier endpoint OpenAI-compatible, sin hardcodear:
1. Introduce variables `.env` genéricas para el tier barato: `CHEAP_LLM_BASE_URL`, `CHEAP_LLM_API_KEY`, `CHEAP_LLM_MODEL`. Si están definidas, el `callLLM` del tier barato las usa. Mantén **retrocompatibilidad**: si no están pero existe `DEEPSEEK_API_KEY`, usa los defaults de DeepSeek (base `https://api.deepseek.com/v1/chat/completions`, modelo `deepseek-chat`).
2. Si ninguna está configurada, el tier barato cae a Groq `llama-3.1-8b-instant` (como hoy).
3. **Failover real (aborda la duda de "fallback"):** si la llamada al proveedor barato devuelve un error (no-2xx), reintenta **una vez** con Groq `llama-3.1-8b-instant` antes de devolver error. Registra el failover en `alerts.jsonl`. El tier pesado (Groq-70B) sigue igual.
4. En `.env.example`, documenta ambos proveedores con ejemplos comentados:
   - DeepSeek: `CHEAP_LLM_BASE_URL=https://api.deepseek.com/v1/chat/completions`, `CHEAP_LLM_MODEL=deepseek-chat`.
   - Alibaba Qwen (DashScope, OpenAI-compatible): `CHEAP_LLM_BASE_URL=https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions`, `CHEAP_LLM_MODEL=qwen-plus` (o `qwen-turbo`).
5. NO pongas ninguna key real en el código ni en `.env.example`.

---

## Instrucciones de ejecución
1. Parte A → B (documentación; verifica contra el código) → C (blog) → D (proveedor).
2. `npm run build` tras C y D (deben salir 10 + páginas de blog, sin errores).
3. Verifica el blog en `npm run dev` (listado y un post renderizan; draft oculto). Verifica que `chat.php` sigue devolviendo 200 con la config por defecto (Parte D no debe romper el flujo actual).
4. Entrega un `planes/ESTADO-SPECS.md` con tabla: Spec | Fase real | Deuda abierta | Próximo hito.
5. Commit: `feat+docs: higiene SDD de las 8 specs, blog en Astro y proveedor LLM economico configurable con failover`.

## Forma de respuesta
- Reporta parte por parte, con verificación real (grep para documentación, Network/build para blog y proveedor).
- Lista las contradicciones documentales que reconciliaste.
- No implementes el resto del backlog (fixes semánticos, sector pages, LangGraph, Content Collections) — solo regístralo.
- No toques el core de WordPress. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el tech_debt refleje la realidad verificable (sin optimismo ni contradicciones), y probará el blog + el proveedor configurable en caliente.
