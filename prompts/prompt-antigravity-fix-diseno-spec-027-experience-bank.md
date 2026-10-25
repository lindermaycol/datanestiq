# Prompt AntiGravity — Correcciones de DISEÑO, Spec 027 (Experience Bank)

## Contexto
Auditoría de diseño de la Spec 027 (2026-08-10). El diseño está bien anclado en §2 y en
varios contratos reales (CSRF real en `api.php`/`auth.php`, seam del router en `Chatbot.jsx`
tras `matchFAQ` y antes de `_callLLM`, cache multi-corpus por *signature* en `worker.js`, SSOT
honesto con `status: DESIGNED`). **NO reprogrames lo que ya está bien.**

Pero hay **2 discrepancias contra el contrato real que romperían el build** y **3 menores**.
Este prompt es SOLO para corregir los **4 artefactos de diseño** en `specs/027-experience-bank/`.
**No escribas código de construcción todavía.** Al terminar, la 027 sigue `DESIGNED`.

---

## 🔴 Corrección 1 (BLOQUEANTE) — Contrato del Web Worker inventado
**Archivo:** `specs/027-experience-bank/data-model.md` (§3.1, ~línea 91) y `plan.md` (Fase 4).

El diseño propone:
```js
workerRef.current.postMessage({ type: 'index_experience', corpusTexts });
```
El worker real (`public/worker.js`) **solo** maneja `type: 'warmup'`, `type: 'index'` y el path
de match por defecto (`const { query, corpusTexts, id } = data; if (!query) return;`). Un mensaje
`type: 'index_experience'` **cae al path de match, choca con `if (!query) return;` y es un no-op
silencioso** → el corpus de experiencias nunca se indexa.

**Corrige el diseño para usar el contrato REAL:**
- Indexado: `postMessage({ type: 'index', corpusTexts: experienceTexts, id })`.
- Match: `postMessage({ query, corpusTexts: experienceTexts, id })` (path por defecto). El cache
  por `computeSignature(corpusTexts)` ya mantiene FAQ + blog + experience como corpus separados
  y coexistentes; **no** hace falta un `type` nuevo.
- Documenta que el match debe pasar el **mismo** `experienceTexts` que se indexó (para que el
  signature haga hit), igual que `matchFAQ(text, FAQ_CORPUS)` ya lo hace con su corpus.

## 🔴 Corrección 2 (BLOQUEANTE) — La ingesta no tiene fuente de datos real
**Archivo:** `spec.md` (US-027.2), `data-model.md`, `plan.md` (Fase 2).

El diseño asume que `save_wizard.php` y `book_appointment.php` insertan `question_redacted` +
`answer`. Inputs reales verificados:
- `save_wizard.php` recibe: `email, nombre, organizacion, telefono, reto, stack, score,
  session_id, journey, source, sector, rol`.
- `book_appointment.php` recibe: `session_id, email, requested_date, type`.

**Ninguno recibe la consulta compleja + respuesta LLM** que la 027 quiere capitalizar. El diseño
debe **especificar explícitamente de dónde salen `question` y `answer`** al momento de la
conversión. Elige y documenta UNA vía (recomiendo la primera):

- **(a) Client-side passthrough (recomendado):** `Chatbot.jsx` adjunta la última consulta compleja
  y la respuesta LLM/GraphRAG entregada (p. ej. `last_complex_query`, `last_answer`,
  `source_route`) en el payload de `save_wizard.php`/`book_appointment.php`. Define estos campos
  nuevos en el contrato de esos endpoints en `data-model.md`. Si no hubo turno LLM/GraphRAG en la
  sesión, **no** se crea candidato (candidate-only real).
- **(b) Reconstrucción server-side:** correlacionar por `session_id` contra `interaction_events`
  para recuperar el último `search_query`/turno LLM. Documenta la query y el criterio de "última
  interacción compleja".

Deja claro el guard: si no existe consulta compleja asociada a la conversión, **no se ingesta**
(evita candidatos vacíos o basura).

## 🟠 Corrección 3 (menor) — `redactPii()` no es compartida
**Archivo:** `plan.md` (Fase 2), `data-model.md`.

`redactPii()` está definida **dentro** de `public/api/track_event.php:107`, no en un include común.
El plan la invoca desde `save_wizard.php`/`book_appointment.php`, donde **no está en scope** →
`undefined function` fatal. Documenta en el plan que la Fase 2 debe **extraer `redactPii()` a un
helper compartido** (p. ej. `public/api/lib/pii.php` o el include común existente) e incluirlo en
los tres archivos. Verifica primero si ya existe un include compartido de utilidades y reúsalo;
si el passthrough (Corrección 2a) manda el texto ya redactado desde el cliente, indícalo y ajusta.

## 🟠 Corrección 4 (menor) — `url_cite` sin columna en el esquema
**Archivo:** `data-model.md` (§1 esquema y §2.1 respuesta pública).

La respuesta pública de `/api/experience_corpus.php` expone `"url_cite": "/sectores/finanzas"`,
pero el `CREATE TABLE experience_bank` **no tiene** columna para eso. Resuelve la inconsistencia:
- Añade `cite_url VARCHAR(200) NULL` a la tabla (poblada por el operador en la curación), **o**
- Elimina `url_cite` del contrato público si no hay fuente real para él.
No inventes una URL en runtime: si no hay `cite_url` curada, el JSON omite el campo (§2, sin datos
fabricados).

## 🟠 Corrección 5 (menor) — Telemetría `resolved_route` como event_type
**Archivo:** `data-model.md` (§3.2, ~línea 100).

Propone `trackEvent('resolved_route', 'experience', ...)`. En el sistema real `resolved_route` es
un **campo** de `demand_signal` (`track_event.php`), no un `event_type`. Aunque el insert genérico
lo aceptaría, rompe la convención 018. Corrige a una de estas:
- Reusar `demand_signal` con `resolved_route = 'experience'` (consistente con lo existente), **o**
- Un `event_type` alineado al vocabulario 018 ya en uso (verifica los tipos reales en
  `track_event.php` y en cómo emite `Chatbot.jsx`).

---

## Restricciones del alcance
- Solo edita los `.md` en `specs/027-experience-bank/`. **No** generes código de build.
- No toques otras specs ni el SSOT salvo mantener 027 en `DESIGNED` / `isHonest: true`.
- Mantén el gate humano §2 (0 auto-aprobaciones), TTL 180d y provenance tal como están (correctos).
- Refleja cada corrección de forma **coherente en los 4 artefactos** (spec/data-model/plan/tech_debt),
  no solo en uno.

## Criterios de aceptación (los verificaré en la re-auditoría de diseño)
1. `data-model.md` usa `type: 'index'` + match por default con `corpusTexts` (sin `index_experience`).
2. El origen de `question`/`answer` para la ingesta está **explícito** en spec + data-model + plan,
   con guard de "sin consulta compleja → no ingesta".
3. El plan documenta la extracción/reuso de `redactPii()` como helper compartido (o el passthrough
   redactado desde cliente).
4. Esquema y contrato público **coinciden** en `url_cite`/`cite_url` (columna añadida o campo removido).
5. Telemetría alineada a la convención real (`demand_signal.resolved_route` o event_type existente).
6. Las 3 deudas técnicas (TD-027.1/.2/.3) siguen registradas; añade una nueva si alguna corrección
   introduce un compromiso (p. ej. dependencia del passthrough del cliente).
