# Prompt para Antigravity — Spec 014: Ciclo de Vida del Lead (mini-CRM, SQLite + panel interno) → ENTREGAR spec.md + plan.md, NO implementar aún

Sigue **SDD** y respeta la **Constitución** ([`.specify/memory/constitution.md`](../.specify/memory/constitution.md)) y tu guía [`AGENTS.md`](../AGENTS.md). En esta ronda **solo produces artefactos**: `specs/014-crm-lead-lifecycle/spec.md` + `plan.md` (+ `tasks.md` preliminar si ayuda). **No implementes código.**

## 0. Problema y objetivo
Hoy hay **buena captura de datos crudos** (conversaciones en `chat.php`→`chat_raw.jsonl`; leads en `save_wizard.php`→`leads_wizard.csv`) pero **cero gestión del pipeline**: no se sabe en qué quedó cada interacción, no se rastrea el recorrido del flujo guiado, ni hay estado/desenlace para accionar. Objetivo: un **mini-CRM interno** que rastree cada interacción y el ciclo de vida del lead, para que el usuario pueda **accionar los escenarios** (contactar los buenos, descartar/derivar el resto).

**Decisiones del usuario (ya tomadas — respétalas):** persistencia **SQLite local**; panel **interno con autenticación + restricción por IP**; el agendador (Spec 015, en paralelo) es aparte.

## 1. Alcance de la Spec 014
1. **Persistencia SQLite** en `secure_leads/` (fuera del webroot, 403 + gitignored — ver Constitución §6/§9). Diseña el **esquema**: `leads` (contacto, `session_id`, timestamps), `interactions` (eventos del recorrido), `lead_status` (estado + `next_action` + notas + historial).
2. **Captura del recorrido completo** (sin romper el 0-LLM, Constitución §5): que `Chatbot.jsx` (`confirmLead`) y el Business Case Estimator envíen, **al capturar el lead**, el contexto del recorrido — `sector/rol/problema`, la **secuencia de botones** del flujo guiado, y un **resumen de la conversación** de texto libre. El flujo guiado sigue **sin `fetch`** clic a clic; el envío ocurre solo al confirmar el lead.
3. **Ciclo de vida / estados:** `status` con transiciones (`nuevo → contactado → cita/diagnóstico solicitado → ganado / perdido / no-interesado`) + `next_action` + `notas` + historial de cambios (quién/cuándo).
4. **Panel interno (PHP):** listar/filtrar/buscar leads, ver el recorrido y la conversación completa de cada uno, y **actualizar estado/next_action/notas**. Debe ir tras **autenticación** (login con contraseña que define el usuario — tú **no** creas cuentas ni credenciales) **+ restricción por IP**, no indexado, fuera del sitio público. Diseña el mecanismo de auth (sesión PHP + hash de contraseña en `.env`/config protegida) sin hardcodear credenciales.
5. **Migración:** importar los datos existentes (`leads_wizard.csv`, y opcionalmente extraer de `chat_raw.jsonl`) a SQLite, sin perder nada. Idempotente.
6. **API interna mínima** para que el panel lea/escriba (endpoints PHP protegidos por la misma auth), reutilizando el pipeline seguro; nada de PII en claro en logs.

## 2. Estructura esperada de los artefactos
- **`spec.md`:** objetivo/negocio · modelo de datos (entidades y campos) · estados y transiciones · requisitos del panel · requisitos de captura del recorrido · seguridad (auth+IP, PII, ubicación SQLite) · criterios de aceptación · exclusiones (el **agendador** es Spec 015).
- **`plan.md`:** esquema SQLite concreto · cambios en `Chatbot.jsx`/estimador para adjuntar recorrido · endpoints del panel · diseño del panel (vistas: lista, detalle, edición de estado) · mecanismo de auth + IP · plan de migración · riesgos · tareas.

## 3. Guardarraíles (además de la Constitución)
- **Seguridad primero:** el panel expone TODA la PII → auth + IP obligatorios; SQLite y logs fuera del webroot, 403 + gitignored; **nunca** al repo. Añade las rutas nuevas al `.gitignore` si generan datos.
- **0-LLM intacto:** no metas `fetch` en el flujo de botones; el envío del recorrido es solo al capturar lead.
- **Sin romper** la captura actual (chat.php/save_wizard.php siguen funcionando durante y después de la migración).

## 4. Decisiones a marcar para revisión del usuario
- Esquema exacto (campos de `leads`/`interactions`/`status`).
- Mecanismo de auth (recomienda uno; el usuario pone la credencial).
- Qué se migra de `chat_raw.jsonl` (todo vs solo leads con contacto).

---
**Nota:** Claude (Opus 4.8) auditará el spec/plan (modelo de datos, seguridad del panel, que el 0-LLM y la captura actual no se rompan). **NO implementes hasta la luz verde.**
