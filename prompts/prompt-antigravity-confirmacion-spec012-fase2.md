# Prompt para Antigravity: Confirmación del Plan Spec 012 Fase 2 → aprobar con 2 correcciones + precisiones

Revisé el plan de la Fase 2. **Muy bien pensado** — el enfoque de **singleton `home.md` + textos actuales como defaults** para garantizar paridad visual es la decisión correcta y de bajo riesgo. **Luz verde**, con **2 correcciones concretas** y 3 precisiones. Actualiza los artefactos SDD y luego implementa por pasos (el home con máximo cuidado); reporta evidencia real.

## ✅ Aprobado
- **Modelo del home: singleton + defaults.** `index.astro` lee `home.md` e inyecta en los componentes existentes vía props, con los textos actuales como **fallbacks** → 100% paridad inmediata. Correcto.
- **Tipos de sección** (testimonials, stats, cta, steps, logos) con su componente `.astro`. Bien.
- **Flujo PR** vía `workflow_dispatch` + `peter-evans/create-pull-request`, humano aprueba/mergea. Bien.

## 🔴 Corrección 1 (BLOQUEANTE) — el home NO debe duplicarse como ruta `/home`
Verifiqué: el catch-all `src/pages/[...slug].astro` hace `getCollection('pages')` y genera `params: { slug: page.id }`. Si metes `home.md` en la colección `pages`, **el catch-all también generará `/home/`** — duplicando el contenido del home (que ya se sirve en `/` vía `index.astro`).
→ **Excluye el singleton del catch-all**: en el `getStaticPaths()` de `[...slug].astro`, filtra `page.id !== 'home'` (y cualquier singleton). Alternativa: pon el home en una **colección aparte** (ej. `siteData`/`home`) que el catch-all no consuma. Elige una y documéntala.

## 🔴 Corrección 2 — el deploy NO es Vercel/Netlify
El plan dice *"Vercel/Netlify reconstruye en producción al mergear"*. **Falso:** `astro.config.mjs` usa `output: 'static'` y el hosting real es **IONOS** (webspace, deploy vía SSH). No hay auto-deploy de Vercel/Netlify.
→ El flujo PR está bien (genera draft → PR → merge produce el contenido en el repo). Pero **no asumas auto-deploy**: al mergear, el contenido queda en `main`; **la publicación a producción usa el pipeline de deploy existente a IONOS** (manual o el que haya). Documenta ese paso real, no uno inventado.

## Precisión 3 — Esquema de secciones: aditivo y por-tipo
Los nuevos tipos necesitan campos propios (testimonials: `author`/`role`/`quote`; cta: `buttonText`/`url`; stats: `value`/`label`; steps: array; logos: array). Extiende el Zod de `pages` de forma **aditiva** (campos `.optional()` o una estructura flexible por sección), sin romper las páginas existentes de la Fase 1.

## Precisión 4 — Secrets del workflow (acción del usuario)
El `content-pr.yml` necesita `GROQ_API_KEY`/`DASHSCOPE_API_KEY`/`GEMINI_API_KEY` como **GitHub Secrets** — configurarlos es **acción del usuario**. Déjalo anotado; keys vía `os.environ`/secrets, nunca en claro.

## Precisión 5 — Refactor del home = riesgo visual
Refactorizar `index.astro` + subcomponentes a props toca la portada. Verifica **paridad visual con evidencia** (screenshots/preview antes vs después) y confirma que las **islas** (SemanticSearch/CopilotDemo/DiagnosticWizard) siguen funcionando.

## Verificación (evidencia real, al implementar)
1. **Home:** paridad visual antes/después (preview/screenshots); `npm run build` verde; el home renderiza desde `home.md`+defaults sin cambios visuales; **NO** aparece `/home/` duplicada.
2. **Secciones:** cada tipo nuevo renderiza; páginas de la Fase 1 intactas.
3. **PR flow:** demuestra draft → PR (o dry-run); nada auto-publicado; draft fuera de prod hasta merge + `draft:false`.
4. **Cero intrusión:** diff que muestre que el LLM solo tocó `src/content/` (los `.astro`/workflow los escribes tú).

## Forma de respuesta
- Actualiza `specs/012-fabrica-contenido-sitio/{spec,plan,tasks}.md` con las decisiones + estas correcciones. Implementa por pasos tras eso.
- Evidencia real (paridad del home, sin `/home` duplicada, PR de muestra, diff solo-contenido, build verde). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará **en preview** la paridad visual del home, que **no exista ruta `/home` duplicada**, que el flujo de deploy documentado sea el real (IONOS, no Vercel), que los nuevos tipos de sección no rompan nada, y que la IA solo haya tocado contenido. **Pendiente del usuario:** rotar la SSH de IONOS + configurar los 3 secrets en GitHub para el workflow.
