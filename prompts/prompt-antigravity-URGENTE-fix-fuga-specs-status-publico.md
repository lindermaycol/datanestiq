# 🔴 Spec 017 — FUGA de info interna: `/api/specs-status.json` es PÚBLICO (expone la debilidad del /admin/)

El build de la 017 quedó bien en casi todo (build-gate antidrift wired en `prebuild`, percentiles con window
functions + guard, endpoints tras `auth.php`, sitio sano). **Pero introdujo una fuga de seguridad** que hay que
cerrar ya.

## 🔴 Diagnóstico (verificado en vivo por Claude)
- `build-specs-status.mjs` escribe una copia a **`public/api/specs-status.json`**, que Astro copia a `dist/` y el
  servidor sirve **públicamente**. Verificado: `https://app.datanestiq.com/api/specs-status.json` → **200** con
  **TODO el estado interno de las specs, incluido `openTechDebt`**.
- Entre lo expuesto al mundo:
  - **014: "Acceso password-only por IP dinámica"** → revela que `/admin/` **no tiene barrera de IP y solo depende
    del password** (la superficie de ataque exacta que compensamos con cuidado).
  - **008: "Rotación de contraseña SSH en IONOS pendiente"** → publica una debilidad de credenciales.
- Esto contradice tu propio CONSTRAINT §6 ("todo tras `auth.php`"). El status de specs debe consumirse **solo por
  el endpoint autenticado** `ops_specs_status`, nunca por un JSON público.

## Fix
1. **Deja de generar el JSON público:** en `build-specs-status.mjs`, **elimina la escritura a
   `public/api/specs-status.json`** (líneas ~91-96). El SSOT sigue en `src/data/specsStatus.json` (build-time, no servido).
2. **El endpoint `ops_specs_status` (tras `auth.php`) lee desde una ubicación PRIVADA, no pública.** En el servidor,
   `src/data/` no se despliega; coloca `specsStatus.json` en el **directorio privado del app** (`htdocs/app/`, junto a
   `.env`/`secure_leads/`, fuera del webroot público) y que el endpoint lo lea de ahí (ej. `__DIR__/../../specsStatus.json`
   desde `admin/`). Quita el fallback que apunta al `public/api/...`.
3. **Despliega `specsStatus.json` al dir privado:** ajusta `deploy_ionos.py` para subir `src/data/specsStatus.json` al
   **padre del webroot** (`htdocs/app/`), igual que hace con `.env`/`scripts/` (no al `public/`).
4. **BORRA el archivo público ya desplegado** en el servidor: `app/public/api/specs-status.json` (el deploy con
   `sftp.put` no elimina archivos removidos, así que hay que borrarlo por SSH explícitamente).
5. **Redeploy gateado** (dry-run → `--confirm`) y verifica.

## Verificación OBLIGATORIA en vivo (evidencia real)
1. `curl -I https://app.datanestiq.com/api/specs-status.json` → **403/404** (ya NO es accesible públicamente).
2. El panel `/admin/` (tras login) **sigue mostrando** el status de specs (leído del dir privado por el endpoint autenticado).
3. `curl -I https://app.datanestiq.com/admin/` → 302; `https://app.datanestiq.com/` → 200; WordPress `datanestiq.com` → 200.
4. `build-specs-status.mjs` sigue corriendo en `prebuild` y **falla el build ante drift** status ↔ `ESTADO-SPECS.md`
   (introduce una discrepancia de prueba → build falla; corrígela → verde).

## Guardarraíles
- §6: el estado interno de specs (fase/deuda) **no** se sirve público; solo tras `auth.php`. Nada de PII.
- Deploy gateado dry-run → `--confirm`; `.htaccess` sigue `FilesMatch` (no deny total); sitio en 200 tras subir.
- Commit en `007-multi-pagina`; pre-commit OK.

---
**Nota:** Claude (Opus 4.8) reauditará en vivo: `/api/specs-status.json` = 403/404 (fuga cerrada), panel de specs
operativo tras auth desde el dir privado, build-gate antidrift intacto, y sitio sano. Es P0 de seguridad
(la fuga anuncia la debilidad del `/admin/`).
