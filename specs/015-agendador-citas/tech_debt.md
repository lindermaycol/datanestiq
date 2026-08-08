# Tech Debt — Spec 015 (Agendador de Citas)

**Fecha de despliegue:** 2026-08-01 (subdominio `https://app.datanestiq.com`)

## Deudas Técnicas Menores (Post-Deploy)
1. **Notificaciones por Email / WhatsApp:** La confirmación de citas se realiza manualmente desde el panel `/admin/`. No se envían correos automáticos (decisión explícita de diseño sin servicio SMTP en esta fase).
2. **Sincronización con Google Calendar / Outlook:** Las citas quedan registradas en `crm.sqlite`. La exportación a iCal/ICS puede implementarse en una fase futura.
