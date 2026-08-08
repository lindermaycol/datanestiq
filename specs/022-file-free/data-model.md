# Data Model — Spec 022: File-Free Storage & Persistence Unification

## 1. Esquema DDL SQLite (`secure_leads/crm.sqlite`)

### 1.1 Tabla `conversations` (Conversaciones Redactadas - Monitoreo & Visor)
Almacena el historial de mensajes de interacción del chatbot con PII redactada.

```sql
CREATE TABLE IF NOT EXISTS conversations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('user', 'assistant', 'system')),
    content_redacted TEXT NOT NULL,
    backend_used TEXT DEFAULT 'unknown',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_conversations_session ON conversations(session_id);
CREATE INDEX IF NOT EXISTS idx_conversations_created ON conversations(created_at);
```

**Política de Retención (180 días)**:
```sql
DELETE FROM conversations WHERE created_at < datetime('now', '-180 days');
```

---

### 1.2 Tabla `chat_raw` (Conversaciones Crudas con PII - Privada de Backend)
Almacena los mensajes crudos con PII para consumo exclusivo del pipeline offline `extraer_leads.php`.

```sql
CREATE TABLE IF NOT EXISTS chat_raw (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('user', 'assistant', 'system')),
    content_raw TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_chat_raw_session ON chat_raw(session_id);
CREATE INDEX IF NOT EXISTS idx_chat_raw_created ON chat_raw(created_at);
```

> ⚠️ **REGLA DE SEGURIDAD §6 Y CONSTITUCIÓN §2**: Esta tabla es estrictamente PRIVADA del backend PHP. **No posee ningún endpoint en `public/admin/api.php`** ni en ninguna API pública.

**Política de Retención (180 días)**:
```sql
DELETE FROM chat_raw WHERE created_at < datetime('now', '-180 days');
```

---

### 1.3 Tabla `usage_daily` (Agregado Diario de Consumo vs Cap)
Reemplaza los archivos efímeros `secure_leads/daily_usage_YYYY-MM-DD.json`.

```sql
CREATE TABLE IF NOT EXISTS usage_daily (
    usage_date TEXT PRIMARY KEY, -- Formato YYYY-MM-DD
    total_tokens INTEGER DEFAULT 0,
    prompt_tokens INTEGER DEFAULT 0,
    completion_tokens INTEGER DEFAULT 0,
    request_count INTEGER DEFAULT 0,
    daily_cap INTEGER DEFAULT 500000,
    cap_alert_80_sent INTEGER DEFAULT 0,
    cap_alert_100_sent INTEGER DEFAULT 0,
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_usage_daily_date ON usage_daily(usage_date);
```

---

### 1.4 Tabla `leads_extracted` (Leads Detectados por LLM desde Chat)
Reemplaza el archivo CSV `secure_leads/leads_datanestiq.csv`. Cierra la deuda técnica TD-021-02.

```sql
CREATE TABLE IF NOT EXISTS leads_extracted (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    nombre TEXT,
    email TEXT,
    telefono TEXT,
    intencion TEXT,
    canal_origen TEXT DEFAULT 'llm_extraction',
    created_at TEXT DEFAULT (datetime('now')),
    UNIQUE(session_id, email)
);

CREATE INDEX IF NOT EXISTS idx_leads_extracted_session ON leads_extracted(session_id);
CREATE INDEX IF NOT EXISTS idx_leads_extracted_email ON leads_extracted(email);
```

---

## 2. Endpoints API (`public/admin/api.php`)

### 2.1 Endpoint `conversations` (GET)
- **Propósito**: Recupera el listado paginado de sesiones de conversación con PII redactada.
- **Parámetros**: `limit` (default 20), `offset` (default 0), `session_id` (opcional para ver detalle).
- **Seguridad**: Resguardado tras `requireAuth()`.
- **Estructura Respuesta**:
```json
{
  "conversations": [
    {
      "session_id": "copilot_9f8d7...",
      "last_activity": "2026-08-07 14:32:10",
      "message_count": 6,
      "backend_used": "groq",
      "preview": "Me interesa implementar un Data Lake..."
    }
  ],
  "total": 45
}
```

### 2.2 Endpoint `usage_daily` (GET)
- **Propósito**: Retorna el consumo acumulado de hoy vs el Daily Cap.
- **Seguridad**: Resguardado tras `requireAuth()`.
- **Estructura Respuesta**:
```json
{
  "usage_date": "2026-08-07",
  "total_tokens": 125400,
  "prompt_tokens": 98200,
  "completion_tokens": 27200,
  "request_count": 42,
  "daily_cap": 500000,
  "percentage_used": 25.08,
  "status": "NORMAL"
}
```

### 2.3 Endpoint `leads_detected` (GET - Repunte a Tabla)
- **Propósito**: Consulta `leads_extracted` deduplicando contra `leads` CRM.
- **Modificación**: En lugar de parsear `leads_datanestiq.csv`, ejecuta la query SQL:
```sql
SELECT le.* 
FROM leads_extracted le 
LEFT JOIN leads l ON le.session_id = l.session_id OR le.email = l.email 
WHERE l.id IS NULL 
ORDER BY le.created_at DESC;
```

---

## 3. Mapa de Retiro de Archivos y Estrategia de Migración

### 3.1 Eliminación de Escrituras en Código Fuente
| Archivo de Código | Operación a Eliminar | Reemplazo SQL |
|---|---|---|
| `public/api/chat.php` | `@file_put_contents($redacted_logFile, ...)` | `INSERT INTO conversations ...` |
| `public/api/chat.php` | `@file_put_contents($raw_logFile, ...)` | `INSERT INTO chat_raw ...` |
| `public/api/chat.php` | `@file_put_contents($daily_cap_file, ...)` | `INSERT INTO usage_daily ... ON CONFLICT DO UPDATE` |
| `public/api/chat.php` | `@file_put_contents('usage_metrics.jsonl', ...)` | Retirado (ya está en `chat_metrics`) |
| `public/api/chat.php` | `@file_put_contents('alerts.jsonl', ...)` | Retirado (ya está en `alerts`) |
| `scripts/extraer_leads.php` | Lectura de `chat_raw.jsonl` y escritura en `leads_datanestiq.csv` | Lectura de `chat_raw` y `INSERT INTO leads_extracted` |
| `public/api/save_wizard.php` | `@file_put_contents('leads_wizard.csv', ...)` | Retirado (tabla `leads` es primaria) |

### 3.2 Script CLI de Migración de Archivos a SQLite (`scripts/migrate_file_free_history.php`)
1. Lee `chat_logs.jsonl` y `other_logs.jsonl` &rarr; Inyecta en `conversations`.
2. Lee `chat_raw.jsonl` &rarr; Inyecta en `chat_raw`.
3. Lee `leads_datanestiq.csv` &rarr; Inyecta en `leads_extracted`.
4. Lee `daily_usage_*.json` &rarr; Inyecta en `usage_daily`.
5. Mueve los archivos procesados al directorio de resguardo `secure_leads/_archive/`.
