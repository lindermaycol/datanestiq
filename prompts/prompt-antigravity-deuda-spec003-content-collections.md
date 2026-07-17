# Prompt para Antigravity: Migración a Content Collections (taxonomía enriquecida) + páginas de sector

Actúa como **arquitecto de contenido Astro**. Sigue el pipeline SDD: primero genera `plan.md` + `tasks.md` en `specs/003-taxonomia-servicios/`, y tras mi revisión, implementa.

## Contexto (auditado por Claude / Sonnet 5)
La Spec 003 tiene dos deudas abiertas:
1. **Hardcoding del corpus:** la taxonomía —hoy **muy enriquecida** tras varios ciclos— vive en JSON plano. Marketing no puede editarla sin arriesgar romper la sintaxis. Migrar a **Astro Content Collections** con validación **Zod**.
2. **Cobertura de sectores:** existen las 6 páginas de pilar (`/soluciones/[id]`) pero **ninguna de los 10 sectores**. Faltan landing pages por sector (SEO long-tail).

> ⚠️ Este prompt fue **actualizado** para reflejar el esquema REAL enriquecido (madurez Gartner, CIIU, personas, industrias extendidas, etc.). El schema Zod DEBE contemplar TODOS los campos actuales.

## Esquema REAL actual (verificado — el Zod debe cubrirlo completo)
**Pilares** (`src/data/taxonomyCorpus.json`, 6): `id, name, slug, maturityStage, standards[], targetRoles[], skills[], seo{title,description}, hero{headline,subheadline}, contrast{problem,solution}, features[], keywords[], copilotPrompts[]`
**Sectores** (`src/data/sectorsCorpus.json`, 10): `id, title, description, icon, ciiu, keywords[], seo{title,description}, hero{headline,subheadline}, contrast{problem,solution}, problems[{code,label,solution}]`
**Industrias extendidas** (`src/data/extendedIndustries.json`, ~14): `id, title, ciiu, keywords[], relatedPillars[]`
**Personas** (`src/data/personas.json`): `{ roles:[{id,title,goals[],pains[],pillarsOfInterest[]}], orgTypes:[{id,title}] }`

## Restricción CRÍTICA — cero rupturas (lista de consumidores AMPLIADA)
Estos consumen los JSON hoy y NO deben romperse:
- `taxonomyCorpus.json` → `DiagnosticWizard.jsx`, `SemanticSearch.jsx`, `CopilotDemo.jsx`, `MultiStepWizard.jsx`, `Footer.astro`, `soluciones/[id].astro`
- `sectorsCorpus.json` → `DiagnosticWizard.jsx`, `SemanticSearch.jsx`, `SolutionsByRoleAndIndustry.jsx`
- `extendedIndustries.json` → `SemanticSearch.jsx`
- `personas.json` → `SolutionsByRoleAndIndustry.jsx`, `soluciones/[id].astro`

**Problema técnico:** las islas `.jsx` hacen `import` estático del JSON en build-time; las Content Collections se leen con `getCollection()` solo en frontmatter `.astro`, **no** son importables desde `.jsx`.

## Estrategia — Content Collections como FUENTE + JSON generado como ARTEFACTO
1. **Fuente de verdad (colecciones `type:'data'`, un archivo YAML por elemento, esquema Zod estricto):**
   - `src/content/pillars/` — 6 pilares, con el esquema COMPLETO de arriba (incluye `maturityStage` como enum: `descriptivo|diagnostico|predictivo|prescriptivo|transversal`; `standards`, `targetRoles`, `skills`, `copilotPrompts`, `keywords` como arrays).
   - `src/content/sectors/` — 10 sectores, esquema COMPLETO (incluye `ciiu`, `problems[{code,label,solution}]`, `seo/hero/contrast`).
   - `src/content/industries/` — ~14 industrias extendidas (`ciiu`, `keywords`, `relatedPillars`).
   - **Personas:** por ser configuración interna (no contenido de marketing) y tener estructura de objeto único (roles + orgTypes), **mantén `personas.json`** como fuente, pero **valídalo con un esquema Zod** en el script de generación (no lo dejes sin validar). Si prefieres colección, justifícalo en el plan.
