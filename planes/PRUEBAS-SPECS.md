# Pruebas de Verificación de Specs — Datanestiq

Documento de QA: registra las pruebas ejecutadas sobre el sitio y su resultado. **Reejecutable** con:

```bash
npm run build && bash scripts/test-specs.sh
```

## Metodología
Dos capas: (1) pruebas automatizadas sobre el **HTML de producción construido (`dist/`)** vía `scripts/test-specs.sh`, y (2) **navegación interactiva en vivo** en el navegador.

> **Nota de entorno:** el `astro dev` server no era alcanzable por el navegador del preview en Windows. Se resolvió sirviendo el build con **`astro preview --host`** (bind a todas las interfaces), que sí permite la navegación real y reproduce producción.

## Verificación interactiva EN VIVO (navegador) ✅
Se navegó el sitio servido (`astro preview`) y se comprobó:
- **Home:** renderiza data-driven desde `home.md` con **paridad visual** (headline con gradiente y salto de línea, subheadline, CTAs). Islas presentes.
- **Chatbot (flujo guiado):** al elegir *Finanzas y Banca* → ofrece los roles **relevantes del sector** (CFO/CISO/CDO = `relevantPersonas` de la taxonomía). Al elegir *CFO* → mensaje personalizado **construido desde el corpus**: *"optimizar KPIs clave como Reducción de falsos positivos en fraude, Tiempo de evaluación crediticia cumpliendo con Basilea III/IV"* + opciones de problema (`problems`).
- **0 llamadas LLM (verificado por red):** durante todo el flujo guiado NO hubo peticiones a `chat.php`/API — solo assets estáticos + `sectorsCorpus.js`/`personas.js`. La personalización es 100% cliente/determinista.
- **Página de sector (finanzas):** renderiza **Subsectores** (Banca Minorista/Corporativa/Fintech), **KPIs de Impacto** con ROI **`[EST]`**, y **Compliance** (Basilea III/IV, PCI-DSS, AML-KYC, SBS Perú).
- **SolutionsByRoleAndIndustry:** grilla de roles con sus metas (isla operativa).

## Resultado — última corrida: **12/12 ✅**

| # | Spec | Prueba | Resultado |
|---|---|---|---|
| 1 | 012 | Home (`/`) publicado y no vacío | ✅ |
| 2 | 012 | Home renderiza el headline desde `home.md` (`<br/>` + "Ventaja Asimétrica") — data-driven con paridad visual | ✅ |
| 3 | 012 | **No** existe `/home` duplicada (el catch-all la excluye) | ✅ |
| 4 | 012 | Página demo (`pagina-muestra`) **despublicada** (draft) | ✅ |
| 5 | 011 | Sector finanzas: regulaciones reales (Basilea / PCI-DSS / **SBS**) | ✅ |
| 6 | 011 | Sector finanzas: KPIs con ROI **`[EST]`** renderizado | ✅ |
| 7 | 011 | Sector salud: **HIPAA / HL7-FHIR** | ✅ |
| 8 | 002 | Home embebe las islas hidratadas (`astro-island`) | ✅ |
| 9 | 002 | Chatbot: flujo guiado usa el corpus (`objections`) **sin nueva llamada LLM** | ✅ |
| 10 | 002 | DiagnosticWizard usa `kpis`/`regulations` del sector | ✅ |
| 11 | 007 | **BreadcrumbList** JSON-LD presente (SEO) | ✅ |
| 12 | 003 | Se generan las **10** páginas de sector | ✅ |

## Navegación del Home (hallazgos)
- El home (`/`) ahora es **data-driven**: `src/pages/index.astro` lee `src/content/pages/home.md` vía `getEntry` e inyecta los textos en `Hero.astro`/`Services.astro`/`TrustLayer.astro` **con los textos originales como default** (fallback).
- **Paridad visual verificada**: el headline con su gradiente (`<span class="bg-clip-text…">`) renderiza como HTML (vía `set:html`), y el salto de línea `<br/>` está presente (se corrigió un `\n` que lo rompía).
- Las **islas** del home (SemanticSearch, CopilotDemo, DiagnosticWizard) siguen embebidas e hidratadas.
- Build: **24 páginas** estáticas.

## Cobertura y límites
- ✅ **Verificado (HTML/código + navegador en vivo):** render de contenido, taxonomía enriquecida en páginas, hidratación de islas, **chatbot guiado recorrido con clicks** (personas por sector + mensaje con KPIs/regulaciones + **0 LLM confirmado por red**), breadcrumbs, draft-gating, paridad del home.
- ⏳ **Pendiente (opcional):** recorrer el `DiagnosticWizard` completo paso a paso y el `SemanticSearch` con el modelo local (descarga ~113MB) — no crítico.

## Pendientes del usuario (fuera del alcance del código)
- 🔴 Rotar la contraseña SSH de IONOS (expuesta en el historial de git).
- Configurar los 3 secrets en GitHub (`GROQ_API_KEY`/`DASHSCOPE_API_KEY`/`GEMINI_API_KEY`) para el workflow `content-pr.yml`.
