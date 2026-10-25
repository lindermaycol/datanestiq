# Prompt IA (navegador) — Navegación detallada de CADA componente del HOME + medición de delays (reporte para Claude)

Eres un QA con **navegador real** (clic, escribir, scroll, panel Network, consola, timing de `performance.now()`, inspección de DOM/clases). Recorre **componente por componente** el home de Datanestiq, **mide latencias reales** y **emite un reporte estructurado con evidencia** para que Claude (Opus 4.8) lo audite. No resumas ni generalices: cada afirmación debe venir con un **valor concreto** (ms, texto observado, clases CSS, presencia/ausencia de requests).

## Acceso al sitio (IMPORTANTE)
Astro estático + islas React + backend PHP. **Reconstruye y sirve con PHP** (para probar la última versión y que el chatbot/backend funcionen):
1. `npm run build`
2. `C:/xampp/php/php.exe -S localhost:8080 -t dist`
3. Abrir `http://localhost:8080/`

## Reglas anti-falsos-positivos (imprescindibles)
- **`localStorage.clear()` + recarga antes de cada bloque** que dependa del contexto (el sitio persiste rol/búsqueda en `datanestiq_context`).
- **Hidratación de islas:** el home usa `client:load`, `client:visible` y `client:idle`. Antes de interactuar con un componente **haz scroll hasta él y espera a que hidrate** (el `<astro-island>` deja de tener el atributo `ssr`). Para el **Chatbot** (`client:idle`) confirma `astro-island[component-url*="Chatbot"]` **sin** atributo `ssr` antes de clicar; si no hidrata, dilo (no marques fallo del código).
- **Buscador semántico = Edge AI (transformers.js):** la **primera** búsqueda descarga el modelo (lento por diseño). **Distingue** "carga del modelo (1ª vez)" de "latencia de una consulta con modelo ya cacheado". Reporta ambos por separado.
- **Highlights:** mide las **clases** `is-highlighted` / `is-dimmed`, no el `opacity` crudo (GSAP lo controla).
- **0-LLM:** el flujo guiado del chatbot (botones) y el buscador **no** deben llamar a `chat.php`. Ten Network y consola abiertos; reporta cualquier error JS literal.

## 🔴 Errores de método que YA produjeron falsos positivos (NO repetir)
Dos auditorías previas marcaron como "FALLO" cosas que **están implementadas y verificadas**. Antes de reportar un fallo en estos puntos, sigue el procedimiento exacto:

1. **HeroRoleLine (microcopy de rol en el Hero) — NO es la etiqueta "Viendo como:".**
   - Son **dos** elementos distintos: (a) la fila de chips muestra "Viendo como: …" (etiqueta del chip); (b) **HeroRoleLine** es un banner aparte, debajo de los chips, con formato `"<Rol>: <meta> · Criterio: <criterio>"` (ej. *"CDO / Chief Data Officer: valor del dato · Criterio: Escalabilidad de la gobernanza"*).
   - **Procedimiento:** `localStorage.clear()` → recarga → **espera a que hidrate el island de HeroRoleLine (`client:load`)** → clic en un chip → busca el texto que contiene `"· Criterio:"`. Verifica que `getBoundingClientRect().height > 0` y `getComputedStyle().visibility === 'visible'`. Solo si tras esto **no existe**, repórtalo como fallo (con el HTML del Hero como evidencia).

2. **Herencia de contexto en el Chatbot — solo hereda en la PRIMERA apertura (por diseño).**
   - La init lee el chip en la **primera** apertura del panel y luego es **idempotente** (no se re-inicializa: eso es correcto). Si abres el chatbot **antes** de elegir el chip (p. ej. al probar el flujo 0-LLM genérico), quedará "inicializado" y **ya no** heredará aunque después elijas un chip. **Eso NO es un bug.**
   - **Procedimiento estricto para probar herencia:** (a) `localStorage.clear()` → recarga; (b) **NO abras el chatbot todavía**; (c) clic en el chip (ej. "Datos / CDO"); (d) espera a que el Chatbot hidrate (`client:idle`, `astro-island` sin `ssr`); (e) **recién ahora** abre el chatbot por primera vez. Debe saludar *"Veo que estás explorando como CDO … en Finanzas y Banca…"*. Si abriste el chatbot antes del paso (c), reinicia con `localStorage.clear()` y empieza de nuevo.

