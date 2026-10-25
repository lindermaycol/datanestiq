# Prompt para Añadir User Stories y Requerimientos a Spec 003

**Rol:** Product Manager Técnico y Arquitecto de Sistemas B2B.

**Contexto y Grafo de Dependencias:**
Estamos documentando las especificaciones de nuestra consultora (Datanestiq). Actualmente, la **Spec 003 (Taxonomía de Servicios)** define los 6 pilares tecnológicos y 10 sectores, pero carece de *User Stories*, *Functional Requirements (FRs)* y *Success Criteria (SCs)*. 
Para redactarlos, debes considerar estrictamente cómo la Spec 003 se conecta con el resto del ecosistema (El Grafo de Dependencias):
- **Con Spec 001 (UI/UX):** La taxonomía debe ser renderizada en el Frontend respetando el diseño de élite y accesibilidad.
- **Con Spec 002 (IA):** Los 6 pilares y 10 sectores son el `Corpus` que consume el Buscador Semántico local (`Transformers.js`).
- **Con Spec 004 (Copywriting):** Las descripciones de esta taxonomía son el insumo (input) para los 10 agentes de IA que redactan las páginas de ventas.
- **Con Spec 005 (OpenWiki):** Cualquier cambio en esta lista de servicios activará alertas de CI/CD para actualizar la documentación viva.

**Tarea:**
Reescribe y expande la Spec 003 para incluir:
1. **User Scenarios & Testing:** Al menos 3 User Stories (formato "Como [perfil], quiero [acción], para [valor]") enfocadas en cómo los usuarios corporativos, motores de búsqueda y la propia IA interactúan con esta taxonomía en la web. Incluye Acceptance Scenarios (Given/When/Then).
2. **Requirements:** Lista detallada de Functional Requirements (FRs) que definan cómo debe almacenarse y consumirse esta taxonomía a nivel de código (ej. estructura de objetos JSON para el buscador semántico, persistencia).
3. **Success Criteria:** Métricas medibles para validar la correcta implementación (ej. latencia de búsqueda sobre la matriz 6x10).

**Formato de Salida:**
Devuelve la Spec 003 con estas nuevas secciones agregadas, en formato Markdown riguroso, manteniendo la taxonomía actual intacta pero rodeada de esta nueva arquitectura de producto.
