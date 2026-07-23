# Plan de Implementación Consolidado: Specs 014 y 015

He revisado los requerimientos de la Especificación 014 (Mini-CRM Interno) y la Especificación 015 (Agendador de Citas) y he generado los artefactos documentales solicitados (`spec.md` y `plan.md`) para ambas. No se ha implementado código funcional aún.

## Documentos Generados

He creado los siguientes 4 archivos en el workspace, que dictaminan la arquitectura a construir:

### [Spec 014 (Ciclo de Vida de Lead)](file:///C:/xampp/htdocs/datanestiq/specs/014-crm-lead-lifecycle)
- **[spec.md](file:///C:/xampp/htdocs/datanestiq/specs/014-crm-lead-lifecycle/spec.md):** Define las entidades `leads`, `interactions`, y `status_history`. Establece el ruteo hacia el panel de administración bajo autenticación obligatoria y lista blanca de IP, garantizando que el 0-LLM y el `save_wizard.php` sigan operativos de forma transparente.
- **[plan.md](file:///C:/xampp/htdocs/datanestiq/specs/014-crm-lead-lifecycle/plan.md):** Traza las tareas para generar `scripts/init_crm_db.php`, `migrate_leads.php`, el frontend interno (`admin/index.php`, `admin/login.php`, `admin/api.php`) y la actualización del endpoint público para insertar datos en SQLite (bajo el directorio seguro `secure_leads/`).

### [Spec 015 (Agendador de Citas)](file:///C:/xampp/htdocs/datanestiq/specs/015-agendador-citas)
- **[spec.md](file:///C:/xampp/htdocs/datanestiq/specs/015-agendador-citas/spec.md):** Añade la tabla `appointments` como extensión de la Spec 014, ligada de 1 a N con la tabla `leads`. Aborda explícitamente que la zona horaria debe bloquearse en `America/Lima` y define el control anti doble-booking a nivel de la API sin enviar correos automatizados.
- **[plan.md](file:///C:/xampp/htdocs/datanestiq/specs/015-agendador-citas/plan.md):** Define la creación de `<AppointmentPicker />` para la UI del prospecto, los endpoints GET/POST en `/api/` y la vista/controles adicionales dentro del panel administrativo (CRM Spec 014) para que el administrador pueda consultar y marcar citas como `confirmada`.

## User Review Required

Revisa los archivos en el árbol de carpetas del proyecto. Si estás de acuerdo con la estrategia de modelado de datos compartida y la delimitación de ambas specs, apruébalo para que Claude audite estos documentos antes de proceder a la fase de implementación o desarrollo de código.

## Open Questions

- **Autenticación (014):** Se planea un login basado en sesión PHP básico verificando un hash de contraseña en las variables de entorno. ¿Estás de acuerdo con este enfoque o prefieres otra alternativa para el panel interno?
- **Captura de Contactos (015):** ¿El agendamiento requerirá que el prospecto rellene el email en el agendador si no ha sido capturado previamente? (Asumo que sí para evitar bookings anónimos, a menos que provengan de un contexto donde ya confirmaron su lead).
