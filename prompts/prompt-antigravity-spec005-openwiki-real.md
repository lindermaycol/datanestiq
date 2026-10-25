# Prompt para Antigravity: Spec 005 (OpenWiki real) — reemplazar el espejismo por un motor documental con balanceo Groq ⇄ DashScope

Actúa como **Ingeniero de plataformas de IA (Node, LLMs OpenAI-compatible) y auditor SDD**. **Sigue el pipeline SDD estrictamente**: (1) refina `spec.md`, (2) entrega `plan.md` + `tasks.md` para mi revisión, (3) recién tras mi visto bueno, implementa. No escribas código de implementación antes de que apruebe el plan.

## Contexto (auditado por Claude / Sonnet 5)
La Spec 005 (OpenWiki / "documentación viva") está documentada como completa pero es un **espejismo técnico** — verificado:
- El workflow `.github/workflows/openwiki-audit.yml` invoca `npm install -g openwiki` y `openwiki --sync --model claude-3-5-sonnet-20240620`. **Ese CLI `openwiki` no existe** como paquete que haga lo que el YAML asume, y el modelo es **obsoleto**.
- El contenido real es solo `src/content/openwiki/dummy.md`. Nunca se generó documentación viva real.
- `scripts/openwiki-local-sync.sh` invoca el mismo CLI inexistente.

Objetivo: reemplazar el CLI ficticio por un **script real en Node que genere/actualice documentación desde los cambios del repo usando un balanceador entre dos proveedores LLM OpenAI-compatible: Groq y Alibaba DashScope (Qwen)**, alimentando la colección Astro `openwiki` (que ya renderiza en `/wiki/[slug]`).

**Decisión de arquitectura del usuario (NO usar Anthropic):** el motor NO debe usar el SDK de Anthropic ni la API de Claude. Debe **repartir la carga (load balancing) entre Groq y DashScope**, reutilizando exactamente el patrón OpenAI-compatible que ya existe en `public/api/chat.php` (`callOpenAICompatible()` + failover atómico), por coherencia con la gobernanza de costos ya implementada.

## Restricciones técnicas verificadas (para NO alucinar)

### La colección Astro ya existe y valida con Zod
`src/content.config.ts` define la colección `openwiki` con este esquema **estricto** — todo markdown generado DEBE cumplirlo o el build falla:
```
title: string (max 100), description: string (max 160), author: string (default 'AI Documenter'),
lastUpdated: date, tags: string[], seoScore: number (0-100, default 100)
```
El frontmatter YAML de cada doc generado debe incluir esos campos (fechas en formato que Zod `z.date()` acepte, ej. `2026-07-08`).

### LLMs: balanceo Groq ⇄ DashScope (OpenAI-compatible) — reutiliza el patrón existente
El proyecto YA tiene el patrón correcto en `public/api/chat.php`. Espéjalo en Node, no reinventes:
- Ambos proveedores exponen el endpoint **OpenAI-compatible `/chat/completions`**. Implementa **un solo helper Node** `callOpenAICompatible(url, key, model, messages, maxTokens)` (equivalente al de `chat.php`), usando el **`fetch` global de Node 18+ (sin dependencia nueva)** — igual que `chat.php` usa curl. No uses el SDK de Anthropic. (Alternativa aceptable: el SDK `openai` apuntado a cada `baseURL`, pero `fetch` es más consistente con lo que ya hay y evita dependencias.)
- **Proveedores y configuración por entorno (reutiliza convenciones existentes):**
  - **Groq:** `GROQ_API_KEY` ya existe. Endpoint `https://api.groq.com/openai/v1/chat/completions`. Modelo por defecto configurable (`GROQ_MODEL`) — recomiendo uno capaz para redacción de docs (ej. `llama-3.3-70b-versatile`), NO el `llama-3.1-8b-instant` que `chat.php` usa solo como fallback rápido. **Verifica el ID vigente en la doc de Groq** (deprecan modelos seguido); no lo hardcodees, léelo de env con default.
  - **DashScope (Qwen):** `DASHSCOPE_API_KEY`. Endpoint OpenAI-compatible `https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions` (internacional; existe la variante `dashscope.aliyuncs.com` para China — hazlo configurable vía `DASHSCOPE_URL`). Modelo configurable `DASHSCOPE_MODEL` (ej. `qwen-plus` o `qwen-max`). **Verifica el ID vigente en la doc de DashScope.**
  - Mantén compatibilidad con el `CHEAP_LLM_URL/KEY/MODEL` ya existente si conviene, pero para OpenWiki nombra explícitamente Groq y DashScope para que el **balanceo entre ambos** sea legible.
