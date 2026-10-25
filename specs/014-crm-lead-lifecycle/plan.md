# Plan de Implementación: Spec 014 (Ciclo de Vida del Lead)

## 1. Esquema SQLite y Migración
1. Crear el script PHP `scripts/init_crm_db.php` para generar el archivo `secure_leads/crm.sqlite` y definir las tablas (`leads`, `interactions`, `status_history`).
2. Crear un script PHP `scripts/migrate_leads.php` que lea `leads_wizard.csv` y realice inserciones idempotentes en la base de datos (basándose en `session_id`).

## 2. Actualización de Captura en Frontend (`Chatbot.jsx`)
1. Refactorizar el state management en `Chatbot.jsx` para almacenar explícitamente las interacciones/path clicks del usuario en un array de historial.
2. Actualizar el método `confirmLead()` para incluir este array en el cuerpo de la petición POST enviada a `save_wizard.php`.

## 3. Endpoints PHP (Modificación y Creación)
1. Modificar `save_wizard.php` para conectarse a `crm.sqlite` (usando PDO):
   - Insertar el lead en `leads` con estado `nuevo`.
   - Iterar sobre el array de `history` y guardar cada paso en `interactions`.
   - Mantener el apéndice de fallback al CSV por seguridad temporal.
2. Crear un nuevo endpoint interno `admin/api.php` que expondrá operaciones CRUD básicas sobre los leads, validando estrictamente la sesión PHP y la IP.

## 4. Diseño del Panel Interno (`admin/index.php`)
1. **Auth & IP Whitelist:** 
   - `admin/login.php` que valida el password contra `ADMIN_PASSWORD_HASH` cargado de `.env`.
   - Validación del array `ALLOWED_IPS` del `.env`.
2. **Dashboard Vista (Lista):** 
   - Tabla HTML clásica renderizada desde servidor o consumida vía React/Vanilla JS con fetch al `admin/api.php`. Filtros por `status`.
3. **Detail Vista (Lead):** 
   - Mostrar datos de contacto (PII visible, ya que es ambiente seguro interno).
   - Componente de línea de tiempo con las interacciones de `interactions`.
   - Formulario para actualizar el `status`, registrar `next_action` y actualizar `notes` (hace POST a `admin/api.php`).

## 5. Riesgos Considerados
- **Interrupción de captura:** El flujo existente no debe bloquearse si SQLite falla. Usar try-catch y garantizar que, como mínimo, se escriba en el CSV heredado de ser necesario.
- **Doble escritura de sesión:** Garantizar que si un usuario reenvía o interactúa de nuevo en la misma sesión, se hace update al lead o se ignoran duplicados.

## 6. Tareas (Preliminar)
1. `init_crm_db.php` e inicialización de SQLite.
2. Modificar `save_wizard.php` para escribir en SQLite.
3. Modificar `Chatbot.jsx` para recolectar path de interacciones.
4. Desarrollar `admin/login.php` y autenticación por IP/sesión.
5. Desarrollar `admin/api.php` para servir datos al dashboard.
6. Construir interfaz web del CRM interno (`admin/index.php`).
7. Construir y probar `migrate_leads.php`.
