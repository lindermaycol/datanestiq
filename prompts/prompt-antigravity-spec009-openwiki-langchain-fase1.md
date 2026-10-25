# Prompt para Antigravity: Spec 009 (NUEVA) — Adoptar OpenWiki de LangChain con Gemini (FASE 1)

Actúa como **Ingeniero de plataformas de IA + auditor SDD**. **Sigue el pipeline SDD estrictamente**: (1) crea `specs/009-openwiki-langchain/spec.md`, (2) entrega `plan.md` + `tasks.md` para mi revisión, (3) recién tras mi visto bueno, implementa. No implementes antes de que apruebe el plan.

## Contexto y corrección importante
En una auditoría previa se afirmó erróneamente que el CLI `openwiki` era "ficticio". **Es falso: `langchain-ai/openwiki` existe y es real** (CLI de LangChain en TypeScript, ~9.7k ⭐). Lo que estaba mal en el workflow original era el **comando** (`--sync` no existe; los reales son `--init`/`--update`) y el modelo obsoleto. Esta spec adopta la herramienta **de verdad**.

**Distinción clave (por qué es una spec nueva y no un reemplazo):** hay DOS "OpenWiki" con propósitos distintos:
- **`langchain-ai/openwiki` (esta Spec 009):** genera documentación **para agentes de IA** (carpeta `openwiki/` + `AGENTS.md`/`CLAUDE.md`) a partir del código. Consumidor: agentes (Claude Code, Antigravity).
- **Nuestro `scripts/openwiki-sync.mjs` (Spec 005 existente):** genera **páginas web para humanos** en `/wiki/[slug]`. Consumidor: visitantes del sitio.

**Arquitectura objetivo por fases** (documenta esto en `spec.md`):
- **FASE 1 (ESTA spec, lo único a implementar ahora):** adoptar `langchain-ai/openwiki` + Gemini y generar la doc de agentes en CI. **NO tocar** `openwiki-sync.mjs` ni el wiki humano.
- **FASE 2 (futura, solo mencionar como dependencia):** el Spec 005 (wiki humano) pasará a **consumir** la salida de esta Spec 009 como fuente rica (en vez de `git diff` crudo), vía una **capa adaptadora**. Es decir, **Spec 005 dependerá de Spec 009**. Deja esa dependencia declarada pero NO la implementes en Fase 1.

## Hechos técnicos verificados (pero VERIFÍCALOS de nuevo — no asumas)
> ⚠️ Dado el error previo, **antes de escribir CI: instala la herramienta y córrela localmente de verdad**, revisa `openwiki --help` y el README oficial, y confirma flags/variables reales. No inventes firmas.

- **Instalación:** `npm install -g openwiki`.
- **Comandos reales:** `--init`, `--update`, `-p`/`--print` (one-shot no interactivo), `--help`.
- **Config (provider OpenAI-compatible), en `~/.openwiki/.env`:**
  ```
  OPENWIKI_PROVIDER=openai-compatible
  OPENAI_COMPATIBLE_API_KEY=<clave>
  OPENAI_COMPATIBLE_BASE_URL=<base url>
  OPENWIKI_MODEL_ID=<modelo>
  ```
- **Para Gemini (verificado en vivo por Claude):**
  - `OPENAI_COMPATIBLE_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai` (la capa OpenAI-compatible de la Generative Language API; el SDK le añade `/chat/completions`). **NO** es `https://googleapis.com` como decía la imagen de referencia — esa URL es incorrecta.
  - `OPENWIKI_MODEL_ID=gemini-2.5-flash`. **Verificado:** `gemini-2.5-flash` responde 200; `gemini-2.5-pro` y `gemini-2.0-flash` dieron **429 (cuota)** con esta clave; `gemini-1.5-flash` da **404 (deprecado)**. Usa `gemini-2.5-flash` por defecto.
  - ⚠️ **Ojo con el ID del modelo:** la imagen mostraba `google/gemini-2.5-pro` — ese prefijo `google/` es de proxies tipo OpenRouter, **NO** del endpoint nativo de Google. Con la Generative Language API el ID es `gemini-2.5-flash` a secas.
  - `OPENAI_COMPATIBLE_API_KEY` = la `GEMINI_API_KEY` (ya está en `.env` local; en CI será el secret `GEMINI_API_KEY`).
- **Salida:** crea la carpeta `openwiki/` (en la raíz) y **anexa** instrucciones a `AGENTS.md`/`CLAUDE.md`; refresca desde cambios del repo si `openwiki/` ya existe. Target: agentes.

## Qué construir (Fase 1)

1. **[NEW] `specs/009-openwiki-langchain/spec.md`** con la distinción de propósitos, el enfoque por fases y la dependencia 005→009 (futura).

