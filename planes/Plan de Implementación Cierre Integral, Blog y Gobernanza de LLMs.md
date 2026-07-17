# Plan de Implementación: Cierre Integral, Blog y Gobernanza de LLMs

Este plan aborda el cierre y saneamiento de la documentación SDD (Specs 001 a 008), la creación de un motor de Blog basado en Astro Content Collections, y la configuración dinámica con failover del proveedor de LLM económico.

## PARTE A: Mantenimiento Cross-Cutting
- **Consolidación de Spec 008:** Eliminar la carpeta redundante `008-headless-wp` y consolidar sus artefactos en `008-headless-wordpress`.
- **Deuda de Seguridad:** Documentar en el `tech_debt.md` de la Spec 008 la presencia de credenciales en texto plano dentro de `remote_extract.py`.
- **Deuda de Verificación:** Documentar en el `tech_debt.md` de la Spec 006 que los scripts de `package.json` (`lint:strict`, `security:scan`, etc.) son stubs temporales.

## PARTE B: Higiene de SDD (Specs 001-008)
Actualización exclusiva de documentación (estados, tech debt, tasks) validando contra el código real:
- **Spec 001:** Actualizar estado a "Producción". Marcar flujo B2B del chatbot como completado. Registrar backlog (UI components, View Transitions).
- **Spec 002:** Actualizar estado. Sincronizar `tasks.md` marcando como `[x]` las implementaciones reales (Buscador, Copilot, Wizard, Ruteo). Registrar backlog y la desviación a CSV.
- **Spec 003:** Corregir ruta de `taxonomyCorpus.json`. Registrar backlog de Content Collections.
- **Spec 004:** Registrar LangGraph como "POSPUESTO" y actualizar estado.
- **Spec 005:** Completar `tech_debt.md` detallando la inoperatividad actual del workflow OpenWiki.
- **Spec 006:** Actualizar estado. Registrar resolución de rutas y deuda de scripts.
- **Spec 007:** Actualizar estado a "Implementado (SSG)". Registrar backlog SEO.
- **Spec 008:** Completar `tech_debt.md` estableciendo el rol de WP como editor UI del Blog.

## PARTE C: Astro Blog (Content Collections)
- **Configuración:** Añadir la colección `blog` en `src/content.config.ts` (esquema Zod estricto con `title`, `description`, `pubDate`, `tags`, `draft`).
- **Contenido:** Redactar 2 posts editoriales en Markdown (perfil C-Level) en `src/content/blog/`.
- **Vistas:** 
  - `src/pages/blog/index.astro`: Grid listado filtrando drafts.
  - `src/pages/blog/[slug].astro`: Plantilla individual con JSON-LD `BlogPosting` y tipografía `prose`.
- **Navegación:** Integrar en `Navbar.astro` y `Footer.astro`.

## PARTE D: Proveedor LLM Configurable y Failover
- **Variables de Entorno:** Soportar `CHEAP_LLM_BASE_URL`, `CHEAP_LLM_API_KEY` y `CHEAP_LLM_MODEL` en `chat.php`.
- **Retrocompatibilidad:** Si no se definen las nuevas variables, fallar hacia la lógica existente de DeepSeek o Groq (`llama-3.1-8b-instant`).
- **Failover Activo:** Si el proveedor económico devuelve error HTTP (fuera de 2xx), registrar en `alerts.jsonl` y ejecutar un reintento automático contra Groq.
- **Documentación:** Actualizar `.env.example` con los endpoints y modelos de DeepSeek y Alibaba Qwen comentados.

## Verificación Planeada
- `npm run build` debe generar el blog correctamente sin incluir los drafts.
- Probar E2E que el `chat.php` mantenga el ruteo hacia Groq por defecto.
