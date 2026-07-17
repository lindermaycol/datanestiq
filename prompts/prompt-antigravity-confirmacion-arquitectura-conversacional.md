# Prompt para Antigravity: Confirmación del Plan de Arquitectura Conversacional (Spec 002 FR-014/015/016) → ejecutar con 4 precisiones

He revisado `planes/Arquitectura Conversacional Ruteo Híbrido y Tool-Centric (Spec 002).md`. Es fiel al prompt y correcto. **Tienes luz verde para ejecutar**, con estas 4 precisiones — la #1 y #2 evitan **romper funcionalidad que ya está viva**, la #3 y #4 completan el diseño.

## Precisión 1 (CRÍTICA) — NO rompas la herencia de contexto de la Spec 007 (`?servicio=` → chatbot)

El `Chatbot.jsx` actual tiene un `useEffect` que reacciona al nanostore `lastUserQuery`: cuando un usuario llega desde una página de solución (`/soluciones/[id]` con `?servicio=`), el chatbot **se abre solo y envía un mensaje contextual al LLM**. Esto es la User Story 3 de la Spec 007, ya auditada y funcionando.

Al refactorizar a máquina de estados, **este flujo debe seguir intacto**: cuando llega contexto heredado (`lastUserQuery` con contenido), el chatbot debe entrar por el **camino SEMÁNTICO (LLM)** — no quedar atrapado en el paso `intro` de botones. Es decir: contexto heredado → arranca directo en conversación LLM con ese mensaje; sin contexto → arranca en `intro` con botones deterministas. No elimines ni ignores el `useEffect` de `lastUserQuery`.

## Precisión 2 (CRÍTICA) — Reutiliza el `saveLead()`/`confirmLead()` y la tarjeta de lead ya existentes

El ciclo de recuperación anterior dejó en `Chatbot.jsx` una función **aislada** `saveLead()` (que postea a `save_wizard.php`), `confirmLead()`, la extracción `extractLeadSignals` y la **tarjeta de confirmación de lead** (email/teléfono editables + botón). El paso `lead` de tu máquina de estados debe **reutilizar exactamente esa tarjeta y esas funciones** — no reinventes la captura de lead ni vuelvas a `localStorage`. La máquina de estados solo decide *cuándo* mostrar la tarjeta (al llegar al paso `lead`); la captura/persistencia ya está resuelta y verificada.

## Precisión 3 — Incluye la "salida de escape" del árbol determinista hacia el LLM + estado final

Tu plan cubre el árbol guiado (sector → problema → solución), pero el árbol determinista solo tiene un subconjunto curado de sectores/problemas (los ~4 del `getSolutionMessage` del prototipo: Gobierno, Salud, Finanzas, Retail). Para no dejar atascado a quien no encaje:
- En el paso `intro` (botones de sector) y en el paso `problem` (botones de problema), incluye **siempre** una opción **"Otro / escribirlo yo mismo"** que baje al camino **semántico (LLM)** con el input de texto libre. Nadie debe quedar sin salida si su caso no está en el árbol.
- Añade un estado final `done` (o similar) tras confirmar el lead: un mensaje de agradecimiento/cierre, sin volver a pedir datos ni re-disparar el flujo.

## Precisión 4 — Orden del prompt para cachear + limpieza + modularidad

En `chat.php`:
- Para que el **Prompt Caching** funcione (FR-010, que el prompt 03 de costos reforzará), el `BASE_PROMPT` estable debe ir **primero** en el array de mensajes (es el prefijo cacheable); el bloque de contexto dinámico (sector/step) va **después** del base y **antes** del historial de conversación. No metas nada volátil dentro del prefijo base.
- La **Verificación #3** de tu plan (volcar el array de `messages` con `error_log` para inspeccionar el prompt modular): **elimínalo al terminar**. No dejes ningún `error_log`/dump de prompts en el código final.
- Mantén los cambios de `chat.php` **modulares y localizados**: el prompt 03 (gobernanza de costos: cap de gasto, metering, alertas) se montará encima de este mismo archivo. No entrelaces la lógica del prompt tool-centric con la del logging/routing de forma que dificulte añadir esas capas después.

## Nota menor — accesibilidad
Los botones del flujo guiado deben ser elementos `<button>` reales, navegables por teclado y con foco visible (Constitution, WCAG 2.2 AA). No uses `<div onClick>`.

---

Con esas 4 precisiones, procede. Verifica rigurosamente en Network:
- Flujo guiado completo por botones (sector → problema → solución → lead) = **cero** requests a `chat.php`.
- Texto libre / opción "Otro" = **una** request a `chat.php`, con el `context` correcto adjunto.
- Herencia de contexto: entra a `/soluciones/ai-data-science`, clic en "Inicia tu Diagnóstico", confirma que el chatbot se abre en modo LLM con el mensaje contextual (no atascado en botones).

`npm run build` debe seguir dando 10 páginas. Reporta con evidencia de Network. No toques `specs/008-headless-wordpress/` ni reescribas a Python/LangGraph. Sección final "Hallazgos adicionales". Yo audito al terminar comparando contra el flujo guiado del `prototype/app.js`.
