# Prompt para Antigravity — Fixes post-auditoría externa (Spec 013): 3 bugs P0 + gaps

Una auditoría externa (Perplexity) recorrió el sitio. **Ya trié sus hallazgos contra el código** y encontré además **un bug que a ella se le pasó**. No repitas el triaje: parte de esto.

**Lo que está bien (no lo toques):** las 6 páginas de solución con `techStack`/`proofPoints [EST]`/`competitivePositioning`, las páginas de sector con subsectores/KPIs `[EST]`/regulaciones, los hubs `/soluciones` y `/sectores`, las legales con `[BORRADOR LEGAL]`, los bloques de objeciones (CIO/CTO/CDO/CFO/CEO) y los 6 posts de Sector Público. Todo eso pasó la auditoría.

---

## ❌ Correcciones al reporte externo (NO persigas estos fantasmas)

1. **"El CTA no se personaliza para CFO ni CEO" → FALSO.** Verifiqué en vivo que al elegir el chip "Finanzas / CFO" el CTA cambia a *"Auditoría de ROI y pérdidas evitables"*. Las variantes existen y funcionan en `ConsultativeCTA.jsx`. **La causa real de lo que observó es el Bug 3 de abajo** (una vez eliges contexto, no puedes cambiarlo, así que quedó bloqueada en "Sector Público").
2. **"Conviven dos CTAs / CTA inconsistente entre páginas" → NO es un CTA hardcodeado duplicado.** Es **un solo componente** (`ConsultativeCTA`) con variantes por contexto; lo que vio es persistencia de `localStorage` + el Bug 3. No "unifiques" los textos: son intencionalmente distintos por contexto.

---

## 🔴 P0 — Bug 1: El chatbot solo ofrece 4 de 10 sectores (Sector Público EXCLUIDO)
`src/components/islands/Chatbot.jsx` línea ~284:
```jsx
{sectorsCorpus.slice(0, 4).map(s => (   // ← solo educacion, finanzas, logistica, manufactura
```
El corpus está ordenado alfabéticamente, así que `slice(0,4)` deja fuera **minería, público, retail, salud, seguros y telecomunicaciones**. **Sector Público —el foco estratégico de la Fase 1— es inalcanzable por botón**; el usuario debe escribirlo a mano. Es irónico: todo el trabajo institucional de la Fase 1 queda escondido.

**Fix:** ofrece los 10 sectores del corpus (no un `slice` arbitrario). Si te preocupa el alto de la lista, usa un contenedor con scroll o prioriza los sectores estratégicos primero — pero **no excluyas ninguno**. **Mantén el flujo guiado 0-LLM** (cero `fetch`).

## 🔴 P0 — Bug 2: Los chips de contexto usan IDs de rol INEXISTENTES → el highlight A9 está roto (silencioso)
`src/components/ui/ContextChips.jsx` usa `cdo_publico`, `cfo_economico`, `ceo_estrategico`, pero los IDs reales en `personas.json` son **`cdo`, `cfo`, `ceo`**. Por eso:
```jsx
const role = personas.roles.find(r => r.id === rolId);   // → undefined SIEMPRE
if (role && role.pillarsOfInterest) { ... semanticHighlight.set(highlightMap); }  // → NUNCA se ejecuta
```
**Consecuencia:** `semanticHighlight` **nunca se setea** → **la "adaptación visual" (resaltar los servicios de interés del rol) NO funciona**. Falla en silencio, por eso nadie lo notó. (El CTA sí funciona porque `ConsultativeCTA` compara contra esos mismos IDs falsos.)

**Fix:** usa los **IDs reales de la taxonomía** (`cdo`, `cfo`, `ceo`) en `ContextChips`, y **actualiza `ConsultativeCTA.jsx`** para que sus condiciones usen esos mismos IDs (hoy compara `context.rol === 'cfo_economico'`, etc.). Verifica que al elegir un chip **sí se resalten** los servicios de `pillarsOfInterest` de esa persona. Un solo vocabulario de IDs: el de la taxonomía.

## 🔴 P0 — Bug 3: No hay forma de cambiar ni resetear el contexto
`ContextChips.jsx` línea 9: `if (context.dismissed || context.rol || context.sector) return null;` → **tras elegir una vez, los chips desaparecen para siempre** (persistido en `localStorage`). Si el usuario se equivoca o quiere ver otro rol, queda atrapado. Esto es lo que confundió al auditor externo.

