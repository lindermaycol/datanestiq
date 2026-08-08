# Prompt para Antigravity: Spec 005 — cerrar el "espejismo v2" (generación real + IGNORE cableado)

Audité tu entrega en caliente. **Lo bueno está verificado y funciona:** el balanceador round-robin ponderado, el failover atómico, la guarda Zod (`validateAndFixFrontmatter` trunca `title`/`description` y valida fecha), el dry-run sin consumo, `dummy.md` borrado + `arquitectura.md` sembrado, `.env.example`, el workflow con `GROQ_API_KEY`/`DASHSCOPE_API_KEY` sin Anthropic, y `npm run build` = 22 páginas con `/wiki/arquitectura` renderizando. `tasks.md` reescrito correctamente. **Nada de eso hay que tocarlo.**

Pero el **núcleo generativo está hueco** — es un espejismo v2. Tres correcciones obligatorias antes de dar la Spec por operativa.

## Corrección 1 (BLOQUEANTE) — Alimentar el contenido/diff real al LLM
Hoy, en modo no-seed, el único contexto que recibe el modelo es la frase `"El archivo X ha cambiado. Actualiza la documentación..."`. **Nunca se envía el contenido del archivo ni el `git diff`.** El modelo redacta sin fundamento ⇒ alucina. Arréglalo:
- Para cada archivo relevante, incluye en el mensaje `user` el **contenido actual del archivo** (o el `git diff` de ese archivo, o ambos: diff + snippet). Trunca de forma segura a un límite de tokens/caracteres (ej. ~8–12k chars) para no reventar `max_tokens`.
- El prompt debe pedir explícitamente que la doc **describa el cambio real observado**, no genérico.
- Sube `max_tokens` si hace falta para docs con contexto (hoy 2048; evalúa 4096) y considera streaming si crece.

## Corrección 2 (BLOQUEANTE) — Cablear de verdad los bloques IGNORE
`extractIgnoreBlocks()` está **definida pero nunca se invoca**; `restoreIgnoreBlocks(content, [])` siempre recibe `[]`. La protección de secciones inmutables (User Story 3 / FR §5.B) **no existe**. Arréglalo:
- Antes de generar, **lee el doc destino existente** (si existe). Ejecuta `extractIgnoreBlocks()` sobre su contenido, guarda los bloques, y **pásale al LLM la versión con placeholders** `%%IGNORE_BLOCK_x%%` (el system prompt ya instruye preservarlos).
- Tras recibir la respuesta, **`restoreIgnoreBlocks(finalContent, blocks)` con los bloques reales** extraídos (no `[]`).
- Prueba de aceptación: un doc con `<!-- OPENWIKI:IGNORE:START -->…<!-- END -->` (como el que ya tiene `arquitectura.md`) debe salir con esa sección **byte-idéntica** tras una corrida de actualización.

## Corrección 3 (BLOQUEANTE) — Mapeo estable fuente → doc canónico (no `*-update.md` alucinados)
Hoy un cambio en `specs/003.../spec.md` escribe `src/content/openwiki/spec-update.md`, y dos `spec.md` de specs distintas colisionarían en el mismo `spec-update.md`. Además nunca actualiza un doc canónico existente (por eso la Corrección 2 no tiene sobre qué operar). Arréglalo:
- Define un **mapeo determinista y único** de archivo fuente → slug de doc (ej. usa la ruta relativa aplanada: `specs/003-taxonomia-servicios/spec.md` → `spec-003-taxonomia-servicios.md`), de modo que **la misma fuente actualice siempre el mismo doc** y no haya colisiones.
- Si el doc ya existe, es una **actualización** (lee→extrae IGNORE→regenera→restaura→escribe el mismo archivo), no un archivo nuevo.

## Corrección 4 (menor) — Dry-run debe simular sin claves
`balanceAndCall()` verifica claves **antes** del corto-circuito `DRY_RUN` de `callOpenAICompatible()`, así que en un clon sin `.env` el `--dry-run` sale sin simular. Mueve el chequeo `if (DRY_RUN)` para que el dry-run **nunca** dependa de claves ni haga `process.exit(0)` por falta de ellas.

## Verificación E2E (reporta con evidencia)
1. **Grounding:** con claves reales, modifica un archivo bajo `specs/` o `src/content/`, corre el motor, y muestra que la doc generada **menciona el cambio concreto** (no texto genérico). Log del proveedor que atendió.
2. **IGNORE byte-idéntico:** corre una actualización sobre un doc con bloque IGNORE y demuestra (diff) que la sección inmutable **no cambió**.
3. **Mapeo:** dos `spec.md` de specs distintas generan **dos** docs distintos (sin colisión); re-correr sobre la misma fuente **actualiza** el mismo archivo, no crea otro.
4. **Failover:** con `GROQ_API_KEY` inválida a propósito → conmuta a DashScope y completa (log).
5. **Dry-run sin claves:** en entorno sin `.env` ni env vars, `--dry-run --seed` **simula** y sale 0 sin llamar a APIs.
6. `npm run build` sigue en verde (esquema Zod OK).

## Cierre (estado honesto)
- `specs/005-openwiki-agentes/tech_debt.md`: mueve #2, #3 (y #4) de 🔴/🟡 ABIERTO a RESUELTO **solo** cuando la verificación de arriba lo respalde; sube el `## Estado` a "operativo real" únicamente entonces.
- `planes/ESTADO-SPECS.md`: fila 005 → "Motor real operativo (generación fundamentada + IGNORE cableado); pendiente solo secrets".
- No re-declares "operativo" sin la evidencia de los puntos 1–3. No sobre-afirmes.

## Forma de respuesta
- Reporta la verificación E2E con evidencia real (grounding, IGNORE byte-idéntico, mapeo, failover, dry-run sin claves, build).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará en caliente que el LLM reciba el contenido/diff real (no un prompt genérico), que `extractIgnoreBlocks` se **invoque** y los bloques inmutables salgan byte-idénticos, que el mapeo fuente→doc sea único y actualice en sitio, y que el dry-run simule sin claves.
