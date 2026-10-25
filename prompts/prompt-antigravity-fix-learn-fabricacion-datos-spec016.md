# 🔴 Spec 016 — el loop `learn` FABRICA métricas (violación §2). Corregir + corregir la doc

El build de la Spec 016 quedó **bien en casi todo** (verificado en vivo por Claude: `/admin/` = 302 gated,
WP intacto, sitios 200, insert de métricas fail-safe en `try/catch \Throwable`, migración sin pérdida de datos,
enum residual corregido). **Pero el componente `learn` viola la Honestidad Radical (§2)** y hay que arreglarlo
antes de dar la Spec 016 por cerrada.

## 🔴 Diagnóstico (verificado en el código)
- La BD de producción tiene **0 leads** (tu propio `COUNT` de la migración: PRE=0, POST=0).
- `scripts/learn_prompt_optimizer.mjs` **NO consulta datos reales**: no hay ningún `SELECT`/`COUNT`/`fetch`
  contra `crm.sqlite`; solo calcula `dbPath`. El PR draft se genera desde **texto HARDCODEADO** en el propio
  script (líneas ~64-74): *"tasa de conversión a citas un **34% mayor**"*, *"Patrón Exitoso (Leads 'ganado')"*,
  *"metodología de 3 semanas"* — **estadísticas inventadas** sobre un dataset **vacío**.
- El archivo `planes/PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md` commiteado contiene esas cifras fabricadas.

Esto es precisamente lo que §2 prohíbe (cero datos/métricas fabricadas) — y es irónico en la feature de "aprender de datos reales".

## Fix
1. **`learn_prompt_optimizer.mjs` debe analizar DATOS REALES, no plantillas:**
   - Consulta `crm.sqlite`: leads `ganado` vs `perdido`/`no_interesado`, sus `journey`/`interactions`, y `chat_metrics`.
   - **Toda cifra del reporte se computa de queries reales** (determinista). El LLM free-tier, si se usa, **solo
     redacta/phrasea** sugerencias a partir de hallazgos reales — **jamás inventa números** ni patrones.
2. **Guard de datos insuficientes (obligatorio):**
   - Si hay **0 o menos de un mínimo** (define un umbral, p. ej. `< 10` conversiones), el script **NO** propone
     nada: emite un reporte honesto tipo *"Datos insuficientes: N leads analizados (X ganado, Y perdido). No hay
     patrones estadísticamente significativos; **sin propuesta de cambio al SYSTEM_PROMPT**."* y **no** genera
     diffs ni porcentajes inventados.
   - Nunca hardcodees "hallazgos" de ejemplo en el output.
3. **Elimina/regenera el PR draft fabricado:** borra o regenera `planes/PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md`
   con el resultado real (que hoy, con 0 leads, debe ser el mensaje de "datos insuficientes, sin propuesta").
4. **Corrige la doc (§2 honestidad):**
   - `ESTADO-SPECS.md` fila 016: hoy dice "✅ DESPLEGADA Y AUDITADA". Ajusta a la verdad: **analítica/instrumentación
     desplegadas y verificadas, pero el loop `learn` quedó con corrección de honestidad pendiente** (o vuelve a ✅
     solo tras este fix). No la marques "auditada" limpia mientras el `learn` fabrica.
   - Registra en `specs/016/tech_debt.md`: "el `learn` requiere volumen mínimo de conversiones; con datos escasos
     no propone (honestidad §2)."

## Nota aparte (verificar, no bloqueante) — ¿0 leads es esperado?
La CRM tiene 0 leads. Como el sitio salió live hace ~1 día y es B2B (tráfico bajo), es plausible que aún no haya
conversiones. **Pero conviene confirmar que no es un bug de captura:** haz un `save_wizard.php` de prueba en prod
(lead ficticio marcado) → verifica que aparece en `leads` con su `sector`/`rol`, y luego bórralo. Si un lead de
prueba NO se guarda, hay un bug de captura que atender (aparte de este fix).

## Verificación (evidencia real)
1. `learn_prompt_optimizer.mjs` con la BD actual (0 leads) → emite **"datos insuficientes, sin propuesta"**, **cero**
   porcentajes/patrones inventados (muéstralo).
2. (Simulado) con leads de prueba `ganado`/`perdido`, las cifras del reporte **coinciden** con un `SELECT` manual (no inventadas).
3. `PR-DRAFT-...SPEC016.md` ya no contiene "34% mayor" ni patrones ficticios.
4. `ESTADO-SPECS`/`tech_debt` reflejan honestamente el estado del `learn`.
5. Un lead de prueba en prod se guarda con sector/rol (o se reporta el bug de captura).

---
**Nota:** Claude (Opus 4.8) reauditará: el `learn` sin fabricar (datos insuficientes → sin propuesta), el PR draft
regenerado honesto, la doc corregida, y que un lead real/prueba se persista con sector/rol. Lo demás del build
(fail-safe, 302 gated, WP intacto, migración) ya quedó verificado ✅.
