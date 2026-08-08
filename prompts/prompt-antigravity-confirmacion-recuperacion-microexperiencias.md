# Prompt para Antigravity: Confirmación del Plan de Recuperación (Spec 002) → ejecutar con 3 correcciones críticas

He revisado `planes/Plan de Ejecución Recuperación de Microexperiencias de IA (Spec 002).md`. El desglose por fases es fiel y correcto. **Tienes luz verde para ejecutar**, pero antes aplica estas 3 correcciones — la #1 evita **re-perder** una funcionalidad que hoy sí sobrevive, la #2 evita un bug de arquitectura React/Astro, y la #3 cierra un hueco de seguridad de rutas.

## Corrección 1 (CRÍTICA) — FASE C: NO reemplaces el panel de sectores; el wizard multi-paso es ADITIVO

Tu plan dice: *"[MODIFY] index.astro: **Sustitución** del formulario/wizard base con este nuevo componente"* y crea `MultiStepWizard.jsx` en lugar de `DiagnosticWizard.jsx`.

**El problema:** `DiagnosticWizard.jsx` es justamente la pieza que **sobrevivió** al borrado de `prototype/` — contiene el **panel de 10 sectores + el pitch generativo por sector (con typewriter) + la creación de sectores on-the-fly**. Eso corresponde a la **User Story 3 / Panel de Sectores** (documentada en el walkthrough "Panel de Sectores Dinámico"). Si lo "sustituyes" por el MultiStepWizard, **vuelves a perder** esa funcionalidad que tanto costó rescatar.

**El MultiStepWizard (US-02: preguntas de arquitectura → Puntaje de Madurez) es una experiencia DISTINTA del panel de sectores (US-03).** Son dos user stories separadas de la Spec 002.

**Instrucción:**
- **Conserva `DiagnosticWizard.jsx` intacto** (panel de sectores + pitch + on-the-fly). No lo elimines ni lo reemplaces.
- Añade `MultiStepWizard.jsx` como una **sección nueva y adicional** del Home (puede ir en la sección "Inicia tu Diagnóstico" actual, o como un bloque propio). El Home tendrá ambos: el panel de sectores (explorar por industria) y el wizard multi-paso (diagnóstico de madurez guiado).
- Si te preocupa la densidad del Home, propón el orden de secciones, pero **no resuelvas la densidad borrando features** — eso lo decide el usuario, no el plan.

## Corrección 2 (CRÍTICA) — FASE B: no manipules por `querySelectorAll` el DOM que controla React

Tu plan hace que `SemanticSearch.jsx` reordene/difumine tarjetas vía `querySelectorAll('.service-card, .sector-card')`. Dos problemas:
1. **Las tarjetas de servicio NO tienen esas clases hoy.** En `Services.astro` son `<a class="glass-card hover:-translate-y-2 block">`. Debes **añadir** un hook estable, ej. `class="... service-card"` y `data-service-id={slug}` a cada tarjeta, para poder seleccionarlas.
2. **Las tarjetas de SECTOR viven dentro de `DiagnosticWizard.jsx` (isla React).** Manipular ese DOM desde otra isla con `querySelectorAll` + estilos inline **pelea con la reconciliación de React** (React puede revertir tus cambios en el siguiente render). No hagas eso.

**Instrucción — dos mecanismos según el dueño del DOM:**
- **Tarjetas de Servicio (estáticas, Astro):** sí puedes reordenar/difuminar vía DOM (`data-service-id`), porque nadie más controla ese DOM. OK.
- **Tarjetas de Sector (React, en `DiagnosticWizard.jsx`):** NO las toques por DOM. En su lugar, `SemanticSearch` escribe el ranking/resaltado en un **nanostore** (ej. `semanticHighlight`), y `DiagnosticWizard.jsx` **se suscribe** y reordena/resalta sus propias tarjetas desde su estado React. Así el resaltado semántico de sectores lo hace el componente dueño, sin conflicto.

## Corrección 3 (Seguridad de rutas) — FASE F: `secure_leads/` SÍ está expuesto; el `.htaccess Deny` es OBLIGATORIO

Tu plan pone el log crudo en `../../secure_leads/` y crea el `.htaccess Deny from all` *"si estuviera expuesto"*. **Sí está expuesto:** el proyecto vive en `htdocs/datanestiq/`, que es el webroot de XAMPP, así que `datanestiq/secure_leads/chat_raw.jsonl` es accesible en `http://localhost/datanestiq/secure_leads/chat_raw.jsonl`. Eso es PII de leads accesible públicamente = incidente.

**Instrucción (no opcional):**
- El `.htaccess` con `Require all denied` (Apache 2.4) / `Deny from all` (2.2) en `secure_leads/` es **obligatorio**, no condicional.
- Como defensa en profundidad **adicional** (por si `AllowOverride` está en `None` y el `.htaccess` se ignora): nombra el archivo con una extensión que Apache no sirva como texto plano no es suficiente — mejor, **verifica** que `AllowOverride` permita el `.htaccess`, o coloca el directorio **un nivel arriba de `htdocs/datanestiq/`** (ej. `../secure_leads/` relativo a la raíz del proyecto, fuera del webroot servido). Elige la opción que garantice de verdad que el `chat_raw.jsonl` **no responde 200 por HTTP**, y **verifícalo** haciendo `curl` a esa URL (debe dar 403/404).
- Aplica el mismo cuidado a `leads_wizard.csv` y `leads_datanestiq.csv`: **nunca** deben ser descargables por HTTP.

## Nota de coordinación (no bloqueante) — este es el prompt 1 de 3 sobre `chat.php`/`Chatbot.jsx`

Recuerda que después vienen:
- **Prompt 02** (arquitectura conversacional): refactoriza `Chatbot.jsx` a máquina de estados y `chat.php` a tool-centric.
- **Prompt 03** (gobernanza de costos): cap de gasto/metering/alertas sobre `chat.php`.

Por eso, en la FASE C, cuando modifiques `confirmLead` de `Chatbot.jsx` para postear a `save_wizard.php`, hazlo de forma **localizada y limpia** (una función `saveLead()` bien aislada), para que el refactor a máquina de estados del prompt 02 pueda envolverla sin reescribir todo. Y tus cambios a `chat.php` (ruteo por sesión + doble sink de logs) deben quedar **modulares**, porque los prompts 02/03 les añadirán capas encima.

---

Con esas 3 correcciones aplicadas, procede con las fases A→F en orden, con `npm run build` entre cada una. Recuerda:
- Marca `[x]` en `tasks.md` **solo** lo que verifiques funcionando (no marques por adelantado).
- Verifica el `curl` a `secure_leads/chat_raw.jsonl` (debe ser 403/404).
- Verifica en Network la descarga de los `.onnx` cuantizados del worker y reporta el impacto de bundle de Transformers.js.
- **No toques `specs/008-headless-wordpress/`.**

Repórtame fase por fase con verificación real, y una sección final "Hallazgos adicionales". Yo audito al terminar comparando contra los walkthroughs originales.
