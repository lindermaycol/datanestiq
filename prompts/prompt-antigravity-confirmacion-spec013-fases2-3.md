# Confirmación — Spec 013 Fase 2/3: LUZ VERDE → ejecutar

El plan (sección 3) incorporó bien mis guardarraíles. Verifiqué los puntos sensibles:
- **Intents al dato:** "EBITDA/TCO" → `sector.problems` de finanzas/seguros; "Sparring/ventaja competitiva" → `retail.yaml`/`logistica.yaml`, leídos estáticamente por `Chatbot.jsx`. **Correcto (0-LLM).**
- **Business Case:** reusa `SAVE_WIZARD_API` (`save_wizard.php`), un endpoint **existente y seguro** (escribe al almacén protegido `secure_leads/` 403+gitignored). **Válido.** No creas endpoint nuevo.
- **ERP:** sección condicional (`sector.relevantPersonas.includes('cfo')`) que consume la objeción ya enriquecida ("no reemplazamos su ERP"). **Correcto.**
- **Blogs:** flip `draft:false` en los 2 pendientes. OK.

**Procede** con estas 3 notas:

## ⚠️ Nota 1 — Ignora la sección 2 del plan (está OBSOLETA)
La sección 2 aún dice "Formulario… usando `remote_extract` o similar / `contact.php`". Eso es texto heredado del plan viejo y **NO refleja el código real** (ya verifiqué que el form usa `open-chatbot` → `chat.php`, sin `remote_extract` ni `contact.php`). **No regreses** el formulario ya construido y auditado. Prohibido tocar/usar `remote_extract`.

## Nota 2 — Business Case: cálculo client-side, PII solo al guardar
La calculadora computa los `[EST]` **en el cliente** desde los inputs del usuario + supuestos visibles. **No envíes PII** para calcular. Solo si el usuario elige "guardar/ser contactado" se llama a `save_wizard.php` (→ `secure_leads/`). Resultados **siempre** con badge `[EST]` + supuestos a la vista; cero cifras presentadas como cliente real.

## Nota 3 — No dejes fuera la FAQ estratégica del CEO (si estaba en alcance)
El plan de Fase 3 solo lista los intents del chatbot. Si la **FAQ estratégica del CEO** (cómo se empieza, riesgos, cómo se mide el éxito) seguía en alcance de la Fase 3, inclúyela o marca explícitamente que se difiere. No la dejes caer en silencio.

## Verificación (evidencia real al cerrar)
1. **Network:** flujo guiado con los nuevos intents (EBITDA/TCO en finanzas/seguros; ventaja competitiva en retail/logística) = **cero `chat.php`**.
2. **Business Case:** `/business-case` calcula en cliente, salidas `[EST]` con supuestos visibles; guardar → `save_wizard.php` (secure_leads), sin PII en claro en logs.
3. **ERP:** sección de coexistencia visible en finanzas/seguros, coherente con la objeción.
4. `build-taxonomy.mjs` verde (nuevos `problems` validados); `npm run build` verde; islas/consola limpias; badges `[EST]` intactos.
5. Blogs `analitica-contrataciones-estado` y `calidad-padrones-ia` publicados (draft:false) y compilando.
6. Sección final "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: Network 0-LLM con los nuevos intents, la calculadora Business Case (client-side + `[EST]`), PII segura, y build/islas verdes. Con esto se cierra la Spec 013.
