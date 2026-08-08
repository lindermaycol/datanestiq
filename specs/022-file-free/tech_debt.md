# Deuda Técnica y Gestión de Riesgos — Spec 022: File-Free Storage

## 1. Registro de Deuda Técnica

### TD-022-01: Migración de Archivos Históricos de Tamaño Significativo
- **Riesgo**: Archivos `.jsonl` de logs antiguos con gran volumen podrían generar timeouts en la ejecución del script de migración por CLI si no se procesan en lotes (chunking).
- **Mitigación**: `scripts/migrate_file_free_history.php` procesa los archivos línea por línea mediante buffers (`fgets`) e inserta dentro de transacciones SQLite en lotes de 1,000 registros.

### TD-022-02: Manejo de Excepciones en Fail-Safe en `chat.php`
- **Riesgo**: Si SQLite se bloquea por concurrencia extrema (bloqueo de base de datos en escritura en disco HDD/SSD), el try/catch capturará el error sin romper la respuesta del chatbot, pero el mensaje se perderá si no hay fallback a archivo.
- **Mitigación**: Aceptación explícita de riesgo por el usuario (privilegiar SQLite como fuente única sin archivos caóticos). Se agrega registro en `sys_get_temp_dir() . '/sqlite_error.log'` solo en caso de falla crítica de PDO para trazabilidad.

### TD-022-03: Cierre de Deuda Técnica TD-021-02 (`leads_extracted`)
- **Estado**: **Cerrado por Spec 022**. Los leads detectados por LLM ahora residen formalmente en la tabla `leads_extracted` con clave única `(session_id, email)`, cerrando la deuda de dependencia de archivos CSV.

---

## 2. Matriz de Seguridad y Privacidad (§2 y §6)

| Componente | Nivel de Exposición | Salvaguarda de Seguridad |
|---|---|---|
| Tabla `conversations` | Solo Administradores (`auth.php`) | Todo texto PII ha sido redactado antes del insert. |
| Tabla `chat_raw` | **Estrictamente Privado Backend (Cero Endpoints)** | Sin visores en panel, sin API en `api.php`. Solo accesible vía PHP CLI local/servidor. |
| Tabla `usage_daily` | Solo Administradores (`auth.php`) | Solo métricas agregadas de tokens y contadores de consumo sin PII. |
| Tabla `leads_extracted` | Solo Administradores (`auth.php`) | Datos desredactados para seguimiento comercial, resguardados tras IP Whitelist / Bcrypt auth. |
