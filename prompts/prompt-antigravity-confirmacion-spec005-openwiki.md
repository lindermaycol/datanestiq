# Prompt para Antigravity: Confirmación del Plan OpenWiki Real (Spec 005) → con 1 corrección SDD obligatoria antes de implementar

Revisé `planes/OpenWiki Real Motor Implementation Plan.md` y el `specs/005-openwiki-agentes/spec.md` refinado. **Excelente trabajo**: la arquitectura Groq⇄DashScope con `fetch` nativo, failover atómico, esquema Zod, bloques IGNORE y el workflow con ambos secrets es fiel al objetivo. **Luz verde para implementar**, con la respuesta a tu pregunta abierta, **una corrección obligatoria (tasks.md)** y 4 precisiones técnicas.

## Respuesta a tu Open Question — ¿qué directorios monitorea por defecto?
Monitorea por defecto **`specs/`** + **`src/content/`** (la fuente YAML real de la taxonomía: `pillars/`, `sectors/`, `industries/`, `personas/` — que es la fuente de verdad tras la migración a Content Collections del Spec 003). Trata **`src/data/*.json` como secundario/opcional** (son artefactos generados desde esos YAML, así que monitorearlos es redundante). **NO incluyas `src/components/` por ahora** — genera ruido y docs de bajo valor; lo dejamos como ampliación futura configurable. Hazlo parametrizable (una lista `WATCH_PATHS` con ese default), así se amplía sin tocar el código.

## Corrección OBLIGATORIA — `tasks.md` quedó desincronizado (higiene SDD)
`specs/005-openwiki-agentes/tasks.md` **todavía describe la arquitectura vieja y está todo marcado `[X]`**, lo cual es falso (el motor real no existe aún). Reescríbelo **completo** para reflejar el motor Groq⇄DashScope, con casillas **`[ ]` sin marcar** (es trabajo pendiente). Concretamente, elimina/reemplaza:
- T003 "instalación global de la **CLI de OpenWiki**" → tarea de crear `scripts/openwiki-sync.mjs` (motor `fetch` OpenAI-compatible).
- T004 "`ANTHROPIC_API_KEY` / `secrets.ANTHROPIC_API_KEY`" → `GROQ_API_KEY` + `DASHSCOPE_API_KEY`.
- T008 "flag `--threshold 0.85` en el comando de la CLI" → umbral implementado **dentro** del script (score en el JSON de respuesta).
- Añade tareas para: helper `callOpenAICompatible`, round-robin ponderado, failover atómico, parseo/validación del JSON contra Zod, `--dry-run`, borrado de `dummy.md` + doc real, actualización de `.env.example` y del workflow.
No dejes `tasks.md` contradiciendo al `spec.md`. Si una tarea antigua ya no aplica, bórrala (no la marques como hecha).

## Precisión 1 — Blindar el JSON del LLM contra el esquema Zod (o el build se rompe)
El `frontmatter` DEBE cumplir Zod o `npm run build` falla. Un LLM fácilmente devuelve `description` > 160 o `title` > 100. Por eso:
- Pide la respuesta en **modo JSON** (`response_format: { type: "json_object" }` — Groq y DashScope compatible-mode lo soportan) y **parsea defensivamente** (quita fences ```json, valida que sea JSON válido; si no, cuenta como fallo del proveedor → failover).
- **Valida longitudes y tipos ANTES de escribir el archivo**: `title` ≤ 100, `description` ≤ 160, `seoScore` 0–100, `tags` array, `lastUpdated` fecha válida. Si algo excede, **trunca de forma segura** (o re-solicita una vez); nunca escribas un frontmatter que Zod vaya a rechazar. Idealmente valida con el **mismo esquema Zod** de `src/content.config.ts` antes de persistir.

## Precisión 2 — `DASHSCOPE_URL` con default en el script (que funcione sin config extra)
El YAML del workflow inyecta `GROQ_MODEL` y `DASHSCOPE_MODEL` pero **no `DASHSCOPE_URL`**. Para que no falle: dale al script un **default interno** para el endpoint DashScope (`https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions`) y permite override por `DASHSCOPE_URL`. Igual para Groq (default `https://api.groq.com/openai/v1/chat/completions`). Verifica que `GROQ_MODEL=llama-3.3-70b-versatile` y `DASHSCOPE_MODEL=qwen-plus` sigan **vigentes** en la doc de cada proveedor al implementar.

## Precisión 3 — Base del diff y no-op cuando no hay cambios (evitar quemar tokens y PRs vacías)
- Define explícitamente contra qué compara `git diff`: por defecto el **último commit** (`HEAD~1..HEAD`) o un ref configurable (`--since <ref>`). El workflow ya usa `fetch-depth: 0`, así que el histórico está disponible.
- **Si no hay archivos relevantes cambiados, el script debe salir 0 SIN llamar a ningún LLM y SIN crear PR.** El cron corre a diario; no queremos gastar tokens ni abrir PRs vacías cuando nada cambió.
- Para el **sembrado inicial** (primera corrida / doc `arquitectura.md`), permite un flag `--seed` o `--all` que genere la doc base ignorando el diff.

## Precisión 4 — Verificación E2E con evidencia
Al implementar, reporta:
1. `--dry-run` **sin claves**: reporta qué haría y a qué proveedor le tocaría cada doc, **sin consumir API**.
2. Corrida real con ambas claves: evidencia en logs de que **los documentos se reparten entre Groq y DashScope** (no que uno sea mero respaldo).
3. **Failover:** con `GROQ_API_KEY` inválida a propósito → conmuta a DashScope y completa (log de conmutación); revierte.
4. **Guarda Zod:** fuerza a propósito una `description` > 160 y demuestra que el script la trunca/re-solicita y que `npm run build` **NO** se rompe.
5. `npm run build`: los docs de `openwiki` compilan y renderizan en `/wiki/[slug]`; `dummy.md` reemplazado por doc real.

## Cierre (documentación de estado)
- `specs/005-openwiki-agentes/tech_debt.md`: de "🔴 espejismo/inoperativo" → estado real (motor OpenAI-compatible con balanceo Groq/DashScope, sin Anthropic), dejando como pendiente solo lo que depende de los secrets de GitHub.
- `planes/ESTADO-SPECS.md`: fila 005 → "Motor real Groq⇄DashScope operativo (pendiente configurar secrets en GitHub)".

## Forma de respuesta
- Reescribe primero `tasks.md` (corrección obligatoria) y luego implementa.
- Reporta la verificación E2E con evidencia (dry-run sin consumo, reparto entre proveedores, failover, guarda Zod, build).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que `tasks.md` ya no contradiga al `spec.md`, que el motor **reparta carga** entre Groq y DashScope (con failover, no solo respaldo), que el frontmatter **nunca** rompa el esquema Zod, que no se gaste API cuando no hay cambios, y que `--dry-run` no consuma API.