**Fix:** deja una afordancia para **cambiar/limpiar** el contexto: p. ej. cuando hay contexto activo, muestra una píldora discreta *"Viendo como: CFO · Finanzas ✕"* que permita cambiar de rol o resetear a default. Debe seguir siendo **no intrusiva** y persistir la elección.

---

## ⚠️ P1 — Gap 4: El formulario consultivo (A5) no cumple los requisitos
El auditor confirmó que el "Diagnóstico de Madurez de Datos" del home es **un wizard de 1 campo genérico** (textarea). La Spec 013 (A5 / CT-PUB-001…005) pedía capturar **tipo de entidad/organización, reto principal, sistemas o situación de datos actual**, y **explicar qué recibe** el usuario tras el contacto.

**Fix:** enriquece el flujo de captura (en el wizard o en el paso de lead del chatbot) con esos campos y una línea explícita de "qué recibes" (p. ej. *"Un arquitecto revisará tu caso y te enviará un diagnóstico inicial en X días. Sin compromiso."*). **Reutiliza el pipeline seguro existente** (`save_wizard.php` / `chat.php`): PII redactada en logs, `secure_leads/` protegido. **No crees endpoints nuevos.**

## ⚠️ P1 — Gap 5: Cobertura de `pillarsOfInterest` deja bloques de objeciones finos
Verificado: `hiperautomatizacion` → solo **COO**; `ai-data-science` → cdo/cto/ceo (**sin CFO**). Por eso esas páginas muestran pocas objeciones.

**Fix (capa de datos):** en `personas.json`, añade esos pilares al `pillarsOfInterest` de los roles **para los que sea genuinamente relevante** (p. ej. CFO en `ai-data-science` e `hiperautomatizacion` por ROI/eficiencia; CIO/CDO en `hiperautomatizacion` por integración/gobierno). **Honestidad:** solo donde el rol realmente tenga interés — no infles las aristas para llenar la UI. Corre `build-taxonomy.mjs` (Zod + integridad de aristas).

---

## 🟢 P2 — Contenido
6. **"Calidad de padrones ciudadanos"** existe como post de blog pero **no como caso de uso en `/sectores/publico`**. Añádelo a `publico.yaml` (capa de datos) para que la página lo renderice.
7. **Faltan posts para CFO y CEO** (la Spec 013 los pedía): genera **2–3** con `docs-generator.mjs` (target `blog`), p. ej. *"Cómo evaluar el ROI de proyectos de IA"*, *"Data & IA como palanca de EBITDA"* (CFO) y *"Ventaja competitiva desde los datos"* (CEO). `pubDate` **sin comillas** con `new Date().toISOString()`; publica con `draft: false`.

---

## Guardarraíles (no negociables)
- **0-LLM:** el flujo guiado del chatbot (incl. los 10 sectores) sigue **sin `fetch`**. Lo verificaré en Network.
- **Taxonomía = fuente de verdad:** los IDs de rol, casos de uso y aristas van en la fuente (`personas.json`/`*.yaml`) + `build-taxonomy.mjs`; nada hardcodeado en `.astro`/`.jsx`.
- **PII:** reutiliza `chat.php`/`save_wizard.php`; sin endpoints nuevos; sin PII en claro.
- **Honestidad:** cero casos/testimonios/certificaciones inventados; estimaciones con `[EST]`.
- **No romper:** islas intactas, `npm run build` verde, consola limpia.

## Verificación (evidencia real)
1. **Chatbot:** los 10 sectores disponibles como botón, **Sector Público incluido**; Network = **cero `chat.php`** en el flujo guiado.
2. **Chips:** elegir "Finanzas / CFO" **resalta** los servicios de `pillarsOfInterest` del CFO (BI + estrategia-datos-ia) y el CTA cambia. Pega evidencia del highlight funcionando (antes fallaba en silencio).
3. **Reset de contexto:** se puede cambiar de rol/limpiar sin recargar ni borrar `localStorage` a mano.
4. **Formulario:** captura tipo de entidad + reto + sistemas y dice qué recibe; PII redactada; sin endpoint nuevo.
5. `build-taxonomy.mjs` verde tras tocar `personas.json`/`publico.yaml`; objeciones más completas en hiperautomatización y ai-data-science.
6. Posts CFO/CEO publicados; `npm run build` verde.
7. Sección final "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: los 10 sectores + 0-LLM en Network, el **highlight realmente funcionando** (era el bug silencioso), el reset de contexto, la PII del formulario y el build verde.
