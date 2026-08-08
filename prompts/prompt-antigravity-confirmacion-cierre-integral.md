# Prompt para Antigravity: Confirmación del Plan de Cierre Integral → ejecutar con 4 precisiones

He revisado `planes/Plan de Implementación Cierre Integral, Blog y Gobernanza de LLMs.md`. Cubre fielmente las 4 partes. **Tienes luz verde para ejecutar**, con estas 4 precisiones — la #1 es correctitud del failover, la #4 exige verificación real.

## Precisión 1 (CRÍTICA) — Correctitud del failover (Parte D)

Al implementar el reintento a Groq cuando el proveedor económico falla, respeta:
1. **Un solo reintento**, nunca un bucle. Cheap falla (no-2xx) → un intento con Groq `llama-3.1-8b-instant` → si eso también falla, devuelve el error (no más reintentos).
2. **El metering y el contador diario deben registrar la llamada que REALMENTE respondió**, con el `model` correcto. Si hubo failover, `usage_metrics.jsonl` debe loggear el modelo de Groq (no el del proveedor caído) y contar **una** sola entrada de métricas (no dos por el intento fallido).
3. **El kill-switch se evalúa antes de todo** (como está hoy): si el cap diario ya se superó, ni siquiera se intenta la primera llamada. El failover vive dentro del flujo de llamada, después del check de cap.
4. La llamada de failover usa **los mismos** `messages`, `max_tokens` y system prompt aislado — no cambies el payload, solo el proveedor/modelo.

## Precisión 2 — Blog: no rompas el JSON-LD existente y reutiliza el patrón wiki

- `src/components/ui/SEO.astro` ya inyecta JSON-LD de tipo `Service` vía el prop `serviceData`. Para el blog, **añade el `BlogPosting` sin romper ni reutilizar mal ese esquema**: inyéctalo directamente en `blog/[slug].astro` (un `<script type="application/ld+json">` propio) o extiende `SEO.astro` con un prop nuevo `articleData`. No mezcles `Service` con `BlogPosting`.
- Ya existe `src/pages/wiki/[slug].astro` con el patrón casi idéntico (getStaticPaths + render + `prose prose-invert`). **Reutiliza ese patrón** para `blog/[slug].astro` por consistencia (mismo layout, misma tipografía de lectura).
- El `BlogPosting` debe tener `datePublished` (ISO desde `pubDate`), `headline`, `author`, y `publisher` (Organization "Datanestiq").

## Precisión 3 — Parte B: verifica contra el código, no asumas; sé franco con OpenWiki

- Antes de marcar `[x]` en el `tasks.md` de la Spec 002, **confirma por grep/lectura** que cada archivo existe (`CopilotDemo.jsx`, `SemanticSearch.jsx`, `worker.js` con `dtype: 'q8'`, `MultiStepWizard.jsx`). No marques por optimismo.
- En la Spec 005, sé **explícito y franco**: el motor documental "vivo" **NO está operativo**. El workflow asume un CLI `openwiki` (`npm install -g openwiki`) cuya existencia y funcionalidad no están verificadas y probablemente no hace lo que el YAML supone; usa un modelo obsoleto y un secret no confirmado; el contenido es solo `dummy.md`. No lo describas como "casi listo" — descríbelo como "andamiaje no probado, sin operación real".

## Precisión 4 (CRÍTICA) — Verificación E2E real, no solo build

Tu "Verificación Planeada" es build + "chat.php ruteo por defecto". Insuficiente. Ejecuta y reporta con evidencia:
1. **Blog en navegador** (`npm run dev`): el listado `/blog` renderiza las tarjetas; un post `/blog/<slug>` renderiza con tipografía prose; un post con `draft: true` **NO** aparece ni se genera en `dist/`.
2. **No-regresión del chat con `.env` actual**: `curl`/navegador → `/api/chat.php` con un mensaje debe seguir devolviendo **200** real de Groq (el `DEEPSEEK_API_KEY` está vacío hoy, así que el tier barato debe caer a Groq-8B sin romperse).
3. **Failover real** (prueba concreta de la Parte D): configura temporalmente en `.env` un `CHEAP_LLM_BASE_URL` + `CHEAP_LLM_API_KEY` **inválidos** (endpoint que dé error), envía un mensaje **simple** (que rutee al tier barato), y confirma que: (a) el proveedor barato falla, (b) hace failover a Groq y devuelve **200** con respuesta real, (c) se registra el failover en `secure_leads/alerts.jsonl`, (d) `usage_metrics.jsonl` loggea el modelo de Groq. Restaura el `.env` al terminar.

Si algo de esto falla, **no cierres el ciclo** — reporta el error.

---

Procede: Parte A → B → C → D. `npm run build` tras C y D (blog generado, drafts ocultos). No implementes el resto del backlog (fixes semánticos, sector pages, LangGraph, Content Collections) — solo regístralo en tech_debt. No toques el core de WordPress.

Entrega el `planes/ESTADO-SPECS.md` (tabla Spec | Fase real | Deuda abierta | Próximo hito) y la sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) re-ejecutará la verificación E2E (blog en navegador + prueba de failover con key inválida) para auditar. Reportar "verificado" sin evidencia de Network/logs se detectará.
