# Plan de Implementación — Spec 022: File-Free Storage & Persistence Unification

## 1. Resumen Ejecutivo
Este plan detalla el paso a paso para consolidar la base de datos SQLite `crm.sqlite` como la **fuente única e indivisible de verdad operacional**, eliminando todas las escrituras a archivos funcionales planos (`.jsonl` y `.csv`). Incluye la creación de visores nativos en el panel de control para conversaciones redactadas y uso diario de tokens vs cap, la migración segura del histórico de archivos y la verificación sin pérdidas de datos.

---

## 2. Fases de Ejecución del Build (Secuencial)

### Fase 1: Creación de Tablas SQLite & Script de Migración de Histórico
1. Actualizar `scripts/init_crm_db.php` para crear idempotentemente las tablas:
   - `conversations`
   - `chat_raw` (privada)
   - `usage_daily`
   - `leads_extracted`
2. Desarrollar el script de migración e importación del histórico: `scripts/migrate_file_free_history.php`.
   - Lee todos los archivos planos existentes y traslada los registros a SQLite.
   - Crea el directorio `secure_leads/_archive/` y traslada los archivos procesados.
   - Muestra reporte de auditoría `COUNT(*)` pre/post migración.

### Fase 2: Implementación de Escritura Primaria en SQLite (Backend PHP)
1. Modificar `public/api/chat.php`:
   - Reemplazar la escritura a `chat_logs.jsonl` y `other_logs.jsonl` por inserts fail-safe en `conversations`.
   - Reemplazar la escritura a `chat_raw.jsonl` por inserts fail-safe en la tabla privada `chat_raw`.
   - Reemplazar el manejo de `daily_usage_*.json` por `INSERT ... ON CONFLICT(usage_date) DO UPDATE` en `usage_daily`.
   - Eliminar escrituras secundarias redundantes a `usage_metrics.jsonl` y `alerts.jsonl`.
2. Modificar `scripts/extraer_leads.php`:
   - Cambiar la fuente de lectura de `secure_leads/chat_raw.jsonl` a la tabla `chat_raw`.
   - Cambiar el destino de escritura de `secure_leads/leads_datanestiq.csv` a la tabla `leads_extracted`.
3. Modificar `public/api/save_wizard.php`:
   - Eliminar el fallback secundario de escritura en `leads_wizard.csv`.

### Fase 3: Endpoints de Administración y Visores en Panel
1. Actualizar `public/admin/api.php`:
   - Implementar endpoint `conversations` (paginado, redactado).
   - Implementar endpoint `usage_daily` (métricas de consumo vs cap).
   - Actualizar endpoint `leads_detected` para consultar `leads_extracted`.
2. Actualizar `public/admin/index.php`:
   - Agregar el Visor de Conversaciones Redactadas en la interfaz de administración.
   - Agregar la tarjeta de Uso Diario vs Cap en la pestaña Observabilidad Ops.

---

## 3. Plan de Despliegue y Verificación en Producción
1. **Paso 1: Recompilación de Sitio con Astro**:
   - Forzar limpieza de dist e invocar `npm run build` para actualizar artefactos estáticos.
2. **Paso 2: Despliegue IONOS**:
   - Ejecutar `python scripts/deploy/deploy_ionos.py --confirm`.
3. **Paso 3: Ejecución de Migración Remota vía SSH**:
   - Ejecutar `init_crm_db.php` y `migrate_file_free_history.php` en el servidor remoto para crear tablas e importar histórico.
4. **Paso 4: Verificación de Endpoints y Cero Archivos Funcionales**:
   - Verificar `conversations`, `usage_daily` y `leads_detected` en vivo.
   - Verificar que no se generen nuevos archivos `.jsonl` / `.csv` en `secure_leads/`.
