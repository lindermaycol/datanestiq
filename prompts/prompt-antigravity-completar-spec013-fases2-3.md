# Spec 013 — Motor + Fase 1 AUDITADOS Y APROBADOS ✅ → completar Fase 2 (CFO) y Fase 3 (CEO)

Audité en el navegador (servido por PHP): **0-LLM intacto** (flujo guiado = cero `chat.php` en Network), **CTA dinámico funciona** (chip Finanzas→"Auditoría de ROI…"), **PII segura** (sin `contact.php`/`remote_extract`, reusa `chat.php`), **badges `[EST] Escenario Ilustrativo` presentes**, bloque On-Prem/VPC solo en público, build verde, consola limpia. Excelente. **Procede con lo remanente** de las Fases 2 y 3, respetando estos guardarraíles:

## Tareas remanentes (de tu `task.md`)
**Fase 2 (CFO):**
- [ ] Sección "Modernización sin reemplazar ERP" (coexistencia con core/ERP) en las landings de finanzas/seguros.
- [ ] Herramienta / documento "Business Case".
- [ ] Intents económicos del chatbot (EBITDA, TCO, payback).

**Fase 3 (CEO):**
- [ ] Intents estratégicos del chatbot (sparring, ventaja competitiva, crecimiento).

## Guardarraíles para esta tanda
1. **🔴 0-LLM — intents del chatbot van al DATO, no a `chat.php`.** Los intents del flujo guiado (EBITDA/TCO/payback, ventaja competitiva) se añaden como `problems`/opciones en la **taxonomía** (`sector.problems` de finanzas/seguros/retail-etc. o el árbol estático de `Chatbot.jsx`) — **cero `fetch`**. Solo el afinado de tono del **modo texto libre** puede tocar el `chat.php` system prompt. No enrutes el flujo guiado por `chat.php` (lo verificaré en Network otra vez).
2. **🔴 Business Case = estimador honesto, no resultados falsos.** Debe ser un **marco/calculadora** con supuestos visibles y salidas marcadas **`[EST]`** (mismo criterio que `<IllustrativeRoiCase>`). Prohibido presentar cifras como si fueran resultados de un cliente real. Sin PII en claro si captura datos (reusa el pipeline de `chat.php`).
3. **Coexistencia con ERP = capacidad real, desde el dato.** Consume `competitivePositioning`/objeciones ya enriquecidas (F3: "no reemplazamos su ERP; coexistimos…"). No inventes integraciones que no ofrecen.
4. **Consumidor:** si necesitas un intent/campo que no existe en la taxonomía, **autóralo en la fuente** (YAML/`personas.json`) y corre `build-taxonomy.mjs` (Zod + aristas) — no lo hardcodees en la isla.
5. **No romper:** islas intactas, `npm run build` verde, sin errores de consola, markers `[EST]` conservados.

## Pendiente menor (decide)
- **Blogs draft:** `analitica-contrataciones-estado` y `calidad-padrones-ia` quedaron `draft: true` (solo 2 de los 4 top-up se publicaron). Si los quieres vivos, cambia `draft: false` y recompila; si son intencionales, déjalo y anótalo.

## Verificación (evidencia real al cerrar Fase 2/3)
1. **Network:** flujo guiado con nuevos intents = **cero `chat.php`**.
2. **Business Case:** salidas `[EST]`, supuestos visibles, sin datos de cliente real; sin PII en claro.
3. **ERP:** sección de coexistencia visible en finanzas/seguros, coherente con la objeción "reemplazar ERP".
4. Chatbot: nuevos intents aparecen en el flujo guiado (consumidos del dato), roles correctos por sector.
5. `npm run build` verde; islas/consola limpias; badges `[EST]` intactos.
6. Sección final "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: Network 0-LLM con los nuevos intents, honestidad del Business Case (`[EST]`, sin clientes inventados), PII segura, y build/islas verdes.
