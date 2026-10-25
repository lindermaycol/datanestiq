# Auditoría plan Calibración Corpus (Spec 019) — APROBADO con 1 corrección CRÍTICA + 3 refuerzos

El plan es fiel al diseño (faq.json con `pendiente_dato_usuario`, corpus combinado `objectionResponses + faq.json`,
filtrado de `answer:null` → LLM, eval offline con precision/recall, cierre de TD-019-06). Apruebo el build **con una
corrección crítica** (invalida la calibración si no se atiende) y 3 refuerzos.

## 🔴 Corrección 1 (CRÍTICA — invalida el eval) — el eval DEBE usar el MISMO modelo que producción
El plan (pasos 4 y el WARNING) usa **`Xenova/all-MiniLM-L6-v2`** en `eval_router.mjs`. **Producción usa otro modelo:**
`public/worker.js:9` → **`Xenova/paraphrase-multilingual-MiniLM-L12-v2`**. Son modelos **distintos** (L6 vs L12,
384-dim inglés-céntrico vs multilingüe): producen **embeddings y distribuciones de similitud diferentes**. Calibrar los
umbrales (`confidence_threshold`, `faq_match_threshold`) contra `all-MiniLM-L6-v2` y aplicarlos a producción daría
**umbrales equivocados** — y peor: las consultas son en **español**, donde el modelo inglés-céntrico da similitudes
poco fiables. **El eval pierde todo su valor si no corre el modelo real.**
- **Fix:** `eval_router.mjs` debe instanciar **`Xenova/paraphrase-multilingual-MiniLM-L12-v2`** (idéntico a `worker.js`),
  con los mismos parámetros (`pooling:'mean', normalize:true`, `quantized:true`/`dtype:'q8'` si aplica en Node).
- Sí, pesa más que el L6 (descarga única offline). Es innegociable: el umbral solo sirve si transfiere a producción.
- **Verificación:** el reporte del eval debe **imprimir el nombre del modelo** que usó — debe decir
  `paraphrase-multilingual-MiniLM-L12-v2`. Actualiza el WARNING del plan (menciona el L6).

## 🟠 Refuerzo 2 — el eval debe replicar EXACTO el scoring de producción
No basta con "similitud máxima". Replica la lógica real de `intentClassifier.ts`:
- **Intento:** score por intento = **promedio del top-3** de similitudes de sus ejemplos (como `classifyIntent`,
  líneas ~213-231), no el máximo global; luego compara contra `confidence_threshold`.
- **FAQ:** score = **máximo** sobre el corpus combinado (`objectionResponses + faq.json`, ya excluidas las `null`),
  compara contra `faq_match_threshold` (como `matchFAQ`).
- Si difieres del algoritmo real, precision/recall no reflejarán el ruteo real. Idealmente **importa/reusa** las mismas
  funciones o replica su fórmula 1:1.

## 🟠 Refuerzo 3 — el set de prueba DEBE incluir negativos fuera-de-catálogo (guard §2)
El objetivo NO es solo "subir recall de FAQ": es subirlo **sin** empezar a responder FAQs equivocadas (una respuesta
enlatada errónea es peor que el LLM — §2). Por eso el dataset etiquetado (~40-60) debe incluir, además de las cortas y
operativas:
- **Consultas `complejo`/fuera de catálogo** que **deben** ir al LLM (ej: "necesito un feature store", "¿me ayudan con
  mi tesis?", "¿venden laptops?").
- El reporte debe medir la **tasa de falsos positivos** (consultas que hicieron match FAQ cuando NO debían) por umbral.
- Elige los umbrales que **maximizan recall manteniendo ~0 falsos positivos** en FAQ operativa/objeciones. Si hay
  tensión, **prioriza no equivocarse** (umbral más estricto): la duda al LLM.

## 🟡 Refuerzo 4 — cómo se usan los `variants` de faq.json
Define explícitamente en `data-model`/plan: **cada `variant` se indexa como entrada matcheable** apuntando a la misma
`answer` (ej: "precio", "tarifas", "¿cuánto vale?" → todas al answer de `precio`). Así las formas cortas matchean. Si no,
`variants` queda decorativo y "¿cuánto cuesta?" seguirá fallando. Las entradas con `answer:null` se **excluyen del corpus
matcheable** por completo (ni se embeben) → su tema cae naturalmente al LLM/escape.

## OK tal como está (no cambiar)
- §2: faq.json con `pendiente_dato_usuario`, datos operativos **solo** de contenido real (contacto/privacidad/footer),
  `null` → LLM, y **reporte de pendientes** para el usuario (lcorro@oece.gob.pe). ✅
- Corpus combinado en `matchFAQ`; ampliación de ejemplos de los 4 intents; cierre de TD-019-06 con métricas. ✅
- Eval **offline** (no toca producción); escape a LLM siempre; guiado 0-LLM intacto. ✅

## Siguiente paso
1. Aplica la Corrección 1 (modelo real en el eval) + refuerzos 2-4.
2. Ejecuta el eval, elige umbrales con evidencia (recall alto, ~0 falsos positivos), documenta en `tech_debt.md`.
3. Build de Astro; deploy **dry-run → `--confirm`** del usuario. Entrégame: (a) la tabla precision/recall por intento
   antes/después, (b) el nombre del modelo del eval, (c) la **lista de FAQs `pendiente_dato_usuario`**. **Reaudito en vivo**
   ("¿cuánto cuesta?", "¿horario de atención?", "¿cómo los contacto?" → FAQ 0-LLM real, sin `chat.php`; y una fuera de
   catálogo → LLM).

---
**Nota:** Claude (Opus 4.8) reauditará en vivo. La corrección crítica es el **modelo del eval**: debe ser
`paraphrase-multilingual-MiniLM-L12-v2` (el de `worker.js`), no `all-MiniLM-L6-v2` — si no, los umbrales calibrados no
transfieren a producción. El resto del plan está bien; prioriza no responder FAQ equivocadas (§2) sobre subir recall.
