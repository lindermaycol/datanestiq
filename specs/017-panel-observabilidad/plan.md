# Plan de Implementación — Spec 017: Panel de Observabilidad Interna (Ops & Specs)

Este documento establece las fases de desarrollo técnico y los gates humanos de despliegue para la **Spec 017**, una vez recibida la auditoría y luz verde de Claude Code.

---

## User Review Required & Guardarraíles

> [!IMPORTANT]
> **Sin Cambios de Runtime durante la Fase de Diseño:**  
> Este artefacto pertenece a la fase de **DISEÑO SDD**. Ningún archivo ejecutable o script de producción será modificado hasta obtener la aprobación formal de la auditoría.

> [!IMPORTANT]
> **SSOT de Specs y Cero Métricas Ficticias (§2):**  
> `src/data/specsStatus.json` actuará como la fuente única de verdad para el estado de las specs. Si una spec tiene deuda abierta o está en desarrollo, la UI reflejará honestamente dicho estado sin simular indicadores de aprobación falsos.

---

## Detalle de Fases de Implementación (FUTURO BUILD)

### FASE 1 · SSOT de Specs y Validador Build-time
1. **SSOT Data:** Crear `src/data/specsStatus.json` registrando las 17+ especificaciones con su estado real.
2. **Script de Verificación:** Crear `scripts/build-specs-status.mjs` que valida la integridad de `specsStatus.json` en tiempo de compilación y emite la salida en `dist/api/specs-status.json`.

### FASE 2 · Endpoints Ops en API Interna (`public/admin/api.php`)
1. **Endpoint `action=ops_telemetry`:** Retorna métricas de SLA global, latencias p50/p95 y tendencias diarias desde `chat_metrics`.
2. **Endpoint `action=ops_specs_status`:** Retorna el catálogo estructurado de specs desde `specsStatus.json`.
3. **Seguridad:** Requerir `auth.php` y garantizar cero filtración de PII (§6).

### FASE 3 · Interfaz UI de Observabilidad en Panel `/admin/`
1. **Navegación:** Incorporar la pestaña "Observabilidad Ops" en la interfaz `/admin/index.php`.
2. **Componentes Vanilla CSS:**
   - Tarjetas de SLA, Latencia p50/p95 e Invocaciones totales.
   - Tabla de Estado del Portafolio de Specs (con insignias por estado).
   - Tabla de Tendencia de Errores y Salud de Proveedores LLM.

### FASE 4 · Build, Pruebas y Despliegue Gateado
1. **Validación de Sintaxis:** `php -l` en `public/admin/api.php` y `public/admin/index.php`.
2. **Build Estático:** `node scripts/build-taxonomy.mjs` y `npm run build`.
3. **Despliegue a Producción:** `python scripts/deploy/deploy_ionos.py` (dry-run → vistos buenos → `--confirm`).
4. **Doc-sync:** Actualizar `planes/ESTADO-SPECS.md` marcando la Spec 017 como ✅.
