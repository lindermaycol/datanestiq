# Spec 009: OpenWiki LangChain (FASE 1)

> [!CAUTION]
> **ESTADO: 🔴 ARCHIVADA.** Adoptar el CLI de terceros `langchain-ai/openwiki` (un **agente**) no es viable en free tier: un run hace decenas de llamadas y agota las cuotas (`RateLimitQuotaExhaustedError`). Su objetivo (doc para agentes) fue **recogido por la Spec 010** (`docs-generator.mjs --target=agents`, 1 llamada por documento). NOTA: algunas menciones a `docs-generator.mjs` en este doc son residuo de un rename global; el script de la Spec 005 se llamaba `openwiki-sync.mjs` en la época de esta spec.

## 1. Propósito y Distinción Clave

Dentro del ecosistema de Datanestiq, existen dos herramientas distintas con el nombre "OpenWiki" que cumplen propósitos separados y se dirigen a consumidores diferentes. Es fundamental mantener esta distinción clara:

- **`langchain-ai/openwiki` (Objeto de esta Spec 009):** Es un CLI oficial de LangChain que genera documentación automática del repositorio dirigida **exclusivamente a agentes de IA** (como Claude Code, Antigravity, etc.). Crea la carpeta `openwiki/` en la raíz del proyecto y actualiza los archivos `AGENTS.md` o `CLAUDE.md`.
- **`scripts/docs-generator.mjs` (Definido en Spec 005):** Es un script interno que genera y sincroniza **páginas web para consumo humano**, publicadas bajo el path `/wiki/[slug]`.

## 2. Arquitectura Objetivo por Fases

Para integrar ambos flujos sin acoplar tempranamente nuestro frontend a un formato externo que puede cambiar, se define la siguiente arquitectura por fases:

### FASE 1: Adopción para Agentes — USO MANUAL (Opción A, decidido)
- `langchain-ai/openwiki` se usa de forma **manual/interactiva** por un desarrollador (ver [USO-MANUAL.md](USO-MANUAL.md)). **La automatización en CI se descartó**: v0.0.3 no tiene modo headless (`--init`/`--update` requieren TTY Ink; `-p` no existe). El workflow `openwiki-langchain.yml` fue **eliminado**.
- Generación de documentación en `openwiki/` + `AGENTS.md` mediante `npx openwiki --init` en un terminal real, apuntando a un proveedor (DashScope `qwen-plus` por defecto) o al gateway LiteLLM opcional (config Qwen en `scripts/openwiki-llm.yaml`).
- **Seguridad:** openwiki respeta `.gitignore` (no `.openwikiignore`). Se reforzó `.gitignore` (`secure_leads/`, `*.jsonl`); verificado que los secretos de `.env` NO se filtran al indexar.
- **Higiene técnica:** la colección de contenido humano se renombró de `src/content/openwiki/` a `src/content/wiki/` (evita colisión con el `openwiki/` del tool). Verificado: build 22 páginas.
- **No se modifica** el script `docs-generator.mjs` ni el frontend humano. Ambas herramientas operan independientes.

### FASE 2: Convergencia (Futura)
- El Spec 005 (Wiki Humano) pasará a consumir la salida estructurada de esta Spec 009.
- **Dependencia:** Spec 005 dependerá de Spec 009.
- En lugar de usar crudos `git diff`, el generador de la web humana leerá el contexto rico depositado por el CLI de LangChain.
- **Capa Adaptadora:** Se introducirá una capa adaptadora intermedia para aislar el frontend (Astro) de los posibles cambios en el formato de salida de `langchain-ai/openwiki`.

## 3. Contrato de Interfaz (Salida del Tool)

Para mitigar el riesgo de acoplamiento, la Fase 2 dependerá estrictamente del siguiente formato de salida generado por `openwiki` (el cual será el único punto de conocimiento que la capa adaptadora tendrá sobre LangChain):

