# Plan de Implementación — Spec 020: Inteligencia de Demanda y Journey Reconstructor

Este documento describe el plan de construcción para implementar la Spec 020 e integrar las 5 precisiones de la auditoría de Claude.

## User Review Required

> [!IMPORTANT]
> **Protocolo de Base de Datos Viva (Precisión 3):**
> Antes de ejecutar la migración en producción (IONOS), se debe realizar un backup del archivo SQLite y ejecutar un conteo de control pre/post para garantizar cero pérdida de datos.
> El comando de migración remota usará explícitamente `/usr/bin/php8.2-cli` para evitar conflictos con versiones obsoletas de PHP.

---

## Proposed Changes

### Componente 1: Base de Datos y APIs Backend

#### [MODIFY] [`scripts/init_crm_db.php`](file:///C:/xampp/htdocs/datanestiq/scripts/init_crm_db.php)
- Añadir la creación de la tabla `demand_signals` y sus índices de rendimiento asociados.
- Asegurar que el script sea no destructivo y se ejecute correctamente.

#### [MODIFY] [`public/api/track_event.php`](file:///C:/xampp/htdocs/datanestiq/public/api/track_event.php)
- Soportar el evento de tipo `demand_signal`.
- Redactar PII del campo `query_redacted` usando la función `redactPii()` existente.
- Insertar los campos desestructurados directamente en la tabla `demand_signals`.

#### [MODIFY] [`public/admin/api.php`](file:///C:/xampp/htdocs/datanestiq/public/admin/api.php)
- Limpieza automática de la tabla `demand_signals` con la política de retención de 180 días en cada consulta.
- Crear endpoint `action=demand_signals` con guard de $N \ge 20$, agregación de ejemplos limitada al **top 5 más recientes** (evitando GROUP_CONCAT ilimitado / Precisión 4) y marcas de hipótesis de brecha.
- Crear endpoint `action=leakage` con guard de $N \ge 10$, porcentaje de fuga por servicio y etiqueta de atribución aproximada (Precisión 5).
- Crear endpoint `action=lead_journey` que unifique por `session_id`: `interaction_events`, `demand_signals`, `status_history` y la tabla **`interactions`** unida por `lead_id` (Precisión 1).

---

### Componente 2: Web Worker Multi-Corpus

#### [MODIFY] [`public/worker.js`](file:///C:/xampp/htdocs/datanestiq/public/worker.js)
- Reemplazar la variable `cachedCorpusData` por un mapa multicanal `cachedCorpora = {}` indexado por la firma del corpus.
- Modificar `indexCorpus` y la lógica de búsqueda para almacenar y recuperar los embeddings de intenciones, FAQ y catálogo de servicios simultáneamente, evitando re-embeddings repetitivos por consulta (Precisión 2).

---

### Componente 3: Chatbot y Admin Panel

#### [MODIFY] [`src/components/islands/Chatbot.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- Cargar y clasificar las consultas contra el catálogo de la taxonomía (`src/data/taxonomyCorpus.json`) usando el worker.
- Calcular `offered = score >= 0.60` y emitir el evento `demand_signal` a `track_event.php`.

#### [MODIFY] [`public/admin/index.php`](file:///C:/xampp/htdocs/datanestiq/public/admin/index.php)
- Añadir la pestaña y los componentes de visualización para Inteligencia de Demanda y visualizador de Journey Reconstructor con Vanilla CSS/HTML.

---

### Componente 4: Registro y Documentación

#### [MODIFY] [`planes/ESTADO-SPECS.md`](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md) / [`src/data/specsStatus.json`](file:///C:/xampp/htdocs/datanestiq/src/data/specsStatus.json)
- Registrar la especificación 020 con estatus `LIVE` una vez completado el build.

---

## Verification Plan

### Migración de Base de Datos y Control Pre/Post (Precisión 3)
1. **Backup Previo:**
   `cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak`
2. **Conteo Pre-Migración:**
   Registrar la cantidad de leads existentes antes del script:
   `sqlite3 secure_leads/crm.sqlite "SELECT COUNT(*) FROM leads;"`
3. **Ejecución del Script:**
   `php scripts/init_crm_db.php`
4. **Conteo Post-Migración:**
   Verificar que la cantidad de leads se mantiene idéntica y la tabla `demand_signals` existe:
   `sqlite3 secure_leads/crm.sqlite "SELECT COUNT(*) FROM leads;"`
   `sqlite3 secure_leads/crm.sqlite ".schema demand_signals"`

### Pruebas del Journey Reconstructor
- Interactuar con el chatbot (ingresar sector/rol, ingresar pregunta que coincida con FAQ/objeción).
- Convertir completando el formulario del lead.
- Ir al panel `/admin/` (detrás de `auth.php`), ver el lead y corroborar que el Timeline muestra:
  - Las búsquedas y chips iniciales (`interaction_events`).
  - Las objeciones matcheadas de la taxonomía (`demand_signals`).
  - Los contactos y notas manuales (`interactions` / Precisión 1).
  - Los cambios de estado (`status_history`).

### Pruebas de Latencia y Cache de Xenova
- Enviar 3 mensajes libres consecutivos en desarrollo y verificar en la consola de depuración que el indexado (`indexCorpus`) ocurre **exactamente 1 vez por corpus** (intenciones, FAQ y catálogo) y las consultas posteriores toman $< 50ms$ al reusar `cachedCorpora` (Precisión 2).
