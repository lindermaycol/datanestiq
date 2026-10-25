# Auditoría del plan de deploy 014/015 — R2 ✅, R1 🔴 NO resuelto (bloquea FASE 3)

Revisé tu plan (`planes/Plan de Implementación — Retomar Despliegue IONOS post-rotación SSH (Specs 014 + 015).md`). El diagnóstico de FASE 0 es útil y **R2 está bien resuelto**. Pero **NO doy luz verde a FASE 3 (deploy)**: R1 quedó ambiguo y tu propio diagnóstico lo agrava. Resuélvelo concretamente y vuelve al gate.

## ✅ Aprobado
- **R2 (PHP CLI):** excelente catch — el `php` por defecto es 4.4.9; usar `/usr/bin/php8.2-cli` explícito para `init_crm_db.php`/`migrate_leads.php`. OK modificar `deploy_ionos.py` para eso (mantén la consistencia: los scripts se suben a la ruta writable elegida y se corren con `php8.2-cli` desde ahí; si dejas de usar el `--init-crm` interno del script, dilo).
- Rollback (backup de `htdocs` antes de sobrescribir), estimación, gates humanos: bien.

## 🔴 R1 NO está resuelto — es contradictorio y tu FASE 0 lo agrava
Tu diagnóstico dice que **`/homepages/37/d4298973101/` (el verdadero padre de `htdocs/`) es propiedad de `root` y NO writable**. Pero el backend lee `.env` y `secure_leads/` en `__DIR__/../../`:
- Si el sitio se despliega en `htdocs/` (PHP en `htdocs/api/*.php`), entonces `../../` = `/homepages/37/d4298973101/` → **root-owned, no escribible** → el backend **no puede** leer ahí. **R1 roto.**
- Tu texto llama a `htdocs/` a la vez "padre del webroot" **y** "webroot" — es autocontradictorio, y ofreces 2 opciones sin decidir ninguna.

**Antes de FASE 3 tienes que:**
1. **Determinar el document root REAL de `datanestiq.com`** (no asumir que es `htdocs/`). Verifícalo: ¿qué carpeta sirve el dominio? (panel IONOS o probando qué URL devuelve `htdocs/index.html`). Repórtalo con evidencia.
2. **Comprometerte a UNA topología concreta y verificada:**
   - **Preferida (limpia, sin tocar el backend):** el sitio se sirve desde un **subdirectorio** (ej. `htdocs/app/`) y `.env` + `secure_leads/` + `scripts/` viven en `htdocs/` (writable, **por encima** de lo servido). Así `../../` desde `htdocs/app/api/*.php` = `htdocs/` ✅ y los secretos **no** son servibles. **Requiere que el document root del dominio apunte al subdirectorio** — si eso es una acción del **panel IONOS**, es del **usuario**: dilo explícitamente y pídelo, no lo asumas.
   - **Fallback (solo si el docroot NO se puede mover):** todo en `htdocs/`, `.env`+`secure_leads/` dentro del webroot protegidos por `.htaccess deny` + `chmod 600`, **y** cambiar las rutas del backend de `../../` a `../` (`save_wizard.php`, `book_appointment.php`, `availability.php`, `auth.php`, `chat.php`). Es **más riesgoso** (secretos + PII dentro del webroot, dependiendo de que Apache honre el deny) y toca código → **requiere aprobación explícita del usuario y mía** antes de aplicarlo. No lo improvises.
3. **Verificación FUNCIONAL de R1 (no basta con que el archivo exista):**
   - El **chatbot texto-libre responde con una respuesta real del LLM** → prueba que `chat.php` **encontró** el `.env` (claves cargadas). Si el chatbot devuelve error/HTML, R1 está mal.
   - Un `POST /api/save_wizard.php` seguido de un `SELECT` que muestra el lead → prueba que `save_wizard.php` resolvió `secure_leads/crm.sqlite` en la ubicación real.

**No procedas a FASE 3 sin decidir esto y sin mi visto bueno del topology.** Entrégalo en el plan actualizado (FASE 1) para revisión.

## Checks que tu plan omitió — mantenlos (no false-green)
- **403 de `/admin/` desde IP NO whitelisted:** es la barrera de seguridad del panel; hay que probar que `ALLOWED_IPS` realmente bloquea. Si no puedes simular otra IP, márcalo **"PENDIENTE: no verificado"**, no lo omitas.
- **PII en logs:** el grep explícito `grep -cE "[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}" logs/*.log` debe dar **0** (con el output en el reporte).
- **Zona horaria:** prueba un slot que **cruce medianoche UTC pero sea el mismo día en `America/Lima`** y confirma que el día del picker es correcto.
- **Anti-doble-booking:** ya tienes el 409 en el 2º request; si puedes, suma el test concurrente (2 requests simultáneos → 1 éxito/1 conflicto); si no, documenta el diseño transaccional como evidencia.

## Higiene menor
- El plan escribe el host/usuario reales (`a2533622@access-...`). No es una fuga **nueva** (ya están en el historial y en el `deploy_dw.py` del proyecto IONOS), pero no los propagues de más y **jamás** escribas el password en ningún archivo/log.

---
**Resumen:** R2 ✅. **R1 🔴 bloqueante** — decide y verifica la topología real (con la posible acción del usuario en el panel para el document root) antes del deploy. Vuelve al gate de FASE 1 con eso resuelto + los 3 checks reincorporados. Yo reaudito el plan actualizado antes de que ejecutes FASE 3.
