# Tech Debt — Spec 014 (CRM Lead Lifecycle)

**Fecha de despliegue:** 2026-08-01 (subdominio `https://app.datanestiq.com`)

## Deudas Técnicas Menores (Post-Deploy)
1. **Filtros Avanzados en UI Admin:** Actualmente el panel `/admin/` lista leads con orden cronológico y estado básico. Se puede añadir búsqueda por palabra clave (nombre/organización) en la tabla sin librerías externas.
2. **Exportación CSV desde Panel:** La exportación a CSV se realiza vía script CLI `migrate_leads.php` / SQLite directo. Se puede añadir un botón `Exportar CSV` en la interfaz del panel `/admin/`.
