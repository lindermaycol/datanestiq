# Guía de Despliegue a IONOS (automatizado con el agente `deploy-ops`)

Esta guía cubre el despliegue del sitio Datanestiq (Astro `dist/` + backend PHP + CRM) a IONOS, **automatizado vía SSH/SFTP con paramiko**. El trabajo lo ejecuta el agente **`deploy-ops`** usando la skill **`ionos-deploy`** y el script `scripts/deploy/deploy_ionos.py`.

> **Principio de seguridad:** las credenciales viven solo en `.env` (gitignored). El despliegue real es una **acción deliberada** (dry-run por defecto; `--confirm` para ejecutar). El agente **no** despliega solo.

---

## Paso 0 — Credenciales SSH (hacer UNA vez) 🔴
El password SSH de IONOS **quedó comprometido en el historial de git**. Antes de automatizar:
- **Recomendado — migrar a clave SSH:**
  1. Genera un par: `ssh-keygen -t ed25519 -f ~/.ssh/datanestiq_ionos` (sin frase o con frase).
  2. Sube la **clave pública** al panel de IONOS (Acceso SSH → claves).
  3. En `.env`: `IONOS_SSH_KEY_PATH=/ruta/a/datanestiq_ionos` (privada, fuera del repo).
- **Alternativa — rotar el password** en el panel de IONOS y ponerlo en `IONOS_SSH_PASSWORD` (menos seguro; migra a clave cuando puedas).

## Paso 1 — Variables en `.env` (local, gitignored)
```
IONOS_SSH_HOST=access-XXXX.webspace-host.com
IONOS_SSH_USER=uXXXXXXXX
IONOS_SSH_KEY_PATH=/ruta/a/datanestiq_ionos     # preferido
# IONOS_SSH_PASSWORD=...                          # fallback (comprometido)
IONOS_REMOTE_PATH=/kunden/homepages/.../htdocs   # ruta remota del sitio
# Claves de runtime del sitio (para el chatbot):
GROQ_API_KEY=... ; DASHSCOPE_API_KEY=... ; GEMINI_API_KEY=...
# CRM (panel interno):
ADMIN_PASSWORD_HASH=$(php -r "echo password_hash('TU_CLAVE', PASSWORD_BCRYPT);")
ALLOWED_IPS=TU.IP.PUBLICA.REAL       # NO 127.0.0.1 en prod
```

## Paso 2 — Build
```
npm run build      # genera dist/ (corre build-taxonomy antes)
```

## Paso 3 — Desplegar (con el agente o el script)
**Con el agente:** pídele a `deploy-ops` *"haz un dry-run del despliegue a IONOS"*, revisa el plan, y luego *"despliega"* para confirmar.

**Directo con el script:**
```
python scripts/deploy/deploy_ionos.py                 # DRY-RUN: muestra qué subiría
python scripts/deploy/deploy_ionos.py --confirm        # sube dist/ + public/api/*.php + public/admin/*.php
python scripts/deploy/deploy_ionos.py --confirm --with-env   # además sube el .env (pide confirmación textual)
python scripts/deploy/deploy_ionos.py --confirm --init-crm   # corre init_crm_db.php + migrate_leads.php en remoto
```

## Paso 4 — Post-deploy (verificación)
- El sitio carga en `https://datanestiq.com` (versión nueva).
- El **chatbot texto-libre** responde (claves LLM en el `.env` remoto).
- **`/admin/`** responde **302 → login** sin sesión, y **403** desde una IP no autorizada (IP+auth activos).
- `secure_leads/` **no** es accesible por URL (`.htaccess deny` en Apache IONOS) y el `.env` remoto tiene permisos `600`.

## Qué se sube y qué NO
Astro copia `public/` (incluidos `api/` y `admin/`) dentro de `dist/` en el build, así que **se sube solo el contenido de `dist/` a la raíz del webroot remoto** — eso ya incluye el sitio + los endpoints PHP + el panel CRM.

| Se sube (contenido de `dist/` → raíz remota) | NO se sube (nunca) |
|---|---|
| `dist/index.html`, assets → `/` | `.env` local al **repo** (sí por SFTP con `--with-env`, permisos `600`) |
| `dist/api/*.php` → `/api/` (chat, save_wizard, book_appointment, availability) | `secure_leads/` (PII + `crm.sqlite`) |
| `dist/admin/*.php` → `/admin/` (panel CRM) | `node_modules`, `.git`, `.venv`, `__pycache__` |

## Checklist de pendientes de infraestructura
- [ ] Paso 0: migrar a clave SSH **o** rotar el password comprometido.
- [ ] `.env` de producción con claves LLM + `ADMIN_PASSWORD_HASH` + `ALLOWED_IPS`.
- [ ] Deploy (`--confirm`) + `--with-env` + `--init-crm` la primera vez.
- [ ] Verificación del Paso 4 (sitio, chatbot, `/admin/` gated, `secure_leads/` protegido).
