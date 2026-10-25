---
title: "Inicialización de la Base de Datos CRM (SQLite)"
description: "Documentación técnica del script `init_crm_db.php`: creación idempotente del esquema SQLite para el mini-CRM, incluyendo tablas, migraciones incrementales (S"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["crm","sqlite","php","database-initialization","migrations","spec-014","spec-015","spec-016","spec-018","spec-020"]
seoScore: 100
---
## 📁 Descripción General

El script `scripts/init_crm_db.php` es el **punto de entrada único para la inicialización y actualización idempotente** del esquema de base de datos SQLite utilizada por el mini-CRM. Está diseñado para ejecutarse manualmente (`php scripts/init_crm_db.php`) o como parte de pipelines de despliegue.

✅ **Ubicación de la BD**: `secure_leads/crm.sqlite` — ubicada *fuera del webroot* para garantizar seguridad.
✅ **Modo WAL activado**: Mejora concurrencia y durabilidad.
✅ **Claves foráneas habilitadas**: Garantiza integridad referencial con `ON DELETE CASCADE`.

---

## 🧱 Estructura de Tablas

### `leads` — Perfil central del lead (Spec 014)
| Campo | Tipo | Valor por defecto | Notas |
|--------|------|-------------------|-------|
| `id` | `INTEGER PRIMARY KEY AUTOINCREMENT` | — | Clave primaria |
| `session_id` | `VARCHAR(100) UNIQUE` | — | Vinculación con sesiones de chat |
| `email`, `telefono`, `nombre`, `organizacion` | `VARCHAR` | `''` | Datos de contacto y contexto |
| `reto`, `stack`, `next_action`, `notes` | `TEXT` | `''` | Campos flexibles para captura cualitativa |
| `score` | `INTEGER` | `0` | Puntuación dinámica (ej. scoring LLM) |
| `source`, `status` | `VARCHAR(50)` | `'chatbot'`, `'nuevo'` | Origen y estado de ciclo de vida |
| `created_at`, `updated_at` | `DATETIME` | `datetime('now')` | Timestamps automáticos |
| `sector`, `rol` | `VARCHAR(100)` | `''` | **Agregados en Spec 016** (migración idempotente) |

> 🔁 **Migración Spec 016**: Se verifica la existencia de `sector` y `rol` antes de `ALTER TABLE`. Idempotente: no falla si ya existen.

---

### `interactions` — Registro de interacciones con el lead (Spec 014)
- Relación `1:N` con `leads(id)` → `lead_id` (con `ON DELETE CASCADE`).
- `interaction_type`: ej. `'greeting'`, `'question'`, `'followup'`.
- `content`: texto completo o resumen de la interacción.

---

### `status_history` — Auditoría de cambios de estado (Spec 014)
- Registra transiciones de `status` en `leads`.
- `changed_by`: `'system'`, `'agent'`, `'api'`, etc.
- Soporta trazabilidad completa y análisis de flujos.

---

### `appointments` — Gestión de citas (Spec 015)
| Campo | Tipo | Valor por defecto | Notas |
|--------|------|-------------------|-------|
| `requested_date` | `DATETIME NOT NULL` | — | Fecha/hora solicitada (zona horaria: `America/Lima`) |
| `duration_minutes` | `INTEGER` | `30` | Duración típica en minutos |
| `type` | `VARCHAR(50)` | `'diagnostico'` | Tipos: `'diagnostico'`, `'demo'`, `'cierre'` |
| `status` | `VARCHAR(50)` | `'solicitada'` | Valores posibles: `'solicitada'`, `'confirmada'`, `'cancelada'`, `'realizada'` |
| `session_id` | `VARCHAR(100)` | — | Para vincular con sesión original del lead |

---

### `availability_config` — Configuración de horarios disponibles (Spec 015)
- Define ventanas de disponibilidad por día de la semana (`day_of_week`: 1=Lunes, 7=Domingo).
- `start_time`/`end_time`: formato `HH:MM` (ej. `'09:00'`).
- `slot_duration_minutes`: duración mínima de cada bloque (ej. `30`).
- `is_active`: `1` = disponible, `0` = deshabilitado temporalmente.

> ✅ **Configuración inicial**: Al primer run, inserta automáticamente Lunes a Viernes (1–5), `09:00–18:00`, `30 min`, `is_active=1`.

---

### `blocked_dates` — Fechas bloqueadas (Spec 015)
- `blocked_date`: fecha única (`DATE NOT NULL UNIQUE`).
- `reason`: explicación breve (ej. `'feriado'`, `'capacitacion'`).

---

### `chat_metrics` — Métricas técnicas de interacción (Spec 016)
| Campo | Tipo | Notas |
|--------|------|-------|
| `backend_used` | `VARCHAR(50)` | `'ollama'`, `'openai'`, `'local-llm'`, etc. |
| `latency_ms` | `INTEGER` | Latencia total end-to-end (ms) |
| `success` | `INTEGER` | `1` = éxito, `0` = fallo |
| `tokens_est` | `INTEGER` | Estimación de tokens procesados |

