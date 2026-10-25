# BUILD — Calibración ampliada del Router 0-LLM (Spec 019): más escenarios + FAQ operativa real

Tras la reauditoría en vivo, el router 0-LLM **funciona** (`cita` → `AppointmentPicker` sin `chat.php`, confirmado).
Pero "¿cuánto cuesta?" cayó al LLM pese a que `intents.json` ya tiene "¿Cuánto cuesta implementar una solución de
datos?". Diagnóstico: la calibración es **estructural**, no solo "agregar frases". Este build la amplía a **muchos
escenarios reales** y arregla la fuente de respuestas FAQ. **Es tuning de la 019, no nueva spec.**

## Problema raíz (dos capas)
1. **Umbral vs consultas cortas.** `confidence_threshold: 0.72` es alto para consultas telegráficas ("¿cuánto
   cuesta?", "¿horario?"). El promedio de similitud coseno de una frase de 3 palabras contra ejemplos largos baja del
   umbral → `unknown` → LLM.
2. **La ruta `faq` responde SOLO desde `objectionResponses`.** Hoy `matchFAQ` compara contra las objeciones de
   `personasCorpus`. Pero preguntas **operativas** (horario de atención, cómo contactar, ubicación, formas de pago)
   **no son objeciones** → no existen en ese corpus → `matchFAQ` retorna null → LLM. Falta una **base de FAQ operativa**.

## WHAT (alcance del build)

### 1. Base de conocimiento FAQ dedicada (nueva fuente de verdad, versionada)
Crea `src/data/faq.json` (o colección de contenido equivalente) con **preguntas frecuentes reales + respuestas
verbatim**, separada de `objectionResponses` (que sigue existiendo para objeciones comerciales). Estructura sugerida:
```json
{
  "version": "1.0.0",
  "faqs": [
    { "id": "precio", "question": "¿Cuánto cuesta?", "variants": ["¿cuál es el precio?", "tarifas", "presupuesto", "¿cuánto vale un proyecto?"], "answer": "<respuesta real>" },
    { "id": "horario", "question": "¿Cuál es el horario de atención?", "variants": ["¿cuándo responden?", "¿a qué hora atienden?"], "answer": "<respuesta real>" }
  ]
}
```
El `matchFAQ` de la ruta `faq` debe buscar contra **`objectionResponses` + `faq.json`** (unión de ambos corpus), y
responder verbatim el mejor match ≥ umbral. Sigue con **botón de escape a LLM** siempre.

**🔴 §2 (Honestidad radical) — NO INVENTAR datos operativos.** Las respuestas de `faq.json` para hechos operativos
(horario, ubicación/oficina, teléfono, correo, WhatsApp, formas de pago, modalidad remoto/presencial, cobertura
geográfica, idiomas) deben salir de **contenido real ya existente** del sitio (footer, página de contacto,
`privacidad.astro`, datos de la empresa) — **jamás fabricados**. Para cada FAQ operativa cuyo dato real **no exista**
en el proyecto, NO inventes: pon `"answer": null` + `"pendiente_dato_usuario": true` y **lístalo en el reporte** para
que el usuario (lcorro@oece.gob.pe) lo complete. Mientras `answer` sea null, esa consulta cae al **LLM/escape**, no a
una respuesta enlatada falsa. Las FAQ de negocio (precio, metodología, tiempos, garantías, ROI) pueden derivarse de la
taxonomía real (`pillars`/`sectors`/`objectionResponses`) — sin cifras inventadas; si el precio es "a medida",
la respuesta honesta es que depende del alcance + invitar a diagnóstico (0-LLM → `cita`).

### 2. Ampliar `intents.json` — cubrir muchos más escenarios
Enriquece los ejemplos de los 4 intents (más densidad = mejor promedio de similitud), incluyendo variantes **cortas**
y coloquiales. Escenarios reales a cubrir en el intent `faq` (además de los actuales):
- **Precio/inversión:** "¿cuánto cuesta?", "precio", "tarifas", "presupuesto", "¿es caro?", "¿cuánto vale?", "formas de pago", "¿facturan?".
- **Horario/disponibilidad operativa:** "¿horario de atención?", "¿cuándo responden?", "¿atienden fines de semana?".
- **Contacto/ubicación:** "¿cómo los contacto?", "¿dónde están?", "¿tienen oficina?", "¿trabajan remoto?", "¿WhatsApp?", "¿correo?".
- **Tiempos:** "¿cuánto tarda?", "¿duración del proyecto?", "¿en cuánto tiempo veo resultados?".
- **Metodología/alcance:** "¿cómo trabajan?", "¿qué incluye?", "¿etapas?", "¿soporte post-proyecto?", "¿garantías?", "¿SLA?".
- **Confianza:** "¿casos de éxito?", "¿referencias?", "¿experiencia en mi sector?", "¿seguridad de datos?", "¿confidencialidad?".
- **A quién atienden:** "¿trabajan con pymes?", "¿qué sectores?", "¿empresas grandes?".

Enriquece también `cita`, `guiado` y `complejo` con 4-6 variantes cortas cada uno (p. ej. `cita`: "agéndame",
"llámenme", "quiero hablar"; `guiado`: "¿qué hacen?", "¿por dónde empiezo?", "ayúdame a elegir").

### 3. Recalibrar umbrales (con evidencia, no a ojo)
- Arma un **set de prueba etiquetado** (`scripts/eval_router.mjs` o similar, offline): ~40-60 consultas reales con su
  intención esperada (incluye las cortas y las operativas). Mide precisión/recall por intento con el clasificador actual.
- Ajusta `confidence_threshold` y `faq_match_threshold` (globales, o **por-intento** si mejora) para maximizar aciertos
  **sin** degradar §2 (la duda debe seguir cayendo al LLM; una FAQ equivocada es peor que ir al LLM). Documenta los
  valores elegidos y el porqué en `tech_debt.md` (cierra/actualiza TD-019-06).
- Si el modelo lo permite, considera un **umbral asimétrico**: `cita` puede ser algo más permisivo (baja consecuencia:
  abre el picker, con escape); `faq` operativa debe ser estricto (alta consecuencia: dato potencialmente incorrecto).

### 4. Sin tocar lo que ya funciona
- No cambies el mecanismo del worker ni `workerRequest` (bugs #1/#2/#3 ya cerrados). No toques el failover de `chat.php`.
- El flujo guiado por botones sigue 0-LLM. El escape a LLM sigue siempre visible.

## Verificación (reaudito EN VIVO, mismo método)
1. **FAQ 0-LLM real:** "¿cuánto cuesta?", "¿horario de atención?", "¿cómo los contacto?" → respuesta **de la taxonomía/
   faq.json** (verbatim), **sin** `POST /api/chat.php`, con botón de escape. (Hoy estas fallan → deben pasar, salvo las
   marcadas `pendiente_dato_usuario`.)
2. **Sin inventar:** ninguna respuesta operativa contiene datos que no existan en el proyecto. Repórtame la lista de
   FAQs `pendiente_dato_usuario`.
3. **Duda sigue al LLM:** consulta ambigua/fuera de catálogo → `chat.php` 200 (no FAQ forzada).
4. **cita/guiado 0-LLM intactos:** "agéndame" → `AppointmentPicker`; "¿qué hacen?" → guiado. Sin `chat.php`.
5. **Eval reproducible:** entrégame la tabla de precisión/recall por intento antes/después de recalibrar.
6. **Sitio sano** (app 200 / admin 302 / WP 200); `trackEvent('intent_routing', …)` registra las nuevas decisiones.

## Deploy
- TS/JSON → build de Astro. Si tocas PHP, `php -l`. Deploy **dry-run → `--confirm`** del usuario. Reporte + reaudito.

---
**Nota:** Claude (Opus 4.8) reauditará en vivo que las nuevas FAQ (precio, horario, contacto, tiempos…) resuelven
**0-LLM desde datos reales**, que nada operativo se inventó (§2), que la duda sigue cayendo al LLM, y que la recalibración
tiene evidencia (eval etiquetado). Las FAQ operativas sin dato real quedan `pendiente_dato_usuario` para que las
completes tú, no inventadas.
