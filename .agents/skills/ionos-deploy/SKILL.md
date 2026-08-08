# SKILL: ionos-deploy — Automatización de servidor IONOS vía SSH/SFTP (paramiko)

**Cuándo usarla:** cuando haya que ejecutar en el servidor IONOS acciones que antes se hacían a mano — desplegar `dist/` + backend PHP, subir el `.env`, correr scripts (init/migración del CRM), ajustar permisos, o cualquier operación remota por SSH.

**Librería:** `paramiko` (ya instalada). Los scripts van en `scripts/deploy/`. El de referencia es `scripts/deploy/deploy_ionos.py`.

## 🔴 Reglas de seguridad (Constitución §6 — NO negociables)
1. **Credenciales SOLO desde `.env`** (gitignored): `IONOS_SSH_HOST`, `IONOS_SSH_USER`, y **`IONOS_SSH_KEY_PATH` (preferido)** o `IONOS_SSH_PASSWORD` (fallback). **NUNCA** hardcodees host/user/clave/password en el código ni en los logs; **nunca** los commitees. El `pre-commit` bloquea `.env` y claves.
2. **Preferir autenticación por CLAVE SSH.** El password de IONOS quedó **comprometido en el historial de git** → hay que rotarlo o migrar a par de claves. Diseña para clave; el password es solo compatibilidad temporal.
3. **Verificación de host key:** usa un `known_hosts` (`.ssh_known_hosts`, gitignored) con `RejectPolicy` en producción; `AutoAddPolicy` solo con aviso en el primer uso.
4. **Nunca subas** al servidor ni toques en el repo: `secure_leads/` (PII/BD), `node_modules`, `.git`, `.venv`, y el `.env` **solo** por SFTP explícito (`--with-env`), con permisos `600` en remoto.
5. **DRY-RUN por defecto.** El despliegue/acción REAL es **outward-facing e irreversible** → requiere `--confirm` y, para subir secretos, una confirmación textual del humano. **Nunca** ejecutes un deploy real de forma autónoma sin que el usuario lo pida explícitamente.

## Cómo escribir/extender un script de ops IONOS
- Reutiliza el patrón de `deploy_ionos.py`: `load_env` (sin volcar valores) → `connect()` (clave preferida, host-key check) → SFTP `put` para subir, `exec_command` para correr comandos remotos.
- **Idempotencia:** crea directorios remotos si faltan (`ensure_remote_dir`); las operaciones deben poder re-correrse sin romper.
- **No loguees secretos** (ni el password, ni el contenido del `.env`). Reporta rutas y conteos, no valores sensibles.
- Para comandos remotos (permisos, php, `.htaccess`): usa `ssh.exec_command` y **muestra stdout/stderr** para que el humano vea el resultado.

## Acciones típicas para IONOS
- **Deploy del sitio:** subir `dist/` + `public/api/*.php` + `public/admin/*.php` a `IONOS_REMOTE_PATH`.
- **Backend/CRM:** subir `.env` (con `--with-env`), correr `php scripts/init_crm_db.php` y `php scripts/migrate_leads.php` en remoto (`--init-crm`).
- **Seguridad:** asegurar `.htaccess deny` en `secure_leads/` remoto y permisos `600` del `.env`.
- **Verificación post-deploy:** por SSH, `curl -sI` local del sitio y del `/admin/` (que responda 302→login sin sesión).

## Qué NO hace esta skill
- No rota credenciales por sí sola ni inventa el password/clave: eso lo decide el usuario (rotación en el panel IONOS o alta de la clave pública). La skill **consume** credenciales que el usuario ya puso en `.env`.
- No ejecuta deploys de producción sin `--confirm` explícito.
