# R1 resuelto — DECISIÓN: subdominio dedicado (NO tocar el WordPress de `datanestiq.com`)

Resolución del bloqueante R1 de la auditoría (`prompt-antigravity-audit-plan-deploy-014-015.md`). **Léelo junto con ese archivo.** Decisión del usuario, es vinculante.

## 🔴 Contexto crítico que cambia el deploy
- **`datanestiq.com` sirve el WordPress EN PRODUCCIÓN (en `htdocs/`).** El plan de subir `dist/` a `htdocs/` habría **SOBRESCRITO el sitio WP live**. **Prohibido desplegar a `htdocs/`** ni tocar nada de WordPress (§9).
- El sitio nuevo (Astro + CRM + Agendador) va en un **subdominio dedicado** que el **usuario creará en el panel de IONOS** (ej. `app.datanestiq.com` / `staging.` / `beta.`), apuntando a una **carpeta nueva y vacía** (no `htdocs/`).

## ✅ TOPOLOGÍA CONFIRMADA (el usuario creó `app.datanestiq.com`)
El subdominio **`app.datanestiq.com`** ya existe con SSL. El usuario ajustará su "webspace directory" a **`/app/public`** (antes `/app`). Topología concreta a usar (verifica los paths absolutos por SSH):

```
{webspace}/htdocs/                       ← WordPress (datanestiq.com) — NO TOCAR
{webspace}/htdocs/app/                    ← PRIVADO, no servido → .env, secure_leads/, scripts/   (+ .htaccess Deny)
{webspace}/htdocs/app/public/             ← document root de app.datanestiq.com → contenido de dist/
   └── api/*.php, admin/*.php, index.html, ...   →  __DIR__/../../ = {webspace}/htdocs/app/  ✅
```

- **`IONOS_REMOTE_PATH`** = la ruta absoluta de **`.../htdocs/app/public`** (NO `htdocs`, NO `htdocs/app`).
- **`.env` + `secure_leads/` + `scripts/`** → **`.../htdocs/app/`** (padre de `public/`; `../../` cae exacto ahí).
- Verifica por SSH la ruta absoluta real de `/app` dentro del webspace del usuario (`a2533622`), y que `htdocs/app/` es writable por el usuario.
- Añade `.htaccess` con `Deny from all` / `Require all denied` en `htdocs/app/` (defensa en profundidad; ese dir cuelga del webroot de WP).

---

## Topología objetivo — principio general (satisface el `../../` del backend, sin tocar código)
El backend lee `.env` y `secure_leads/` en `__DIR__/../../` (dos niveles arriba del PHP). Por tanto:
- **Raíz servida del subdominio** (document root que asigna IONOS) = donde va el contenido de `dist/` (index.html, `api/`, `admin/`, assets). Ej. `.../<carpeta-subdominio>/`.
- **`.env` + `secure_leads/` + `scripts/`** = **el directorio PADRE de esa raíz servida** (para que `{raíz}/api/x.php → ../../` caiga ahí). Ese padre **debe ser writable por el usuario SSH y NO servible por ningún dominio/subdominio**.
- `IONOS_REMOTE_PATH` en `.env` = la **raíz servida del subdominio** (NO `htdocs/`).

## Pasos que DEBES hacer (con SSH, ya tienes acceso)
1. **Confirma la carpeta real del subdominio** que creó el usuario y **cuál es su padre escribible** (`ls -la`, `stat`, y probando qué sirve cada URL). Recuerda: el verdadero `/homepages/37/d4298973101/` es **root-owned**; el padre servible/escribible que uses debe estar **dentro** del espacio del usuario.
2. **Verifica que el padre elegido NO es servible por WordPress ni por el subdominio.** Si existe cualquier riesgo de que `datanestiq.com/<algo>/.env` o `<subdominio>/.env` resuelva al archivo, **nést­alo más profundo** (que la raíz servida sea `.../app/public/` y el padre `.../app/`) y **añade `.htaccess` con `Deny from all` / `Require all denied`** en la carpeta de `.env`/`secure_leads/` como defensa en profundidad.
3. Ajusta `IONOS_REMOTE_PATH` a la raíz servida del subdominio y procede con el resto del plan (deploy de `dist/` ahí, `.env`/`secure_leads/`/`scripts/` en el padre, `init_crm_db.php` con `/usr/bin/php8.2-cli`).

## Verificación OBLIGATORIA de R1 (evidencia real, no "el archivo existe")
- `curl -I https://datanestiq.com/.env` **y** `curl -I https://<subdominio>/.env` → **403/404** en ambos (el `.env` NO es alcanzable por ninguno).
- `curl -I https://<subdominio>/secure_leads/crm.sqlite` → **403/404**.
- **`https://datanestiq.com` sigue sirviendo el WordPress intacto** (verifícalo — no lo rompiste).
- **Funcional:** el **chatbot texto-libre del subdominio responde con salida real del LLM** (prueba que `chat.php` encontró el `.env`) y un `POST /api/save_wizard.php` + `SELECT` confirma el lead en `secure_leads/crm.sqlite`.

## Guardarraíles reforzados
- **NO tocar `htdocs/` ni WordPress** (`wp-admin/`, `wp-includes/`, `wp-content/`, `wp-config.php`). El deploy va **solo** a la carpeta del subdominio.
- Backup previo: NO aplica sobrescribir WP (no lo toques). Si la carpeta del subdominio tuviera algo, respáldalo.
- Todo lo demás del plan y de la auditoría sigue vigente: dry-run→`--confirm` con gate humano, `php8.2-cli`, checks de 403 `/admin/` por IP no-whitelisted, grep PII=0, zona `America/Lima`, anti-doble-booking, doc-sync.

---
**Nota:** el usuario crea el subdominio en el panel (acción suya). Tú determinas/─verificas la topología real por SSH y confirmas que WP queda intacto y los secretos no son servibles. Claude reaudita el plan actualizado (con la carpeta real del subdominio + la verificación de exposición) antes del deploy real.
