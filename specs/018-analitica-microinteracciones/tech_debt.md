# Deuda Técnica — Spec 018: Analítica de Micro-Interacciones (Behavioral)

**Estado:** Fase de Diseño SDD (2026-08-04)

---

## Riesgos e Implicaciones de Diseño Identificados

### 1. Crecimiento Exponencial de la Tabla `interaction_events`
- **Riesgo:** A diferencia de la tabla `leads` (que solo guarda conversiones reales), la tabla `interaction_events` registra eventos de **todos** los usuarios del sitio. En escenarios de tráfico medio/alto, esta tabla puede experimentar un crecimiento acelerado, afectando el tamaño de `crm.sqlite` y ralentizando las consultas del panel de administración.
- **Mitigación de Diseño:**
  - Evitar el registro excesivo de eventos redundantes (por ejemplo, registrar solo el cambio de estado del cursor o scroll está fuera de alcance).
  - Implementar una política de retención y depuración automática mediante consulta SQL:
    ```sql
    DELETE FROM interaction_events WHERE created_at < datetime('now', '-180 days');
    ```
    Este mantenimiento se gatilla periódicamente desde el servidor, eliminando registros obsoletos de forma silenciosa e idempotente.

### 2. Sobrecarga de Peticiones HTTP en Red (Latencia)
- **Riesgo:** El envío constante de beacons de analítica podría degradar el ancho de banda disponible en el navegador del cliente o saturar las conexiones simultáneas permitidas por el servidor web (IONOS).
- **Mitigación de Diseño:**
  - Los beacons se enviarán mediante `navigator.sendBeacon()` en navegadores modernos. Esta API de bajo nivel delega la transmisión al hilo de fondo del navegador, realizándose incluso después de que el usuario cambie o cierre la pestaña, reduciendo la latencia de red a cero para la UX.
  - Como fallback, se utiliza un `fetch()` asíncrono envuelto en un bloque `try/catch` silencioso y sin la instrucción `await`.

### 3. Falsos Negativos en la Redacción de PII (§6)
- **Riesgo:** Aunque los patrones de regex para correo electrónico y número telefónico cubren el 99% de los casos comunes, variaciones exóticas de escritura (ej. espaciado manual o sustitución de caracteres como `usuario [at] dominio.com`) podrían burlar el filtro de sanitización y persistirse en texto plano.
- **Mitigación de Diseño:**
  - Documentar en los términos de privacidad del sitio la restricción de ingresar datos personales en campos de búsqueda.
  - Realizar una revisión y afinamiento periódico de las expresiones regulares de redacción.
  - Aplicar el saneamiento de forma defensiva antes de la inserción. Si la redacción falla por cualquier motivo inesperado, la cadena se guarda vacía o el evento es ignorado de forma fail-closed.