## 🖥️ Servidor de dev `php -S` es single-thread (afecta la MEDICIÓN, no el sitio)
- `php -S` atiende **una petición a la vez**. Mientras `chat.php` espera al LLM externo (~12–15s), **cualquier** otra petición se bloquea; por eso puedes ver `ERR_CONNECTION_REFUSED` o "sin respuesta". **No lo reportes como bug del sitio** (en producción corre Apache/PHP-FPM multi-proceso).
- **El modelo WASM del buscador NO pasa por `php -S`**: el worker lo baja de `cdn.jsdelivr.net`. No confundas la carga del modelo con carga del servidor PHP.
- **Recomendación de método:** prueba las features LLM (Copiloto, roles, texto libre del chatbot) **de a una**, esperando a que cada respuesta termine antes de la siguiente. No dispares varias llamadas LLM en paralelo bajo `php -S`. Si una respuesta LLM no llega tras ~20s y el servidor sigue vivo, márcalo **"no-verificado (límite del dev-server)"**, no "fallo".

## Cómo medir cada latencia (obligatorio)
Usa `performance.now()` alrededor de la acción, o el panel Network (columna Time). Reporta en **ms**. Ejemplo:
```js
const t0 = performance.now();
/* acción: clic / búsqueda / abrir panel */
/* esperar al cambio visible (resultado, mensaje, highlight) */
const t1 = performance.now(); // delta = t1 - t0
```
Clasifica cada delay: **Instantáneo (<100ms) · Aceptable (100–1000ms) · Lento (1–3s) · Crítico (>3s)**. Para el buscador, la carga del modelo (1ª vez) se reporta aparte y **no** cuenta como "crítico del código".

---

## Componentes a recorrer (uno por uno, con evidencia y latencia)

### C1 — Hero + Chips de contexto + HeroRoleLine
1. Anota título/subtítulo y los **4 chips**: "Sector Público", "Finanzas / CFO", "Datos / CDO", "Estrategia / CEO".
2. Para **cada uno de los 4 chips**: clic → mide el delay hasta que aparezca la micro-línea **HeroRoleLine** (banner "Rol: meta · Criterio: …"). Anota el texto exacto por rol y el **delay (ms)**.
3. Verifica que el chip cambia también el **CTA** (`ConsultativeCTA`) y que "Viendo como:" refleja el rol. `localStorage.clear()` + recarga entre chips.
4. **Criterio:** cada chip produce HeroRoleLine y CTA coherentes; sin contexto no aparece.

### C2 — Buscador Semántico (Edge AI)
1. Scroll al buscador. Mide **tiempo de carga del modelo (1ª vez)** desde que aparece "Cargando el modelo de IA (solo la primera vez)… X%" hasta "listo". Reporta ms y confirma que el **Skeleton (3 tarjetas)** se muestra durante la carga.
2. Busca: **"quiero reducir costos operativos"**. Mide **latencia de la consulta** (modelo ya cargado) hasta que aparecen resultados. Reporta ms.
3. Segunda búsqueda distinta (**"prevención de fraude"**): mide latencia (debe ser rápida, modelo cacheado).
4. Verifica el **estado `searching`** (spinner + skeleton) y que aparece el bloque **"Encontramos [N] soluciones…"** con 3 botones. Anota N.
5. **Criterio:** modelo carga con feedback claro; consultas subsecuentes rápidas; **cero `chat.php`**.

### C3 — CTA encadenado del buscador (los 3 botones)
1. "Ver cómo razona nuestra IA →" → mide delay del smooth-scroll a `#copilot-section`.
2. "Iniciar Diagnóstico con este contexto →" → scroll a `#diagnostic-section`.
3. "Consultar con el AI Concierge →" → abre el chatbot **con la consulta cargada**. Anota qué muestra y el delay de apertura.
4. **Criterio:** los 3 navegan/abren correctamente; reporta ms de cada uno.

