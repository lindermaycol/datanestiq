# Tech Debt — Spec 014 (CRM Lead Lifecycle)

**Fecha de despliegue:** 2026-08-01 (subdominio `https://app.datanestiq.com`)

## Registro de Riesgo Aceptado y Deudas Técnicas

### 1. Riesgo Aceptado: Panel `/admin/` en Acceso Password-Only (Sin Whitelist de IP Fija)
- **Estado:** Decisión explícita del usuario debido a IP pública dinámica del proveedor de internet del usuario.
- **Configuración en Prod:** `ALLOWED_IPS=*` en `.env` (el default en código `auth.php` se restableció a fail-closed `127.0.0.1,::1`).
- **Riesgo:** Superficie de ataque expuesta a internet en `/admin/`.
- **Controles Compensatorios Activos y Auditados:**
  - Autenticación por hash bcrypt (`ADMIN_PASSWORD_HASH`).
  - Rate-limiting estricto anti-fuerza-bruta: Máximo 5 intentos fallidos por 5 minutos por IP (6º intento bloqueado).
  - Protección CSRF con token bin2hex.
  - Cookies de sesión seguras (`HttpOnly`, `SameSite=Strict`, `Secure` bajo HTTPS).
- **Acción futura recomendada:** Evaluar la adopción de IP fija o VPN de egreso fijo si la infraestructura del usuario lo permite.

### 2. Deudas Menores de Interfaz
- **Filtros Avanzados en UI Admin:** Actualmente el panel `/admin/` lista leads en orden cronológico. Se puede añadir búsqueda por palabra clave en la tabla.
- **Exportación CSV desde Panel:** Se ejecuta vía script CLI `migrate_leads.php` / SQLite. Se puede añadir un botón `Exportar CSV` en la interfaz del panel `/admin/`.
