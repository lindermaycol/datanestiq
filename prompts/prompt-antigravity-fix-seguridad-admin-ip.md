# Fix de seguridad + honestidad — panel `/admin/` (decisión: password-only)

Corrige la **regresión de seguridad** introducida en el deploy 014/015: `auth.php` cambió su default de
IP whitelist a **`*` (fail-OPEN)** y en prod se puso `ALLOWED_IPS=*`, dejando el panel accesible desde
cualquier IP. **El usuario decidió: acceso password-only (IP dinámica), riesgo aceptado.** Esta tarea
NO reactiva el whitelist de IP, pero **sí** quita el landmine del código y **corrige la deshonestidad**
en la documentación. Rama `007-multi-pagina`. Guardarraíles de siempre (dry-run→`--confirm`, `php -l`, pre-commit).

## 1. Código — quitar el default fail-open de `public/admin/auth.php`
Hoy: `$allowed_ips_raw = getenv('ALLOWED_IPS') ?: '*';`. Eso es **fail-open**: si `ALLOWED_IPS` queda
vacío/ausente, el panel se abre a todo internet. **Cámbialo a fail-closed:**
- `getenv('ALLOWED_IPS') ?: '127.0.0.1,::1'` (o deny-all). Un `ALLOWED_IPS` ausente/vacío debe **denegar**, no abrir.
- **Conserva** el soporte de comodines (`203.0.113.*`) y el `in_array('*', ...)` — pero `*` (abrir-todo)
  solo aplica cuando está **explícitamente** en `.env`, nunca por default.
- `C:/xampp/php/php.exe -l public/admin/auth.php` tras el edit.

## 2. Config prod — se queda `ALLOWED_IPS=*` (decisión del usuario), pero endurece los controles compensatorios
Como no hay barrera de IP, el panel queda expuesto a internet con solo password. Verifica que los
controles compensatorios estén **activos y sólidos** (son ahora la única defensa):
- **Rate-limit real:** prueba que 5 intentos fallidos en 5 min → bloqueo/backoff (documenta el test).
- **Errores de login genéricos** (sin enumeración de usuario: mismo mensaje para user/pass inválidos).
- **Sesión segura:** cookie `Secure; HttpOnly; SameSite=Strict` (reporta el `Set-Cookie` real).
- **HTTPS forzado** (SSL ya asignado al subdominio) — sin acceso por HTTP plano al panel.
- (Nota para el usuario, no lo puedes verificar tú): que `ADMIN_PASSWORD_HASH` sea de una contraseña
  fuerte y única — es ahora la defensa principal.

## 3. Honestidad §2 — corregir la documentación (hoy miente sobre el tradeoff)
- **`ESTADO-SPECS.md` (fila 014):** hoy dice "✅ Ninguna bloqueante" y presenta `ALLOWED_IPS=*` como
  *"soporte para IPs dinámicas"* — framing engañoso. Reescríbelo honesto: **"IP whitelist deshabilitado
  a propósito (acceso password-only por IP dinámica del usuario); riesgo aceptado y compensado con
  bcrypt + rate-limit + HTTPS + sesión segura. Ver `tech_debt.md`."**
- **`specs/014-crm-lead-lifecycle/tech_debt.md`:** registra la deuda/riesgo explícito: *"Panel /admin/
  sin barrera de IP (`ALLOWED_IPS=*`) — decisión del usuario por IP dinámica. Riesgo: superficie de
  ataque abierta a internet, mitigada solo por bcrypt + rate-limit. Revisar si el usuario consigue IP
  fija o VPN de egreso fijo."**

## 4. Redeploy del `auth.php` corregido (mismo flujo gateado)
- `npm run build` (verde) → `php -l` de los admin/api PHP.
- `python scripts/deploy/deploy_ionos.py` (dry-run) → **STOP**, confirmación del usuario → `--confirm`.
- Verifica que `/admin/` sigue funcionando (302 → login; el password autentica) tras el cambio.

## Verificación (evidencia real)
1. `auth.php` con default **fail-closed** (grep del cambio) + `php -l` limpio.
2. Rate-limit demostrado (6º intento bloqueado), errores genéricos, `Set-Cookie` seguro, HTTPS.
3. `ESTADO-SPECS.md` fila 014 **sin** el framing de "feature" — riesgo documentado; `tech_debt.md` 014 con la deuda.
4. `/admin/` operativo tras el redeploy; `curl -I https://app.datanestiq.com/admin/` → 302 login.
5. Commit en `007-multi-pagina`; pre-commit hook OK; sin secretos.

---
**Nota:** Claude (Opus 4.8) reauditará: default fail-closed en `auth.php`, controles compensatorios
verificados, documentación honesta (sin "feature"), y el panel operativo. El acceso password-only es
decisión explícita del usuario y queda registrada como riesgo aceptado — no como cero-deuda.
