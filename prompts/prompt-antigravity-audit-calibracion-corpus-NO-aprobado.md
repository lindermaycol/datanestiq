# Auditoría Calibración Corpus (Spec 019) — NO APROBADO: la FAQ 0-LLM no funciona en producción

Reauditoría **EN VIVO** (commit `43f89e0`, `app.datanestiq.com`). Lo bueno primero: la corrección crítica del modelo
del eval se aplicó (`paraphrase-multilingual-MiniLM-L12-v2`, idéntico a producción ✅), el código es §2-honesto (las 5
FAQs operativas con `answer:null` se **excluyen** bien del corpus, `Chatbot.jsx:30-31` `filter(f => f.answer !== null)`),
el `FAQ_CORPUS` de `objectionResponses` se construye bien (`or.response`), y `cita`/`guiado` rutean 0-LLM.

**Pero el objetivo central — responder FAQ 0-LLM — NO funciona en producción.** Probé 4 consultas tipo-FAQ y **las 4
fueron al LLM** (cada una generó un `POST /api/chat.php → 200` nuevo; si hubiera resuelto por FAQ no habría `chat.php`):

| Consulta | Esperado | Real |
|----------|----------|------|
| "¿cuánto cuesta?" | — (precio es `null`) | LLM ✔ (correcto: null → LLM) |
| "¿mis datos van a la nube?" | FAQ 0-LLM (objeción) | **LLM ✗** |
| "Ya tenemos contrato con Microsoft o Google" | FAQ 0-LLM (objeción) | **LLM ✗** |
| **"Nuestros datos son demasiado sensibles para la nube"** (VERBATIM del corpus, `personas.json:106`) | FAQ 0-LLM (~1.0 similitud) | **LLM ✗** |

**Cero respuestas FAQ 0-LLM observadas.** El caso verbatim es la prueba decisiva.

## Causa raíz (confirmada) — el **gate de intención** bloquea `matchFAQ`
El ruteo (`Chatbot.jsx:303`) solo llama a `matchFAQ` **si `classifyIntent` devuelve `'faq'`**:
```js
if (intent === 'faq') { const faqMatch = await matchFAQ(text, FAQ_CORPUS); ... }
// si intent es complejo/guiado/unknown → NUNCA se intenta la FAQ → _callLLM
```
Las objeciones reales son **declarativas** ("Nuestros datos son demasiado sensibles...", "Ya tenemos contrato con...")
y clasifican como **`complejo`/`guiado`**, no `faq` — así que **nunca llegan al matcher**, por más que el texto sea
**verbatim** del corpus (habría dado ~1.0, muy por encima de 0.89). El corpus está bien; el problema es el **gate de
dos etapas**: `classifyIntent` → (solo si `faq`) → `matchFAQ`.

**Secundario:** `faq_match_threshold: 0.89` es **demasiado estricto** aun cuando el gate pasa — paráfrasis reales de
objeciones difícilmente llegan a 0.89. Combinado con el gate, la ruta FAQ queda **prácticamente desactivada**.

## Por qué el eval dijo "recall 0.88" y producción da 0
El eval reportó FAQ recall 0.88 / precision 1.0, pero **no refleja producción**. Señales de que el eval midió otra cosa:
- Probablemente evaluó **`classifyIntent` en aislamiento** (o el match contra el corpus con queries casi-copia de las
  entradas), **no el pipeline real end-to-end** (`classifyIntent` → gate `intent==='faq'` → `matchFAQ`).
- Es el patrón recurrente "eval verde, roto en vivo". El eval **debe** ejercer el camino real completo.

Además, el reporte llama "**caso clave resuelto: ¿cuánto cuesta? → faq (antes: LLM)**" — es **engañoso**: `precio.answer`
es `null` → esa consulta **sigue yendo al LLM**. No reportes como "resueltas" las FAQ con dato pendiente.

## Correcciones

