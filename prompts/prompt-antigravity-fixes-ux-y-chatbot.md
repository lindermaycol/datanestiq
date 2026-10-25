# Prompt para Antigravity: 4 fixes de UX/navegación + Chatbot resiliente (failover 3 APIs) + grounded en servicios

Dos partes. **Parte A** (fixes de navegación) se implementa directo. **Parte B** (chatbot / `chat.php`) toca backend con seguridad (PII) → **entrega un plan para MI revisión antes de implementar**.

Contexto: una auditoría de navegación real encontró 4 hallazgos de UX/SEO. Además, el **chatbot en modo texto libre falla** con *"Ups, hubo un problema conectando con mi cerebro (Groq API)"* — porque el `chat.php` actual solo hace failover **cheap-tier → Groq**, y si Groq (proveedor terminal) da 429/cae, **no hay más fallback** y el usuario ve el error. No usa Gemini.

---

## PARTE A — 4 fixes de UX/navegación (implementar + evidencia)

**A1 — Sectores rastreables (SEO/descubrimiento) [P2, alto impacto]:** hoy las 10 páginas `/sectores/*` **solo** se alcanzan por `<button onClick>` de la isla "Soluciones por Industria" — **no hay `<a href>`** ni ítem de sectores en el menú. Añade **enlaces `<a href="/sectores/[slug]">` reales** (una grilla de sectores enlazada y/o un ítem "Sectores"/"Industrias" en el nav). Puedes conservar la isla, pero los sectores deben tener `<a>` reales para que los crawlers los descubran. Confirma que el sitemap incluye las 10.

**A2 — "Metodología" del menú apunta al FAQ [P2]:** el nav "Metodología" enlaza a `/#faq`. Existe una sección de metodología en el home (los 3 pasos: Diagnóstico/Diseño/Implementación). Apunta "Metodología" a **esa sección** (dale un `id` a la sección de metodología y enlaza ahí), no al FAQ.

**A3 — Ancla muerta `#roi` [P2/P3]:** hay un enlace en el home que apunta a `#roi`, pero **no existe `id="roi"`**. Apúntalo a la sección/página de casos ROI (`/casos-de-exito` o la sección correcta) o quítalo.

**A4 — Rutas `/servicios` y `/metodologia` = 404 [P3]:** el menú usa anclas (`/#servicios`, `/#faq`), así que NO rompe la navegación. Opcional: crear redirecciones a las anclas o dejarlo. Documenta la decisión (no es urgente).

**Verificación A:** los sectores tienen `<a href>` reales (no solo botones); "Metodología" lleva a la sección de metodología; `#roi` resuelve; `npm run build` verde; sin errores de consola.

---

## PARTE B — Chatbot resiliente + orientado a servicios (`public/api/chat.php`) — ENTREGAR PLAN PARA MI REVISIÓN

### B1 — Failover de 3 proveedores (recomendado sobre balanceo)
Es una **conversación** → prima la **coherencia**, no el reparto. Implementa **failover secuencial: Groq → DashScope → Gemini**, reutilizando el helper `callOpenAICompatible($url,$key,$model,$messages,$max_tokens)` que ya existe.
- **Reemplaza** la cadena actual (cheap-tier → Groq 1-time) por: intenta Groq; si error/429 → DashScope; si error/429 → Gemini; **solo si los 3 fallan**, devuelve un mensaje amable (no el error crudo de "Groq API").
- Endpoints: Groq `https://api.groq.com/openai/v1/chat/completions`; DashScope `https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions`; Gemini `https://generativelanguage.googleapis.com/v1beta/openai/chat/completions`.
- Modelos por env con defaults verificados: Groq `llama-3.3-70b-versatile`, DashScope `qwen-plus`, Gemini `gemini-2.5-flash` (o `gemini-3.1-flash-lite`).
- **Claves:** `chat.php` lee `GROQ_API_KEY` vía `getenv`. **Verifica por qué mecanismo se inyecta** (`.env` loader, `SetEnv` en `.htaccess`, o env del server) y **asegura que `DASHSCOPE_API_KEY` y `GEMINI_API_KEY` también estén disponibles** por ese mismo mecanismo (si no, el failover no tendrá claves). Nunca en claro en el código.
- Esto **arregla el error de la imagen**.

### B2 — Grounded en los servicios (taxonomía = fuente de verdad)
El chatbot debe **siempre orientar hacia los servicios de Datanestiq**, mapear la necesidad del usuario a el/los servicio(s), enlazarlos y explicarlos.
- **Fuente:** los 6 pilares/servicios están en la taxonomía. Como `chat.php` corre en PHP (sin acceso garantizado a `src/`), genera un archivo liviano **`public/api/services.json`** desde la taxonomía (en `scripts/build-taxonomy.mjs` o un paso de build) con, por pilar: `{ name, slug, url: "/soluciones/<slug>", descripcion, en_que_consiste }`. `chat.php` lo lee en runtime para construir el `SYSTEM_PROMPT`.
- **Instrucciones al modelo (en el system prompt):**
  1. **Siempre** relacionar la necesidad del usuario con el/los servicio(s) relevante(s) de Datanestiq.
  2. **Recomendar y enlazar** `/soluciones/<slug>` del servicio pertinente.
  3. **Explicar en qué consiste** un servicio si se lo piden.
  4. Si detecta un sector, referenciarlo; tono **on-brand B2B**, conciso y consultivo (no genérico).
  - Ejemplo objetivo: *"quiero un sistema para mi colegio"* → mapear a **Sistemas Digitales / IA** (+ sector Educación), recomendar el servicio con **link** y explicar brevemente en qué consiste.

### B3 — No romper (crítico)
- **No toques el flujo guiado 0-LLM** (vive en `Chatbot.jsx`, client-side, y NO llama a `chat.php`). Solo cambias el modo **texto libre**.
- **Mantén** la redacción de PII (Constitución §5) y el aislamiento del system prompt que ya tiene `chat.php`. Nunca expongas claves ni PII en logs en claro.

**Verificación B (tras mi aprobación):**
1. Fuerza un fallo de Groq (clave inválida a propósito) → el chat responde vía **DashScope/Gemini**, SIN el mensaje de error. Log de la conmutación.
2. En texto libre, el chatbot **recomienda un servicio con link `/soluciones/<slug>`** y lo explica si se le pregunta.
3. PII sigue redactada; el flujo guiado (botones) sigue en **0 llamadas**.
4. `services.json` se genera desde la taxonomía (no hardcodeado).

---

## Forma de respuesta
- **Parte A:** implementa y reporta evidencia (enlaces de sector rastreables, "Metodología"/`#roi` corregidos, build verde, sin errores de consola).
- **Parte B:** entrega un **plan** (cambios en `chat.php`, `services.json`, system prompt, mecanismo de claves) para **mi revisión**. **NO implementes la Parte B aún.**
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará **en el navegador** que los sectores queden con `<a href>` reales, que "Metodología"/`#roi` funcionen, y en la Parte B que el failover 3-APIs realmente conmute (sin mostrar el error), que el chatbot enlace/explique servicios desde la taxonomía, que la PII siga redactada y que el flujo guiado 0-LLM quede intacto. **Pendiente del usuario:** asegurar que `DASHSCOPE_API_KEY` y `GEMINI_API_KEY` estén disponibles para `chat.php` (mismo mecanismo que `GROQ_API_KEY`) + rotar la SSH de IONOS.
