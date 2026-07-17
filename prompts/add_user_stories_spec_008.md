# Prompt para Añadir User Stories y Requerimientos a Spec 008

**Rol:** Arquitecto de Backend y Especialista en Seguridad de Infraestructura B2B.

**Contexto y Grafo de Dependencias:**
La **Spec 008 (Headless WordPress)** define la integración del CMS backend para la captura definitiva de Leads y aislamiento de seguridad, sustituyendo el uso de almacenamiento temporal. Carece de la estructura de *User Stories*, *Functional Requirements (FRs)* y *Success Criteria (SCs)*.
Considera el Grafo de Dependencias:
- **Resuelve deuda de Spec 002:** Elimina el uso de `LocalStorage` y lo reemplaza por llamadas a la API (REST o GraphQL).
- **Extiende la Spec 006:** El Frontend de Astro ahora empuja datos de escritura (POST) hacia el backend aislado.

**Tarea:**
Añade las secciones faltantes a la Spec 008:
1. **User Scenarios & Testing:** Crea al menos 3 User Stories. Ejemplos: a) El C-Level de Datanestiq entra al panel `/wp-admin` y visualiza los leads calificados en un Custom Post Type privado. b) Un atacante intenta acceder al wp-login.php público y es redirigido o recibe un 404. Incluye Acceptance Scenarios (Given/When/Then).
2. **Requirements (FRs):** Define los requisitos funcionales de seguridad e infraestructura backend. Ej: Creación de Custom Post Types (CPT) con Advanced Custom Fields (ACF) para mapear el estado del Wizard, validación CORS para Webhooks (POST) provenientes del Frontend Astro, y configuración `.htaccess`.
3. **Success Criteria:** Define cómo mediremos el éxito de la integración (ej. Latencia de API < 200ms, cero exposición pública del motor PHP, Leads guardados con 100% de persistencia relacional).

**Formato de Salida:**
Devuelve la Spec 008 completa en formato Markdown, fusionando el diseño de backend Headless existente con las nuevas secciones de producto (User Stories, Requerimientos Funcionales y Criterios de Éxito).