### 🔴 1 (arquitectural, la clave) — desacoplar la respuesta FAQ del gate de intención
No condiciones `matchFAQ` a `intent==='faq'`. Intenta el match FAQ **siempre que la alternativa sería ir al LLM**
(es decir, para `intent ∈ {faq, complejo, unknown}`). Patrón sugerido — **FAQ-first sobre las ramas que irían al LLM**:
```js
// cita y guiado conservan su ruta fuerte (UI/flujo). Para el resto:
if (intent === 'cita')   { ...AppointmentPicker; return; }
if (intent === 'guiado') { ...flujo guiado; return; }
// faq | complejo | unknown → intenta FAQ primero; si hay match fuerte, 0-LLM; si no, LLM
const faqMatch = await matchFAQ(text, FAQ_CORPUS);
if (faqMatch) { ...responder verbatim + escape; return; }
await _callLLM(text, ...);
```
Así una objeción declarativa que clasifica `complejo` igual recibe su respuesta de la taxonomía (0-LLM) en vez del LLM.
**§2 se preserva con el umbral:** una consulta genuinamente compleja ("necesito un feature store") no matchea ninguna
objeción a alta similitud → sigue al LLM. La duda cae al LLM, pero ya no por un gate que ignora una FAQ perfecta.

### 🔴 2 (eval válido) — medir el pipeline REAL end-to-end, no `classifyIntent` aislado
`eval_router.mjs` debe simular el **camino de producción completo**: para cada consulta de prueba, correr
`classifyIntent` **y luego** la lógica de ruteo (con el nuevo FAQ-first) y reportar el **resultado final real**:
`0llm-faq` / `0llm-cita` / `0llm-guiado` / `llm`. La métrica que importa es la **tasa de resolución 0-LLM real** y los
**falsos positivos FAQ** (respuestas enlatadas equivocadas), no la precisión de `classifyIntent` sola.
- El set de prueba debe usar **paráfrasis realistas** de objeciones (no copias del corpus) como positivos, y
  **negativos fuera de catálogo** (deben ir a LLM). Solo así el número transfiere a producción.

### 🟠 3 (umbral) — recalibrar `faq_match_threshold` con el eval arreglado
0.89 es casi seguro muy alto para paráfrasis. Tras arreglar el eval (corrección 2), reelige el umbral que **maximiza
resolución FAQ 0-LLM real manteniendo ~0 falsos positivos** sobre los negativos realistas. Documenta el valor y la
matriz en `tech_debt.md`. (No lo bajes a ciegas: el eval válido manda.)

### 🟡 4 (honestidad del reporte)
No reportes FAQs con `answer:null` (pendiente_dato_usuario) como "resueltas". El estado real hoy: las 5 operativas están
pendientes de tu dato (correcto §2), y las de objeciones **deben** empezar a resolver 0-LLM tras la corrección 1.

## Verificación (reaudito EN VIVO, mismo método que encontró esto)
1. **Objeción verbatim** ("Nuestros datos son demasiado sensibles para la nube") → respuesta **de la taxonomía**
   (`response` real) **sin** `chat.php`, con botón de escape. **Hoy falla — debe pasar.**
2. **Paráfrasis de objeción** ("¿mis datos van a la nube?") → FAQ 0-LLM si supera umbral; si no, LLM (aceptable si el
   umbral es honesto). Reporta el score real.
3. **Consulta compleja de verdad** ("necesito un feature store para mi equipo de ML") → **LLM** (no FAQ forzada). §2.
4. **FAQ operativa pendiente** ("¿cuánto cuesta?") → LLM (porque `precio.answer` es null), **no** una FAQ en blanco.
5. `cita`/`guiado` 0-LLM intactos; sitio sano; eval end-to-end con tasa 0-LLM real + FP, y el nombre del modelo.

## Deploy
- TS/JSON → build Astro. Deploy **dry-run → `--confirm`**. Entrégame: (a) tabla end-to-end (0llm-faq/cita/guiado/llm +
  FP) del eval corregido, (b) los umbrales elegidos, (c) confirmación de que la objeción verbatim resuelve 0-LLM en vivo.

---
**Nota:** Claude (Opus 4.8) verificó en vivo que **ninguna** FAQ resuelve 0-LLM hoy (incluida una objeción verbatim del
corpus → LLM). La causa es el **gate `intent==='faq'`** que impide `matchFAQ` para objeciones declarativas
(clasificadas `complejo`/`guiado`), agravado por un umbral 0.89 muy estricto. El eval "verde" no midió el pipeline real.
Corrige el ruteo (FAQ-first sobre las ramas LLM), arregla el eval (end-to-end) y recalibra. El código §2 (filtrado de
`null`, sin inventar) está bien; el problema es de ruteo, no de honestidad.
