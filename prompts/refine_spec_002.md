# Prompt para Perfeccionar Spec 002: Microexperiencias de IA

**Rol:** Eres un Arquitecto Principal de IA (Principal AI Architect) y un Ingeniero Frontend Full-Stack especializado en la integración de modelos LLM y NLP en navegadores.

**Contexto:**
En Datanestiq (agencia de IA y Datos B2B), hemos definido la "Spec 002". Esta especificación detalla cómo transformamos nuestra web estática en un ecosistema inteligente a través de "Microexperiencias IA". Sus componentes principales son:
1. **Buscador Semántico Local:** Usa `Transformers.js` (Web Worker) en el navegador para comparar embeddings (búsqueda semántica) sin latencia de red.
2. **Bridges (Proxy Backend):** Un archivo `chat.php` que conecta los inputs del frontend con modelos LLM de clase mundial (Llama 3.1 vía Groq) para redactar pitches de ventas dinámicos.
3. **Demo Interactiva de Copiloto & Diagnóstico:** Interfaces dinámicas para capturar leads mediante flujos conversacionales.

**Tarea:**
Audita y perfecciona la Spec 002. Necesito que:
1. **Mejores la Arquitectura Local:** ¿Es `Transformers.js` la opción más robusta y performante para 2026? ¿Existen estrategias de cuantización de modelos o caché local (IndexedDB) que debamos añadir a los requerimientos para hacer la búsqueda semántica absolutamente instantánea y ligera?
2. **Robustez del Proxy Backend:** Propón mejoras de seguridad para el proxy (Rate limiting, sanitización de inputs, manejo avanzado de contextos) para evitar vulnerabilidades de Prompt Injection.
3. **Casos de Uso B2B:** Refina las "User Stories" para que los ejemplos de interacción reflejen verdaderamente los dolores y necesidades de un C-Level corporativo interactuando con la web.

**Formato de Salida:**
Devuelve la Spec 002 perfeccionada en formato Markdown, con un enfoque técnico exhaustivo y listo para guiar el desarrollo de ingeniería en Datanestiq.
