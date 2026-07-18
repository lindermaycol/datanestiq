# Prompt para Antigravity — 3 fixes de conversión (del recorrido en primera persona del CFO)

Un recorrido en primera persona (CFO de aseguradora) dio un veredicto **"tal vez, 13/15"** y 3 brechas de conversión **de código** (ya diagnosticadas con precisión). El resto de sus quejas de credibilidad (logos/testimonio fabricados) ya están resueltas — **no las toques**. Implementa estos 3:

## 🎯 Fix 1 (alto ROI) — La calculadora de ROI es INDESCUBRIBLE desde donde el CFO la busca
**Diagnóstico:** `/business-case` (el Business Case Estimator) **no está enlazado desde ningún lado** (grep = 0 referencias) y el CTA consultivo **siempre** hace `open-chatbot` (`ConsultativeCTA.jsx` línea 21), nunca lleva a la calculadora. El CFO hizo clic en *"Auditoría de ROI y pérdidas evitables"* esperando una calculadora y solo se abrió el chatbot. Su *"UNA cosa que me haría decir sí"* es exactamente esa calculadora en su sector.

**Fix (haz ambos):**
1. **Enlaza `/business-case` de forma visible desde las landings de `finanzas` y `seguros`** (`src/pages/sectores/[slug].astro`, condicional a que el sector sea financiero o tenga CFO en `relevantPersonas`): un bloque/botón claro tipo *"Calcula tu Business Case de ROI →"* que lleve a `/business-case`.
2. **CTA dinámico contextual:** cuando `userContext` sea CFO / finanzas / seguros, que `<ConsultativeCTA>` **lleve a `/business-case`** (renderiza un `<a href="/business-case">` en vez del `open-chatbot`) — el comprador económico quiere una herramienta, no un chat. Mantén `open-chatbot` para los demás contextos (público/CEO/neutro).

## 🔴 Fix 2 — El chatbot renderiza el Markdown como texto plano
**Diagnóstico:** `formatText` en `src/components/islands/Chatbot.jsx` (~línea 226) solo convierte `**negrita**`, `*itálica*`, `[texto](url)` y saltos de línea. **No maneja listas** (numeradas `1. ` ni viñetas `- `/`* `), así que las opciones que devuelve el LLM se ven en crudo (el CFO vio `[Tienes la opción de elegir]{1. Implementar...}`). Además, el regex `\*(.*?)\*` de itálica **puede romper** una viñeta que empiece con `* `.

**Fix:** mejora `formatText` (o extrae un helper de render markdown) para que:
- Renderice **listas numeradas** (`^\d+\.\s`) y **viñetas** (`^[-*]\s`) como ítems legibles (con sangría/estilo), no en crudo.
- El regex de itálica **no** confunda viñetas al inicio de línea (procesa las listas por línea **antes** de aplicar `*itálica*`, o ancla la itálica para que no matchee `* ` inicial).
- Mantén el escape seguro (sigue usando `dangerouslySetInnerHTML` con contenido controlado; no introduzcas XSS: escapa `<`/`>` del texto del LLM antes de inyectar las etiquetas de formato).
- Prueba con una respuesta típica del bot que tenga una lista numerada de 3 opciones.

## Fix 3 — El chatbot pide el email ANTES de responder (fricción de venta prematura)
**Diagnóstico:** en texto libre, el bot pidió el correo **antes** de responder la pregunta de ROI/timeframe del CFO — *"venta prematura"*. El `SYSTEM_PROMPT` de `public/api/chat.php` obliga a pedir contacto, pero el modelo lo hace demasiado pronto.

**Fix (system prompt de `chat.php`):** reordena la instrucción para **valor antes de contacto**: primero responde/aporta valor útil a la pregunta del usuario; pide correo y teléfono **solo después** de haber entregado una respuesta útil, o cuando el usuario muestre intención de avanzar (agendar/diagnóstico). No pidas datos de contacto en el primer turno si el usuario hizo una pregunta concreta. Mantén el resto de reglas (grounded en servicios, enlaza `/soluciones/<slug>`, honestidad).

## Guardarraíles
- **No** reintroduzcas prueba social fabricada; el testimonio sigue retirado.
- **0-LLM** del flujo guiado intacto; PII redactada (reusa `save_wizard.php`); `npm run build` verde; consola limpia.
- No inventes datos de la empresa (dirección/equipo) — eso queda para el usuario con info real.

## Verificación (evidencia real)
1. **Calculadora descubrible:** en `/sectores/finanzas` y `/sectores/seguros` hay un enlace visible a `/business-case`; con contexto CFO, el CTA lleva a `/business-case` (no abre el chatbot). Captura/`grep` en `dist/`.
2. **Markdown:** una respuesta del chatbot con lista numerada se ve **formateada** (no en crudo); las viñetas no rompen la itálica. Captura en navegador.
3. **Secuencia:** en texto libre, una pregunta concreta recibe **respuesta primero**; el bot pide contacto después. Ejemplo de conversación.
4. `npm run build` verde; islas/consola limpias. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador (servido por PHP): que el CTA/landing financiero lleve a la calculadora, que el chatbot renderice listas bien, y que no pida contacto antes de aportar valor.
