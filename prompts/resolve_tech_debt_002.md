# Objetivo
Actuar como un Ingeniero Full-Stack para resolver la Deuda Técnica de la Spec 002 (Microexperiencias IA).

# Instrucciones
1. Leer el archivo `specs/002-microexperiencias-ia/tech_debt.md`.
2. Identificar y proponer la resolución de los puntos críticos:
   - Diseñar la arquitectura para reemplazar el uso temporal de `LocalStorage` por llamadas a la API de WordPress.
   - Refactorizar las variables globales (`window.DatanestiqContext`) hacia un State Manager seguro (Nano Stores u otro).
   - Diseñar la capa de abstracción del chatbot para conectarlo al flujo real de LangGraph.
3. Actualizar el documento `tech_debt.md` marcando los planes de resolución.
