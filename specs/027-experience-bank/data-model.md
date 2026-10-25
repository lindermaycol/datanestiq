# Modelo de Datos & Contratos API — Spec 027: Experience Bank (Banco de Experiencia Curada 0-LLM)

Este documento especifica el esquema SQL en SQLite (`experience_bank`), los contratos de los endpoints backend y el contrato exacto del Web Worker `public/worker.js`.

---

## 1. Esquema SQLite SSOT (`experience_bank`)

Se añade la tabla `experience_bank` en `scripts/init_crm_db.php`:

```sql
CREATE TABLE IF NOT EXISTS experience_bank (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_redacted TEXT NOT NULL,         -- Consulta del usuario (PII-redactada §6)
    answer TEXT NOT NULL,                    -- Respuesta curada/editada por humano
    cite_url VARCHAR(200) NULL,              -- URL de referencia opcional (/soluciones/*, /sectores/*)
    source_route VARCHAR(20) DEFAULT 'llm',  -- 'llm' | 'graphrag' | 'manual'
    outcome VARCHAR(20) NOT NULL,            -- 'lead' | 'cita' | 'manual' (criterio candidato)
    status VARCHAR(15) DEFAULT 'pendiente',  -- 'pendiente' | 'aprobado' | 'rechazado'
    usage_count INTEGER DEFAULT 0,           -- Veces servida exitosamente 0-LLM
    created_at DATETIME DEFAULT (datetime('now')),
    approved_at DATETIME NULL,
    reviewed_by VARCHAR(50) NULL,
    expires_at DATETIME DEFAULT (datetime('now', '+180 days'))
);

CREATE INDEX IF NOT EXISTS idx_exp_bank_status ON experience_bank(status);
CREATE INDEX IF NOT EXISTS idx_exp_bank_expires ON experience_bank(expires_at);
```

---

## 2. Contrato de Endpoints API Backend

### 2.1 Endpoint Público de Corpus (`GET /api/experience_corpus.php`)
Endpoint de solo-lectura consumido por el chatbot al iniciar para alimentar el Web Worker.
- **Filtro SQL**: `WHERE status = 'aprobado' AND expires_at > datetime('now')`
- **Respuesta JSON**:
```json
{
  "total": 12,
  "experiences": [
    {
      "id": 1,
      "question": "Cómo integran modelos de datos en sector finanzas con regulación local",
      "answer": "En Datanestiq implementamos arquitectura de datos con gobierno estricto y encriptación en reposo para entidades financieras...",
      "cite_url": "/sectores/finanzas"
    }
  ]
}
```
*Nota §2: Si `cite_url` es NULL en la BD, la propiedad `cite_url` se omite o es null sin inventar URLs ficticias.*

### 2.2 Ingesta Candidate Passthrough (`save_wizard.php` & `book_appointment.php`)
Los endpoints de conversión reciben opcionalmente el contexto de la consulta compleja previa registrada en el cliente:
- **Campos HTTP añadidos**: `last_complex_query` (string), `last_answer` (string), `source_route` ('graphrag'|'llm').
- **Lógica de Ingesta Backend**:
  ```php
  if (!empty($last_complex_query) && !empty($last_answer)) {
      $question_clean = redactPii($last_complex_query);
      // Insertar candidato con status = 'pendiente' y outcome = 'lead' | 'cita'
  }
  ```
- **Guard Outcome-Gated**: Si `$last_complex_query` está vacío, no se realiza inserción en `experience_bank`.

### 2.3 Endpoints de Administración (`public/admin/api.php`)

#### a) Listar Candidatos Pendientes (`GET /admin/api.php?action=experience_pending`)
- **Respuesta JSON**:
```json
{
  "total_pending": 3,
  "candidates": [
    {
      "id": 4,
      "question_redacted": "Necesitamos estimación de ROI para migración de data warehouse en retail",
      "answer": "La migración permite optimizar costos operativos hasta un 35%...",
      "cite_url": null,
      "outcome": "lead",
      "source_route": "graphrag",
      "created_at": "2026-08-09 14:10:00"
    }
  ]
}
```

#### b) Aprobar / Editar / Rechazar Candidato (`POST /admin/api.php?action=experience_review`)
- **Payload HTTP**:
```json
{
  "id": 4,
  "status": "aprobado",               // 'aprobado' | 'rechazado'
  "answer": "Respuesta editada...",    // Opcional, permite pulir la respuesta
  "cite_url": "/soluciones/migración", // Opcional
  "expires_days": 180,                 // TTL en días
  "csrf_token": "..."                  // Token CSRF de sesión admin
}
```

---

## 3. Contrato REAL del Web Worker (`public/worker.js`) y Matching 0-LLM

### 3.1 Carga e Indexación en el Cliente (Contrato Real del Worker)
Al cargar el chatbot, `Chatbot.jsx` consume `/api/experience_corpus.php`. Para indexar y buscar utiliza la estructura real del worker:

- **Indexado (Mensaje `type: 'index'`)**:
```typescript
const experienceTexts = experiences.map(e => e.question);
const reqId = `index-exp-${Date.now()}`;

workerRef.current.postMessage({
  type: 'index',
  corpusTexts: experienceTexts,
  id: reqId
});
```
*Nota Técnica*: El algoritmo `computeSignature(corpusTexts)` interno en `public/worker.js` genera un hash único basado en los textos del corpus. Esto permite que los corpus de FAQ, blog y experience coexistan en la caché en memoria del worker sin necesidad de un `type` nuevo.

- **Búsqueda (Mensaje por Defecto con `query` y `corpusTexts`)**:
```typescript
const reqId = `search-exp-${Date.now()}`;

workerRef.current.postMessage({
  query: userQuery,
  corpusTexts: experienceTexts,
  id: reqId
});
```

### 3.2 Criterio de Coincidencia & Umbral Calibrado
- **Umbral de Aceptación**: Similitud Coseno $\ge 0.75$.
- **Respuesta Entregada**: Si `bestScore >= 0.75`, se retorna la respuesta pre-aprobada correspondiente al índice del resultado devuelto.
- **Telemetría 018**: Emite la señal con la convención estándar en `track_event.php`:
  `trackEvent('demand_signal', 'chatbot', JSON.stringify({ resolved_route: 'experience', experience_id: id, score: bestScore }))`.
- **Conteo de Uso**: Se invoca en background el incremento de contador `UPDATE experience_bank SET usage_count = usage_count + 1 WHERE id = :id`.
