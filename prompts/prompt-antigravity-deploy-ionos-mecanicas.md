# Prompt para Antigravity — Desplegar Datanestiq a IONOS (mecánicas del agente `deploy-ops`)

Ejecuta el despliegue del sitio (Astro `dist/` + backend PHP + CRM) a IONOS usando la máquina que **ya existe**: agente `deploy-ops` + skill `ionos-deploy` + `scripts/deploy/deploy_ionos.py`, siguiendo `planes/DESPLIEGUE-IONOS.md`. **No** rehagas el script. Constitución §6 (seguridad de credenciales) es innegociable.

## 🔴 Prerrequisitos que hace EL USUARIO (no AntiGravity) — verifícalos, no los ejecutes
El password SSH está **comprometido en el historial de git** y el servidor es **compartido con el proyecto IONOS**. Antes de desplegar, el usuario debe haber hecho (en el panel web de IONOS y en su `.env` local):
1. **Migrado a clave SSH** (subir la **pública** en el panel IONOS → Acceso SSH) **o** rotado el password en el panel.
2. Puesto en `.env` (local, gitignored): `IONOS_SSH_HOST`, `IONOS_SSH_USER`, `IONOS_SSH_KEY_PATH` (preferido) o `IONOS_SSH_PASSWORD`, `IONOS_REMOTE_PATH`.
3. Añadido al `.env` los secretos de runtime: `GROQ_API_KEY`/`DASHSCOPE_API_KEY`/`GEMINI_API_KEY`, `ADMIN_PASSWORD_HASH` (bcrypt de la clave que el usuario eligió), `ALLOWED_IPS` (IP pública real, **no** 127.0.0.1).

**Si falta cualquiera de estos, PÁRATE y pídeselo al usuario.** No inventes credenciales, no uses el password comprometido para "bootstrapear" una clave salvo que el usuario lo autorice explícitamente, y **nunca** escribas valores de secretos en logs, prompts, el repo ni el vault.

## Trabajo de AntiGravity (mecánicas)
1. **Preparar autenticación (si el usuario eligió clave):**
   - Si no existe, genera el par: `ssh-keygen -t ed25519 -f ~/.ssh/datanestiq_ionos -N ""` (la privada queda fuera del repo; confirma que `~/.ssh/*` está gitignoreado). Dile al usuario que **suba la .pub al panel de IONOS** (eso lo hace él).
   - Genera `.ssh_known_hosts` del host: `ssh-keyscan -H <IONOS_SSH_HOST> > .ssh_known_hosts` (para que el script use `RejectPolicy`, no AutoAdd). `.ssh_known_hosts` ya está gitignoreado.
2. **(Opcional) Ayudar con el hash del admin:** si el usuario te da su clave, corre `php -r "echo password_hash('LA_CLAVE', PASSWORD_BCRYPT);"` y que **él** la pegue en `.env` como `ADMIN_PASSWORD_HASH`. No la elijas tú ni la dejes en ningún log.
3. **Build:** `npm run build` (verde, corre build-taxonomy).
4. **Dry-run OBLIGATORIO primero:** `python scripts/deploy/deploy_ionos.py` → muestra el plan (nº de archivos, destino). Revísalo con el usuario.
5. **Deploy real — SOLO tras el "sí" explícito del usuario en esta sesión** (es una acción deliberada; el agente no despliega solo):
   `python scripts/deploy/deploy_ionos.py --confirm --with-env --init-crm`
   (sube `dist/` a la raíz del webroot —incluye `api/` y `admin/`—, sube el `.env` por SFTP con permisos 600, y corre `init_crm_db.php` + `migrate_leads.php` en remoto).
6. **Verificación (Paso 4 de la guía):**
   - `https://datanestiq.com` sirve la versión nueva.
   - Chatbot texto-libre responde (claves LLM en el `.env` remoto).
   - `curl -I https://datanestiq.com/admin/` → **302 → login** sin sesión; **403** desde una IP no autorizada (auth + IP activos).
   - `secure_leads/` **no** accesible por URL (403 del `.htaccess`); `.env` remoto en `600`.

## Guardarraíles de seguridad (Constitución §6) — duros
- Credenciales **solo** desde `.env` (gitignored). **Nunca** hardcodear/loguear/commitear secretos ni el password comprometido. No lo re-filtres en ningún archivo.
- **Nunca** subas al repo: `.env` local, `secure_leads/` (PII + `crm.sqlite`), claves privadas. `secure_leads/` vive **fuera del webroot** + 403.
- El panel `/admin/` va con **auth + whitelist de IP** obligatorios; tú no ingresas credenciales.
- Deploy real = `--confirm` deliberado, con confirmación del usuario en la sesión. Dry-run primero, siempre.
- No toques el proyecto IONOS (CAPA 3, territorio Gemini): `core/`, `agentes/*.txt`, `GEMINI.md`, `knowledge/`.

## Reporte esperado (para Claude)
- Qué prerrequisitos faltaban (si aplica) y qué pediste al usuario.
- Salida del dry-run (nº de archivos, destino) y del deploy real.
- Resultado de las 4 verificaciones del Paso 4 (con los códigos HTTP reales).
- Confirmación de que **cero** secretos quedaron en logs/repo/vault.

---
**Nota:** Claude (Opus 4.8) auditará el reporte: que el deploy fuera dry-run→confirm, que `/admin/` esté gated (302/403), `secure_leads/` protegido, y que ningún secreto se haya filtrado. Los prerrequisitos del panel IONOS (rotación/clave, IP, clave admin) son responsabilidad del usuario y no se delegan.