- **Estrategia de balanceo (repartir carga, según lo pedido por el usuario):**
  - **Round-robin ponderado** entre Groq y DashScope: alterna el proveedor por documento/tarea (opcionalmente con peso configurable `LLM_WEIGHT_GROQ`/`LLM_WEIGHT_DASHSCOPE`), de modo que la carga se **reparta** entre ambos, no que uno sea solo respaldo del otro.
  - **Failover atómico:** si el proveedor elegido falla (HTTP != 2xx o error de red), reintenta 1 vez en el **otro** proveedor (igual que el failover atómico de `chat.php`), registrando la conmutación.
  - Si **ambos** fallan, sale con error claro sin corromper archivos.
  - Documenta la estrategia y los pesos por defecto en el `plan.md`.
- **Una sola llamada `/chat/completions`** por documento (es generación/resumen, no agentes ni tool use). Para docs moderados no hace falta streaming.

## Qué construir (target — descríbelo en `spec.md` y desglósalo en `tasks.md`)

1. **[NEW] `scripts/openwiki-sync.mjs`** — script Node que:
   - Determina qué cambió (ej. `git diff --name-only` contra el último commit, filtrando `specs/**`, `src/data/*.json`, `src/content/**`), o recibe rutas por argumento.
   - Para cada área relevante, arma un prompt y llama al **balanceador Groq ⇄ DashScope** (`callOpenAICompatible` vía `fetch`) para **generar o actualizar** el doc markdown correspondiente en `src/content/openwiki/`, con **frontmatter que cumpla el esquema Zod** de arriba.
   - **Respeta los bloques inmutables** `<!-- OPENWIKI:IGNORE:START -->` / `<!-- OPENWIKI:IGNORE:END -->`: nunca los sobrescribe (extráelos, genera alrededor, y re-insértalos intactos).
   - **Umbral de confianza (FR de la spec):** pide al modelo un score de confianza; si < 0.85, en vez de escribir el doc directo, marca el cambio como `[HUMAN REVIEW REQUIRED]` (para que el PR lo señale) o genera un issue. Documenta cómo obtienes ese score (ej. pedirlo en la respuesta estructurada).
   - **Balanceo + failover:** reparte los documentos entre Groq y DashScope (round-robin ponderado) y conmuta 1 vez al otro proveedor ante fallo; loguea la conmutación.
   - `--dry-run`: no escribe archivos, solo reporta qué haría y a qué proveedor le tocaría cada doc (para el testeo local, reemplaza el `openwiki --dry-run` ficticio de `openwiki-local-sync.sh`).
   - **Fail-safe:** si faltan **ambas** claves (`GROQ_API_KEY` y `DASHSCOPE_API_KEY`) o ambos proveedores fallan, sale con mensaje claro sin romper nada más; en `--dry-run` no debe consumir ninguna API. Si solo hay una clave, opera con ese único proveedor (sin balanceo) y avisa.

