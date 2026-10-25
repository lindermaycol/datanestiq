# Deuda Técnica — Spec 027: Experience Bank (Banco de Experiencia Curada 0-LLM)

Este documento registra los compromisos de arquitectura, límites de rendimiento y posibles áreas de mejora identificadas durante el diseño de la Spec 027.

---

## 1. Deuda Técnica Registrada y Calibraciones

### TD-027.1: Dependencia de la Curación Humana para el Crecimiento del Banco
- **Descripción**: Dado que la auto-aprobación está prohibida por la Constitución §2, si el equipo de administración no revisa la bandeja de candidatos pendientes en `/admin/`, el corpus de experiencias aprobadas no crecerá.
- **Mitigación**: Se incluye un contador destacado en el badge del panel notificando la presencia de candidatos pendientes para agilizar la revisión humana.

### TD-027.2: Calibración del Umbral de Similitud en 0.75
- **Descripción**: Un umbral estricto en $\ge 0.75$ garantiza alta precisión para no servir respuestas descontextualizadas, pero puede dejar pasar paráfrasis complejas a la ruta de LLM.
- **Mitigación**: Se prioriza la exactitud sobre la cobertura para resguardar la reputación técnica. El umbral podrá calibrarse en futuras versiones basándose en telemetría 018.

### TD-027.3: Arranque en Frío del Banco Vacío
- **Descripción**: Al desplegar la Spec 027 por primera vez, la tabla `experience_bank` estará vacía y el endpoint `/api/experience_corpus.php` devolverá 0 experiencias.
- **Mitigación**: El chatbot detecta el corpus vacío y cae suavemente al flujo existente de FAQ + GraphRAG sin generar errores ni degradar la experiencia de usuario.

### TD-027.4: Dependencia del Estado en Cliente para la Ingesta Passthrough
- **Descripción**: La ingesta de candidatos depende de que `Chatbot.jsx` mantenga en memoria de sesión `last_complex_query` y `last_answer`. Si el usuario recarga la página o borra la memoria del navegador antes de completar el formulario de lead o cita, el passthrough se omite.
- **Mitigación**: Esta omisión ocurre de forma segura (candidate-only real) garantizando que no ingresen candidatos corruptos ni vacíos al banco.
