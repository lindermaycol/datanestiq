# Walkthrough: Microexperiencias de IA (Spec 002) Completado

He ejecutado exitosamente todas las implementaciones detalladas en el Plan Técnico para blindar y optimizar las microexperiencias de IA de Datanestiq.

## 1. Integración del Flujo de Diagnóstico (Wizard) a CSV Local
He creado un nuevo endpoint en el servidor (`api/save_wizard.php`) que recibe los datos del formulario multi-paso y los añade (append) al archivo `api/leads_wizard.csv`. 
- **Verificado:** El frontend `app.js` ya no apunta a Formspree, sino que hace `fetch` directo a nuestro endpoint local de forma segura, garantizando la recolección de Leads de manera soberana y sin sobrescribir datos pasados.

## 2. Máquina de Estados y Ruteo Híbrido Cero-Latencia (UI)
He refactorizado la lógica del Chatbot AI en `app.js` (`startChatWithSector`):
- **Deterministic Routing:** Ahora, cuando el usuario hace clic en un sector (ej. Salud, Finanzas), el sistema cambia su `chatState` y renderiza una respuesta *inmediata y local* (Hardcoded) en milisegundos.
- **Evitar Token Burn:** Ya no se llama a la API de Groq para estos saludos iniciales ni flujos estáticos, ahorrando costos y mejorando drásticamente el UX percibido.

## 3. Seguridad Perimetral y Anti-Loop en el Proxy (`chat.php`)
He inyectado un escudo protector masivo en `api/chat.php`:
- **Turn Limiter (Anti-Loop):** Si el array de mensajes supera las 12 iteraciones, el backend bloquea la respuesta y pide al usuario que deje sus datos, previniendo que un bot malicioso o un bug drene el saldo de la API.
- **Rate Limiting:** Un sistema basado en IP que escribe en el directorio temporal (`sys_get_temp_dir()`) para bloquear IPs que hagan más de 20 peticiones en 5 minutos.
- **Sanitización:** Implementado `strip_tags` y `htmlspecialchars` en cada mensaje entrante para anular inyecciones XSS.

## 4. LLM Routing Inteligente (`chat.php`)
- **Enrutamiento por Sesión:** Las consultas regulares del chatbot asisten mediante el velocísimo y económico `llama-3.1-8b-instant`.
- Las consultas originadas desde la consola del "Copilot Demo" (identificadas como `unknown_session`) se enrutan automáticamente al modelo pesado `llama-3.3-70b-versatile` para poder manejar el razonamiento complejo sin gastar de más en el chatbot normal.

## 5. Cuantización en Web Workers
He forzado la configuración `{ quantized: true, dtype: 'q8' }` en `worker.js`. 
- **Impacto:** El buscador semántico ahora descarga la red neuronal en su formato más comprimido, agilizando el arranque del Hilo Web y minimizando el uso de memoria RAM del cliente.
