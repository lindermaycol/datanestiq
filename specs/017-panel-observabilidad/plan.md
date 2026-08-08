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

### FASE 1 · SSOT de Specs y Validador Build-time Antidrift
1. **SSOT Data:** Crear `src/data/specsStatus.json` registrando las 17+ especificaciones con su estado real (`LIVE`, `DESIGNED`, `SUPERSEDED`, `IN_PROGRESS`).
2. **Script de Verificación Antidrift:** Crear `scripts/build-specs-status.mjs` que valida la integridad de `specsStatus.json` en tiempo de compilación y verifica que cada estado coincida exactamente con la declaración de `planes/ESTADO-SPECS.md`. Si se detecta *drift*, el script rompe la compilación (`process.exit(1)`).
3. **Generación de Artefacto:** Emite la salida limpia en `dist/api/specs-status.json`.

### FASE 2 · Endpoints Ops en API Interna (`public/admin/api.php`)
1. **Endpoint `action=ops_telemetry`:** Ejecuta las consultas SQL con CTE y funciones de ventana (`ROW_NUMBER()`) sobre la población `success = 1` para calcular SLA global, latencias p50/p95 (con guardarraíl de muestra `< 10`) y tendencias diarias.
2. **Endpoint `action=ops_specs_status`:** Retorna el catálogo estructurado de specs desde `specsStatus.json`.
3. **Seguridad:** Requerir `auth.php` en cada acción y garantizar cero filtración de PII (§6).

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
