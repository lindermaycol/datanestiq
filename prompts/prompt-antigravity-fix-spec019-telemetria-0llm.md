# FIX menor (Spec 019) — la telemetría subcuenta el % 0-LLM (FAQ-first no se refleja en `resolved`)

Auditoría en vivo: el ruteo FAQ-first quedó **correcto y funcional** (objeción verbatim y paráfrasis → 0-LLM sin
`chat.php`; consulta compleja → LLM sin falso positivo). Solo queda un ajuste de **instrumentación**, no de ruteo.

## Problema
En `src/components/islands/Chatbot.jsx` (~línea 279), el flag de telemetría se calcula **antes** del match FAQ-first:
```js
const routingResolved = ['faq', 'cita', 'guiado'].includes(intent) ? '0llm' : 'llm';
trackEvent('intent_routing', 'chatbot-router', JSON.stringify({ intent, confidence: confidence.toFixed(3), resolved: routingResolved }));
...
// más abajo, para intent complejo/unknown:
const faqMatch = await matchFAQ(text, FAQ_CORPUS);
if (faqMatch) { ...return; }  // ← ESTO es 0-LLM, pero ya se registró resolved:'llm'
```
Una objeción declarativa que clasifica como `complejo` pero **sí** resuelve por FAQ (0-LLM) se registra como
`resolved:'llm'`. Resultado: la métrica "% resuelto 0-LLM" — justo la que instrumentamos para medir el ahorro del
router — **subcuenta**. El ruteo funciona bien; solo el número miente a la baja.

## Fix
Registra la decisión **después** de conocer el resultado real del match FAQ-first. Sugerido:
```js
const { intent, confidence } = await classifyIntent(text);

// cita / guiado: 0-LLM inmediato
if (intent === 'cita')   { trackEvent('intent_routing','chatbot-router', JSON.stringify({intent, confidence:confidence.toFixed(3), resolved:'0llm', route:'cita'})); ...return; }
if (intent === 'guiado') { trackEvent('intent_routing','chatbot-router', JSON.stringify({intent, confidence:confidence.toFixed(3), resolved:'0llm', route:'guiado'})); ...return; }

// FAQ-first sobre faq|complejo|unknown
const faqMatch = await matchFAQ(text, FAQ_CORPUS);
if (faqMatch) {
  trackEvent('intent_routing','chatbot-router', JSON.stringify({intent, confidence:confidence.toFixed(3), resolved:'0llm', route:'faq', faqScore:(faqMatch.score ?? 0).toFixed(3)}));
  ...return;
}
// sin match → LLM
trackEvent('intent_routing','chatbot-router', JSON.stringify({intent, confidence:confidence.toFixed(3), resolved:'llm', route:'llm'}));
await _callLLM(...);
```
- Añade `route` (`cita`/`guiado`/`faq`/`llm`) para desglosar **de dónde** viene cada resolución 0-LLM (útil para la
  futura Spec 020). Incluye `faqScore` cuando resuelve por FAQ (ayuda a re-calibrar el umbral con datos reales).
- **No** cambies la lógica de ruteo (ya está correcta). Es solo mover/duplicar el `trackEvent` al punto de decisión real.
- Sin PII: `intent_routing` no debe registrar el texto del usuario (solo intent/score/route), consistente con 018.

## Verificación
- En `/admin/` (o en `interaction_events`), tras una objeción que resuelve por FAQ, el evento debe decir
  `resolved:'0llm', route:'faq'` (hoy diría `'llm'`). Una consulta compleja real → `resolved:'llm', route:'llm'`.
- Sitio sano; ruteo 0-LLM/LLM sin cambios de comportamiento (solo la métrica corregida).

## Deploy
- Solo TS → build Astro. Deploy **dry-run → `--confirm`**. Es de bajo riesgo (instrumentación).

---
**Nota:** Claude (Opus 4.8) verificó que el ruteo funciona; este fix solo alinea la **medición** con la realidad para
que el "% 0-LLM" no subcuente las FAQ resueltas desde ramas `complejo`/`unknown`.