### C4 — Sección "Descubre el Impacto en tu Sector" (DiagnosticWizard)
1. Tras una búsqueda: ¿se **resaltan** sectores relevantes (`is-highlighted`) y se **atenúan** los demás (`is-dimmed`)? ¿O se atenúan todos (bug)?
2. ¿Los inputs "¿Cuál es tu mayor desafío en {sector}?" vienen **pre-llenados** con la consulta? Borra un input: ¿queda vacío (correcto) o **rebota** a la consulta (bug)?
3. Escribe un desafío y envía: mide el delay hasta la respuesta/apertura del chatbot. Reporta si llama a `chat.php` (aquí sí puede) y su **Time** en Network.
4. **Criterio:** discrimina sectores + pre-llena + editable; latencia reportada.

### C5 — "Soluciones por Rol e Industria" (SolutionsByRoleAndIndustry)
1. Interactúa con los selectores de rol/industria. Mide el delay de actualización de la grilla.
2. Verifica que los enlaces a `/soluciones/*` y `/sectores/*` son correctos (sin `href="#"`).

### C6 — Copiloto Estratégico (CopilotDemo)
1. Anota los **3 escenarios** por defecto. Tras una búsqueda previa, ¿cambian a pilares relevantes ("Basado en tu búsqueda…")?
2. Clic en un escenario → mide el delay hasta la respuesta (llama a `chat.php`; anota **Time** de Network). Si hay failover LLM, repórtalo.
3. **CRÍTICO (F-01):** si la respuesta trae links `[texto](url)`, ¿se renderizan como **hipervínculos clicables** (no texto crudo)? ¿Las **listas/viñetas** se ven formateadas? Verifica que **no** se inyecta HTML crudo (anti-XSS).
4. Verifica el subtítulo aclaratorio ("distinto del AI Concierge") y el puente "¿Tu caso no está…? Pregúntale al AI Concierge →" (abre chatbot).

### C7 — AI Concierge / Chatbot (herencia + flujo guiado 0-LLM)
1. `localStorage.clear()` + recarga. **Elige el chip "Datos / CDO"**, espera a que el chatbot hidrate, **ábrelo**: ¿saluda heredando el contexto ("Veo que estás explorando como CDO… en Finanzas y Banca…") y arranca en el paso **problema**? Repite con **"Sector Público" (CIO)** y **"Finanzas / CFO"**.
2. **Sin chip:** abre el chatbot → debe ser genérico ("¿A qué sector perteneces?").
3. Recorre el **flujo guiado por botones** (sector → rol → problema): en Network **debe haber cero `POST /api/chat.php`**. Mide el delay de cada transición de paso.
4. **Texto libre:** escribe una consulta → llama a `chat.php`; anota **Time** y si hay failover. Verifica render de listas/links en la respuesta.
5. **Formulario de lead:** ¿`reto` viene pre-llenado y **`organizacion` vacía** (no una etiqueta de sector)? ¿Editable?
6. **Idempotencia:** cierra y reabre el chatbot → no debe reiniciar el saludo ni perder el avance.

### C8 — MultiStepWizard + footer/navbar
1. Recorre el `MultiStepWizard` (zona CONVIERTE): mide delays de cada paso.
2. Navbar: menús desktop y mobile (hamburguesa) despliegan `/soluciones` y `/sectores`. Footer: **cero `href="#"`**; enlaces legales presentes.

---

## Formato del reporte (para Claude)
1. **Tabla por componente (C1–C8):** `Componente · Acción · PASS/FALLA/NO-VERIFICADO · Latencia (ms) + clasificación · Evidencia literal`.
2. **Tabla de latencias** consolidada (todas las acciones medidas, ordenadas de mayor a menor ms), separando "carga de modelo (1ª vez)" del resto.
3. **Errores de consola** (texto literal) y **requests inesperados** (ej. `chat.php` donde no debería).
4. **Qué NO pudiste verificar y por qué** (ej. una isla no hidrató).
5. **Veredicto:** ¿hay algún delay perceptible al usuario (>1s) fuera de la carga inicial del modelo? ¿Dónde?

Sé **literal**: qué hiciste, qué observaste, cuántos ms. Si algo "parece" lento pero no lo mediste, márcalo *no verificado*. **No inventes resultados.**
