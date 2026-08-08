# Confirmación — Specs 014 (CRM) + 015 (Agendador): LUZ VERDE al diseño, con endurecimiento de seguridad + 2 respuestas → luego implementar

Revisé los 4 artefactos (`specs/014-*/spec.md`+`plan.md`, `specs/015-*/spec.md`+`plan.md`). El diseño es sólido: **SQLite compartida** en `secure_leads/crm.sqlite`, modelo de datos correcto (`leads`/`interactions`/`status_history` + `appointments` 1:N), **0-LLM preservado** (el recorrido se envía solo en `confirmLead`, sin `fetch` por clic), migración idempotente, America/Lima, anti-doble-booking y confirmación manual sin correos. Buena higiene SDD (spec+plan en `specs/`). **Apruebo el diseño.** Antes de implementar, ajusta esto:

## 🔴 Endurecimiento de seguridad del panel (crítico — es el mayor riesgo)
El panel expone **toda la PII** y será una URL en internet (gated). El "session PHP + hash + IP whitelist" es el enfoque correcto, pero especifícalo **robusto**:
1. **IP whitelist como PRIMERA barrera en CADA request** del panel (incluida la página de login y `admin/api.php`): si la IP no está en `ALLOWED_IPS`, responde **403 antes de renderizar/procesar nada**. No solo en el login.
2. **Contraseña:** `password_hash`/`password_verify` (bcrypt), comparación en tiempo constante. `ADMIN_PASSWORD_HASH` en `.env` (nunca la contraseña en claro; el usuario genera el hash).
3. **Sesión segura:** cookies `HttpOnly` + `Secure` + `SameSite=Strict`; `session_regenerate_id(true)` al iniciar sesión; **timeout** de inactividad.
4. **Anti-fuerza-bruta:** rate-limiting / lockout en el login (reusa el patrón de rate-limit de `chat.php` si sirve).
5. **CSRF token** en todos los POST que cambian estado (actualizar status/notes, config de disponibilidad, confirmar cita).
6. **Re-verificación en cada endpoint:** `admin/api.php` valida sesión **y** IP en cada llamada, no solo la vista.

## 🔴 Ubicación y protección de archivos
- **`admin/` debe vivir en `public/admin/`** (para que el build lo incluya en `dist/admin/` y se sirva, como `public/api/`). Confírmalo.
- **`secure_leads/crm.sqlite` fuera del webroot** ✓ — pero ojo: el `.htaccess deny` **solo funciona en Apache (IONOS)**, **no** en el servidor PHP de desarrollo (`php -S` lo ignora). La protección real en local es que `secure_leads/` está **fuera del directorio servido** (`-t dist`). Documenta ambas: `.htaccess` para IONOS + estar fuera de `dist/` en local. El panel PHP lee la SQLite por ruta relativa (`../../secure_leads/crm.sqlite`).
- **`.gitignore`:** `secure_leads/` ya está ignorado (y el `pre-commit` lo bloquea); asegúrate de que `crm.sqlite` y sus `-wal`/`-shm` (si usas WAL) queden bajo `secure_leads/`. Nunca al repo.

## Respuestas a tus Open Questions
1. **Auth (014):** ✅ de acuerdo con sesión PHP + hash en `.env`, **con el endurecimiento de arriba** (bcrypt, IP-first, sesión segura, rate-limit, CSRF). Yo **no** creo la credencial: el panel debe leer `ADMIN_PASSWORD_HASH` de `.env` y el usuario la genera.
2. **Captura de contacto (015):** ✅ **Sí, exige email al agendar** si el lead no fue capturado antes. Una cita sin contacto es inaccionable (no puedes confirmarla). Si no existe lead para esa sesión, **crea el lead** (estado `nuevo`) y liga la cita (`appointments.lead_id`). Si ya hay lead, solo asocia.

## Precisiones menores
- **Dedup:** la idempotencia por `session_id` cubre reenvíos en la misma sesión; el mismo email en sesiones distintas creará leads separados (aceptable; el admin los une a mano). Documenta esto como comportamiento esperado.
- **Doble-escritura CSV:** mantener el CSV heredado como fallback está bien; deja anotado en el plan **cuándo** se retira (cuando SQLite esté validado en producción).
- **BCE:** confirma que el Business Case Estimator también adjunte su contexto (inputs/escenario) al capturar lead, no solo el chatbot.

## Guardarraíles (Constitución)
Respeta la Constitución (§5 0-LLM, §6 PII/seguridad, §9 persistencia SQLite) y `AGENTS.md`. `npm run build` verde; el flujo actual (`save_wizard.php`, chatbot) no se rompe durante ni tras la migración. **El `pre-commit` bloqueará** cualquier intento de commitear `crm.sqlite`/PII — no lo evadas.

## Orden y forma
- **Fija primero el esquema SQLite** (014) y construye ambas sobre él (la 015 solo añade `appointments`).
- Cuando implementes, reporta con **evidencia real**: esquema creado, migración corrida (conteo importado), Network del flujo guiado = **cero `chat.php`**, y una demo del panel exigiendo login + rechazando IP no autorizada.

---
**Nota:** Claude (Opus 4.8) auditará la implementación: seguridad del panel (IP-first, bcrypt, sesión, CSRF), que la SQLite quede fuera del webroot y del repo, que el 0-LLM y la captura actual sigan intactos, y la integridad citas↔leads. Procede a implementar **tras aplicar los ajustes de seguridad de arriba**.
