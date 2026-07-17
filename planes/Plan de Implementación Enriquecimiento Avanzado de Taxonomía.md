# Plan de Implementación: Enriquecimiento Avanzado de Taxonomía

Este plan detalla los pasos para llevar la taxonomía actual a un estándar de industria B2B de élite, incluyendo capacidades de búsqueda semántica multilingüe, códigos internacionales (CIIU), frameworks de madurez y targeting por roles directivos.

## User Review Required

> [!IMPORTANT]
> El cambio principal a nivel de código es el modelo de **Transformers.js**. Propongo usar `Xenova/paraphrase-multilingual-MiniLM-L12-v2` por defecto, ya que no requiere inyectar prefijos de texto (`query: / passage:`) y maneja el español de forma nativa manteniendo baja latencia. 

## Open Questions

1. Para los códigos **CIIU**, ¿Deseas que agregue también la clasificación **GICS** (Global Industry Classification Standard) para los sectores, o nos mantenemos solo con CIIU (estándar INEI)?
2. Respecto a la expansión de **keywords**, generaré 6-10 términos adicionales manualmente enfocados en el mercado B2B peruano/latino. ¿Tienes alguna preferencia de vocabulario (ej. más orientado a tecnología vs. más orientado a negocio)?

## Proposed Changes

---

### Motor de Búsqueda Semántica

#### [MODIFY] [worker.js](file:///C:/xampp/htdocs/datanestiq/public/worker.js)
- **Cambio de Modelo**: Reemplazar `Xenova/all-MiniLM-L6-v2` por `Xenova/paraphrase-multilingual-MiniLM-L12-v2`.
- **Justificación**: Optimiza el *retrieval* de consultas en español para el Semantic Search, reduciendo el ruido al buscar términos de negocio ("optimización de rutas"). No se requieren cambios en la lógica de pooling.

---

### Enriquecimiento de Esquemas JSON

#### [MODIFY] [sectorsCorpus.json](file:///C:/xampp/htdocs/datanestiq/src/data/sectorsCorpus.json)
- **CIIU**: Se agregará el campo `ciiu: string` a cada uno de los 10 sectores, alineado a la Clasificación Industrial Internacional Uniforme Rev.4 (ej. `84` para Sector Público, `86` para Salud).
- **Keywords Expandidos**: Se ampliará el array de `keywords` a 6-10 términos altamente relevantes por sector.

#### [MODIFY] [taxonomyCorpus.json](file:///C:/xampp/htdocs/datanestiq/src/data/taxonomyCorpus.json)
- **Marcos de Industria (Standards)**: Se añadirá el array `standards: string[]` a cada pilar tecnológico (ej. `CRISP-DM` para AI, `DAMA-DMBOK` para Data Engineering).
- **Roles y Skills (ESCO)**: Se añadirán `targetRoles: string[]` (ej. "CTO", "CDO", "CEO") y `skills: string[]` (ej. "Gobernanza de Datos") utilizando nomenclatura alineada a ESCO.
- **Keywords Expandidos**: Se ampliará el array de `keywords`.

---

### Documentación de Deuda Técnica

#### [MODIFY] [tech_debt.md (003)](file:///C:/xampp/htdocs/datanestiq/specs/003-taxonomia-servicios/tech_debt.md)
- Actualizar el registro para incluir el enriquecimiento multilingüe, CIIU, y marcos de referencia.
- **Añadir nota arquitectónica**: El esquema Zod para la futura migración a Content Collections deberá incluir obligatoriamente los campos `ciiu`, `standards`, `targetRoles`, `skills` y los `keywords` expandidos.

#### [MODIFY] [tech_debt.md (002)](file:///C:/xampp/htdocs/datanestiq/specs/002-microexperiencias-ia/tech_debt.md)
- Registrar que el cambio a un modelo multilingüe (`paraphrase-multilingual-MiniLM-L12-v2`) resuelve/mitiga el problema de precisión en español del buscador semántico local.

## Verification Plan

### Verificación Automatizada (Build)
- Ejecutar `npm run build` para asegurar que las modificaciones en los JSON no rompen el empaquetado del sitio.

### Verificación Manual (E2E)
1. **Network Tab**: Al cargar el buscador, confirmar que descarga los pesos (`.onnx`) del nuevo modelo `Xenova/paraphrase-multilingual-MiniLM-L12-v2`.
2. **Precisión de Búsqueda**: Ingresar términos como *"optimización de rutas y cadena de suministro"* y reportar que resalta Logística con un umbral diferencial mayor al modelo antiguo.
3. **Regresión Chatbot**: Validar el flujo determinista y asegurar que las llamadas al LLM (`chat.php`) sigan siendo **0**.
4. **Regresión Wizard & Copilot**: Asegurar que renderizan sin errores de parsing de los JSON enriquecidos.
