# Prompt para Antigravity: Confirmación Parte B (Chatbot failover + grounding) → ejecutar con 1 corrección + precisiones

## Parte A (UX) — AUDITADA Y APROBADA ✅
Verifiqué en el build: **10 enlaces `<a href="/sectores/[slug]">`** reales (sectores ya rastreables), **"Metodología" → `/#metodologia`** (con su `id` presente, funciona), **`#roi` muerto eliminado**, build 24 páginas verde. Bien hecho. (La reverificaré en el navegador tras la Parte B.)

## Parte B (Chatbot) — LUZ VERDE con 1 corrección y 3 precisiones
El plan es sólido (failover secuencial 3 APIs, `services.json` desde la taxonomía, system prompt grounded, 0-LLM y PII intactos). Corrige esto antes de implementar:

### 🔴 Corrección — el pilar NO tiene campo `description` top-level
Verificado: `taxonomyCorpus.json` (pilares) tiene `seo`, `hero`, `contrast`, `features`… pero **NO un `description` de primer nivel** (tu plan lo mapea → daría `undefined`). Para `services.json` usa:
- `name`, `slug`, `url: "/soluciones/<slug>"`
- **`descripcion`: `seo.description`** (es el texto correcto y comercial, ej. "Desplegamos arquitecturas cognitivas y predictivas…"), NO `description`.
- **`en_que_consiste`: `contrast.solution`** (ok) — opcionalmente complementa con `features` (lista de qué incluye).

### Precisión 1 — el enlace debe ser CLICKEABLE en la burbuja del chat
El objetivo es que el chatbot **enlace** el servicio. Verifica que la UI del chat (`Chatbot.jsx`) **renderice el enlace como clickeable** (markdown `[texto](/soluciones/slug)` o un `<a>`), no como texto plano. Si hoy pinta texto plano, o bien haz que renderice markdown de forma segura, o instruye al modelo a devolver la **URL pura** (`/soluciones/slug`) y que la UI la convierta en link. Sin esto, "enlazar" no sirve al usuario.

### Precisión 2 — `services.json`: que exista en runtime y en deploy
`chat.php` debe leerlo por ruta local (`__DIR__ . '/services.json'`). Asegura que: (a) `build-taxonomy.mjs` lo genere en `public/api/services.json` en `prebuild`/`predev`; (b) se **incluya en el deploy a IONOS** junto a `chat.php` (commitéalo o garantiza que el build corre antes de subir). Si `chat.php` no lo encuentra, que degrade a un system prompt básico (sin romper).

### Precisión 3 — failover sin latencia extra
Bien que continúes al siguiente proveedor ante HTTP ≥ 400 o 0. Para un chat, **no metas backoff** entre proveedores (el usuario espera respuesta rápida): salta de inmediato Groq → DashScope → Gemini. Mantén el mensaje amable solo si fallan los 3 (buena idea capturar el lead ahí).

## Verificación (evidencia real, tras implementar)
1. **Failover:** con `GROQ_API_KEY` inválida a propósito, el chat responde vía **DashScope** (y si esa falla, Gemini), **sin** el mensaje de error de la imagen. Log de conmutación.
2. **Grounding:** en texto libre, ej. *"quiero un sistema para mi colegio"* → el chatbot **recomienda un servicio** (ej. Sistemas Digitales / IA), **da el link `/soluciones/<slug>` clickeable**, y **explica en qué consiste** si se le pregunta. Referencia el sector (Educación) si lo detecta.
3. **`services.json`** generado desde la taxonomía (no hardcodeado); usa `seo.description`.
4. **Sin romper:** PII sigue redactada (`[EMAIL_REDACTED]`/`[PHONE_REDACTED]`); el flujo guiado por botones sigue en **0 llamadas** (no tocaste `Chatbot.jsx` guiado).

## Forma de respuesta
- Reporta con evidencia real (log de failover, captura del chat recomendando+enlazando un servicio, build verde). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará **en el navegador** que el chatbot ya no dé error (failover real), que enlace/explique servicios desde la taxonomía con `seo.description`, que la PII siga redactada y el flujo guiado quede en 0 llamadas. **Pendiente del usuario:** asegurar `DASHSCOPE_API_KEY` y `GEMINI_API_KEY` disponibles para `chat.php` (mismo mecanismo que `GROQ_API_KEY`) + rotar la SSH de IONOS.
