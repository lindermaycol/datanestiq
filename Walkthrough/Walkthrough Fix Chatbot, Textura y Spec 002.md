# Walkthrough: Fix Chatbot, Textura y Spec 002

El plan de ejecución para la corrección del Chatbot global, la implementación del fondo general del prototipo, el hardening de seguridad del proxy de Groq, y la alineación de la documentación SDD con la realidad (Spec 002) ha sido completado exitosamente con todas las precisiones requeridas.

## Resumen de Cambios

### Bloque 1: Chatbot Global
- **Problema resuelto**: Los botones de los CTAs de páginas de soluciones que intentaban abrir el Chatbot no funcionaban porque la isla solo estaba en la página inicial (Home).
- **Acción**: Se eliminó `<Chatbot client:idle />` de `src/pages/index.astro` y se movió de forma segura justo antes del cierre del body en `src/layouts/BaseLayout.astro`.
- **Resultado**: Ahora los botones "Auditoría Gratuita" del Navbar abren el Chatbot sin problemas desde cualquier URL (`/nosotros`, `/casos-de-exito`, etc.).

### Bloque 2: Textura de Fondo Global
- **Problema resuelto**: La textura (grid) solo se renderizaba dentro de `Hero.astro` y `SolutionHero.astro`, creando un salto visual indeseado en el resto de la página en contraste con el prototipo inicial.
- **Acción**: Se añadió la textura (grid con size `24px` y un glow azul de color `#2563EB`) en todo el viewport mediante clases `fixed z-[-1]` inyectadas en `src/layouts/BaseLayout.astro`. Las texturas locales redundantes de los Heros han sido eliminadas.
- **Resultado**: La interfaz recobra su diseño original del prototipo sin importar en qué página se esté navegando.

### Bloque 3: Hardening del Proxy (`chat.php`)
- **Implementación de Seguridad**: Se ha fortalecido radicalmente el archivo `public/api/chat.php` de cara a producción siguiendo las directrices de la Constitution:
  - **Enmascaramiento de PII**: A través de expresiones regulares, todo email o número de teléfono provisto por el usuario ahora se convierte en `[EMAIL_REDACTED]` y `[PHONE_REDACTED]` ANTES de escribirse en el disco, para cumplir con el almacenamiento seguro de PII.
  - **CORS Restringido**: Se restringió el envío a dominios locales (`localhost` indistintamente del puerto) y en la URL permitida del frontend para evitar que el proxy sea utilizado por herramientas de terceros; respetando a su vez, la política same-origin para que la app no colapse en desarrollo.
  - **Aislamiento del Prompt**: Se ha migrado `SYSTEM_PROMPT` hacia PHP, garantizando que ya no viaje vía payload desde el cliente y previniendo así un Prompt Injection trivial.
  - **Control de Turnos y Payload**: Si la conversación supera los 20 mensajes, o si el input del cliente supera 2,000 caracteres, el sistema responde con el HTTP status adecuado sin invocar a la API de Groq, previniendo loops costosos.
  - **Rate Limiting Degradable (Fail-Open)**: Se implementó control por IP con threshold de `20 req/10 min`. Está configurado de tal manera que si el almacenamiento en archivos temporales del sistema no pudiese escribir, la política cede el acceso previniendo un bloqueo total.

### Bloque 4: Honestidad SDD
- **Sincronización Código/Spec**: La especificación de `specs/002-microexperiencias-ia/spec.md` ha sido corregida formalmente. Se clarificó la narrativa (indicando que Web Workers o Semantic Search en cliente aún no están implementados) estableciéndose como "Arquitectura Objetivo". Adicionalmente, el status en `tasks.md` es veraz al avance actual del desarrollo.
- **Registro en `tech_debt.md`**: Ha quedado debidamente asentada la brecha de implementación entre el documento de IA original y el repositorio Astro.

### Bloque 5: Plan de Construcción IA
- **Roadmap Fasado**: En base a la precisión del prompt, la nueva sección del roadmap de IA se ha anexado (en modo append) dentro del archivo `specs/002-microexperiencias-ia/plan.md`, manteniendo el tracking de complejidad previamente construido sobre Transformers.js. El Roadmap incluye fases secuenciales: A (Copilot Demo), B (Semantic Search) y C (Adaptive Wizard).

## Verificaciones Ejecutadas
1. **Comprobación de Build (Astro)**:
   - El entorno local ejecutó un `npm run build` confirmándose de forma limpia un empaquetado de las 10 rutas sin errores.
2. **Validación de PII Vía XAMPP/Curl**:
   - Se levantó un requerimiento (tipo POST simulado con PowerShell) hacia el backend PHP que corre en XAMPP.
   - En la conversación (incluida en el archivo de logs `chat_logs.jsonl`), el texto `test@example.com` junto al número móvil ha quedado documentado de la siguiente manera, comprobando el éxito de las expresiones regulares:
     `"content":"Mi correo es [EMAIL_REDACTED] y mi numero es [PHONE_REDACTED]"`
