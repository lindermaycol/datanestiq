# Spec 015: Agendador de Citas

## 1. Objetivo
Implementar un sistema de agendamiento donde los prospectos puedan reservar una cita (diagnóstico o reunión). Estas reservas deben fluir hacia el CRM creado en la Spec 014, convirtiéndose en un atributo y estado del lead correspondiente.

## 2. Modelo de Datos (Extensión de Spec 014)
La persistencia usa la MISMA base de datos SQLite `secure_leads/crm.sqlite`.

### Nueva Entidad:
1. **appointments**
   - `id` (PK, INTEGER AUTOINCREMENT)
   - `lead_id` (FK a leads.id)
   - `session_id` (VARCHAR)
   - `requested_date` (DATETIME) - Formato estricto UTC o América/Lima.
   - `type` (VARCHAR) - Ej. 'diagnostico', 'reunion'.
   - `status` (VARCHAR) - Estados: 'solicitada', 'confirmada', 'cancelada'.
   - `notes` (TEXT) - Para notas del administrador sobre la cita.
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)

## 3. Flujo de Reserva y Disponibilidad
- **Dónde:** El componente de agendamiento de citas se presentará (Progressive Enhancement) en el flujo del Chatbot y/o tras el form de lead, únicamente DESPUÉS de haber aportado valor consultivo, o en landings de venta directa (BCE).
- **Control de doble-booking:** Antes de persistir una cita `solicitada`, la API revisará en `appointments` que no exista una cita en estado `solicitada` o `confirmada` para esa misma franja horaria.
- **Zona Horaria:** Toda validación e interfaz asume que los slots se exponen y almacenan base al huso horario de Perú (`America/Lima`, UTC-5).

## 4. Confirmación Manual en el CRM
- En el panel interno de la Spec 014, al visualizar un Lead que tiene un Appointment asociado, aparecerá la información de la cita.
- El administrador deberá cambiar el estado de la cita a `confirmada` de manera manual.
- Esto NO disparará correos electrónicos (fuera de alcance en esta Spec).

## 5. Seguridad y PII
- Al igual que en la Spec 014, todo registro es PII y se persiste bajo la misma BD protegida.
- La configuración de disponibilidad horaria (bloqueo de días/horas) se realizará detrás del auth del CRM.

## 6. Criterios de Aceptación
1. Integridad referencial exitosa entre citas y leads existentes.
2. Evitación efectiva del doble-booking en slots idénticos.
3. El frontend de selección de cita es intuitivo, accesible y deja en claro al prospecto que la cita es una "solicitud" y requiere confirmación.
4. El panel interno permite actualizar el estado de la cita.
5. Zona horaria de `America/Lima` se aplica uniformemente, impidiendo desfases de agendamiento.

## 7. Exclusiones Importantes
- Sistema automatizado de correos de confirmación y recordatorios.
- Sincronización directa bidireccional con Google Calendar o Outlook O365. (Podrá hacerse a futuro o resolverse visualizando un iCal feed básico).
