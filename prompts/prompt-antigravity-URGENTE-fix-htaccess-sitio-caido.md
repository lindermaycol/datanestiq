# 🚨 URGENTE — el subdominio `app.datanestiq.com` está caído (403 Apache) por el `.htaccess` de deny

## Diagnóstico (verificado en vivo por Claude)
- `https://app.datanestiq.com/` y `/index.html` → **403 Forbidden** (página Apache por defecto, `Server: Apache`) — **el sitio nuevo está DOWN**. `datanestiq.com` (WordPress) sigue `200` (intacto).
- **Causa raíz:** `deploy_ionos.py` crea un `.htaccess` con `Require all denied` / `Deny from all` en el **PADRE** del docroot (`htdocs/app/`, líneas ~164-167). En Apache, el `.htaccess` de un directorio **CASCADEA a sus subdirectorios**, y el docroot servido (`htdocs/app/public/`) **no tiene un `.htaccess` propio que lo revierta** → Apache deniega todo el subdominio.
- El deny total es además **innecesario**: `.env` y `secure_leads/` ya viven **fuera** del docroot servido (en `htdocs/app/`, no en `htdocs/app/public/`). La única vía de exposición era `datanestiq.com/app/...` (WP sirviendo `htdocs/`), que se protege con un deny **por-archivo**, sin tumbar el sitio.

## Fix
### 1. Cambiar el `.htaccess` que genera `deploy_ionos.py` (deny por-archivo, NO total)
En `scripts/deploy/deploy_ionos.py`, reemplaza el contenido del `.htaccess` del padre:
```
# ANTES (rompe el sitio al cascadear):
b"Require all denied\nDeny from all\n"
# DESPUÉS (protege solo secretos/BD, no cascadea-bloquea el sitio):
```
```apache
# Protege .env y la BD SQLite sin denegar el resto (evita cascada al docroot servido)
<FilesMatch "(^\.env$|\.sqlite$|\.sqlite-wal$|\.sqlite-shm$)">
    Require all denied
</FilesMatch>
# Compatibilidad Apache 2.2 por si aplica:
<IfModule !mod_authz_core.c>
    <FilesMatch "(^\.env$|\.sqlite$)">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>
```
`php`/`sintaxis`: es texto plano; solo verifica que el heredoc/bytes queden correctos.

### 2. Redeploy (mismo flujo gateado) — sobrescribe el `.htaccess` malo
- `npm run build` verde.
- `python scripts/deploy/deploy_ionos.py` (dry-run) → **STOP**, confirmación del usuario → `--confirm`.
- El real-deploy re-escribe `htdocs/app/.htaccess` con el nuevo contenido, reemplazando el `Require all denied` que tumbó el sitio.
- **Alternativa/defensa extra (opcional):** coloca también un `.htaccess` con `Require all granted` en el docroot servido (`htdocs/app/public/`) para blindar contra cualquier deny heredado futuro.

## Verificación OBLIGATORIA en vivo (evidencia real, sin false-green)
1. `curl -I https://app.datanestiq.com/` → **200** (sitio de vuelta). `curl -I https://app.datanestiq.com/index.html` → 200.
2. `curl -I https://app.datanestiq.com/.env` → 403/404 (sigue protegido).
3. `curl -I https://datanestiq.com/app/.env` → **403/404** (la vía de exposición por WP sigue bloqueada por el `<FilesMatch>`).
4. `curl -I https://datanestiq.com/app/secure_leads/crm.sqlite` → 403/404.
5. `curl -I https://datanestiq.com/` → **200** (WordPress intacto).
6. `curl -I https://app.datanestiq.com/admin/` → 302 → login (panel operativo).
7. **Funcional:** el chatbot del subdominio responde (LLM) — el sitio vuelve a operar de punta a punta.

## Guardarraíles
- Dry-run → `--confirm` con gate humano. No tocar WordPress ni `remote_extract.py`. Credenciales solo de `.env`.
- Commit en `007-multi-pagina`: `fix(deploy): htaccess por-archivo (no cascada) — restaura app.datanestiq.com`. Pre-commit hook OK.
- **Honestidad §2:** reporta los códigos HTTP reales de los 7 checks; no marques "OK" sin el `curl` ejecutado. `ESTADO-SPECS`/`tech_debt` no deben decir "verificado 403" sobre un `.htaccess` que en realidad tumbó el sitio — corrige esa nota si quedó.

---
**Nota:** Claude (Opus 4.8) reauditará en vivo: `app.datanestiq.com/` = 200, `.env`/`crm.sqlite` no accesibles por **ninguno** de los dos dominios, WP intacto, y `/admin/` operativo. Esto es P0 (sitio caído) — prioridad máxima.
