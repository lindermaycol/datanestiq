# Prompt para Antigravity: Confirmación del Plan de Estandarización de Diseño & Spec 004 → ejecutar

He revisado `planes/Plan de Ejecución Estandarización de Diseño & Ejecución de Agentes (Spec 004).md`. Es fiel al prompt original, no hay decisiones estratégicas pendientes esta vez. **Tienes luz verde para ejecutar el plan completo**, con estas 4 precisiones que debes aplicar mientras ejecutas (no cambian el alcance, solo evitan inconsistencias):

## Precisión 1 — Color del glow/shadow en `.btn-primary`

El plan dice "actualizar `.btn-primary` a `rounded-full`", correcto, pero no menciona el color del `box-shadow`. Hoy usa `rgba(59,130,246,0.5)` (el azul de `primary` #3B82F6, que estás eliminando). Actualízalo también a `rgba(37,99,235,0.5)` para que coincida con el nuevo `brand` (#2563EB) — si no, el glow del botón quedará con un azul ligeramente distinto al resto del sistema.

## Precisión 2 — No toques la forma del JSON al refinar el copy (Agente 05)

Al refinar `hero.headline`, `hero.subheadline`, `contrast.problem/solution` en `taxonomyCorpus.json`: mantén exactamente las mismas claves y estructura (no renombres campos, no cambies `seo.description`). `SEO.astro` y `[id].astro` dependen de esa forma exacta para el JSON-LD y los meta tags — un cambio de esquema ahí rompería lo que ya validamos en el ciclo anterior. Si mejoras `seo.description` también, hazlo, pero como refinamiento de texto, no de estructura.

## Precisión 3 — Verificación de conteo de páginas en el build

Antes de este ciclo `dist/soluciones/` tenía 6 páginas + home + wiki = 8 páginas totales. Tras el Bloque 3 deberían ser **10**: home, 6 de `/soluciones/`, `/wiki/dummy`, `/nosotros`, `/casos-de-exito`. Repórtame el conteo exacto que arroja `npm run build` (el log de Astro lista cada ruta generada) para que la verificación sea explícita, no solo "compiló sin errores".

## Precisión 4 — Recordatorio de alcance

Sigue sin tocar `specs/008-headless-wordpress/` — eso queda en pausa hasta que confirmemos que este ciclo (diseño + Spec 004) está validado funcional y visualmente.

---

Procede con la ejecución completa de los 3 bloques + trazabilidad tal como los desglosaste. Cuando termines, repórtame igual que en los ciclos anteriores (qué archivos tocaste por bloque, resultado de `npm run build`/`npm run preview`, conteo de páginas, y "Hallazgos adicionales" si aplica). Yo audito al final comparando visualmente contra `prototype/` como referencia.