2. **[MODIFY] `.github/workflows/openwiki-audit.yml`** — reemplaza `npm install -g openwiki` + `openwiki --sync ...` por:
   - `npm ci` (o install). No requiere SDK de Anthropic; el motor usa `fetch` nativo.
   - `node scripts/openwiki-sync.mjs` con `env: GROQ_API_KEY: ${{ secrets.GROQ_API_KEY }}` y `DASHSCOPE_API_KEY: ${{ secrets.DASHSCOPE_API_KEY }}` (más `GROQ_MODEL`/`DASHSCOPE_MODEL`/`DASHSCOPE_URL` si se parametrizan).
   - Elimina toda referencia a `ANTHROPIC_API_KEY` y al modelo `claude-*` obsoleto.
   - Conserva el `peter-evans/create-pull-request@v6` (crear PR con los docs generados) y el flag `[HUMAN REVIEW REQUIRED]` cuando cambien specs críticas (001/003).

3. **[MODIFY] `scripts/openwiki-local-sync.sh`** — que invoque `node scripts/openwiki-sync.mjs --dry-run` (motor real), no el CLI ficticio.

4. **[NEW] Contenido inicial real** — genera al menos 1-2 docs reales en `src/content/openwiki/` (ej. una página que documente la taxonomía actual y otra la arquitectura Astro), reemplazando/complementando `dummy.md`. Deben renderizar en `/wiki/[slug]` y cumplir el esquema.

## Decisiones a resolver en el plan (SDD)
- **Alcance del disparo:** ¿qué rutas monitorea (specs, taxonomía, src)? Empieza acotado.
- **Balanceo:** estrategia (round-robin ponderado) y pesos por defecto Groq/DashScope; modelos por defecto (`GROQ_MODEL`, `DASHSCOPE_MODEL`) con sus IDs vigentes verificados en la doc de cada proveedor.
- **Umbral/confianza:** cómo se calcula y qué pasa bajo 0.85 (PR con flag vs issue).
- **Secrets:** el workflow depende de `secrets.GROQ_API_KEY` y `secrets.DASHSCOPE_API_KEY` — nota que el usuario debe configurarlos en GitHub (no lo puede hacer Antigravity). Márcalo como acción pendiente del usuario. Actualiza `.env.example` con `GROQ_MODEL`, `DASHSCOPE_API_KEY`, `DASHSCOPE_MODEL`, `DASHSCOPE_URL` y pesos de balanceo.

## Verificación (E2E, cuando implementes)
1. `node scripts/openwiki-sync.mjs --dry-run` corre sin claves y reporta qué haría y a qué proveedor le tocaría cada doc (sin consumir ninguna API).
2. Con `GROQ_API_KEY` y `DASHSCOPE_API_KEY` disponibles localmente, una corrida real genera/actualiza docs **repartiendo entre ambos proveedores** (evidencia en logs: cuál atendió cada doc); un bloque `IGNORE` de prueba **no** se altera.
3. **Failover:** con una clave inválida a propósito en un proveedor, el motor conmuta al otro y completa (log de conmutación); luego revierte.
4. `npm run build`: los docs de `openwiki` compilan y renderizan en `/wiki/[slug]` (esquema Zod OK).
5. `tech_debt.md` de la Spec 005 actualizado: de "🔴 espejismo/inoperativo" a su estado real (motor real OpenAI-compatible con balanceo Groq/DashScope, sin dependencia de Anthropic), dejando como pendiente lo que dependa de los secrets de GitHub.

## Forma de respuesta
- **Primero** `spec.md` refinado (arquitectura objetivo realista) + `plan.md` + `tasks.md` para mi revisión. NO implementes aún.
- Al implementar (tras mi aprobación), reporta la verificación E2E (dry-run, generación real con reparto, failover, IGNORE respetado, build).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el motor **reparta la carga entre Groq y DashScope** (no que uno sea mero respaldo), reutilizando el patrón OpenAI-compatible de `chat.php` (nada de SDK de Anthropic ni CLI ficticio), con failover atómico, IDs de modelo vigentes por env, respeto a los bloques IGNORE, frontmatter que cumpla el esquema Zod y `--dry-run` que no consuma API.
