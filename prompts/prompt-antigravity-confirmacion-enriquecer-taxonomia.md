# Prompt para Antigravity: Confirmación del Plan de Enriquecimiento de Taxonomía → ejecutar

Revisé `planes/Enriquecimiento de la Taxonomía como Fuente Única de Verdad.md`. Es fiel al prompt. **Luz verde para ejecutar**, con las respuestas a tus 2 preguntas y 3 precisiones.

## Respuestas a tus Open Questions

**1. CopilotDemo — ¿aleatorio o fijo?** → **Selección determinista curada, no aleatoria.** Muestra 3–4 prompts tomados de **pilares distintos** (ej. el primer `copilotPrompt` de 3–4 pilares representativos) para que el demo siempre exhiba variedad de la oferta y sea estable entre cargas. Evita el aleatorio puro (puede mostrar 3 del mismo pilar o sentirse inestable). Un orden fijo o una rotación estable está bien; sorpresa aleatoria por carga, no.

**2. MultiStepWizard — ¿mapear a un pilar?** → **Sí, impleméntalo (simple, basado en keywords).** Al terminar el diagnóstico, sugiere UN pilar tecnológico según las respuestas/keywords y enlaza a su `/soluciones/[slug]`. Cierra el embudo hacia una solución concreta y hace el wizard taxonomy-driven. Mantenlo simple (match por keywords contra el nuevo campo `keywords[]` de los pilares).

## Precisiones

### P1 — Identidad de sector consistente en el Chatbot (no reintroducir el mismatch)
Hoy el Chatbot usa strings sueltos ('Gobierno', 'Salud'…) que NO coinciden con los `id` de `sectorsCorpus.json` (`sector-publico`, `sector-salud`…). Al refactorizar:
- Usa el **`id`** del JSON para la lógica y el **`title`** para mostrar. No dejes strings de sector sueltos.
- `getSolutionMessage` se reemplaza por: buscar el sector por `id` → dentro de sus `problems[]`, encontrar el `problem` por `code` → devolver su `solution`. Nada hardcodeado.
- Para el `intro`, mostrar los **primeros 4–5 sectores** del JSON + "Otro / Escribir libremente" está bien (los primeros 4 —Público, Salud, Finanzas, Retail— son justo los que ya tienen el copy más rico).

### P2 — DiagnosticWizard: mantener mutable el estado y la creación on-the-fly
Al importar `sectorsCorpus.json` (que es de solo lectura), **inicializa el estado con una copia mutable**: `useState([...sectorsCorpus])`. No mutes el objeto importado directamente, o romperás la creación de sectores "on-the-fly" (`handleCreateCustomSector`). Verifica que agregar un sector personalizado siga funcionando tras el refactor.

### P3 — Verificación E2E con evidencia real (no asumida)
Ejecuta y reporta con evidencia concreta:
- **Chatbot (Network):** flujo guiado sector→problema→solución = **0** requests a `/api/chat.php` (adjunta el conteo). Texto libre / "Otro" = 1 request. Y que la solución mostrada corresponde al `problem.solution` del JSON.
- **DiagnosticWizard:** los 10 sectores renderizan desde la fuente única; pitch generativo OK; resaltado semántico (`semanticHighlight`) sigue funcionando; crear un sector custom funciona.
- **CopilotDemo:** los prompts provienen de la taxonomía (de pilares distintos) y responden vía `/api/chat.php`.
- **SemanticSearch:** sigue reordenando (con `keywords` debería discriminar mejor que antes).
- **Grep de no-duplicación:** confirma que NO queda ningún array de sectores ni de prompts hardcodeado en ningún `.jsx` (solo imports de `sectorsCorpus.json` / `taxonomyCorpus.json`).
- `npm run build` sin errores.

---

Procede: Parte 1 (enriquecer JSON) → Parte 2 (refactor islas) → documentación. Recuerda que el esquema enriquecido que produzcas será migrado a Content Collections por el prompt 003 después, así que el copy de `seo/hero/contrast/problems` debe quedar completo y de calidad premium desde ya.

No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que ninguna isla quede con datos hardcodeados, que el chatbot guiado mantenga 0 llamadas, y que el buscador semántico y el wizard sigan funcionando — con re-ejecución E2E en el navegador.
