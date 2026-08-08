# Plan de Implementación — Spec 016 (BUILD: Analítica de Conversión + Loop `chat → lead → learn`)

Este documento presenta el plan de desarrollo técnico detallado para la implementación de la **Spec 016** (Analítica de Conversión + Bucle Offline de Aprendizaje) tras haber recibido la auditoría favorable y luz verde de Claude Code.

---

## User Review Required

> [!IMPORTANT]
> **Migración Idempotente en BD de Producción Vivo (`crm.sqlite`):**  
> La implementación agregará las columnas `sector` y `rol` a la tabla `leads` en `secure_leads/crm.sqlite`. Para garantizar **cero pérdida de datos preexistentes** en producción, la migración se ejecutará con respaldo preventivo (`cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak`) e inspección de esquema vía `PRAGMA table_info(leads)` antes de lanzar `ALTER TABLE`.

> [!IMPORTANT]
> **Gobierno de Prompts (0 Auto-Merge):**  
> El script de optimización `scripts/learn_prompt_optimizer.mjs` operará únicamente de forma **offline** utilizando APIs free-tier. Sus sugerencias de mejora al `SYSTEM_PROMPT` se emitirán **exclusivamente como PR Draft** (patrón `content-pr.yml`). Ningún cambio al prompt se aplicará automáticamente en producción.

---

## Proposed Changes

### 1. Instrumentación Backend y Esquema CRM

#### [MODIFY] [init_crm_db.php](file:///C:/xampp/htdocs/datanestiq/scripts/init_crm_db.php)
- Añadir la tabla `chat_metrics` con campos `id`, `session_id`, `backend_used`, `latency_ms`, `success`, `tokens_est`, `created_at` e índices correspondientes.
- Incorporar la adición idempotente de columnas `sector VARCHAR(100)` y `rol VARCHAR(100)` a la tabla `leads` mediante `PRAGMA table_info(leads)`.

#### [MODIFY] [save_wizard.php](file:///C:/xampp/htdocs/datanestiq/public/api/save_wizard.php)
- Extraer `sector` y `rol` del payload recibido de la aplicación (`journey`, `userContext` o `context_inherited` de Spec 013).
- Escribir `sector` y `rol` en las nuevas columnas de la tabla `leads` al registrar o actualizar el lead.

#### [MODIFY] [chat.php](file:///C:/xampp/htdocs/datanestiq/public/api/chat.php)
- Registrar `$start_time = microtime(true);` al recibir la petición.
- Capturar el proveedor real que respondió en la rotación de failover (`$actual_backend`: `groq` | `dashscope` | `gemini`).
- Calcular `$latency_ms = (int)((microtime(true) - $start_time) * 1000);`.
- Insertar registro de métricas en la tabla `chat_metrics`.

---

### 2. Endpoints de Analítica Agregada

#### [MODIFY] [api.php](file:///C:/xampp/htdocs/datanestiq/public/admin/api.php)
- Agregar endpoint `action=analytics_funnel`: Retorna el conteo y porcentaje por cada uno de los 6 estados del enum real (`nuevo`, `contactado`, `cita_solicitada`, `ganado`, `perdido`, `no_interesado`), junto con la tasa de conversión a citas y tiempo promedio de permanencia por etapa.
- Agregar endpoint `action=analytics_by_dimension`: Retorna el desglose de conversiones agrupando por `l.sector` y `l.rol` con `COALESCE(..., 'no_especificado')`.
- Agregar endpoint `action=analytics_llm_metrics`: Retorna volumen de llamadas, latencia promedio (ms) y tasa de éxito (%) por proveedor LLM.
- Validar protección de autenticación por `auth.php` y enmascaramiento 100% libre de PII.

---

### 3. Interfaz UI del Panel Interno CRM

#### [MODIFY] [index.php](file:///C:/xampp/htdocs/datanestiq/public/admin/index.php)
- Agregar pestaña de navegación "Analítica de Conversión" en el encabezado del panel `/admin/`.
- Construir vista con Vanilla CSS y HTML semántico (0 dependencias de librerías de gráficos pesadas):
  - Tarjetas de KPIs clave (Conversión a Citas %, Clientes Ganados %, Latencia Promedio LLM ms).
  - Embudo visual con barras de avance porcentual por etapa (`nuevo` → `contactado` → `cita_solicitada` → `ganado` → `perdido` → `no_interesado`).
  - Tablas de rendimiento por Sector × Rol y Métricas por Proveedor LLM.
  - Enlace en el modal de detalle del lead para visualizar la secuencia de mensajes de su `session_id`.

---

### 4. Loop `learn` Offline

#### [NEW] [learn_prompt_optimizer.mjs](file:///C:/xampp/htdocs/datanestiq/scripts/learn_prompt_optimizer.mjs)
- Crear script CLI ejecutable en lote.
- Consultar leads con estado `ganado` vs `perdido`/`no_interesado` en `crm.sqlite`.
- Identificar patrones de objeciones resueltas o vacíos de información con la API free-tier.
- Emitir informe y estructura de Pull Request en borrador (PR Draft), sin modificar `chat.php` de manera directa.

---

## Verification Plan

### Automated & CLI Verification
1. **Sintaxis PHP:**
   ```bash
   C:/xampp/php/php.exe -l scripts/init_crm_db.php
   C:/xampp/php/php.exe -l public/api/save_wizard.php
   C:/xampp/php/php.exe -l public/api/chat.php
   C:/xampp/php/php.exe -l public/admin/api.php
   C:/xampp/php/php.exe -l public/admin/index.php
   ```
2. **Compilación Estática:**
   ```bash
   node scripts/build-taxonomy.mjs
   npm run build
   ```
3. **Prueba de Fuga PII / Data Cruda:**
   - Verificar con `grep` que las respuestas agregadas de la API no contengan emails ni números telefónicos.

### Live & Remote Verification
1. **Migración en Producción:**
   - Respaldo previo: `cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak`.
   - Ejecución remota de `/usr/bin/php8.2-cli scripts/init_crm_db.php`.
   - Verificar con `sqlite3` que `leads` conserva todos los registros preexistentes e incluye las columnas `sector` y `rol`.
2. **Despliegue a Producción:**
   - `python scripts/deploy/deploy_ionos.py --confirm`
3. **Pruebas HTTP `curl`:**
   - `curl -I https://app.datanestiq.com/admin/` → 302 a `/admin/login.php`.
   - `curl -I https://app.datanestiq.com/` → 200 OK.
4. **Sincronización de Documentación:**
   - `npm run docs:sync` y actualizar `planes/ESTADO-SPECS.md` marcando la Spec 016 en ✅.
