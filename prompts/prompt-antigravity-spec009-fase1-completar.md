# Prompt para Antigravity: Spec 009 Fase 1 — cerrar con gateway LiteLLM (balanceo Qwen) + corrida real

Audité tu entrega. **Lo verificado y correcto (NO lo toques):** el hallazgo de que `openwiki --update` usa Ink y crashea sin TTY → `-p` no interactivo en CI ✓; el rename `src/content/openwiki` → `src/content/wiki` sin romper consumidores (build 22 páginas, `/wiki/arquitectura` viva) ✓; `openwiki` fuera del `package.json` vía `npx -y` ✓; triggers `workflow_dispatch` + `schedule` (no push a main) ✓.

**Faltan dos cosas para cerrar la Fase 1:** (1) una corrida real que produzca `openwiki/` y llene el "Contrato de Interfaz" (aún placeholder), y (2) el **balanceo multiproveedor vía gateway** que decidimos, para vencer el 429 sin sacrificar calidad.

## Corrección de seguridad ya aplicada por Claude (respétala)
El `.openwikiignore` que generaba tu workflow no excluía `.env`, `secure_leads/` ni el core de WordPress. Como `openwiki` escanea el **disco** (no solo git), de no fallar por 429 habría **enviado a Google** las API keys/credenciales SSH de `.env`, la **PII** de `secure_leads/`, y el core WP (causa real del 429). Ya remedié:
- **`.openwikiignore` commiteado** en la raíz (excluye `.env*`, `secure_leads/`, `*.jsonl`, `wp-config.php`, `wp-admin|wp-includes|wp-content/`, `node_modules/`, `dist/`, y ruido).
- El workflow ahora **verifica** ese archivo commiteado en vez de regenerar uno inseguro.

**NO reintroduzcas el `echo ... > .openwikiignore` ni una lista más corta.**

## Arquitectura de balanceo: gateway LiteLLM + estandarización en Qwen
`langchain-ai/openwiki` **no balancea nativamente** (acepta un solo provider/model). La solución acordada (Opción A) es un **gateway OpenAI-compatible con LiteLLM** al que openwiki apunta; el gateway reparte cada petición entre proveedores. Esto ataca el 429 de raíz (la carga se divide) y openwiki ni se entera.

**Estandarización de modelo (verificado con la clave real):** no se puede usar el mismo modelo en todos los proveedores (Gemini solo existe en Google). Para minimizar la disparidad, **estandariza en la familia Qwen**, disponible en Groq y DashScope:
- **Groq:** `qwen/qwen3-32b` (verificado disponible; también existe `qwen/qwen3.6-27b`).
- **DashScope (Alibaba):** `qwen-plus` (verificado HTTP 200).
- **Gemini:** `gemini-3.1-flash-lite` (verificado) como **tercer leg de bajo peso / failover** (familia distinta, solo para descargar si los Qwen se estrangulan).

**Config de LiteLLM (forma esperada — VERIFICA los strings de provider en la doc de LiteLLM, no asumas):**
```yaml
model_list:
  - model_name: openwiki-model              # alias único que verá openwiki
    litellm_params:
      model: groq/qwen/qwen3-32b            # verificar prefijo groq/ en docs LiteLLM
      api_key: os.environ/GROQ_API_KEY
    model_info: { weight: 5 }
  - model_name: openwiki-model
    litellm_params:
      model: openai/qwen-plus               # DashScope vía openai-compatible
      api_base: https://dashscope-intl.aliyuncs.com/compatible-mode/v1
      api_key: os.environ/DASHSCOPE_API_KEY
    model_info: { weight: 5 }
  - model_name: openwiki-model
    litellm_params:
      model: gemini/gemini-3.1-flash-lite   # tercer leg, failover/bajo peso
      api_key: os.environ/GEMINI_API_KEY
    model_info: { weight: 1 }
# routing_strategy ponderado (verifica el nombre exacto en LiteLLM, ej. "simple-shuffle")
```
- openwiki apunta al gateway: `OPENWIKI_PROVIDER=openai-compatible`, `OPENAI_COMPATIBLE_BASE_URL=http://localhost:4000/v1`, `OPENWIKI_MODEL_ID=openwiki-model`, `OPENAI_COMPATIBLE_API_KEY=<master key del proxy o cualquier valor si no exiges auth>`.
- Pesos configurables: por defecto favorece el par Qwen (Groq/DashScope) y usa Gemini poco. Documenta cómo cambiarlos.

