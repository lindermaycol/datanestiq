# Confirmación — Spec 011 Fase 3 (Enriquecimiento): LUZ VERDE con 4 precisiones → ejecutar

Revisé el plan (`planes/Plan de Implementación Spec 011 Fase 3 (Enriquecimiento de la Taxonomía).md`). Está **sólido y fiel** al prompt: **Opción A aprobada** (`objectionResponses` en `personaSchema`, `deploymentModels`/`engagementModels` en `sectorSchema`, todo `.optional()` para retrocompatibilidad). Buen enfoque enriquecer también los 4 pilares restantes para consistencia. **Procede a implementar** con estas 4 precisiones:

## 🔴 Precisión 1 — `objectionResponses` debe emparejar 1:1 con `objections[]` (evita desincronización)
El **flujo guiado del chatbot (0-LLM) ya lee `objections[]`** (`Chatbot.jsx` usa `roleObj.objections[0]`). Por eso:
- **NO cambies ni elimines** el array `objections[]` existente (rompería el chatbot). Solo **añade** `objectionResponses`.
- Cada entrada de `objectionResponses.objection` debe ser **idéntica** (mismo string) a una entrada de `objections[]`, para que la UI pueda emparejarlas. Cobertura **1:1**: toda objeción existente tiene su respuesta.
- Si prefieres no duplicar el texto, documenta en el plan que `objectionResponses` es la fuente autoritativa para render y `objections[]` se conserva solo por compatibilidad — pero mantén ambos coherentes.

## 🔴 Precisión 2 — Honestidad: el label `[EST]` es necesario pero NO suficiente
Tu mitigación "asegurar que tengan `[EST]`" no basta. El riesgo real es **inventar cifras o capacidades plausibles pero falsas**. Regla:
- Los KPIs/`proofPoints` `[EST]` deben ser **rangos genéricos defendibles** (basados en benchmarks públicos del sector / lógica de negocio), presentados como **modelo/estimación**, nunca como resultado de un cliente real.
- `objectionResponses` = **solo capacidades reales de Datanestiq** ("desplegamos en VPC/on-prem", "coexistimos con tu stack actual", "roadmap por fases con transferencia de conocimiento"). **Prohibido** afirmar certificaciones, sellos, clientes o cifras de casos que no existen.
- ✅ *"[EST] La detección de fraude con ML puede reducir la pérdida esperada en rangos de ~X% (modelo ilustrativo)"* · ❌ *"Redujimos el fraude del Banco X un 32%"*.

## Precisión 3 — El Sector Público también necesita KPIs reorientados (no solo deployment/engagement)
Además de `deploymentModels`/`engagementModels`, **amplía los `kpis` `[EST]` de `publico.yaml`** hacia el matiz de **riesgo reputacional/control**: supervisión, alertas tempranas, reducción de retrabajo, trazabilidad documental, tiempos de atención de expedientes — no solo eficiencia. (Es dato que la Fase 1 de la Spec 013 va a consumir.)

## Precisión 4 — Validación completa e integridad del corpus
- `build-taxonomy.mjs` debe pasar **Zod Y la integridad referencial de aristas** (`relevantPersonas`/`relevantSectors`, exit 1 ante inválido) — no solo Zod.
- Los campos nuevos (`objectionResponses`, `deploymentModels`, `engagementModels`) **deben llegar al corpus cliente** que renderizan las páginas/islas (a diferencia de `contentAngles`, que **sigue extraído** a `contentAngles.json` y fuera del corpus cliente). Verifica que la extracción de `contentAngles` no arrastre por error los campos nuevos.
- Solo **añade** claves; no toques las existentes. `npm run build` **verde**; islas (chatbot 0-LLM, wizard, buscador) intactas.

## Verificación (evidencia real al reportar)
1. Salida de `node scripts/build-taxonomy.mjs` **verde** (Zod + aristas), con **conteos antes/después** de campos (p. ej. KPIs por sector, objectionResponses por persona).
2. Un **ejemplo real** pegado: 1 `objectionResponse` (CIO o CFO), el bloque `techStack`/`competitivePositioning` enriquecido de `sistemas-digitales`, y los `deploymentModels`/`engagementModels` + KPIs nuevos de `publico`.
3. Confirmación de que `objections[]` sigue intacto y emparejado 1:1 con `objectionResponses`.
4. `npm run build` verde; sin regresiones de islas/consola.
5. Sección final "Hallazgos adicionales".

## Recordatorio de pipeline
Esta Fase 3 es la **capa de datos** y va **primero**. Tras tu implementación y mi auditoría, siguen (2) fundación y (3) Spec 013 como **consumidores puros**. No adelantes render/composición aquí: solo datos + schema + validación.

---
**Nota:** Claude (Opus 4.8) auditará el resultado leyendo los YAML/JSON y corriendo `build-taxonomy.mjs` + `npm run build`, y verificará la honestidad (cero datos fabricados) y el emparejamiento 1:1 de objeciones.
