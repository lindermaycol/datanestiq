# Arquitectura Conversacional: Ruteo Híbrido y Tool-Centric (Spec 002)

Este plan detalla la migración de la arquitectura actual del `Chatbot.jsx` (puramente basada en LLM de texto libre) hacia un **Patrón Híbrido basado en Máquina de Estados** y un backend PHP con diseño **Tool-Centric**. Estas características resuelven los FR-014, FR-015 y FR-016.

## Proposed Changes

### Frontend (React)

#### [MODIFY] [src/components/islands/Chatbot.jsx](file:///c:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- **Máquina de Estados**: Implementar estado `chatState` con la estructura `{ step: 'intro', sector: null, problem: null }`. Los pasos válidos serán `intro`, `problem`, `solution`, `lead`.
- **Ruteo Determinista (0 latencia)**: 
  - Al iniciar (paso `intro`), se renderizan los mensajes de bienvenida **junto con botones** para seleccionar el sector de interés.
  - Al hacer clic en un botón de sector, se inyecta visualmente la elección como un mensaje de usuario (sin invocar a `chat.php`) y se renderizan los problemas específicos de ese sector (avanzando al paso `problem`).
  - Al seleccionar el problema, se inyecta la solución predefinida (basada en el `getSolutionMessage` del prototipo original) y se solicita directamente el lead.
- **Ruteo Semántico (LLM)**:
  - El input de texto libre persistirá. Si el usuario escribe y envía texto manualmente, se pasará al ruteo semántico, ejecutando la llamada `fetch` hacia `chat.php`.
  - El payload del POST hacia `chat.php` incluirá un nuevo campo `context: { step, sector, problem }` para informar al LLM sobre la posición actual en la máquina de estados.

---

### Backend (PHP Proxy)

#### [MODIFY] [public/api/chat.php](file:///c:/xampp/htdocs/datanestiq/public/api/chat.php)
- **System Prompt Tool-Centric**: 
  - Leer el objeto `context` enviado desde el frontend.
  - El `SYSTEM_PROMPT` actual dejará de ser monolítico. Mantendremos un `BASE_PROMPT` corto, estable e invariable (para maximizar el uso de Prompt Caching de Groq).
  - Si viene contexto (ej. `sector = 'Finanzas'`), se inyectará dinámicamente un segundo bloque `system` con instrucciones ultra-específicas (*tool-centric*). Por ejemplo: *"El usuario ha expresado interés en Finanzas. Tus respuestas deben priorizar casos de uso de riesgo, scoring y detección de fraude."*
- Seguir eliminando mensajes con `role: 'system'` que provengan maliciosamente desde el frontend, preservando la barrera de seguridad de los prompts inyectados en PHP.

## Verification Plan

### Automated Tests
- `npm run build` para asegurar que el bundle de Astro / React compile sin errores de sintaxis y la interfaz general cargue correctamente.

### Manual Verification
1. **Verificación de Cero Requests**: 
   - Abrir el chatbot y realizar el flujo guiado completo mediante los botones (Sector -> Problema).
   - Observar la pestaña *Network* de DevTools y confirmar rigurosamente que existen **cero (0)** requests a `chat.php`.
2. **Verificación de Ruteo Híbrido**:
   - Ingresar texto libre a mitad del flujo guiado (ej. tras seleccionar un sector).
   - Confirmar que la petición sale hacia `chat.php` adjuntando el contexto correcto, y el LLM responde adecuadamente.
3. **Verificación de Prompt Modular**:
   - Habilitar temporalmente un volcado (`error_log` o respuesta del proxy) del array de `messages` resultante para comprobar que el prompt de sistema ha sido desglosado en `BASE` y `CONTEXTO`, evitando el diseño monolítico.
