# Prompt para Antigravity — Spec 015: Agendador de Citas (sobre el CRM de la Spec 014) → ENTREGAR spec.md + plan.md, NO implementar aún

Sigue **SDD** y respeta la **Constitución** ([`.specify/memory/constitution.md`](../.specify/memory/constitution.md)) y [`AGENTS.md`](../AGENTS.md). En esta ronda **solo produces** `specs/015-agendador-citas/spec.md` + `plan.md` (+ `tasks.md` preliminar si ayuda). **No implementes código.**

## 0. Objetivo
Permitir que un prospecto **reserve una cita** (diagnóstico/reunión) desde el sitio, de modo que *"cita agendada"* pase a ser un **estado real** en el CRM (Spec 014), no solo una promesa del chatbot. Se ejecuta **en paralelo** con la Spec 014, pero **depende de su modelo de datos** (la cita es un registro asociado al lead).

**Decisión del usuario (respétala):** por ahora la cita **solo queda registrada**; el usuario la **confirma manualmente**. **NO** hay envío de correos/recordatorios automáticos en esta spec.

## 1. Alcance de la Spec 015
1. **Reserva client-facing:** un componente donde el prospecto elige un **slot disponible** (día/hora). Aparece en el punto adecuado del embudo — p. ej. tras el chatbot/formulario **después de aportar valor** (Constitución §2/§7: valor antes de contacto), o desde la landing. Progressive enhancement (funciona sin romper nada).
2. **Registro de la cita:** guardar `{lead_id/session_id, fecha_hora, tipo (diagnóstico/reunión), estado_cita (solicitada/confirmada/cancelada), notas}` en la **misma SQLite** de la Spec 014, y **actualizar el `status` del lead** a "cita/diagnóstico solicitado".
3. **Disponibilidad:** configuración de slots disponibles desde el **panel interno** (Spec 014) — franjas, duración, días bloqueados. Diseña cómo se evita el **doble-booking** (un slot tomado deja de ofrecerse).
4. **Confirmación manual:** el usuario ve las citas solicitadas en el panel y las marca **confirmada/cancelada** a mano. Sin correo automático (por ahora).
5. **Zona horaria:** define y fija una zona horaria (Perú / America/Lima) para evitar ambigüedad.

## 2. Estructura esperada de los artefactos
- **`spec.md`:** objetivo · modelo de datos de citas (extiende el de la Spec 014, no dupliques persistencia) · flujo de reserva (dónde y cuándo aparece) · manejo de disponibilidad/doble-booking/zona horaria · confirmación manual · seguridad/PII · criterios de aceptación · **exclusiones** (correos/recordatorios automáticos = fuera de alcance; posible fase futura).
- **`plan.md`:** cambios de esquema SQLite (tabla `appointments` + relación con `leads`) · componente de reserva (isla React o form) · endpoints PHP (protegidos donde toque) · config de disponibilidad en el panel · lógica anti-doble-booking · riesgos · tareas.

## 3. Dependencia con la Spec 014 (crítico)
- **No crees una persistencia paralela.** Usa la **misma SQLite** y el mismo mecanismo de acceso de la Spec 014. Si el modelo de datos de la 014 aún no está fijado, **coordina**: propón la tabla `appointments` como extensión del esquema de la 014 y deja explícito el punto de integración (`appointments.lead_id → leads.id`).
- El estado de cita **alimenta** el ciclo de vida del lead (la 014 es la fuente de verdad del lead; la cita es un atributo/evento).

## 4. Guardarraíles (además de la Constitución)
- **PII/seguridad (§6):** los datos de citas son PII → misma SQLite en `secure_leads/` (403 + gitignored), fuera del webroot. La config de disponibilidad y la gestión van tras la **auth + IP** del panel (Spec 014).
- **0-LLM / no romper:** el agendador no altera el flujo guiado 0-LLM; `npm run build` verde; progressive enhancement.
- **Honestidad:** no prometas confirmaciones/tiempos que no se cumplen; el copy debe reflejar que es **solicitud de cita** (se confirma manualmente).

## 5. Decisiones a marcar para revisión del usuario
- Dónde aparece el agendador (chatbot tras valor / landing / ambos).
- Duración y franjas por defecto de los slots.
- Si el registro de cita exige datos de contacto (email) o puede ir anónimo hasta la confirmación.

---
**Nota:** Claude (Opus 4.8) auditará el spec/plan (que reuse la SQLite de la 014, sin doble-booking, zona horaria fija, PII segura). **NO implementes hasta la luz verde.** Recomendación de ejecución: fija primero el **modelo de datos de la 014**, luego construyen 014 y 015 sobre ese esquema.
