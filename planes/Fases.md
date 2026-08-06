# Datanestiq — Hoja de ruta por fases (SDD)

Este documento resume la evolución del proyecto por **fases**, para que cualquier colaborador (humano o IA) entienda de inmediato qué se entregó, qué está en curso y qué falta. El estado detallado por especificación está en [`ESTADO-SPECS.md`](ESTADO-SPECS.md).

> **Rama activa:** todo el trabajo posterior a la Fase 0 vive en la rama **`007-multi-pagina`**. La rama `main` contiene solo el setup inicial; auditorías del repo deben mirar `007-multi-pagina`.

---

## Fase 0 — Prototipo estático y estrategia multi-industria ✅
*(commits `feat: Add Phase 0 Static Prototype…` y `fix: post-audit corrections Fase 0 → ready for Fase 1`)*

- Prototipo Astro (SSG) con hero, servicios, islas base (chatbot, buscador, wizards).
- **Taxonomía inicial** (pilares/servicios, sectores, personas del comité de compra) como fuente de verdad, con `build-taxonomy.mjs` (validación Zod + integridad de aristas).
- Correcciones post-auditoría (Perplexity/Claude) para dejar la base coherente con las specs.

## Fase de Fundación — Profundidad de páginas + navegación ✅
*(Specs 003/007/011/012 — auditada en navegador)*

Pipeline en 3 capas (**datos → presentación → conversión**), auditado por capa:

1. **Enriquecimiento de taxonomía (Spec 011 Fase 3):** se profundizaron los campos que la conversión por rol necesita — `techStack`/`competitivePositioning` con arquitectura/despliegue/on-prem/TCO; KPIs `[EST]` (4–6 en sectores financieros); `objectionResponses` **1:1** con `objections`; `deploymentModels`/`engagementModels`; `roiCases`.
2. **Fundación (presentación):** `/soluciones/*` y `/sectores/*` renderizan la taxonomía enriquecida + bloque **"Resolvemos tus dudas"** (objeción→respuesta por rol) en las 16 páginas; **hubs** `/soluciones` y `/sectores` (en nav + sitemap); **páginas legales** `/privacidad` y `/terminos` (footer sin `href="#"`); blog base.

## Fase de Conversión — Spec 013 (Motor consultivo por Rol × Sector) ✅
*(spec-marco con fases — implementada y auditada en navegador)*

- **Motor A1–A9** (reutilizable, parametrizado por `(rol, sector)`): secuencia de conversión, objeción→respuesta, **CTA consultivo dinámico**, intents de chatbot, **formulario consultivo** (reusa `save_wizard.php`, PII segura), evidencia honesta (`<IllustrativeRoiCase>` con badge `[EST]`), blog por rol, extensibilidad, y **captura de contexto de entrada** (chips rol/sector + bus `semanticHighlight`, con `<HighlightSync>` como único consumidor del resaltado).
- **Fase 1 — Sector Público:** bloque seguridad On-Prem/VPC, intents institucionales (expedientes/trazabilidad/contratación OECE), FAQ, blogs.
- **Fase 2 — CFO Económico** (Finanzas/Seguros): intents 0-LLM EBITDA/TCO, coexistencia con ERP, **Business Case Estimator** (`/business-case`, cálculo client-side, modelo defendible con `[EST]`).
- **Fase 3 — CEO Estratégico** (Retail/multi): intents de ventaja competitiva, perspectiva estratégica, CTA de evaluación.
- **Invariante mantenido:** el flujo guiado del chatbot es **0-LLM** (cero `fetch`), verificado en Network.

## Higiene post-auditoría ✅
- **Honestidad:** se eliminó la prueba social **fabricada** del home (logos y testimonio inventados) → fila "Tecnologías que dominamos" (stack real) + testimonio **retirado** hasta tener casos verificables; FAQ reformulado.
- **Búsqueda semántica:** discretización a top-3 para discriminar de verdad.
- **Fixes de runtime:** refactor del resaltado (single consumer + clases `!important` inmunes a GSAP), pivote de rol en 1 clic (+ fix de una regresión TDZ).

---

## Estado actual (resumen)

| Área | Estado |
|---|---|
| Prototipo + taxonomía | ✅ |
| Fundación (páginas, hubs, legales) | ✅ auditado |
| Spec 013 (motor + fases Público/CFO/CEO) | ✅ auditado |
| Honestidad de contenido | ✅ sin prueba social fabricada |
| Backend chatbot/leads (PHP) | ✅ en repo (`chat.php`/`save_wizard.php`) |

## Pendiente / Roadmap

### Despliegue (ahora AUTOMATIZADO)
- **Deploy a IONOS automatizado** con paramiko: agente `deploy-ops` + skill `ionos-deploy` + `scripts/deploy/deploy_ionos.py` (dry-run por defecto; `--confirm` para ejecutar). Guía completa: [`DESPLIEGUE-IONOS.md`](DESPLIEGUE-IONOS.md).
- 🔴 **Acción del usuario (Paso 0):** migrar a **clave SSH** o **rotar la contraseña** de IONOS (comprometida en el historial de git) — el deploy automatizado usa la credencial del `.env`.
- 🔴 **Acción del usuario:** poblar el `.env` de producción (claves LLM + `ADMIN_PASSWORD_HASH` + `ALLOWED_IPS`) antes del primer deploy con `--with-env --init-crm`.

### Documentación sincronizada (Constitución §11 — activa)
- Doc-sync obligatorio: `ESTADO-SPECS.md` + `Fases.md` + specs se actualizan en cada cambio; wiki con `npm run docs:sync` (Spec 010) antes de desplegar.

### Producto / decisiones abiertas
- **Spec 016 (Analítica de Conversión y Loop `chat → lead → learn`):** ✅ **DESPLEGADA Y AUDITADA EN PRODUCCIÓN.** Instrumentación en `chat_metrics`, columnas `sector`/`rol` en CRM y script offline para optimización de prompt en CI.
- **Spec 020 (Inteligencia de Demanda y Journey Reconstructor):** 🟠 Artefactos de diseño SDD creados (`spec.md`, `data-model.md`, `plan.md`, `tech_debt.md`). Clasificación client-side 0-LLM de demanda, segmentación en 4 buckets y reconstrucción cronológica del Journey de leads. En espera de auditoría por Claude Code.
- **Spec 008 (Headless WordPress):** en pausa. Decidir si el sitio se mantiene como Astro estático (recomendado por simplicidad) o se porta a WordPress headless.
- **Casos de éxito / prueba social real:** reincorporar testimonios y casos **cuando existan clientes verificables** (hoy retirados por honestidad).
- **README/documentación de entorno:** creados (`README.md`, este `Fases.md`); pendiente opcional un índice de `specs/`/`prompts/`.

### Extensión del motor (cuando lleguen insumos)
- Nuevos roles del comité de compra (**CISO, COO, CTO**) entran como **fases nuevas de la Spec 013** reutilizando el motor A1–A9 — sin reescribir nada, solo poblando taxonomía y parametrizando componentes.

### Deuda técnica menor (no bloqueante)
- Baseline de ESLint por limpiar; Lighthouse pendiente (bloqueado por `EPERM` de Windows en Temp).
- `personas.json` aún envía `contentAngles` al cliente (peso innecesario, no sensible).
- Higiene SDD: consolidar `plan.md`/`tasks.md` formales dentro de `specs/013/` (hoy el plan vive en `planes/`).
