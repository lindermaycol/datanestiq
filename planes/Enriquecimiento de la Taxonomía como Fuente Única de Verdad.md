# Enriquecimiento de la Taxonomía como Fuente Única de Verdad

Este plan aborda la refactorización necesaria para eliminar la duplicación de datos (hardcoding) de sectores y prompts en las microinteracciones de IA, centralizando toda la información en los archivos JSON base (`taxonomyCorpus.json` y `sectorsCorpus.json`).

## User Review Required

> [!IMPORTANT]
> El enfoque principal es **cero rupturas** en la experiencia del usuario (E2E). Revisa las adiciones propuestas a los esquemas de datos JSON y confirma si estás de acuerdo con la estrategia de inyección dinámica para los componentes de React.

## Open Questions

1. Para el **CopilotDemo.jsx**, ¿prefieres que se muestre un subconjunto aleatorio de 3 prompts extraídos de la taxonomía cada vez que carga, o una selección fija predeterminada?
2. Para el **MultiStepWizard.jsx**, ¿deseas que implemente una lógica simple para mapear las respuestas a un pilar tecnológico específico al final del diagnóstico?

## Proposed Changes

---

### Modificaciones en Capa de Datos

#### [MODIFY] [taxonomyCorpus.json](file:///C:/xampp/htdocs/datanestiq/src/data/taxonomyCorpus.json)
Se enriquecerá cada uno de los 6 pilares actuales añadiendo:
- `keywords: string[]`: Términos clave para mejorar la precisión del buscador semántico local.
- `copilotPrompts: string[]`: 2 prompts ejecutivos B2B por pilar (para alimentar al CopilotDemo).

#### [MODIFY] [sectorsCorpus.json](file:///C:/xampp/htdocs/datanestiq/src/data/sectorsCorpus.json)
Se enriquecerán los 10 sectores actuales (manteniendo la compatibilidad actual) añadiendo:
- `keywords: string[]`: Para el buscador semántico.
- `seo`, `hero`, y `contrast`: Estructuras de metadatos (copywriting premium) para la posterior generación dinámica de landing pages de sector.
- `problems: [{code, label, solution}]`: 2 problemas corporativos por sector con su solución canónica. Se migrarán los 4 existentes en Chatbot (Gobierno, Salud, Finanzas, Retail) y se redactarán nuevos para los 6 restantes.

---

### Refactorización de Islas (Componentes React)

#### [MODIFY] [DiagnosticWizard.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/DiagnosticWizard.jsx)
- **Eliminar** el array `sectorsCorpus` hardcodeado (líneas 7-68).
- **Importar** directamente `../../data/sectorsCorpus.json`.
- **Validar** que el renderizado, el ID mapping y el `semanticHighlight` funcionen con los datos importados.

#### [MODIFY] [CopilotDemo.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/CopilotDemo.jsx)
- **Eliminar** el array `prompts` hardcodeado.
- **Importar** `taxonomyCorpus.json`.
- **Implementar** lógica para extraer y aplanar todos los `copilotPrompts` de los pilares, seleccionando dinámicamente los 3 o 4 a mostrar en la terminal del demo.

#### [MODIFY] [Chatbot.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- **Importar** `sectorsCorpus.json`.
- **Refactorizar `intro`**: Generar los botones de selección de sector dinámicamente desde los primeros N sectores del JSON, manteniendo la opción "Otro / Escribir libremente".
- **Refactorizar `problem`**: Reemplazar la lógica de botones hardcodeada para renderizar los `problems` asociados al sector seleccionado.
- **Refactorizar `getSolutionMessage`**: Eliminar el objeto harcodeado local y buscar la respuesta canónica directamente en la propiedad `solution` del problema dentro del sector seleccionado en el JSON.
- **Validación CRÍTICA**: Asegurar que este flujo determinista mantenga 0 peticiones al endpoint `chat.php`.

#### [MODIFY] [MultiStepWizard.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/MultiStepWizard.jsx)
- **Opcional/Recomendado**: Implementar una recomendación básica final basada en palabras clave para sugerir uno de los 6 pilares tecnológicos al usuario al completar el diagnóstico, con enlace a su respectiva solución.

---

### Documentación de Deuda Técnica

#### [MODIFY] [tech_debt.md](file:///C:/xampp/htdocs/datanestiq/specs/003-taxonomia-servicios/tech_debt.md) (y Spec 002 si aplica)
- **Actualizar** la documentación para marcar como "Completado" la erradicación del hardcoding en los componentes `.jsx` y certificar a los JSON como la fuente única de verdad.

## Verification Plan

### Manual Verification
1. **npm run dev** y verificar visualmente:
   - `DiagnosticWizard`: Renderiza los 10 sectores, el pitch de IA se genera, se resaltan los sectores vía búsqueda.
   - `CopilotDemo`: Los botones de prompt funcionan y consultan a la API correctamente.
   - `Chatbot`: Hacer el flujo guiado seleccionando un sector, luego un problema, y verificar que da la solución correcta.
2. **Network Tab**: Confirmar que en el Chatbot, al seleccionar botones, el contador de requests hacia `/api/chat.php` es 0.
3. **npm run build**: Confirmar que Astro compila el proyecto sin errores.
4. Búsqueda en el proyecto de los arrays antiguos (`sectorsCorpus` dentro de `.jsx`) para garantizar que la duplicación fue eliminada.
