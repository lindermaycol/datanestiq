# Prompt para Antigravity: Spec 012 Fase 2 — Home data-driven + secciones + flujo de aprobación por PR (SDD)

**Sigue SDD estricto.** La Fase 1 está operativa y auditada (colección `pages` con `loader:glob` en `src/content.config.ts`, plantilla catch-all `src/pages/[...slug].astro`, `docs-generator.mjs --target=page` que fuerza `draft:true`, y draft-gating verificado: draft→excluida de prod, publish→incluida). Esta Fase 2 es **más riesgosa** (toca el home), así que: **entrega `plan.md` + `tasks.md` (o spec 012 actualizada) para MI REVISIÓN antes de implementar. NO implementes aún.**

## Principios NO NEGOCIABLES (recordatorio)
1. El agente escribe **CONTENIDO/DATOS** (markdown/JSON validado por Zod) — **NUNCA `.astro`/JS/lógica**.
2. Todo nace **draft-gated** + requiere **aprobación humana** antes de publicar. El **home = máxima cautela**.
3. Reutiliza el motor de la Spec 010 (balanceo 3 keys, seguridad git-tracked, exclusión de secretos) y la **taxonomía** como fuente de verdad.

---

## PARTE 1 — Home data-driven (ALTO RIESGO — cuidado)
Objetivo: que el **contenido del home** viva como datos (colección) y una **plantilla fija** lo renderice, para que la fábrica pueda **proponer** cambios del home sin tocar su código.
- **Paso 1 — Migración sin cambiar el look:** extrae el contenido actual del home (`src/pages/index.astro` o el que sea) a una entrada de contenido (ej. un singleton `home` o secciones componibles), y haz que una plantilla fija lo renderice. **CRÍTICO: paridad visual — el home debe verse EXACTAMENTE igual tras la migración.** Nada de rediseño.
- **Paso 2 — Propuestas:** solo después, el generador puede producir **propuestas de contenido de home** como **draft/PR** (nunca auto-publicar). Tú apruebas.
- ⚠️ La IA **no rediseña** el home; solo se migra contenido a datos + se proponen cambios que el humano aprueba.

## PARTE 2 — Más tipos de sección + componentes de render
- Extiende el `enum` de `sections.type` (hoy `features|benefits|faq|trust`) con los que el sitio necesite (ej. `testimonials`, `stats`, `cta`, `steps/process`, `logos`). Propón la lista concreta en el plan.
- Un **componente Astro fijo por tipo** (escrito por humano) que renderice bien cada sección. Aditivo — no rompas las secciones/páginas existentes.
- Actualiza el esquema Zod de `pages` de forma **aditiva** (campos opcionales) para soportar los datos de los nuevos tipos.

## PARTE 3 — Flujo de aprobación por PR (agentes proponen, humano aprueba)
- Un flujo (workflow GitHub Actions **o** script local) que: corra el generador → cree contenido **draft** → **abra un PR** (`peter-evans/create-pull-request`) para revisión humana → al **mergear** + `draft:false`, se publica.
- **Sin auto-merge.** El humano revisa y aprueba. Hasta entonces, el draft **no** sale en prod (ya garantizado por el draft-gating de Fase 1).
- Si es CI, reutiliza el patrón/secrets existentes; keys vía `os.environ`/secrets, nunca en claro.

## Decisiones a resolver en el plan (para mi revisión)
- **Modelo del home:** ¿un singleton `home` o secciones componibles reutilizables? Propón y justifica.
- **Tipos de sección** concretos a añadir (lista).
- **Flujo PR:** local (script que abre PR) vs CI (workflow). Y si requiere configurar secrets en GitHub (acción del usuario).
- Cómo garantizas la **paridad visual** del home migrado.

## Verificación (al implementar, tras mi aprobación)
1. **Home: paridad visual** antes/después (screenshots/preview); `npm run build` verde; el home renderiza desde datos sin cambios visuales.
2. **Secciones:** cada tipo nuevo renderiza correctamente; páginas/secciones existentes intactas.
3. **PR flow:** demuestra un draft → PR (o dry-run del flujo). **Nada auto-publicado**; el draft no aparece en prod hasta merge + `draft:false`.
4. **Cero intrusión:** diff que muestre que el agente/LLM solo tocó **contenido** (`src/content/`), ningún `.astro`/lógica (esos los escribes tú).
5. Reparto de proveedores (balanceo) en la generación.

## Forma de respuesta
- **Primero:** `plan.md` + `tasks.md` (o spec 012 actualizada) para mi revisión, con las decisiones resueltas. **NO implementes aún** (sobre todo el home).
- Al implementar (tras mi visto bueno), reporta evidencia real (paridad del home, PR de muestra, diff solo-contenido, build verde).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará **en el navegador (preview)** la **paridad visual del home** tras migrarlo a datos, que ningún draft se publique sin aprobación, que los nuevos tipos de sección no rompan nada, y que la IA no haya tocado código (solo contenido). **Pendiente del usuario:** rotar la SSH de IONOS.
