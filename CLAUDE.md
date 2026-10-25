# CLAUDE.md — Guía de Claude (auditor) para Datanestiq

**Mi rol:** auditor/arquitecto. **AntiGravity** implementa y despliega; **yo audito** (leo código, verifico en vivo) y escribo prompts en `prompts/` para AntiGravity. Ante conflicto, manda `.specify/memory/constitution.md`.

## Hechos del ecosistema (no re-descubrir)
- **Backend PHP se sirve desde `dist/`** (Astro copia `public/`→`dist/` en `npm run build`). Todo cambio de PHP requiere `npm run build` **antes** de `deploy_ionos.py --confirm`; sin recompilar se sube la versión vieja.
- **Producción:** `app.datanestiq.com` (Astro + islas React + PHP), webroot `/app/public`; secretos en `/app/` (fuera del webroot). WordPress en `datanestiq.com` está en pausa/legado.
- **Panel `/admin/`:** tras `auth.php` (IP whitelist + password; prod `ALLOWED_IPS=*`). CRM SQLite en `secure_leads/crm.sqlite`. Endpoints en `public/admin/api.php` (switch por `action`). Vistas en `index.php` (`view-*` deben ser **hermanas**; `switchTab`/`switchLeadsSubTab` deben manejar **todas** las ramas).
- **0-LLM:** router del chatbot y buscadores usan Xenova client-side (`public/worker.js`, cache multi-corpus). Free-tier LLM (Groq→DashScope→Gemini) en `chat.php`.
- **§2 Honestidad radical:** cero datos/métricas fabricados; `[EST]` con base real; guards "datos insuficientes"; DEMO marcado y excluido por defecto.

## Mi checklist de auditoría en vivo (usar SIEMPRE al auditar un build/deploy)
1. **Código:** el cambio/corrección está en disco y es correcto (esquema real, sin reutilizar `:param`, helpers definidos).
2. **Desplegado de verdad:** consultar el **endpoint REAL** en prod con cache-buster → 200 + un **marcador** que solo emite el código nuevo (p. ej. un campo del JSON). Si falta → no está vivo (build/opcache). **Nunca** dar verde con solo evidencia local o el reporte del agente.
3. **UI con datos:** **click-through** de cada pestaña Y sub-pestaña → carga **datos** (no solo el encabezado); consola sin `ReferenceError`. "Visible" ≠ "funciona".
4. **§2/seguridad:** guards de muestra mínima, PII redactada donde toca, `chat_raw`/privados **sin endpoint público** (curl → 404), estimaciones `[EST]` honestas.
5. **Veredicto honesto:** reportar lo que realmente pasó (200/500, datos/vacío), no lo que el reporte afirma.

> Skill relacionada: **`verificar-en-vivo`** encapsula este checklist. El patrón meta de esta relación: **"verde" del maker ≠ realidad** — cada ronda, la verificación en vivo cazó lo que el reporte no vio.

## Convenciones
- Prompts para AntiGravity → `prompts/prompt-antigravity-*.md`. Commits: rama de trabajo, no `main`; termina con `Co-Authored-By`.
- Estado de specs: `planes/ESTADO-SPECS.md` ↔ `src/data/specsStatus.json` (build-gate antidrift). Roadmap: `planes/Fases.md`.
- Deploy/QA operativo: ver `AGENTS.md §4` (reglas de campo).

<!-- project-standard:begin (v1.0.0 — bloque gestionado por apply_standard.py; no editar dentro) -->
## Estándar común del ecosistema

- **Proyecto:** `datanestiq` · **Memoria:** `C:\Users\Lisbeth\vault\datanestiq\` (decisions + logs) · **SDD:** spec-kit
- **Orientación barata primero:** `wiki/Root.md` → `decisions.md` (grep, no completo) → últimos logs; para código, CodeGraph (`codegraph_context` / `codegraph_impact`) y Graphify (`graphify query`) antes de leer archivos.
- **Roles:** AntiGravity planifica/implementa; Claude Code audita y opera Orca. Cada ronda deja `prompts/<NNN>-<slug>.md` (ida, plantilla `prompts/_PLANTILLA-prompt.md`) y `prompts/<NNN>-<slug>.retorno.md` (vuelta, plantilla `prompts/_PLANTILLA-retorno.md`).
- **Auditoría:** se verifica el estado real (`git show --stat`, consultas, tests), nunca el resumen.
- **ADR:** toda decisión de arquitectura va a `decisions.md` con la plantilla `vault/templates/adr.md`; la sesión se cierra con `/guardar datanestiq`.
- **Regla de Oro:** bloqueo o algo no verificable → se documenta y se detiene; nunca se inventan resultados.
- **Datos:** backup antes de trabajar (`backup_all.py`), base aislada por worktree (`.orca/worktree.py`), esquema por migraciones; nada de `.env` ni bases en git (hooks `_standard`).
<!-- project-standard:end -->
