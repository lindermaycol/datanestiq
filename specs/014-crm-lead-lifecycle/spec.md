# Spec 014: Ciclo de Vida del Lead (Mini-CRM Interno)

## 1. Objetivo y Negocio
El objetivo es transformar la captura de leads actual (archivos CSV y JSONL planos) en un sistema estructurado de CRM mínimo viable basado en SQLite. Esto permitirá a Datanestiq no solo recolectar leads, sino gestionar su ciclo de vida, visualizar el contexto completo (botones clickeados en flujo guiado o preguntas de texto libre) y accionar el pipeline de ventas a través de un panel de administración interno y seguro.

## 2. Modelo de Datos (SQLite)
La persistencia residirá en una base de datos SQLite `secure_leads/crm.sqlite`, fuera del webroot.

### 2.1 Entidades
1. **leads**
   - `id` (PK, INTEGER AUTOINCREMENT)
   - `session_id` (VARCHAR)
   - `email` (VARCHAR, enmascarado en logs públicos)
   - `telefono` (VARCHAR)
   - `organizacion` (VARCHAR)
   - `reto` (TEXT)
   - `stack` (TEXT)
   - `status` (VARCHAR) - Estado actual
   - `next_action` (TEXT) - Siguiente acción planificada
   - `notes` (TEXT) - Notas generales
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)

2. **interactions**
   - `id` (PK, INTEGER AUTOINCREMENT)
   - `lead_id` (FK a leads.id)
   - `interaction_type` (VARCHAR) - Ej. 'flow_step', 'chat_message', 'roi_calculator'
   - `content` (TEXT) - Detalles de la interacción (ej. JSON con sector/rol, o texto de la pregunta)
   - `created_at` (DATETIME)

3. **status_history**
   - `id` (PK, INTEGER AUTOINCREMENT)
   - `lead_id` (FK a leads.id)
   - `old_status` (VARCHAR)
   - `new_status` (VARCHAR)
   - `changed_by` (VARCHAR) - Usuario del panel que hizo el cambio
   - `created_at` (DATETIME)

## 3. Estados y Transiciones
Los estados permitidos del lead serán:
- `nuevo`: Creado vía form/chatbot.
- `contactado`: Un arquitecto inició el contacto.
- `cita/diagnóstico solicitado`: El prospecto agendó/solicitó reunión (Se integra con Spec 015).
- `ganado`: Convertido en cliente.
- `perdido`: Oportunidad cerrada sin éxito.
- `no-interesado`: Descartado temprano.

## 4. Requisitos del Panel
- Interfaz interna para listar, filtrar y buscar leads.
- Vista de detalle: Mostrar historial completo de la conversación y botones presionados.
- Controles para editar `status`, `next_action` y `notes`.
- Mecanismo de autenticación mediante sesión PHP y contraseña almacenada de forma segura (hash en `.env`).
- Lista blanca de IPs para acceder al panel.

## 5. Requisitos de Captura
- Modificar `Chatbot.jsx` y estimador de ROI para adjuntar el contexto del recorrido al payload que se envía a `save_wizard.php`.
- Mantener la integridad de la regla 0-LLM: ningún fetch extra ocurre al hacer clic en opciones, sólo se envía toda la secuencia acumulada al confirmar el envío del formulario.

## 6. Seguridad y PII
- `crm.sqlite` estará estrictamente bajo `/secure_leads/` con archivo `.htaccess` (deny from all) o equivalente si es apache, y explícitamente excluido en `.gitignore`.
- Las credenciales del panel se manejarán por variables de entorno y no estarán hardcodeadas en los scripts PHP.

## 7. Criterios de Aceptación
1. Captura de lead guarda correctamente los datos y su historial de interacciones en SQLite.
2. `save_wizard.php` sigue respondiendo transparente y no se interrumpe la actual generación del CSV para no romper compatibilidad temporal.
3. El Panel exige Login y sólo admite acceso desde IPs autorizadas.
4. Se pueden transicionar leads entre estados desde el panel.
5. Script de migración importa los datos históricos desde `leads_wizard.csv` hacia `crm.sqlite`.

## 8. Exclusiones
- Funciones de agendamiento de citas y manejo de calendarios o slots quedan delegadas a la Spec 015.
