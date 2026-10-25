# Prompt para Antigravity: Generador propio de documentación multi-destino (pivote desde openwiki) — SDD

Actúa como **Ingeniero de plataformas de IA (Node) + auditor SDD**. **Sigue el pipeline SDD**: (1) crea/refina el `spec.md`, (2) entrega `plan.md` + `tasks.md` para mi revisión, (3) recién tras mi visto bueno, implementa. No implementes antes de que apruebe el plan.

> **Numeración:** propón en el plan si esto es la **Spec 010 (Generador de documentación multi-destino)** o una evolución de la Spec 005. Recomiendo **Spec 010 nueva** (capacidad distinta), dejando la 005 como la wiki humana base.

## Contexto y por qué pivotamos
La Spec 009 (adoptar `langchain-ai/openwiki`) quedó **bloqueada por cuota**: openwiki es un **agente** que hace decenas de llamadas por corrida y **agota los free tiers** (crash `RateLimitQuotaExhaustedError`; Gemini free = 20 req/día). No es viable en gratuito.

**Pivotamos a nuestro propio generador** [`scripts/openwiki-sync.mjs`](../../scripts/openwiki-sync.mjs) (Spec 005), que hace **UNA llamada controlada por documento** (no agente) y **ya escribe documentación real y fundamentada** (generó `src/content/wiki/arquitectura.md`). Además **ya tiene un balanceador interno** Groq⇄DashScope⇄Gemini (funciones `buildPool`, `getProviderOrder`, round-robin + failover, `callOpenAICompatible` vía `fetch`). **NO usa LiteLLM** — balancea en Node.

**Por qué el balanceo SÍ es correcto aquí** (y rompía openwiki): cada documento es una **petición independiente** — no hay coherencia de agente que preservar. Repartir cada doc entre los 3 proveedores **suma las 3 cuotas gratuitas** y sobrevive el free tier. Este es el uso para el que el balanceo fue diseñado.

## Objetivo
Extender el generador (renómbralo a algo claro, ej. `scripts/docs-generator.mjs`) a un **generador multi-destino** que, **reutilizando el balanceador existente** (Groq⇄DashScope⇄Gemini, round-robin por documento + failover), produzca **cuatro tipos** de documentación:

| Destino | Fuente | Salida | Esquema / estilo |
|---|---|---|---|
| **wiki (técnica)** | código, specs, taxonomía (git diff o `--seed`) | `src/content/wiki/*.md` | esquema Zod `wiki` (title≤100, description≤160, author, lastUpdated, tags, seoScore). Fundamentado en el código real. |
| **agentes** | estructura del repo, convenciones, specs | `AGENTS.md` (raíz) | overview para agentes IA (Claude Code/Antigravity): arquitectura, archivos clave, convenciones, cómo navegar. Sin frontmatter (no es colección). |
| **skills** | skills/comandos del proyecto | `src/content/wiki/skills-*.md` (o `docs/`) | descripción de las skills/capacidades disponibles. |
| **blog** | **taxonomía** (pilares/sectores/personas) + **brief del usuario** | `src/content/blog/*.md` | esquema Zod `blog` (title≤120, description≤200, pubDate, author=Datanestiq, tags[], draft). Editorial/on-brand, no code-grounded. |

## Requisitos técnicos

### Balanceo (reutilizar y endurecer el existente)
- **Mantén** el balanceador interno Groq⇄DashScope⇄Gemini (round-robin ponderado por documento + failover a otro proveedor si uno da error/429). Lee las 3 claves de `.env` (`GROQ_API_KEY`, `DASHSCOPE_API_KEY`, `GEMINI_API_KEY`) — ya están.
- **Endurece contra 429:** si un proveedor devuelve 429, salta al siguiente; si todos dan 429, backoff exponencial y reintento (con tope). Como cada doc = 1 llamada y se reparte, un lote de N docs hace ~N/3 llamadas por proveedor → holgado para free tier.
- Modelos configurables por proveedor vía env (`GROQ_MODEL`, `DASHSCOPE_MODEL`, `GEMINI_MODEL`). Para generación single-shot, modelos económicos bastan; NO requiere consistencia entre docs (cada uno es independiente).
- Al terminar un lote, **reporta la distribución** (cuántos docs atendió cada proveedor) para poder verificar el reparto.

### Interfaz (CLI)
Diseña una CLI clara, ej.:
```
node scripts/docs-generator.mjs --target=wiki   [--since=<ref> | --seed]
node scripts/docs-generator.mjs --target=agents
node scripts/docs-generator.mjs --target=skills
node scripts/docs-generator.mjs --target=blog   --brief="tema/ángulo del post" [--slug=...]
node scripts/docs-generator.mjs --dry-run ...    (no llama a APIs, no escribe)
```
Propón en el plan si además conviene un **manifiesto** (ej. `docs.manifest.json`) para generar varios docs en lote.

### Por destino
- **wiki/agents/skills:** fundamentados en el **código/estructura real** (incluye contenido/diff del archivo en el prompt; el modelo NO debe inventar). Respeta los bloques `<!-- OPENWIKI:IGNORE:START/END -->` (ya implementado) y sella `lastUpdated` a la fecha real (ya implementado).
- **blog:** usa la **taxonomía** (`src/data/*.json`: pilares, sectores, personas) como contexto para contenido **on-brand**, más el `--brief` del usuario. Frontmatter con esquema `blog` (`pubDate` = fecha real, `draft: true` por defecto para revisar antes de publicar). Guarda como `stripFrontmatter` defensivo (evita doble frontmatter, ya visto con Gemini).

## Seguridad (obligatorio)
- El generador **envía contenido a los proveedores LLM**. NO incluir jamás en los prompts: `.env`, `secure_leads/` (PII), `wp-config.php` (creds BD), `*.jsonl`, ni el core WP. Filtra las fuentes por una lista de exclusión explícita.
- No tocar el core de WordPress.

## Verificación (E2E, al implementar)
1. **`--dry-run`** por cada destino: simula sin llamar a APIs ni escribir.
2. **Generación real (lote pequeño):** 1 doc por destino con **claves reales**, y **reporta la distribución de proveedores** (evidencia del balanceo Groq/DashScope/Gemini). Confirma que un lote típico hace **pocas llamadas** (cabe en free tier).
3. **Esquemas válidos:** los `.md` de `wiki` y `blog` cumplen su Zod respectivo; `AGENTS.md` legible; sin doble frontmatter.
4. **`npm run build`** compila (wiki en `/wiki/[slug]`, blog en su ruta) sin romper las 22 páginas previas.
5. **Failover:** con una clave inválida a propósito en un proveedor, el motor conmuta a otro y completa.

## Cierre de estado
- `planes/ESTADO-SPECS.md`: nueva fila (010) con el estado real; y nota que la 009 (openwiki) queda archivada como "requiere tier de pago".
- `.env.example`: documenta `GROQ_MODEL`/`DASHSCOPE_MODEL`/`GEMINI_MODEL` y (si aplica) pesos del balanceo.

## Forma de respuesta
- **Primero** `spec.md` + `plan.md` + `tasks.md` para mi revisión. NO implementes aún.
- Al implementar, reporta la verificación E2E con la **distribución real de proveedores** y una muestra por destino.
- Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el balanceo reparta de verdad entre las 3 claves (evidencia de distribución), que cada destino cumpla su esquema, que los secretos/PII NO se envíen a los proveedores, que el failover/backoff maneje 429, y que un lote quepa en free tier (pocas llamadas). **Pendiente del usuario:** nada nuevo — las 3 claves ya están en `.env`.