## Pasos para cerrar la Fase 1
1. **Levantar LiteLLM (local y CI):** `pip install litellm`, arrancar `litellm --config <config>.yaml` en background, esperar health (`http://localhost:4000/health`), y recién correr openwiki. Añade teardown.
2. **Corrida real CON el ignore seguro, vía gateway:** ejecuta `npx -y openwiki -p "..."` apuntando al gateway. **Confirma empíricamente** que (a) el `.openwikiignore` aplica (no escanea `wp-admin/`/`.env`), y (b) el balanceo reparte (log de LiteLLM mostrando peticiones a Groq/DashScope/Gemini).
3. **Si el 429 persiste** incluso repartido: sube el peso de los Qwen (Groq/DashScope tienen cuotas independientes de Gemini), acota una primera pasada a `src/`/`scripts/`/`specs/`, y/o backoff. Reporta lo real; si sigue bloqueado, NO cierres la fase.
4. **Captura la salida real** (`openwiki/` + `AGENTS.md`) y **llena el Contrato de Interfaz** en `spec.md` §3 con la estructura real. Commitea `openwiki/`.
5. **`CLAUDE.md`/`AGENTS.md`:** no existe `CLAUDE.md` en la raíz; si el tool lo crea, inclúyelo en el PR (preferible `AGENTS.md`). Sin sobreescritura destructiva.

## CI (`.github/workflows/openwiki-langchain.yml`)
- Añade: setup de Python + `pip install litellm`, generación del `config.yaml` (o commitéalo), arranque del proxy en background con espera de health, y luego el step de openwiki apuntando a `localhost:4000`.
- **Secrets requeridos:** `GROQ_API_KEY`, `DASHSCOPE_API_KEY`, `GEMINI_API_KEY` (los tres, inyectados como env del proxy).
- Mantén `-p` (no interactivo), `workflow_dispatch` + `schedule`, y el `.openwikiignore` commiteado.
- Si commiteas el `config.yaml` de LiteLLM, que use `os.environ/...` para las keys (NUNCA claves en claro).

## Verificación (reporta con evidencia)
1. Log de LiteLLM mostrando **reparto real** entre Groq/DashScope/Gemini durante la corrida de openwiki.
2. Log de openwiki mostrando que el ignore aplica y que generó doc **fundamentada en el código** (no genérica).
3. `spec.md` §3 con la **muestra real** pegada (no placeholder).
4. `openwiki/` commiteado; diff de `AGENTS.md`.
5. Build sigue en 22 páginas.

## Cierre de estado (honesto)
- `planes/ESTADO-SPECS.md` fila 009: súbela a "✅ [Fase 1] Finalizado" **solo** cuando la corrida real produzca salida y el contrato esté relleno. Si sigue bloqueada, déjala en 🟡 con la causa exacta.
- `.env.example`: documenta las vars del gateway (base URL `localhost:4000`, alias `openwiki-model`) y las 3 keys de proveedor.
- No sobre-afirmes: gateway + config listos ≠ operativo; requiere una corrida exitosa.

## Forma de respuesta
- Reporta la verificación con evidencia real (reparto en LiteLLM + salida de openwiki). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que exista **balanceo real** vía LiteLLM (no un solo provider disfrazado), que se estandarice en Qwen (Groq `qwen/qwen3-32b` + DashScope `qwen-plus`) con Gemini como leg menor, que el `.openwikiignore` seguro siga intacto, que el Contrato de Interfaz esté relleno con salida real, y que el estado no diga "Finalizado" sin corrida exitosa. **Pendiente del usuario:** configurar los secrets `GROQ_API_KEY`, `DASHSCOPE_API_KEY` y `GEMINI_API_KEY` en GitHub.
