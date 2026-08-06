# Plan de Implementación: Calibración del Corpus Router 0-LLM (Spec 019)

El objetivo de este plan es calibrar estructuralmente el Router 0-LLM para que maneje correctamente preguntas cortas operativas y FAQ reales sin caer en la ruta de "duda/LLM".

## 1. Cambios en Base de Conocimiento FAQ

Se creará una nueva fuente de verdad exclusiva para preguntas operativas.

### [NEW] `src/data/faq.json`
- **Contenido**: Archivo JSON con una estructura de array de objetos (id, question, variants, answer, pendiente_dato_usuario).
- **Alcance**: Preguntas como "¿cuánto cuesta?", "¿cuál es el horario de atención?", "¿dónde están ubicados?", "¿cómo los contacto?".
- **Regla de Honestidad Radical (§2)**: Todo dato fáctico u operativo debe ser verídico y basarse en información existente en la página (p. ej., página de contacto o privacidad). Si no existe el dato en el proyecto, el campo `answer` se definirá como `null` y se marcará `"pendiente_dato_usuario": true`. Las preguntas con `answer: null` deben ser forzadas a no responderse en la capa 0-LLM y caer al LLM.

## 2. Cambios en Lógica de Ruteo y Clasificador

El clasificador necesita procesar este nuevo corpus junto con las objeciones de las personas.

### [MODIFY] `src/lib/intentClassifier.ts` y `src/components/islands/Chatbot.jsx`
- `Chatbot.jsx`: Enviar a `initIntentClassifier` tanto `objectionResponses` como el contenido de `faq.json`.
- `intentClassifier.ts`: Combinar ambas fuentes en el array de `faqEntries` y utilizarlas en la ruta `matchFAQ`.
- Filtrar en el flujo o en el match aquellas entradas cuya respuesta sea `null` (`pendiente_dato_usuario`) para evitar procesarlas con el LLM desde un estado vacío.

## 3. Ampliación de Ejemplos en `intents.json`

### [MODIFY] `src/data/intents.json`
- **Intención `faq`**: Añadir variantes cortas y operativas (ej: "¿cuánto cuesta?", "precio", "tarifas", "¿horario de atención?", "¿tienen oficina?").
- **Intención `cita`**: Añadir variantes cortas (ej: "agéndame", "llámenme", "quiero hablar").
- **Intención `guiado`**: Añadir variantes (ej: "¿qué hacen?", "¿por dónde empiezo?").
- **Intención `complejo`**: Incorporar 4-6 variantes cortas si aplica.

## 4. Script de Evaluación y Recalibración de Umbrales

### [NEW] `scripts/eval_router.mjs`
- Script de evaluación offline que instancia la función `pipeline` (o simula la llamada a `worker.js`) y prueba un dataset de ~40-60 consultas de prueba etiquetadas.
- Evalúa el intent clasificado y la similaridad máxima devuelta por el match de FAQ frente a la respuesta esperada.
- Generará un reporte de *Precision* y *Recall* por intento.

### [MODIFY] Umbrales (en `src/data/intents.json`)
- Ajustar `confidence_threshold` y `faq_match_threshold` tras los resultados del eval para maximizar el recall de las FAQ sin inventar datos operativos (la duda siempre va al LLM).

## 5. Documentación y Deuda Técnica

### [MODIFY] `specs/019-router-0llm-ampliado/tech_debt.md`
- Cerrar/Actualizar TD-019-06 tras la calibración. Documentar métricas y valores de los umbrales escogidos.

---

> [!WARNING]
> **User Review Required**
> Al ejecutarse este plan, el script de evaluación procesará de manera local un modelo de HuggingFace (`Xenova/all-MiniLM-L6-v2`). Asegúrese de que no haya dependencias cruzadas faltantes en su entorno. 
> Además, se le reportará la lista de variables operacionales marcadas como `pendiente_dato_usuario` para que usted decida sobre el texto definitivo, ya que está prohibido inventarlo.

> [!TIP]
> Proceda con "Approve" (Proceder) para iniciar la ejecución.
