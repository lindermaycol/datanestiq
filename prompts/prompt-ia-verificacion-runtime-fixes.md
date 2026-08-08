# Prompt para IA con navegador — Verificación de runtime de los fixes recientes (Datanestiq)

Eres un QA con **navegador real** (clic, escribir, panel Network, consola, inspección de DOM). Tu tarea es **verificar en runtime** un conjunto acotado de arreglos recientes del sitio Datanestiq y confirmar que funcionan (o reportar exactamente qué falla). No es una auditoría amplia: son checks puntuales con criterio binario.

## Acceso al sitio
El sitio es **Astro estático + islas React + backend PHP**. Debe servirse con PHP (no `astro preview`):
1. `npm run build`
2. `C:/xampp/php/php.exe -S localhost:8080 -t dist` (o Apache de XAMPP a `dist/`)
3. Abrir `http://localhost:8080/`

## Reglas anti-falsos-positivos (imprescindibles)
- **Interactúa de verdad** (haz clic / escribe / mira Network). No juzgues por el HTML estático.
- **`localStorage.clear()` + recarga antes de cada bloque** que dependa del contexto (el sitio persiste la elección de rol).
- **Highlight de servicios:** haz **scroll hasta "Nuestros Pilares de Expertise"** para que GSAP monte las tarjetas; luego mide clases `is-highlighted` / `is-dimmed` (no solo `opacity`, que GSAP controla).
- **Consola:** ten la consola abierta; reporta **cualquier** error JS (los bugs de estos fixes fueron de runtime, no de build).
- **0-LLM:** flujo guiado del chatbot = **sin** `POST /api/chat.php` en Network. Texto libre **sí** llama a `chat.php`. Guardar lead llama a `save_wizard.php` (esperado).

---

## Checks a verificar (marca PASS/FALLA con evidencia)

### V1 — Búsqueda semántica DISCRIMINA (fix Gap 1) 🔴 el más importante
1. Scroll al "Buscador Semántico impulsado por IA". Espera a que cargue el modelo ("IA lista para búsqueda instantánea").
2. Escribe: **"tengo mucha morosidad en mi cartera de créditos"** y ejecuta.
3. Scroll a "Nuestros Pilares de Expertise".
4. **Criterio:** deben resaltarse **1–3 servicios pertinentes** (esperable: AI & Data Science y/o Business Intelligence) y **atenuarse el resto**. **FALLA** si se resaltan los 6 (sin diferenciación) o ninguno.
5. Reporta qué servicios quedaron `is-highlighted` vs `is-dimmed`.
6. Confirma en **Network** que la búsqueda **no** hizo POST a `chat.php` (es client-side).

### V2 — Pivote de rol en 1 clic, sin excepción (fix TDZ)
1. `localStorage.clear()` + recarga. Elige el chip **"Finanzas / CFO"**.
2. Con contexto activo aparece el selector *"Viendo como: Público (CDO) · Finanzas (CFO) · Estrategia (CEO) · ✕"*. Haz clic directo en **"Público (CDO)"** (SIN usar ✕).
3. **Criterio:** **cero errores en consola**; el contexto cambia a CDO **en 1 clic**; el CTA pasa a *"Solicita diagnóstico de madurez en IA"*; y (tras scroll a los pilares) se resaltan los del CDO (Data Engineering + AI & Data Science) atenuando el resto.
4. Repite pivoteando entre los 3 roles. Confirma que ✕ resetea a neutro.

### V3 — On-Premise en Hiperautomatización (fix Gap 2)
1. Ve a `/soluciones/hiperautomatizacion`.
2. **Criterio:** el `techStack`/posicionamiento menciona **Cloud / Híbrido / On-Premise**. PASS/FALLA.

### V4 — Honestidad: prueba social ya NO fabricada
1. En el home, revisa la sección que antes decía "Trusted by Visionary Organizations".
2. **Criterio:** **NO** deben aparecer logos de empresas-cliente inventadas (FINCORP, MEDITECH…) ni testimonio con nombre/empresa/cifra ("María Jiménez", "RetailCorp", "24%"). Debe verse "Tecnologías que dominamos" con stack real (AWS, Databricks, Snowflake…). Reporta si sobrevive algo fabricado.
3. Revisa el FAQ: la respuesta de ROI **no** debe afirmar "nuestros clientes reportan…".

### V5 — Regresión: lo previamente aprobado sigue OK
1. **Chatbot:** abre → deben estar los **10 sectores** (incluido **Sector Público**) con scroll → elige Finanzas → CFO → verifica que el problema **"Impacto en EBITDA y TCO"** aparece → clic → **Network: cero `chat.php`** en todo el flujo guiado.
2. **Business Case** (`/business-case`): ingresos 5M, costos 2M, eficiencia 25% → ROI/payback **creíbles** (~150% / ~8 meses, no miles de % ni sub-mes) con badge `[EST] Escenario Ilustrativo`.
3. **Legales:** `/privacidad` y `/terminos` cargan; footer sin `href="#"`.

---

## Formato de salida (Markdown)
- **Tabla:** `Check (V1–V5) · PASS/FALLA · Evidencia (qué hiciste, qué observaste: clases de tarjetas, texto del CTA, presencia/ausencia de `chat.php` en Network, números de la calculadora)`.
- **Errores de consola** encontrados (con el mensaje textual), si los hay.
- **Qué NO pudiste verificar** y por qué (ej. el modelo de embeddings no cargó).
Sé literal con la evidencia; si no interactuaste algo, márcalo *no verificado*, no como defecto.
