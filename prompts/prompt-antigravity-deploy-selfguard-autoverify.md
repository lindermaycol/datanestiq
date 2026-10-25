# TAREA (infra) — `deploy_ionos.py` auto-verificado: build-fresco obligatorio + verificación de endpoint real post-deploy

Aprendizaje de campo: **3 deploys reportados "verde/desplegado" que NO estaban vivos** (código correcto en local, pero
prod servía la versión vieja). Causa raíz: el **backend PHP se sirve desde `dist/`** (Astro copia `public/`→`dist/` en
`npm run build`), y se desplegó `dist/` sin recompilar; a veces opcache servía bytecode viejo. La solución de fondo es
hacer el **propio `scripts/deploy/deploy_ionos.py` auto-verificado**, para que "desplegado" **signifique** "vivo y
verificado", sin importar quién lo invoque (AntiGravity o Claude).

## Cambios a `scripts/deploy/deploy_ionos.py`
### 1. Guard de build fresco (pre-deploy) — bloqueante
Antes de subir `dist/`, comprobar que `dist/` es **más nuevo** que los fuentes que se sirven desde ahí:
- Compara el mtime más reciente de `public/**` y `src/**` contra el de `dist/**`. Si algún fuente es más nuevo que
  `dist/` → **abortar** con mensaje claro: *"dist/ está desactualizado respecto a public/src — corre `npm run build`
  antes de desplegar"*.
- **Mejor aún (recomendado):** si `--build` (o por defecto), **ejecuta `npm run build` automáticamente** antes del upload
  y aborta si el build falla (captura el `EPERM` de Windows: si `dist/` está bloqueado, renombrar/limpiar y reintentar,
  como ya hiciste manualmente). Nunca subir un `dist/` stale.

### 2. Verificación de endpoint real (post-deploy) — bloqueante
Tras el SFTP + (opcional) migración, correr una verificación en caliente y **fallar el deploy** si no pasa:
- Reusa **`scripts/deploy/verify_remote_endpoints.py`**: por cada endpoint/acción tocado en el cambio, hacer la petición
  real y **assert HTTP 200 + un marcador esperado** en la respuesta (p. ej. una clave nueva del JSON, como el `include_demo`
  que ya usamos para saber si el `api.php` nuevo tomó). Lista de endpoints verificable por flag o config.
- **Limpiar opcache** con **`scripts/deploy/clear_prod_opcache.py`** como parte del flujo (por si IONOS tiene
  `opcache.validate_timestamps=0`), **antes** de la verificación.
- Si la verificación falla → salir con código ≠ 0 y mensaje: *"deploy subido pero NO verificado en vivo (endpoint no
  refleja el cambio) — revisa build/opcache"*. Que un deploy no verificado **no** se reporte como éxito.

### 3. Resumen final honesto
Al terminar, imprimir un resumen: archivos subidos, opcache limpiado (sí/no), y la tabla de endpoints verificados
(200 + marcador ✓/✗). "Éxito" solo si **todos** verifican.

## Restricciones
- Mantener el gate **dry-run por defecto → `--confirm`** (nunca autónomo) y credenciales solo desde `.env` (§6).
- No romper el flujo actual (backup, `--init-crm`, migración remota con `php8.2-cli`).
- Idempotente y seguro: si el build o la verificación fallan, **no** dejar el sitio a medias (idealmente verificar antes
  de considerar el deploy cerrado; si aplica, subir a un dir temporal y promover — opcional, no bloqueante en v1).

## Verificación de esta tarea
- Un cambio de PHP sin `npm run build` → `deploy_ionos.py` **aborta** (o recompila) y **no** sube dist stale.
- Un deploy normal → recompila, sube, limpia opcache, verifica endpoints (200 + marcador), imprime la tabla. Si fuerzas
  un endpoint a fallar (marcador ausente) → el script **falla**, no reporta verde.
- `php -l` no aplica (es Python); corre el script en dry-run para validar el flujo.

---
**Nota:** Claude (Opus 4.8) pide esta mejora tras 3 "verde falso por deploy". El objetivo: que `deploy_ionos.py`
**garantice** build-fresco + verificación de endpoint real (reusando `verify_remote_endpoints.py` y `clear_prod_opcache.py`
que ya creaste), de modo que ningún deploy pueda reportarse exitoso si el cambio no está realmente vivo.