2. **Artefacto generado:** crea `scripts/build-taxonomy.mjs` (o hook `prebuild` en `package.json`) que lee las colecciones y **regenera** `src/data/taxonomyCorpus.json`, `src/data/sectorsCorpus.json` y `src/data/extendedIndustries.json` con **exactamente el mismo shape** que consumen hoy las islas. Ningún consumidor `.jsx` cambia. Integra el script para que corra **antes** del `astro build` (y disponible como `npm run build:taxonomy`).
3. **Migra el contenido actual sin perder ni un campo** (6 pilares + 10 sectores + 14 industrias → YAML individuales). Verifica campo por campo.
4. **Validación de integridad referencial en el script:** `relatedPillars`, `pillarsOfInterest` y los `slug` referenciados deben existir; si alguno no existe, el script debe **fallar el build** con un mensaje claro (evita datos rotos silenciosos).

## Páginas de sector `/sectores/[id]`
- Crea `src/pages/sectores/[id].astro` con `getStaticPaths()` sobre la colección `sectors`, reutilizando `SolutionHero`, `SolutionContrast`, `SEO.astro` (JSON-LD), `BaseLayout`.
- Aprovecha los campos ya enriquecidos: `seo/hero/contrast`, el **código CIIU** (mostrado como señal de especialización + opcional en JSON-LD), y `problems[]` (retos típicos del sector).
- **Referencias de dominio (estilo Deloitte):** cada landing incluye los retos regulatorios/operativos del vertical (desde `problems`/`contrast`) y **cross-linking** hacia los pilares relevantes.
- Enlázalas desde el Home (la pestaña "Soluciones por Industria" de `SolutionsByRoleAndIndustry` puede apuntar a `/sectores/[id]` en vez de solo abrir el buscador) y/o Footer.

## Verificación (E2E, no solo build)
1. `npm run build`: se generan 6 páginas de solución + **10 de sector** + Home + wiki + nosotros + casos-de-exito + blog → confirma el conteo (sube de 12 a ~22+).
2. **Diff de shape:** los JSON regenerados desde las colecciones son **idénticos en estructura y contenido** a los actuales (compara antes/después; sin campos perdidos).
3. **No-regresión (E2E en `npm run dev`):**
   - Buscador semántico construye su corpus (pilares + sectores + industrias extendidas) y reordena.
   - `SolutionsByRoleAndIndustry`: clic en rol resalta pilares; pestaña industria enlaza/busca.
   - Páginas de pilar renderizan madurez + roles + aplicaciones por industria.
   - Chatbot guiado = **0 llamadas** (usa `sectorsCorpus.problems`).
   - Footer, DiagnosticWizard, CopilotDemo, MultiStepWizard OK.
4. **Editabilidad:** cambia un `headline` en un YAML, regenera, y confirma que la página lo refleja — demostrando que marketing edita sin tocar JSON.
5. **Integridad referencial:** introduce a propósito un `relatedPillar` inválido en un YAML y confirma que el script **falla el build** con mensaje claro (luego revierte).

## Cierre documental
- `specs/003-taxonomia-servicios/tech_debt.md`: marca la deuda #1 (Content Collections) como **✅ RESUELTO** (arquitectura fuente-YAML + artefacto-JSON, con validación Zod e integridad referencial) y la cobertura de sectores como resuelta. Corrige el `## Estado`.
- `planes/ESTADO-SPECS.md`: actualiza la fila 003 (ya no "corpus JSON estático"; ahora "Content Collections validadas + páginas de sector").

## Forma de respuesta
- Primero `plan.md` + `tasks.md` para mi revisión (SDD).
- Al implementar, reporta la verificación E2E (conteo de páginas, diff de shape, edición YAML reflejada, integridad referencial que falla el build).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que (a) el Zod cubra TODOS los campos enriquecidos, (b) los JSON regenerados sean idénticos en shape (ningún consumidor roto), (c) las 10 páginas de sector se generen, y (d) editar un YAML se refleje en el sitio.
