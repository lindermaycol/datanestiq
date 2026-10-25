# Prompt para Antigravity: Confirmación del Plan Spec 009 (Fase 1) → ejecutar con condiciones

Revisé `specs/009-openwiki-langchain/{spec.md,plan.md,tasks.md}` y el plan resumen. Están bien: capturan la distinción de propósitos, la arquitectura por fases, la dependencia 005→009 y la config de Gemini corregida (base URL `.../v1beta/openai`, `gemini-2.5-flash`, sin prefijo `google/`). **Luz verde con condiciones**, respondiendo tus 2 Open Questions y con 6 precisiones.

## Respuestas a tus Open Questions

**1. ¿`--update` en CI en vez de `--print`?** → **No lo fijes por asunción.** Tu propio WARNING admite que solo verificaste `--help`, no una corrida real con Gemini. **La corrida local real decide** (ver Open Question 2). El criterio duro: **el comando de CI debe ser NO INTERACTIVO**. `--init` casi seguro es interactivo (pregunta el provider, etc.) → sirve local pero **jamás en CI** (colgaría sin TTY). `-p`/`--print` es explícitamente el modo one-shot no interactivo. Entonces: prueba empíricamente si `openwiki --update` corre **sin prompts** en un entorno sin TTY; si pregunta algo, usa `-p`/`--print` o los flags que lo fuercen a no interactivo. **Decisión por evidencia, no por asunción.**

**2. ¿Corrida local inyectando la API key?** → **Sí, y es OBLIGATORIA como gate antes de tocar CI.** Usa el valor de `GEMINI_API_KEY` que ya está en `.env`, mapeándolo a `OPENAI_COMPATIBLE_API_KEY` (que es la var que el tool lee). Esa corrida real debe: (a) probar que Gemini funciona end-to-end con el tool, (b) determinar el comando no interactivo para CI, (c) **llenar el "Contrato de Interfaz"** con la estructura real de salida. **No cablees el workflow sobre una suposición** — cierra el WARNING en esta misma fase.

## Precisión 1 — `openwiki` FUERA del `package.json` del proyecto
El `ERESOLVE` que encontraste es la prueba de que choca con las peer-deps de Astro. **No lo agregues como dependencia del proyecto.** En CI, instálalo **global** (`npm install -g openwiki`) o vía `npx openwiki`, aislado del `package.json` raíz. Tu enfoque de directorio temporal fue correcto para local; mantén esa separación.

## Precisión 2 — Trigger del workflow: cron + manual, NO en cada push
Tu tarea 3 menciona "crontab o en empujes a `main`". **Descarta el push a main**: regenerar doc con LLM y abrir PR en cada merge es costoso y ruidoso. Usa solo **`workflow_dispatch` (manual) + `schedule` (cron)**, igual que el patrón de `openwiki-audit.yml` de la Spec 005.

## Precisión 3 — Consciencia de cuota (429) de Gemini
Verifiqué que `gemini-2.5-flash` responde 200, pero `gemini-2.5-pro` y `gemini-2.0-flash` dieron **429 (cuota)** con esta clave. Como `openwiki` analiza el repo puede hacer **muchas llamadas** y toparse con rate-limit del free tier. En la corrida real: vigila 429s; si el output queda a medias por cuota, **repórtalo explícitamente** (no lo des por bueno) y evalúa acotar el alcance de análisis.

## Precisión 4 — Alcance EXACTO del rename (no rompas consumidores)
Verifiqué las referencias reales. Renombra **solo** la colección de contenido, la URL `/wiki/` NO cambia:
- Mover carpeta `src/content/openwiki/` → `src/content/wiki/`.
- `src/content.config.ts`: `base: "./src/content/openwiki"` → `wiki`; y la clave `'openwiki'` (línea 29) → `'wiki'` (y el nombre de var `openwikiCollection`, cosmético).
- `src/pages/wiki/[slug].astro`: `getCollection('openwiki')` → `getCollection('wiki')`.
- `scripts/openwiki-sync.mjs`: el `outPath` `src/content/openwiki` → `src/content/wiki`.
- Corrige el texto autorreferente en `arquitectura.md` (menciona `/src/content/openwiki`).
- **NO** renombres el workflow `openwiki-audit.yml` (Spec 005) ni lo confundas con el `openwiki/` raíz del tool. Verifica `npm run build` = **22 páginas** y `/wiki/arquitectura` viva.

## Precisión 5 — CLAUDE.md / AGENTS.md
Confirmé que **no existe** un `CLAUDE.md` en la raíz del repo (no hay riesgo de sobreescritura destructiva). Pero ojo: este repo se usa con Claude Code, así que si el tool **crea** un `CLAUDE.md` con instrucciones para agentes, influirá en futuras sesiones. Preferencia: apunta a **`AGENTS.md`** si el tool lo permite; si igual escribe `CLAUDE.md`, **incluye su contenido en el PR** para revisarlo. Mantén la verificación "append-only, sin sobreescritura destructiva".

## Precisión 6 — Llena el Contrato de Interfaz (no dejes el placeholder)
El `spec.md` §3 tiene *"(Pendiente: insertar la muestra real…)"*. Esta fase **no se da por terminada** hasta que ese contrato esté relleno con la **estructura real** de `openwiki/` capturada en la corrida local (archivos, formato). Es la frontera sobre la que la Fase 2 construirá el adaptador.

## Verificación (reporta con evidencia)
1. **Corrida local real con Gemini:** comando exacto usado, si fue no interactivo (o cómo lo forzaste), salida `openwiki/` generada + diff de `AGENTS.md`/`CLAUDE.md`. Fundamentada en el código real, no genérica.
2. **Contrato de Interfaz** en `spec.md` relleno con la muestra real.
3. **Build tras rename:** 22 páginas, `/wiki/arquitectura` viva.
4. **CI:** workflow válido con `workflow_dispatch` + `schedule`, `secrets.GEMINI_API_KEY`, `openwiki` global/npx (no en package.json).
5. `planes/ESTADO-SPECS.md`: nueva fila 009 [Fase 1] + nota de dependencia 005→009.

## Forma de respuesta
- Reporta la verificación con la muestra real de salida del tool. No des por válido lo no probado con Gemini.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que la corrida con Gemini sea **real** (no asumida), que el comando de CI sea no interactivo, que `openwiki` no entre al `package.json`, que el rename no rompa el build (22 páginas), que el Contrato de Interfaz esté relleno, y que el trigger no sea push-a-main. **Pendiente del usuario:** configurar el secret `GEMINI_API_KEY` en GitHub.