**Hallazgos Empíricos (Julio 2026):**
Durante las pruebas, se descubrió que OpenWiki escanea el repositorio de manera masiva, provocando errores 429 (Rate Limit) al conectar directamente a proveedores individuales (Gemini).

Para resolver esto en la Fase 1, se han implementado dos mecanismos:
1.  **Filtro estricto (`.openwikiignore`):** Un archivo commiteado en la raíz excluye explícitamente el core de WordPress (`wp-admin/`, `wp-includes/`, `wp-content/`), secretos (`.env`) y PII (`secure_leads/`). Esto protege la privacidad y reduce drásticamente el payload.
2.  **Gateway LiteLLM:** El CLI no ataca un solo LLM, sino que se enruta a un proxy local `litellm` (orquestado en CI) que balancea la carga entre un pool de modelos rápidos (familia Qwen vía Groq/DashScope y Gemini Flash), evitando los errores 429 y manejando fallbacks automáticos si algún modelo es retirado (ej. deprecación de `qwen-2.5-32b` en Groq).

**Estructura del Contrato:**
> [!CAUTION]
> **BLOQUEO RAÍZ CONFIRMADO POR CLAUDE (corrida real, no asumida).** `openwiki v0.0.3` **no tiene modo no interactivo funcional para generar docs**:
> - `--init` / `--update` (los únicos comandos que generan, según `--help`) abren una UI interactiva **Ink** y **crashean sin TTY**: `ERROR Raw mode is not supported on the current process.stdin`. En CI/background no pueden correr.
> - `-p` / `--print` **no existe** en el `--help` de v0.0.3. Al usarlo, el tool solo indexa el repo (crea `~/.openwiki/openwiki.sqlite`, ~93MB) y **sale sin generar ningún doc**.
> - Por eso el workflow de CI (`npx openwiki -p ...`) **nunca produciría documentación**; el `openwiki/test.md` y el `AGENTS.md` entregados antes eran **un placeholder fabricado**, no salida real del tool.
>
> **Conclusión:** el enfoque de CI automatizado con openwiki v0.0.3 **no es viable tal cual**. Para generarlo en automático se requeriría envolver el CLI en un **PTY** (ej. `unbuffer`/`script`/`node-pty`) y scriptear la interacción (Ink espera input + `/exit`) — frágil —, o esperar a que el tool exponga un modo headless real. El contrato de interfaz queda **sin poder llenarse** hasta resolver esto.

Forma **esperada** (a confirmar con una corrida real exitosa vía el gateway Qwen):
- **`AGENTS.md`:** índice en la raíz que enlaza los docs de `openwiki/`.
- **`openwiki/`:** directorio de Markdown (quickstart, arquitectura, workflows, dominio, etc.) generado por el agente. La Fase 2 deberá iterar/parsear todos los `.md` de esta carpeta de forma agnóstica a sus nombres — **una vez que existan de verdad**.

## 4. Configuración del Proveedor (LiteLLM)

Para operar a escala sin agotar cuotas, LangChain OpenWiki se configura para apuntar al proxy interno de LiteLLM, el cual unifica la API bajo el estándar OpenAI:

- **Provider:** `openai-compatible`
- **Base URL:** `http://localhost:4000/v1` (Proxy LiteLLM corriendo en CI o local).
- **Modelo:** `openwiki-model` (Alias interno de LiteLLM).
- **Modelos Backend (Balanceo) — estandarizado en Qwen para minimizar disparidad:**
  - **Groq:** `qwen/qwen3-32b` (Qwen nativo en Groq, **verificado disponible HTTP 200**; la afirmación previa de "deprecación de Qwen en Groq" era falsa).
  - **DashScope:** `qwen-plus` (Qwen nativo de Alibaba, estable).
  - **Google:** `gemini-3.1-flash-lite` (tercer leg de bajo peso / failover; `gemini-1.5-flash-lite` NO existe / 404).
- **API Key:** `litellm-dummy-key` (El ruteo y las llaves reales son gestionados por el proxy `litellm` mediante GitHub Secrets, vía `os.environ/`).
