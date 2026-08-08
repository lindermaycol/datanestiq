# Spec 019 (DISEÑO) — Router Determinístico Ampliado del Chatbot (más rutas 0-LLM)

**Tarea de DISEÑO SDD, NO implementación.** Escribe los artefactos de la nueva **Spec 019**
(`specs/019-router-0llm-ampliado/`) con los **5 bloques** (WHY/WHAT/CONSTRAINTS/OUT-OF-SCOPE/TASKS) +
`data-model.md` (config del clasificador + fuente de FAQ) + `plan.md` + `tech_debt.md`. **Entrégalos para que
Claude los audite ANTES de implementar.** Cero runtime todavía.

## Contexto (verificado)
- Hoy el chatbot tiene **2 rutas**: flujo guiado (botones, **0-LLM**) y **texto libre → `chat.php`** (LLM con
  failover Groq→DashScope→Gemini). Es decir, **toda** consulta de texto libre gasta LLM.
- El proyecto **ya carga un modelo Xenova en el cliente** (`@xenova/transformers`, buscador semántico, `public/worker.js`)
  — embeddings en el navegador, **0 tokens LLM**. La taxonomía tiene `objectionResponses` (objeción→respuesta) y FAQ reales.
- Existe `<AppointmentPicker>` (Spec 015) para agendar.

## WHY
Reducir costo/latencia y aumentar el determinismo: **muchas** consultas de texto libre son en realidad FAQ,
intención de agendar, o algo que el flujo guiado resuelve — y hoy **todas** llaman al LLM. Clasificándolas en
cliente (0-LLM) se resuelven sin gastar LLM; solo lo genuinamente **complejo** escala a `chat.php`.

## WHAT (alcance — diseño)
### 1. Clasificador de intención en cliente (reusa el Xenova ya cargado)
- Al recibir texto libre, **embebe la consulta** (Xenova, mismo worker del buscador — **no** un modelo nuevo) y
  compárala contra **prototipos de intención** (embeddings de frases-ejemplo curadas por intención) → coseno → intención
  más cercana con su **confianza**.
- Intenciones: **`faq` · `cita` · `guiado` · `complejo`** (o `unknown` por baja confianza).

### 2. Ruteo por intención
- **`faq` (0-LLM):** responde con la **respuesta real de la taxonomía** (`objectionResponses`/FAQ más cercana). **Nunca**
  inventes la respuesta. Muestra siempre un **escape**: *"¿No era lo que buscabas? Pregúntame libremente →"* (→ LLM).
- **`cita` (0-LLM):** abre el `<AppointmentPicker>` (Spec 015) directamente.
- **`guiado` (0-LLM):** ofrece el flujo guiado / recomendación de servicio (reuso de `semanticHighlight`).
- **`complejo` O baja confianza → `chat.php` (LLM):** fallback exacto de hoy. **La duda SIEMPRE cae al LLM**, no a una FAQ forzada.

### 3. Instrumentación
- Registra la **decisión de ruteo** (intención + confianza + si fue 0-LLM o LLM) en la observabilidad existente
  (`chat_metrics` de 016 y/o `interaction_events` de 018) para medir **cuántas consultas se resolvieron 0-LLM**.

## CONSTRAINTS (declararlas en el spec)
- **§5 (0-LLM) — es el objetivo:** la clasificación es **100% cliente** (Xenova, 0 tokens LLM); `faq`/`cita`/`guiado`
  son 0-LLM. **Solo** `complejo`/baja-confianza llama a `chat.php`. Declara honestamente que esto **aumenta** las rutas 0-LLM.
- **§2 (Honestidad):** las respuestas `faq` salen de la **taxonomía real** (`objectionResponses`/FAQ), jamás fabricadas.
  **Umbral de confianza alto**: ante duda → **LLM fallback**, nunca una FAQ equivocada (una respuesta enlatada errónea
  es peor que la del LLM).
- **UX:** siempre un **escape** a texto libre/LLM; el usuario nunca queda atrapado en una respuesta enlatada.
- **Reuso:** el **mismo worker Xenova** (sin modelo nuevo); `<AppointmentPicker>` (015); `objectionResponses` de la
  taxonomía. Free-tier; Claude solo audita.
- **No romper:** el flujo guiado por botones sigue **0-LLM intacto**; el failover de `chat.php` no se toca.

## OUT-OF-SCOPE (declararlo)
- **NO** usar un LLM para clasificar (la clasificación es cliente/Xenova). **NO** fabricar respuestas de FAQ.
- **NO** reemplazar el flujo guiado (lo extiende). **NO** tocar el failover Groq→DashScope→Gemini de `chat.php`.
- GraphRAG / MCP endpoint / buscador sobre blog (otros ítems del backlog).

## data-model.md (define)
- **Config de intenciones:** el set curado de **frases-ejemplo por intención** (`faq`/`cita`/`guiado`) del que se
  derivan los prototipos (embeddings al cargar), en un archivo versionado (ej. `src/data/intents.json` o taxonomía) —
  **fuente de verdad**, editable, sin hardcodear en el JSX.
- **Mapeo FAQ:** cómo la consulta clasificada `faq` se resuelve a la `objectionResponse`/FAQ real más cercana.
- **Umbral de confianza** (parámetro) y la regla de fallback a LLM.

## Entregables (solo diseño)
- `specs/019-router-0llm-ampliado/{spec.md, data-model.md, plan.md, tech_debt.md}`.
- Doc-sync: fila 019 en `ESTADO-SPECS.md` (🟠 diseñada) **y** `src/data/specsStatus.json` (recuerda el **build-gate
  antidrift** de la 017: deben coincidir o el build falla). `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará el diseño: clasificación **100% cliente** (0 tokens LLM), FAQ de la **taxonomía
real** (§2, sin inventar), **fallback a LLM ante baja confianza** (no FAQ forzada), escape a texto libre siempre, reuso
del worker Xenova y del AppointmentPicker (sin duplicar), 0-LLM del guiado intacto, y consistencia
`specsStatus.json` ↔ `ESTADO-SPECS.md`. Tras mi visto bueno se pasa a build.
