# Lista de Tareas — Spec 010: Generador Multi-Destino OpenWiki

- [x] Diseñar motor single-shot `scripts/docs-generator.mjs` con failover ponderado (Groq / DashScope / Gemini).
- [x] Implementar targets `wiki`, `agents`, `skills`, `blog` y `page`.
- [x] Incorporar filtro de exclusión de seguridad (`SECURITY_EXCLUDES`: `.env`, `secure_leads`, `wp-config.php`, etc.).
- [x] Optimizar la ejecución concurrente con el **Patrón Diamante (`Promise.all`)** para los 4 targets independientes.
- [x] Formalizar los artefactos SDD (`plan.md`, `tasks.md`) en `specs/010-generador-multidestino/`.
