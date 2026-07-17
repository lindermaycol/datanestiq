# Prompt para Añadir User Stories y Requerimientos a Spec 005

**Rol:** MLOps Product Manager y Experto en Automatización CI/CD.

**Contexto y Grafo de Dependencias:**
La **Spec 005 (OpenWiki y Documentación Viva)** define el uso de la CLI de OpenWiki y GitHub Actions para mantener la documentación actualizada mediante Agentes IA que auditan el código. Carece de la estructura de *User Stories*, *Functional Requirements (FRs)* y *Success Criteria (SCs)*.
Considera el Grafo de Dependencias Maestro:
- **Gobierna sobre Specs 001 a 004:** OpenWiki es el "guardián". Tiene que leer el `AGENTS.md` y vigilar que el código Frontend cumpla con la Spec 001, que la API cumpla con la Spec 002, que el objeto de datos respete la Spec 003, y que los prompts no diverjan de la Spec 004.

**Tarea:**
Añade las secciones faltantes a la Spec 005:
1. **Sección del Grafo de Dependencias:** Documenta explícitamente y de forma oficial el "Grafo de Dependencias Maestro" dentro de la Spec 005. Explica en una sección nueva cómo la Spec 001 (UI), 002 (IA), 003 (Taxonomía) y 004 (Metodología) interactúan entre sí, y cómo OpenWiki (Spec 005) supervisa este grafo.
2. **User Scenarios & Testing:** Crea al menos 3 User Stories. Ejemplos: a) Un desarrollador añade un nuevo pilar de servicio en el código JS y espera que la IA actualice el Markdown. b) Un líder técnico revisa una Pull Request generada automáticamente. Incluye Acceptance Scenarios (Given/When/Then).
3. **Requirements (FRs):** Define los requisitos funcionales de infraestructura. Ej: Requisitos del umbral de confianza (threshold), reglas de bloqueo de ramas (Branch Protection), notificaciones (Slack/Teams).
4. **Success Criteria:** Define cómo mediremos que este agente documentador funciona (ej. % de PRs aceptadas sin intervención humana, reducción a cero de los "drift" documentales).

**Formato de Salida:**
Devuelve la Spec 005 completa en formato Markdown, fusionando el contenido técnico existente de OpenWiki con las nuevas secciones de producto (User Stories, Requerimientos Funcionales y Criterios de Éxito).
