# Contexto del Proyecto
Tengo un chatbot de IA para mi consultora "Datanestiq" (B2B de IA y Datos) desarrollado con un backend en PHP (`chat.php`) y un frontend en JavaScript (`app.js`). Quiero implementar un sistema completo y altamente eficiente (ahorro de tokens) para capturar leads, guardar el historial de chats y exportarlos a un archivo CSV (abrible en Excel).

# Arquitectura Deseada (El Flujo)
El procesamiento debe dividirse para no afectar el tiempo de respuesta del bot ni gastar tokens innecesarios. El chatbot solo conversa y guarda en crudo (`.jsonl`). Un script secundario leerá ese archivo después, extraerá los datos y llenará el Excel, asegurándose de no procesar conversaciones duplicadas.

# Requerimientos de Implementación

## Fase 1: Frontend (`app.js`)
1. **System Prompt Mejorado:** Modifica la variable inicial `chatHistory` (donde está el `role: "system"`) para obligar a la IA a:
   - Presentar siempre opciones en formato de lista cuando pregunte por el "Desafío/Necesidad" o proponga "Soluciones", indicando que el usuario puede escribir una diferente.
   - Pedir OBLIGATORIAMENTE un correo electrónico y un número de teléfono (celular/fijo) antes de concluir o agendar una reunión.
2. **Generación de Session ID:** Implementa la creación de un identificador único de sesión (ej. `uuid` o `Date.now()`) al cargar la página.
3. **Payload del Fetch:** Asegúrate de que el `fetch` hacia `chat.php` envíe, además del array `messages`, la variable `session_id`.

## Fase 2: Backend del Chat (`chat.php`)
1. **Recepción:** Recibe el `session_id` enviado por el frontend.
2. **Guardado en Crudo (Log):** Antes de devolver la respuesta del LLM (Groq/DashScope) al usuario, crea una función que tome el `session_id`, la fecha/hora actual, y el array completo de `$messages` (incluyendo la nueva respuesta del bot) y lo guarde en formato JSON en una nueva línea en el archivo `chat_logs.jsonl` usando `file_put_contents` en modo `FILE_APPEND`.

## Fase 3: Script de Extracción para Excel (Crear `extraer_leads.php` o `.py`)
Crea un script separado (que yo pueda ejecutar manualmente o por cron) que haga lo siguiente:
1. **Control de Duplicados:** Lea un archivo de control (ej. `procesados.txt`) que contiene los `session_id` que ya fueron exportados al Excel.
2. **Lectura del Log:** Lea `chat_logs.jsonl` y agrupe los datos por `session_id`. Debe ignorar los IDs que ya estén en `procesados.txt`. De los IDs nuevos, debe tomar solo la última línea (que contiene la conversación completa).
3. **Extracción con IA (Llamada optimizada):** Por cada conversación nueva completa, debe hacer una única llamada a la API del LLM (Groq u OpenAI) con un prompt de sistema que le indique extraer en formato JSON estricto los siguientes campos: `Fecha, Nombre de Empresa, Sector, Necesidad Principal, Solución Ofrecida, Siguiente Paso, Email, Teléfono`.
4. **Escritura en CSV:** Tome ese JSON de respuesta y haga un "append" en un archivo `leads_datanestiq.csv`.
5. **Actualización del Control:** Añada los `session_id` procesados exitosamente al archivo `procesados.txt` para no volver a leerlos en el futuro.

# Instrucción Final para ti (IA)
Entiende esta arquitectura. Si estás de acuerdo, por favor dame el código exacto y comentado de:
1. Las modificaciones para `app.js`.
2. Las modificaciones para `chat.php`.
3. El código completo del nuevo script `extraer_leads.php` (o en Python, el que consideres más óptimo).
