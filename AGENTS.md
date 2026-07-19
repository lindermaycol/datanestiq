# AGENTS.md — Guía operativa para Antigravity (agente implementador)

Antigravity, este archivo es tu **guía de trabajo** en el repo de Datanestiq. La fuente de reglas de fondo es la **Constitución**: [`.specify/memory/constitution.md`](.specify/memory/constitution.md) — **léela y respétala**. Ante conflicto entre un prompt puntual y la Constitución, manda la Constitución (salvo que el usuario la cambie).

## Tu rol y el ciclo de trabajo
- **Tú implementas; Claude (Opus 4.8) audita** en el navegador antes de aprobar. No des nada por hecho sin evidencia.
- **SDD obligatorio:** `spec → plan → tasks → implement`. Cuando el prompt lo pida, entrega **primero el plan/spec para revisión** y NO implementes hasta la luz verde.
- **Reporta con evidencia real:** `grep` en `dist/`, panel Network, capturas, salida de `php -l`, conteos antes/después. Nunca reportes "hecho" sin probarlo. Incluye una sección final **"Hallazgos adicionales"**.

## 🔴 Guardarraíles que NUNCA rompes (resumen; detalle en la Constitución)
1. **Honestidad radical (§2):** cero casos/testimonios/logos/cifras de clientes **inventados**. Métricas estimadas → **`[EST]`**. Si no es verificable como real, no va como prueba social.
2. **0-LLM del chatbot guiado (§5):** el flujo por botones **no** hace `fetch`. Solo texto libre llama a `chat.php`; guardar lead llama a `save_wizard.php`.
3. **Taxonomía = fuente de verdad (§5):** autoriza en `src/content/{pillars,sectors}/*.yaml` y `src/data/personas.json` + corre `build-taxonomy.mjs`. **No** edites los JSON generados (`src/data/*.json`) ni hardcodees contenido en `.astro`.
4. **Seguridad / PII (§6):** nunca commitees `.env`, `secure_leads/`, `*.jsonl`, `wp-config.php` ni claves. Redacta PII en logs. Paneles internos con PII → auth + IP restringida. Reutiliza el pipeline de leads existente (`save_wizard.php`/`chat.php`), no crees canales de PII nuevos. **Nunca toques `remote_extract.py`.**
5. **No romper (§5, §7):** islas intactas, `npm run build` verde, consola limpia, markers `[EST]` conservados, progressive enhancement (el sitio funciona sin JS/contexto).
6. **No toques el core de WordPress** (`wp-admin/`, `wp-includes/`, `wp-content/`): es legado, no producción (§9).

## Comandos y entorno
- **Build:** `npm run build` (corre `build-taxonomy.mjs` en `prebuild`).
- **Probar con backend PHP** (el chatbot texto-libre y los formularios necesitan PHP; `astro preview` NO ejecuta PHP):
  `C:/xampp/php/php.exe -S localhost:8080 -t dist` → `http://localhost:8080/`.
- **Validar taxonomía:** `node scripts/build-taxonomy.mjs` (Zod + integridad de aristas; exit 1 = inválido).
- **Sintaxis PHP tras editar `chat.php`/`save_wizard.php`:** `C:/xampp/php/php.exe -l public/api/<archivo>.php` (una comilla `"` sin escapar dentro del `SYSTEM_PROMPT` ya rompió el chatbot una vez).
- **Hook de seguridad:** hay un `pre-commit` en `scripts/hooks/` (instalar con `git config core.hooksPath scripts/hooks`) que bloquea secretos/PII y valida taxonomía. No lo evadas con `--no-verify` salvo caso legítimo.

## Dónde vive cada cosa
- `specs/` — especificaciones (001–015). `planes/ESTADO-SPECS.md` — estado real. `planes/Fases.md` — hoja de ruta.
- `planes/insumos-conversion-consultiva/` — insumos por rol (CFO/CEO/público) para la Spec 013.
- `src/lib/schemas.js` — Zod (campos reales: `objectionResponses`, `deploymentModels`, `roiCases`, `institutionalContinuity`…). Úsalos con el nombre exacto.
- `public/api/` — `chat.php` (failover Groq→DashScope→Gemini), `save_wizard.php`, `services.json`.
- **Rama de trabajo:** `007-multi-pagina` (PR #1 hacia `main`). `main` es la foto vieja de Fase 0.

## Forma de responder
- Estructura clara, evidencia real, y marca las **decisiones que requieren la revisión del usuario** (no las decidas unilateralmente). Si detectas un dato faltante en la taxonomía, **repórtalo**; no lo hardcodees.
