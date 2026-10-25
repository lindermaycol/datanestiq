# Plan de Implementación — Spec 027: Experience Bank (Banco de Experiencia Curada 0-LLM)

Este documento guiará la fase de construcción de la Spec 027 una vez aprobada la fase de diseño.

---

## 1. Arquitectura y Flujo de Datos

```text
[Chatbot.jsx (last_complex_query)] ───(Outcome: Lead/Cita)───► [save_wizard.php / book_appointment.php]
                                                                        │
                                                                   (redactPii)
                                                                        │
                                                                        ▼
                                                [SQLite: experience_bank (status='pendiente')]
                                                                        │
                                                                        ▼
                                                        [Panel /admin/ (Curación Humana)]
                                                                        │
                                                                (status='aprobado')
                                                                        │
                                                                        ▼
[Chatbot.jsx] ◄─── (0-LLM match >= 0.75) ◄─── [public/worker.js] ◄─── [/api/experience_corpus.php]
```

---

## 2. Fases de Trabajo en Construcción (Build)

### Fase 1: Creación de la Tabla `experience_bank` (`scripts/init_crm_db.php`)
- Añadir la sentencia `CREATE TABLE IF NOT EXISTS experience_bank` con columna `cite_url VARCHAR(200) NULL` e índices en `status` y `expires_at`.
- Ejecutar la inicialización para actualizar el esquema de SQLite en local y producción.

### Fase 2: Extracción de Helper `redactPii()` e Ingesta Candidate Passthrough
- **Extracción de Helper Compartido**: Extraer la función `redactPii()` a un include compartido backend `public/api/lib/pii.php` para que esté disponible en `track_event.php`, `save_wizard.php` y `book_appointment.php`.
- **Client Passthrough**: Modificar `Chatbot.jsx` para almacenar en el estado de sesión `last_complex_query`, `last_answer` y `source_route` al completarse una respuesta de GraphRAG/LLM.
- **Backend Ingesta**: Modificar `save_wizard.php` y `book_appointment.php` para incluir `require_once __DIR__ . '/lib/pii.php'`. Si reciben `last_complex_query` y `last_answer`, sanitizan la pregunta con `redactPii()` e insertan un candidato en `experience_bank` con `status = 'pendiente'`. Si `$last_complex_query` está vacío, omiten la inserción.

### Fase 3: Endpoints Backend y Endpoint Público de Corpus
- Crear `/public/api/experience_corpus.php` (solo-lectura, retorna únicamente registros `aprobados` y vigentes con `status = 'aprobado' AND expires_at > NOW()`).
- Implementar las acciones `experience_pending` y `experience_review` en `public/admin/api.php` con validación de CSRF token.

### Fase 4: Integración 0-LLM en el Router del Chatbot (`src/components/islands/Chatbot.jsx`)
- En `Chatbot.jsx`, consumir `/api/experience_corpus.php` al montar la isla.
- Indexar el corpus de experiencias mediante `workerRef.current.postMessage({ type: 'index', corpusTexts: experienceTexts, id: reqId })`.
- Insertar la verificación del Experience Bank **después** del fallo de `matchFAQ` y **antes** de llamar a `_callLLM`.
- Consultar el worker pasando `{ query: userQuery, corpusTexts: experienceTexts, id: reqId }`.
- Si la similitud coseno es $\ge 0.75$, entregar la respuesta curada 0-LLM, registrar telemetría `demand_signal` e incrementar `usage_count`.

### Fase 5: Módulo de Curación en Panel Admin (`public/admin/index.php`)
- Crear los contenedores HTML explícitos `#experience-bank-grid` y `#experience-pending-table` en la vista `view-behavior`.
- Implementar la función JavaScript `loadExperienceBank()` reusando el helper defensivo `setHTML(id, html)`.

### Fase 6: Build-Gate Antidrift y Despliegue Auto-Verificado
- Registrar `027` como `COMPLETED` en `planes/ESTADO-SPECS.md` y `src/data/specsStatus.json`.
- Añadir el marcador de auto-verificación `experiences` para `experience_corpus` en `scripts/deploy/deploy_ionos.py`.
- Ejecutar `node scripts/build-specs-status.mjs` (Build-Gate Antidrift 100%).
- Compilar con `npm run build` y desplegar a IONOS con `python scripts/deploy/deploy_ionos.py --confirm` validando el **JS Syntax Gate (`node --check`)**.

---

## 3. Plan de Verificación y Pruebas en Vivo

### 3.1 Pruebas de Sintaxis y Linting
- Validar sintaxis PHP con `php -l public/api/experience_corpus.php`, `php -l public/api/lib/pii.php` y `php -l public/admin/api.php`.
- Validar sintaxis de scripts de panel con `verify_inline_js_syntax()` (`node --check`).

### 3.2 Pruebas en Vivo de Endpoints y Curación
- Invocación de `GET /api/experience_corpus.php`: verificar que retorne únicamente registros aprobados.
- Invocación de `POST /admin/api.php?action=experience_review`: probar la aprobación y edición de un candidato.

### 3.3 QA de Click-Through en Panel
- Navegar en `/admin/` a la sub-sección de curación del Experience Bank, probar el botón de aprobación/rechazo y confirmar consola limpia de errores.
