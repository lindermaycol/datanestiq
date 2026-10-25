# Prompt para Antigravity — Fix de conversión del CIO Público (continuidad institucional) + copy

Un recorrido en primera persona (CIO de Gobierno Regional) dio veredicto **"NO agendaría hoy" (10/15)**. **Ya trié sus hallazgos**: la mayoría de los "gravísimos" son de un **build viejo** (la IA no reconstruyó) y ya están resueltos; otros dependen de datos reales del usuario. Queda **1 gap real de alto valor** + 1 copy menor. No re-hagas lo ya resuelto.

## ❌ NO persigas estos (stale / ya resueltos — la IA vio un build previo)
- *"[BORRADOR PARA APROBACIÓN - Opción 2]" en el home* → **ya eliminado** (testimonio retirado). El dist actual: 0 ocurrencias.
- *Logos FINCORP sin disclaimer* → **ya reemplazados** por "Tecnologías que dominamos" (stack real). 0 FINCORP en dist.
- *Buscador semántico no discrimina* → **ya corregido** (discretización top-3). No lo toques.

## ❌ NO inventes esto (le pertenece al usuario, requiere datos REALES)
- **Páginas legales `/privacidad` y `/terminos`** marcadas "[BORRADOR LEGAL]": el CIO nota que una entidad pública no puede formalizar con documentos legales en borrador. **Correcto, pero necesita contenido legal REAL** — no lo inventes. (Queda para el usuario.)
- **`/nosotros` sin equipo/nombres/trayectoria:** el CIO no puede validar quién custodia datos ciudadanos. **Necesita info real de la empresa** — no inventes personas. (Queda para el usuario.)
- **Casos todos del sector privado:** no fabriques casos públicos. Puedes reencuadrar los modelos como **escenarios `[EST]` del ámbito público**, pero sin inventar clientes.

---

## 🎯 Fix 1 (ALTO VALOR) — "Continuidad ante cambio de gestión" en `/sectores/publico`
**Este fue el bloqueador #1 del CIO**, repetido como *"la UNA cosa que me haría decir sí"*: no hay ninguna sección que explique **cómo el proyecto sobrevive al siguiente cambio de gestión**. Verificado: `publico.yaml` **no menciona** continuidad / transferencia de conocimiento / capacidad instalada / documentación / autonomía del equipo. En el Estado, la **continuidad institucional vale más que el ROI del primer año**.

Es una de las **6 matices del buyer público** que ya están documentadas en el insumo `planes/insumos-conversion-consultiva/sector-publico-matices.md` (secciones "Continuidad institucional y ciclo político" y "Gestión del cambio y adopción") — **extrae de ahí, no inventes.**

**Implementación (capa de datos + render):**
- En `src/content/sectors/publico.yaml`, añade contenido de **continuidad institucional** (nuevo campo estructurado o dentro de los existentes; decide la forma más limpia y valídala con `build-taxonomy.mjs`): documentación entregable, **transferencia de conocimiento** al equipo interno, capacitación/adopción, **ownership institucional** (la entidad queda con capacidad instalada, no dependencia del proveedor), y **sostenibilidad ante rotación de autoridades**.
- Renderiza un bloque en `/sectores/publico` tipo **"Así sobrevive tu proyecto al cambio de gestión"** (o "Continuidad y transferencia de conocimiento"), condicional al sector público. Mensaje honesto: son **capacidades/metodología reales**, no casos inventados.
- (Opcional pero recomendado) que el **chatbot público** referencie continuidad/transferencia cuando el problema sea institucional (intents ya existentes; solo enriquecer el mensaje de solución si aplica, **sin romper 0-LLM**).

## Fix 2 (menor, copy) — Inconsistencia editorial en el footer
El footer dice *"para corporaciones B2B"*, que choca en una página de Sector Público. Ajusta el copy del footer (`src/components/ui/Footer.astro`) a algo **inclusivo** que sirva a empresas **y** entidades públicas (ej. "para corporaciones y entidades públicas", o un texto neutro de propuesta de valor). No exclusivices a ninguno.

---

## Guardarraíles
- **Honestidad:** cero casos/testimonios/logos/equipo inventados; métricas con `[EST]`. La continuidad se comunica como **metodología y capacidades reales** (documentación, transferencia, fases), no como clientes.
- **Taxonomía = fuente de verdad:** la continuidad va en `publico.yaml` + `build-taxonomy.mjs`, no hardcodeada en `.astro`.
- **0-LLM** del flujo guiado intacto; PII segura; `npm run build` verde; consola limpia.
- **Marca no exclusiva:** el refuerzo público no rompe la propuesta para otros sectores.

## Verificación (evidencia real)
1. `/sectores/publico` muestra un bloque de **continuidad / transferencia de conocimiento / sobrevivir al cambio de gestión** (grep en `dist/` + captura). Deriva del insumo, no inventado.
2. `build-taxonomy.mjs` verde tras tocar `publico.yaml`.
3. Footer sin la frase que excluye al sector público.
4. `npm run build` verde; islas/consola limpias; nada de lo ya resuelto se rompe.
5. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador que la página pública tenga el bloque de continuidad institucional (el bloqueador #1 del CIO), que el footer no excluya al sector público, y que el build/islas sigan verdes. Los ítems de legal real, equipo real y casos públicos reales quedan pendientes del **usuario** (requieren datos verídicos).
