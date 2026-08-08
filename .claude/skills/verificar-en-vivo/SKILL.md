---
name: verificar-en-vivo
description: >-
  Checklist de auditoría EN VIVO para Datanestiq cuando AntiGravity (u otro implementador) reporta que un build, fix o
  deploy está "terminado/desplegado/verificado". Úsalo SIEMPRE antes de dar por bueno un cambio en producción, aunque el
  reporte diga "verde", "100% funcional" o "verificado en vivo" — en este proyecto esos reportes fallaron repetidas veces
  (deploy sin recompilar, endpoints "Unknown action", pestañas del panel sin datos). Dispáralo cuando el usuario diga
  "audita su trabajo", "verifica", "reaudita en vivo", "AntiGravity culminó/desplegó", o cuando toques el panel /admin/,
  endpoints de api.php, chat.php, o cualquier cambio que llegue a app.datanestiq.com.
---

# Verificar en vivo — auditoría de deploy/build (Datanestiq)

**Por qué existe:** el maker (AntiGravity) construye y despliega; tú auditas. En este proyecto, el "verde" del reporte
**≠ realidad** una y otra vez — cada ronda la verificación en vivo cazó lo que el reporte no vio. La causa raíz recurrente:
el **backend PHP se sirve desde `dist/`** (Astro copia `public/`→`dist/` en `npm run build`), así que un deploy sin
recompilar sube la versión vieja; y "pestaña visible" no es "pestaña con datos". Este checklist evita firmar en falso.

**Regla de oro:** no reportes un veredicto positivo apoyándote en el reporte del agente ni en evidencia local. **Consulta
el artefacto REAL en producción.** Si algo no puedes verificar en vivo, dilo explícitamente.

## Los 5 pasos (hazlos en orden; salta lo que no aplique, pero declara qué saltaste)

### 1. Código en disco
Lee el/los archivo(s) del cambio y confirma que la corrección está y es correcta. Chequeos típicos que fallan:
- Queries SQL contra el **esquema real** (`scripts/init_crm_db.php` — p. ej. `requested_date`/`type`/`status='solicitada'`,
  no `service_slug`/`start_time`/`pending`). Placeholders nombrados **únicos** (`:p1..:pN`), nunca reutilizar `:param` (HY093).
- Helpers referenciados **existen** (p. ej. `getIncludeDemoFlag`), funciones `switch*` manejan **todas** las ramas, las
  vistas `view-*` son **hermanas** (no anidadas).

### 2. ¿Desplegado DE VERDAD? (el paso que más se salta el maker)
El backend se sirve desde `dist/`; un deploy sin `npm run build` sube código viejo, y opcache puede servir bytecode viejo.
Verifica contra el **endpoint real**, autenticado, con **cache-buster** y busca un **marcador** que solo emita el código nuevo:
```js
// desde la consola del panel (mismo origen, sesión activa):
await (await fetch('/admin/api.php?action=<ACCION>&_='+Date.now(), {cache:'no-store'})).text()
```
- ✅ **Vivo:** 200 + el marcador presente (un campo/valor nuevo del JSON, p. ej. `include_demo`, o el endpoint deja de dar
  `{"error":"Unknown action"}`).
- ❌ **No vivo:** el marcador falta, el total no cambió, o "Unknown action" → prod corre el `api.php` viejo. Pide
  **recompilar + redeploy + limpiar opcache** y re-verifica. **No des verde.**

### 3. Click-through de la UI (cada pestaña Y sub-pestaña, con DATOS)
Un contenedor que renderiza su encabezado **no** significa que sus datos cargaron (un `ReferenceError` en el loader deja la
lista vacía). Abre **todas** las pestañas y sub-pestañas y confirma que muestran **datos**, no solo el título, y que la
**consola no tiene errores**. Útil por JS:
```js
// visibilidad + contenido real del pane, y helpers/loaders:
document.getElementById('view-<x>').offsetParent!==null
document.getElementById('<tbody-id>').querySelectorAll('tr').length   // > 0 si hay datos
// y captura errores al invocar el loader/switch directamente:
try{ switchTab('<x>'); switchLeadsSubTab('<y>'); }catch(e){ e.message }
```
Comprueba también el mapeo `onclick`↔`id` de vista y que cada `switch*` tenga rama para el destino (Leads y Demanda suelen
funcionar; las intermedias/nuevas son las que fallan).

### 4. §2 (Honestidad) y seguridad
- Guards de **muestra mínima** ("datos insuficientes" cuando N < umbral) — que no muestre gráficos/cifras engañosas.
- **PII redactada** en vistas de comportamiento; contacto sin redactar SOLO tras `auth.php`.
- **Privados sin endpoint público:** los `.md`/`.csv`/tablas privadas (`chat_raw`) → probar la URL/acción y confirmar
  **404 / "Unknown action"** (cero fuga). Ej: `curl`/fetch a `/admin/PR-DRAFT-*.md`, `/secure_leads/*.csv`.
- Estimaciones marcadas **`[EST]`** con base real (tokens×tarifa, **nunca** latencia); datos DEMO marcados y **excluidos por
  defecto**.

### 5. Veredicto honesto
Reporta **lo que realmente observaste** (200/500, datos/vacío, marcador presente/ausente), no lo que el reporte afirma.
Si algo pasa, dilo con la evidencia; si algo no se pudo verificar en vivo (p. ej. requiere login que no tienes, o volumen
de datos), **decláralo** como pendiente en vez de asumir. Distingue: código correcto ≠ desplegado ≠ funciona con datos.

## Contexto del ecosistema (para no re-descubrir)
- Prod: `app.datanestiq.com` (webroot `/app/public`, secretos en `/app/`). Panel `/admin/` tras `auth.php`.
- Endpoints: `public/admin/api.php` (switch por `?action=`). CRM: `secure_leads/crm.sqlite`.
- Deploy: `scripts/deploy/deploy_ionos.py --confirm` (recompilar `npm run build` **antes**). Reglas: `AGENTS.md §4`,
  Constitución §11.