---

### `interaction_events` — Micro-interacciones analíticas (Spec 018)
- Captura eventos granulares: clics, scrolls, tiempos de espera, selecciones.
- `event_type`: `'click'`, `'hover'`, `'input_focus'`, `'copy'`, etc.
- `event_target`: identificador del elemento (ej. `'btn-schedule'`, `'faq-item-3'`).
- `event_value`: valor capturado (ej. `'si'`, `'30min'`, `'copied'`).

---

### `demand_signals` — Inteligencia de demanda (Spec 020)
| Campo | Tipo | Notas |
|--------|------|-------|
| `query_redacted` | `TEXT NOT NULL` | Consulta anonimizada (sin PII) |
| `intent` | `VARCHAR(50) NOT NULL` | Ej. `'cotizar'`, `'soporte'`, `'integrar'` |
| `confidence` | `REAL NOT NULL` | Confianza del clasificador (0.0–1.0) |
| `matched_service` | `VARCHAR(100)` | Servicio asociado (ej. `'cloud-migration'`) |
| `offered` | `INTEGER NOT NULL` | `1` = oferta generada, `0` = no aplicable |
| `resolved` | `VARCHAR(50) NOT NULL` | `'si'`, `'no'`, `'parcial'` |
| `resolved_route` | `VARCHAR(50)` | `'llm'`, `'faq'`, `'human'`, `'redirect'` (**añadido en Spec 020 Fixes**) |
| `sector`, `role` | `VARCHAR(100)` | Contexto inferido (**añadidos en Spec 020 Fixes**) |
| `score`, `faq_score` | `REAL` | Puntajes de relevancia y match FAQ (**añadidos en Spec 020 Fixes**) |

> 🔁 **Migración Spec 020 Fixes**: Verifica y añade columnas faltantes (`resolved_route`, `sector`, `role`, `score`, `faq_score`) de forma segura e idempotente.

---

## ⚡ Índices de Rendimiento

Todos los índices son `IF NOT EXISTS` para evitar errores en re-ejecuciones:

- `leads`: `status`, `sector`, `rol`, `email`
- `interactions`: `lead_id`
- `appointments`: `lead_id`, `requested_date`, `status`
- `chat_metrics`: `session_id`, `backend_used`, `created_at`
- `interaction_events`: `session_id`, `event_type`, `created_at`
- `demand_signals`: `session_id`, `offered`, `created_at`, `resolved_route`, `sector`, `role`

Estos índices optimizan consultas frecuentes: listados por estado, búsquedas por email, reportes diarios, análisis por sector/rol y auditorías de resolución.

---

## 🛠️ Uso y Operación

### Ejecución
```bash
php scripts/init_crm_db.php
```

### Comportamiento
- Crea el directorio `secure_leads/` con permisos `0700` si no existe.
- Inicializa `crm.sqlite` con todas las tablas y migraciones.
- Inserta disponibilidad por defecto **solo si la tabla está vacía**.
- Imprime mensajes de éxito o error en `STDERR`.

### Seguridad
- La BD está fuera del `webroot`: imposible acceder vía HTTP.
- No se exponen credenciales: SQLite no requiere autenticación, pero el archivo tiene permisos restrictivos (`0600` implícito por PDO + modo `0700` del directorio).

---

## 📜 Especificaciones Asociadas

| Spec | Funcionalidad |
|------|----------------|
| **014** | Modelo básico de leads + interacciones + historial de estado |
| **015** | Citas, disponibilidad y fechas bloqueadas |
| **016** | Métricas de chat + columnas `sector`/`rol` en `leads` |
| **018** | Eventos de micro-interacción para analítica UX |
| **020** | Señales de demanda con scoring, intención y rutas de resolución |
| **020 Fixes** | Corrección y expansión del esquema `demand_signals` |

---

## 🔄 Idempotencia y Mantenimiento

Este script está diseñado para ser **ejecutado múltiples veces sin efectos colaterales**: 
- Usa `CREATE TABLE IF NOT EXISTS`.
- Las migraciones `ALTER TABLE` verifican existencia de columnas.
- Los `INSERT`s de disponibilidad usan conteo previo.
- Los índices usan `IF NOT EXISTS`.

➡️ Ideal para entornos CI/CD, staging y producción durante actualizaciones de esquema.

---

## 📌 Notas Técnicas

- **Zona horaria**: Todos los `DATETIME` usan `datetime('now')`, que depende del TZ del servidor. Se recomienda configurar `date.timezone = 'America/Lima'` en `php.ini` para coherencia.
- **Escalabilidad**: SQLite es adecuado para carga ligera (< 10K leads). Para escenarios de alta concurrencia o volumen, considerar migración a PostgreSQL con el mismo esquema lógico.
- **Backup**: No implementado aquí; debe gestionarse externamente (ej. copia de `crm.sqlite` + `cron`).