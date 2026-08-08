# Spec 018 (DISEÑO) — Analítica de Micro-Interacciones (Behavioral / Engagement)

**Tarea de DISEÑO SDD, NO implementación.** Escribe los artefactos de la nueva **Spec 018**
(`specs/018-analitica-microinteracciones/`) con los **5 bloques** (WHY/WHAT/CONSTRAINTS/OUT-OF-SCOPE/TASKS) +
`data-model.md` + `plan.md` + `tech_debt.md`. **Entrégalos para que Claude los audite ANTES de implementar.**
Cero runtime todavía.

## Contexto y encaje (3 lentes sobre la misma actividad)
- **Spec 016 (live):** analítica de **negocio/conversión** — funnel, journey de LEADS, qué convierte. Ya captura
  `journey` **solo de los leads** (al confirmar) + `chat_metrics`.
- **Spec 017 (live):** **ops/sistema** — salud del failover, status de specs.
- **Spec 018 (esta):** **comportamiento/engagement** — qué HACE la gente en las micro-experiencias (Spec 002),
  **convierta o no**. Es el "¿qué se usa más y cómo juega el conjunto?" del usuario.

## WHY
Hoy no hay visibilidad de las micro-interacciones de los **usuarios anónimos** (la mayoría, que no dejan lead):
qué chips/sectores/roles eligen, qué buscan, qué escenarios del Copiloto clickean, qué servicios ven, dónde
**abandonan** el flujo guiado. Sin eso no se puede optimizar la Spec 002 con datos reales (solo intuición).

## WHAT (alcance — diseño)
### 1. Instrumentación de eventos de micro-interacción (nueva capa)
- Capturar eventos de **todos** los usuarios (no solo leads): clic de chip (sector/rol), query del buscador
  semántico, clic de escenario del Copiloto, paso del flujo guiado del chatbot (sector→rol→problema), vista de
  servicio/página, apertura de FAQ/objeción, uso del CTA encadenado.
- **Endpoint ligero** (ej. `public/api/track_event.php`) + **beacons fire-and-forget** desde las islas
  (`Chatbot`, `SemanticSearch`, `CopilotDemo`, `ContextChips`, `DiagnosticWizard`). Persistencia en el **mismo
  `crm.sqlite`** (tabla nueva `interaction_events`) en `secure_leads/` (privado).
- **Relación con la 016:** el `journey`-de-leads de la 016 es un **subconjunto** de estos eventos. **No dupliques
  la instrumentación** del mismo clic: decide en el diseño si 018 es la fuente única de eventos y 016 lo consume,
  o cómo se evita el doble registro.

### 2. Vistas de analítica de comportamiento (panel `/admin/`, pestaña "Comportamiento")
- **Popularidad:** top chips/sectores/roles, top queries del buscador, top escenarios del Copiloto, servicios más vistos.
- **Drop-off / flujo:** dónde abandonan el flujo guiado; conversión paso→paso; caminos muertos.
- **"Cómo juega el conjunto":** recorrido agregado entre islas (buscador → copiloto → chatbot → wizard).
- (Opcional, si es limpio) **"reglas de interacción":** vista read-only del árbol del flujo guiado / relaciones de
  la taxonomía (cómo está configurado) — evalúa si aporta o si se difiere.
- **Ubicación:** pestaña dentro de la **sección "Analítica" de `/admin/`** (junto a Conversión de 016 y Ops de 017),
  **tras `auth.php`**. Vanilla, sin libs pesadas.

## CONSTRAINTS (declararlas explícitas en el spec)
- **§5 (0-LLM) — matiz honesto:** el flujo guiado **sigue siendo 0-LLM** (los beacons **jamás** llaman a un LLM).
  Pero declara que el flujo guiado deja de ser "0 fetch": ahora emite **beacons de analítica fire-and-forget**
  (no-bloqueantes, sin `await`, fallo silencioso) que **no deben degradar la UX ni la latencia** percibida.
- **§6 (PII):** eventos **anónimos** (`session_id`, no email/teléfono). El **texto libre** (query del buscador,
  mensaje al chatbot) **puede** traer PII → **redáctalo** en el `track_event.php` con el mismo patrón de
  `chat.php` (`[EMAIL_REDACTED]`/`[PHONE_REDACTED]`). Persistencia en `secure_leads/` (privado, fuera del webroot).
- **§2 (Honestidad):** agregados de eventos **reales**; con muestra escasa → "datos insuficientes", nunca cifras
  fabricadas (mismo criterio que el `learn` de 016 y los percentiles de 017).
- **Privacidad/datos:** **sin trackers de terceros** (nada de Google Analytics/cookies comerciales); self-hosted,
  primera-parte, mínimo necesario. Respeta la disciplina de datos del proyecto.
- **Free-tier / Vanilla; Claude solo audita; todo tras `auth.php` (las vistas), el endpoint de tracking es de
  escritura anónima (sin exponer lecturas públicas).**

## OUT-OF-SCOPE (declararlo)
- Analítica de conversión/negocio (Spec 016) y ops/sistema (Spec 017) — no las dupliques.
- A/B testing framework, personalización en runtime, auto-acciones sobre el sitio.
- Tracking de PII, fingerprinting, cookies de terceros, analítica comercial.
- GraphRAG / MCP endpoint / buscador sobre blog (backlog aparte).

## Entregables (solo diseño)
- `specs/018-analitica-microinteracciones/{spec.md (5 bloques), data-model.md (tabla `interaction_events` +
  queries de popularidad/drop-off/flujo + relación con el journey de 016), plan.md (fases + gate de deploy +
  migración idempotente de `crm.sqlite` con backup, como en 016), tech_debt.md}`.
- Doc-sync: fila 018 en `ESTADO-SPECS.md` (🟠 "diseñada, pendiente de implementación") **y** `src/data/specsStatus.json`
  (recuerda el **build-gate antidrift** de la 017: ambos deben coincidir o el build falla). `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará el diseño: instrumentación fire-and-forget que **no** rompa 0-LLM ni la UX,
PII redactada (§6), honestidad con muestra escasa (§2), **no duplicar** la captura del journey de la 016, sin
trackers de terceros, todo tras auth, y consistencia `specsStatus.json` ↔ `ESTADO-SPECS.md` (build-gate). Tras mi
visto bueno se pasa a build.
