# Confirmación — casos-de-exito → roiCases + honestidad: LUZ VERDE con 2 precisiones → ejecutar

El plan (`planes/Plan de Implementación — casos-de-exito Migración a roiCases (SSOT) + Honestidad.md`) es correcto: agrega `roiCases` a 5 sectores, migra la página a leerlos de `sectorsCorpus.json` (`flatMap`), y reescribe disclaimer/intro/CTA de forma honesta. Verifiqué que `sector.slug` existe en el JSON (bien). Ejecuta con estas 2 precisiones.

## 🔴 Precisión 1 (la importante) — REUTILIZA `<IllustrativeRoiCase>`, no reinventes el `[EST]`
Ya existe `src/components/ui/IllustrativeRoiCase.astro` y **ya resuelve la honestidad mejor** que el badge inline que propone el plan (línea ~72):
- Badge **`[EST] Escenario Ilustrativo`** a nivel de tarjeta + **tooltip** que dice: *"Esta proyección es un modelo basado en promedios de la industria y nuestras capacidades arquitectónicas, **no representa un cliente real histórico**. Datanestiq prioriza la transparencia."*
- Renderiza `title`, `context`, `description` y `metrics` (`value`/`label`).
- **Es el MISMO componente que las páginas de sector** ya usan para `roiCases` (`/sectores/[slug].astro` línea 118).

**Haz esto:** en `casos-de-exito.astro`, por cada caso agregado del `flatMap`, renderiza **`<IllustrativeRoiCase title={rc.title} context={rc.context} description={rc.description} metrics={rc.metrics} />`** (puedes anteponer el badge de sector `Escenario: {sectorTitle}`). **Elimina** el markup manual de `[EST]` por métrica del plan — el componente ya lo cubre a nivel de tarjeta, con tooltip honesto, y queda **consistente con las páginas de sector**. Mantén además el disclaimer honesto de página (intro/disclaimer/CTA reescritos como dice el plan).

## 🔴 Precisión 2 (consistencia que el plan destapa) — heading "Demostrable" en páginas de sector
Al enriquecer 5 sectores con `roiCases`, esos casos aparecerán **también** en 5 páginas de sector (vía `IllustrativeRoiCase` — ya honesto ✅). Pero el heading de esa sección en `/sectores/[slug].astro` (línea ~115) dice **"Impacto Económico Demostrable"** — *"Demostrable"* implica algo probado, y ahora estará visible en más páginas sobre casos que son `[EST]` ilustrativos. **Cámbialo** a algo honesto, ej. **"Escenarios de Impacto Económico"** o **"Impacto Económico Potencial"**.

## Aclaración menor
- La §1 del plan no lista `seguros.yaml` en los MODIFY, pero la sección de revisión sí menciona un caso de seguros. `seguros` **ya tiene** `roiCases`. Decide: conserva el existente o actualízalo al del review — no dupliques. (No bloqueante.)

## OK tal como está
- `roiCases` en finanzas/retail/salud/publico/seguros; agregación por `flatMap` (usa `sector.slug`, que existe) ✅.
- Disclaimer honesto (sin "confidencialidad corporativa"), intro "ilustrativos", CTA sin "historia de éxito" ✅.
- Taxonomía = SSOT (sin escenarios hardcodeados en el `.astro`); `build-taxonomy` + `npm run build` verdes ✅.

## Verificación (evidencia real)
1. **SSOT:** `casos-de-exito.astro` no contiene textos de casos; quitar un `roiCase` de un YAML lo elimina de la página tras rebuild.
2. **Honestidad (vía componente):** cada caso en `/casos-de-exito` se muestra con el badge **`[EST] Escenario Ilustrativo`** + tooltip (mismo `<IllustrativeRoiCase>` que los sectores); disclaimer de página sin "confidencialidad corporativa".
3. **Consistencia:** el heading de la sección de casos en las páginas de sector ya **no** dice "Demostrable".
4. Cobertura ≥ 4-5 casos; `build-taxonomy` + `npm run build` verdes; `ESTADO-SPECS.md` actualizado.

---
**Nota:** Claude (Opus 4.8) reauditará: casos desde `roiCases` (no hardcodeados), render vía `<IllustrativeRoiCase>` con badge `[EST]` + tooltip en `/casos-de-exito` y en las páginas de sector, heading sin "Demostrable", y cero clientes/cifras presentados como reales.
