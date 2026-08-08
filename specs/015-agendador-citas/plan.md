# Plan de Implementación: Spec 015 (Agendador de Citas)

## 1. Modificación de Esquema SQLite
1. Crear el script PHP `scripts/update_crm_db_015.php` que inyecte la creación de la tabla `appointments` dentro de `secure_leads/crm.sqlite`.
2. Crear/modificar (si se requiere) una tabla de configuración `availability_config` para almacenar horas de trabajo y días bloqueados, o manejar la configuración por JSON/env.

## 2. API de Disponibilidad y Reserva (`book_appointment.php`)
1. **Endpoint `GET public/api/availability.php`:**
   - Lee el huso horario `America/Lima`.
   - Consulta `appointments` buscando franjas ya ocupadas (estados `solicitada`, `confirmada`).
   - Devuelve un JSON con los slots libres de los próximos X días.
2. **Endpoint `POST public/api/book_appointment.php`:**
   - Recibe `session_id`, `email`, `requested_date`.
   - Identifica al Lead correspondiente (o exige que haya sido capturado antes vía `save_wizard`).
   - Bloquea (transaction-safe) verificando doble-booking.
   - Inserta el registro en `appointments` con estado `solicitada`.
   - Actualiza la tabla `leads` seteando el status a `cita/diagnóstico solicitado`.

## 3. Frontend: Componente `<AppointmentPicker />`
1. Componente React que obtiene los slots vía GET `/availability`.
2. Presenta un calendario interactivo y una grilla de horas disponibles.
3. Dispara la petición POST a `/book_appointment` e indica claramente al usuario "Solicitud Enviada, un arquitecto te confirmará a la brevedad".
4. Integrarlo condicionalmente en `Chatbot.jsx` luego del state de `done` (Lead exitoso), permitiendo el upsell de la cita, o en la landing de `business-case`.

## 4. Actualización del Panel Interno (CRM Spec 014)
1. Modificar `admin/api.php` para realizar un JOIN con `appointments` al consultar los leads.
2. En la vista de detalle de Lead, incluir una sección de Cita que permita:
   - Visualizar la fecha y hora.
   - Cambiar estado (`solicitada` -> `confirmada` o `cancelada`).
3. (Opcional) Una vista tipo Calendario en el panel que liste todas las citas confirmadas y pendientes por orden cronológico.

## 5. Riesgos Considerados
- **Concurrencia SQLite:** Prevenir "database is locked" errors limitando el timeout, usar PRAGMA journal_mode=WAL en la creación si SQLite lo soporta.
- **Race conditions:** Dos prospectos eligiendo el mismo slot a la vez. Controlable mediante una validación final y un mensaje amigable indicando que el slot expiró.

## 6. Tareas (Preliminar)
1. Ejecutar alter table/crear `appointments`.
2. Crear `public/api/availability.php`.
3. Crear `public/api/book_appointment.php` con prevención de doble-booking.
4. Desarrollar componente UI `<AppointmentPicker />`.
5. Integrar el Appointment Picker en el flujo de finalización del Chatbot.
6. Actualizar el CRM Panel (`admin/index.php`) para exponer y editar la información y estados de las citas.
