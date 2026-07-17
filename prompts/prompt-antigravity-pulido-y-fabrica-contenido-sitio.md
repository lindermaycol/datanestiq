# Prompt para Antigravity: (1) pulir 2 detalles menores + (2) NUEVA Spec 012 "Fábrica de contenido del sitio"

Dos frentes. **Parte 1** implementa directo (con evidencia). **Parte 2** sigue **SDD estricto**: entrega `spec.md`+`plan.md`+`tasks.md` para **mi revisión ANTES de implementar** (es una decisión de arquitectura del sitio).

---

# PARTE 1 — Pulir 2 detalles (implementar y reportar evidencia)

## 1.1 — KPIs de sector: renderizar también su ROI `[EST]`
Hoy `src/pages/sectores/[slug].astro` muestra el **nombre** del KPI (`metric`) pero **no su `expectedRoi`** (ej. `[EST] 45% de mejora`). Renderiza **ambos** (ej. tarjeta de métrica con `metric` + `expectedRoi`). Aditivo/condicional (`if` existe). Build verde. Verifica en el HTML construido que el `[EST]` aparezca.

## 1.2 — Lighthouse: que produzca scores reales (hoy falla con `EPERM`)
`lhci autorun` falla con `EPERM` en el Temp de Windows y **no genera scores**. Diagnostica y configúralo para que **corra de verdad**:
- En `lighthouserc.json`: usa `ci.collect.staticDistDir: "./dist"` (audita el build estático) y dirige la salida/upload a un **directorio del proyecto** (ej. `.lighthouseci/`) en vez del Temp del sistema; ajusta `chromePath`/flags si hace falta (`--no-sandbox`).
- Si Chrome no está disponible en el entorno, **documenta el bloqueo exacto** y el comando que sí funcionaría — no lo dejes en un error silencioso ni en un stub.
- Pega los **scores reales** si corre.

---

# PARTE 2 — NUEVA Spec 012: Fábrica de contenido del sitio (SDD)

## Contexto y objetivo
El usuario quiere **agentes que redacten, mantengan y creen el contenido de páginas, secciones y el home** del sitio Datanestiq (uso interno / dogfooding). **Lección aprendida (Spec 009):** los agentes autónomos multi-turno son frágiles y agotan cuotas. El patrón **confiable** es el de la **Spec 010**: generador **single-shot controlado** → escribe contenido a **colecciones** → **plantillas fijas** renderizan → **revisión humana**. Esta spec extiende ese patrón a páginas/secciones.

## Principio arquitectónico — NO NEGOCIABLE
1. El agente **escribe CONTENIDO/DATOS** (markdown/JSON validado por **Zod**) en **colecciones de contenido**. **NUNCA escribe `.astro`, JS ni lógica de la app.**
2. **Plantillas Astro fijas** (escritas por humano) renderizan esas colecciones.
3. Todo se genera con **`draft: true`** → el humano **revisa y aprueba** (pone `draft: false` o hace merge del PR) **antes de que salga en vivo**. Sobre todo el **home** y páginas de alta visibilidad.
4. Reutiliza de la Spec 010: el **balanceo 3 keys**, la **seguridad** (solo archivos git-tracked, excluir `.env`/`secure_leads`/WP core), la **taxonomía** como fuente de verdad, y los **content-angles** como fuente de temas.

## Diseño a definir en el plan (para mi revisión)
- **Modelo de contenido:** propón una colección (ej. `src/content/pages/` o `landing-sections/`) con **esquema Zod** para páginas/secciones (ej. `slug`, `seo`, `hero{headline,sub,cta}`, `sections[{type, heading, body, items, cta}]`, `draft`). Debe encajar con el Astro actual **sin romper** consumidores ni el build.
- **Plantilla de render:** una ruta dinámica (ej. `src/pages/[...slug].astro`) o componentes fijos que rendericen la colección. La escribe el humano (no el agente).
- **Target del generador:** añade `--target=page` (y/o `section`) a `docs-generator.mjs` que produzca un **draft** desde la taxonomía + `--brief`/`--slug`, cumpliendo el esquema. Reutiliza `stripFrontmatter`, sellado de fechas, etc.
- **Flujo de revisión:** cómo aprueba el humano (draft→publish o PR). Documéntalo. Que los drafts **no salgan en producción** hasta aprobarse.
- **Alcance Fase 1 (ACOTADO):** NO regenerar el home/sitio completo de golpe. Entrega: el **modelo** + la **plantilla** + el **target** + **1 página/sección de muestra** generada como draft. El home y el rollout masivo son Fase 2.
- **Fuera de alcance Fase 1:** estructura/navegación del sitio y reescritura de páginas existentes (posterior).

## Verificación (al implementar, tras mi aprobación)
1. `--dry-run` no consume API.
2. Genera **1 página/sección de muestra** desde un brief de la taxonomía (`--target=page`) con `draft: true`; contenido on-brand y fundamentado.
3. `npm run build` verde; el draft **no aparece en producción** hasta `draft:false` (demuéstralo).
4. El agente **no tocó ningún `.astro`/lógica** (solo la colección) — muéstralo con el diff.
5. Reparto de proveedores (balanceo) en la generación.

## Estado
- `planes/ESTADO-SPECS.md`: fila **012** (nueva).

---

## Forma de respuesta
- **Parte 1:** implementa y reporta evidencia real (KPI ROI en el HTML construido; scores de Lighthouse **o** el bloqueo documentado con el comando correcto). `npm run build` verde.
- **Parte 2:** entrega `spec.md`+`plan.md`+`tasks.md` de la Spec 012 para **mi revisión**. **NO implementes la Parte 2 todavía.**
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que la Parte 1 realmente renderice el ROI y que Lighthouse dé scores reales (o el bloqueo esté honestamente documentado); y en la Parte 2, que el diseño respete el principio no negociable (agente escribe **contenido**, no código; todo **draft-gated** con aprobación humana; reutiliza el motor y la seguridad de la Spec 010). **Pendiente del usuario:** rotar la SSH de IONOS.
