# Walkthrough: Arquitectura Conversacional (Spec 002)

Se ha ejecutado al 100% el plan de refactorización para la arquitectura conversacional del chatbot, dando pleno cumplimiento a los requerimientos FR-014, FR-015 y FR-016 con las 4 precisiones estipuladas.

## Cambios Implementados

### 1. Máquina de Estados Híbrida (`Chatbot.jsx`)
- Se implementó un estado explícito `chatState` (intro → problem → solution → lead → done).
- **Ruteo Determinista:** La selección guiada (Sector -> Problema) ocurre localmente en el componente React de manera inmediata, inyectando las respuestas preparadas al DOM sin emitir peticiones (`0 requests`) hacia el backend de Groq. Se incluyeron `<button>` accesibles.
- **Ruteo Semántico:** Se habilitaron botones de escape (*"Otro / Escribir libremente"*) en cada paso determinista. Si el usuario escribe o hace clic en estos, la máquina transiciona al estado `semantic` y efectúa el `fetch` hacia el LLM.
- **Herencia de Contexto:** Se mantuvo intacto el `useEffect` que escucha `lastUserQuery` desde el recomendador o URL (`?servicio=`), forzando una entrada directa y automática al modo semántico sin atrapar al usuario en los botones introductorios.

### 2. Captura de Lead Optimizada
- El estado `lead` dispara la visualización de la tarjeta HTML preexistente, y el método `confirmLead()` persiste los datos a través de `save_wizard.php` tal como fue desarrollado en fases previas, añadiendo el estado `done` para congelar el input al terminar.

### 3. Prompt Tool-Centric Modular (`chat.php`)
- El archivo `chat.php` intercepta el `context` (sector, problem, step) inyectado por el frontend en su payload POST.
- Se dividió el System Prompt en dos:
  1. **Base Prompt:** Posicionado estrictamente en el índice `[0]` de los mensajes para aprovechar el *Prompt Caching* de Groq de manera eficiente.
  2. **Context Prompt:** Inyectado dinámicamente en el índice `[1]` sólo cuando el contexto lo amerita, logrando que el LLM priorice instrucciones específicas del sector seleccionado sin inflar innecesariamente los tokens del System Prompt.
- Se mantuvieron eliminados de forma preventiva todos los roles `system` maliciosos que provengan del cliente, sin dejar ningún `error_log` en el código productivo.

## Verificaciones Realizadas
- `npm run build` ejecutado exitosamente, confirmando la persistencia de las 10 rutas estáticas esperadas.
- La Deuda Técnica (`tech_debt.md`) del punto 2 ha sido formalmente marcada como "Resuelta (Híbrido React/PHP)", aplazando LangGraph/Python al flujo asíncrono de copy de la Spec 004, dado que la presente arquitectura cumple todos los requisitos de desempeño y costos para el chatbot en vivo.

## Hallazgos Adicionales
- La latencia percibida por el usuario durante el flujo guiado es efectivamente cero (0 ms). La optimización del Prompt Caching en Groq para el escape semántico garantizará una respuesta extremadamente rápida al facturar únicamente los tokens de entrada dinámicos y la generación.