2. **Adopción del tool en CI — [NEW/MODIFY] workflow:**
   - Un GitHub Action (nuevo `openwiki-langchain.yml`, o job separado; **no mezcles** con `openwiki-audit.yml` de la Spec 005) que: instale `openwiki`, lo corra en modo **no interactivo** con Gemini (`--update` o `-p`, el que corresponda tras verificar), y abra un PR con la doc de agentes generada (`peter-evans/create-pull-request@v6`).
   - Config por **variables de entorno del step** (no por `~/.openwiki/.env` versionado): `OPENWIKI_PROVIDER`, `OPENAI_COMPATIBLE_BASE_URL`, `OPENWIKI_MODEL_ID` como valores fijos, y `OPENAI_COMPATIBLE_API_KEY: ${{ secrets.GEMINI_API_KEY }}`.
   - Si el tool exige el `~/.openwiki/.env`, genéralo en runtime dentro del runner desde esas env vars (nunca lo commitees).

3. **[NEW] Contenido inicial real:** corre el tool una vez y **commitea la carpeta `openwiki/` generada** (los agentes leen el repo, así que debe versionarse). Captura y adjunta al PR una **muestra real** del formato de salida.

## Higiene técnica (requerida)
- **Desambiguación de nombres:** existe confusión entre el `openwiki/` (raíz, del tool) y nuestra colección humana `src/content/openwiki/`. **Renombra la colección humana a `src/content/wiki/`** y actualiza `src/content.config.ts`, la ruta `/wiki/[slug]`, y el `outPath` de `scripts/openwiki-sync.mjs`. Verifica con `npm run build` (deben seguir siendo 22 páginas y `/wiki/arquitectura` renderizando). *(Este es el único toque permitido al lado humano en Fase 1, y es solo un rename seguro.)*
- **No clobber:** el tool **anexa** a `AGENTS.md`/`CLAUDE.md`. Verifica que **no sobreescriba** contenido existente de `CLAUDE.md` si ya lo hubiera; si el repo ya tiene uno, respáldalo y confirma que solo se anexó.
- **Secrets fuera del repo:** nada de claves en archivos versionados. `.env.example`: documenta las vars `OPENWIKI_*` para uso local, con placeholders.
- **`.gitignore`:** ignora `~/.openwiki/` no aplica (está en HOME), pero asegúrate de no versionar ningún `.env` real ni caché local del tool.

## Mitigación del riesgo real (acoplamiento al formato de salida)
El riesgo de la arquitectura por fases es que el **wiki humano (Fase 2) quede acoplado al formato de salida** de `langchain-openwiki`, que LangChain puede cambiar. Para mitigarlo desde ya:
- En `spec.md`, **documenta explícitamente el "contrato de interfaz"**: qué archivos/estructura produce el tool (con la muestra real capturada), que será lo que el adaptador de la Fase 2 consuma.
- Deja **anotado** que la Fase 2 introducirá una **capa adaptadora** delgada (único punto que conoce el formato de `openwiki/`), de modo que un cambio de formato de LangChain se absorba en un solo lugar. NO construyas el adaptador ahora; solo define la frontera.
- Mantén Spec 009 y Spec 005 **desacopladas** en Fase 1 (salvo el rename de higiene).

## Decisiones a resolver en el plan (SDD)
- Comando exacto no interactivo para CI (`--update` vs `-p`), verificado contra `openwiki --help`.
- Si `openwiki/` se commitea (recomendado sí) y si `AGENTS.md`/`CLAUDE.md` van al PR.
- Alcance de análisis del tool (todo el repo vs subset) y costo estimado con `gemini-2.5-flash`.
- Numeración/nombre de carpeta de la spec (`009-openwiki-langchain`) y actualización de `planes/ESTADO-SPECS.md` (nueva fila 009 + nota de dependencia 005→009).

## Verificación (E2E, al implementar)
1. **Corrida local real** con Gemini: `openwiki --update` (o el flag correcto) genera `openwiki/` + AGENTS.md con contenido **fundamentado en el código** (no genérico). Adjunta muestra.
2. **Modelo/URL:** demuestra que la config de Gemini (`gemini-2.5-flash` + base URL corregida) funciona (log de la corrida).
3. **Higiene:** `npm run build` sigue verde tras el rename a `src/content/wiki/` (22 páginas, `/wiki/arquitectura` OK); `CLAUDE.md` no perdió contenido.
4. **CI:** el workflow es válido (`workflow_dispatch` disponible) y usa `secrets.GEMINI_API_KEY`.

## Forma de respuesta
- **Primero** `spec.md` + `plan.md` + `tasks.md` para mi revisión. NO implementes aún.
- Al implementar, reporta la verificación E2E con la muestra real de salida del tool.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el tool usado sea el real de LangChain corrido de verdad (no asumido), que la config de Gemini use la base URL corregida y `gemini-2.5-flash`, que el rename de higiene no rompa el build, que `CLAUDE.md` no se sobreescriba, y que la frontera para la Fase 2 (contrato de salida + adaptador futuro) quede documentada sin acoplar todavía. **Pendiente del usuario:** configurar el secret `GEMINI_API_KEY` en GitHub.
