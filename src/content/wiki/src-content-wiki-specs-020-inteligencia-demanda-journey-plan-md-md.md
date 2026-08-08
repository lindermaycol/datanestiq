---
title: "Spec 020: Inteligencia de Demanda y Journey Reconstructor"
description: "Implementación técnica completa de la Spec 020, incluyendo cambios en base de datos, APIs, frontend, y planes de verificación para inteligencia de demanda y "
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["inteligencia de demanda","journey reconstructor","Spec 020","CRM analytics","event-driven architecture"]
seoScore: 100
---
# Spec 020: Inteligencia de Demanda y Journey Reconstructor

Esta especificación define la arquitectura, integraciones y protocolos necesarios para habilitar **inteligencia proactiva de demanda** y una **reconstrucción fiable del customer journey**, basada en señales multicanal estructuradas y taxonomía semántica.

## Arquitectura Clave

### Modelo de Datos
- Tabla `demand_signals` con campos:
  - `id` (UUID v4, PK)
  - `contact_id` (FK a `contacts.id`)
  - `signal_type` (ENUM: `'intent', 'urgency', 'channel_preference', 'objection'`)
  - `confidence_score` (DECIMAL(3,2), 0.00–1.00)
  - `taxonomy_path` (VARCHAR(255), ej. `"B2B/SaaS/Lead/FreeTrial/ChurnRisk"`)
  - `timestamp` (TIMESTAMP WITH TIME ZONE)
  - `source_event_id` (UUID, referencia al evento original en `events_log`)
- Índices optimizados:
  - `(contact_id, timestamp)` — para consultas de journey por contacto
  - `(signal_type, confidence_score)` — para segmentación de alta prioridad
  - `(taxonomy_path)` — para búsquedas jerárquicas

### Flujo de Eventos
```mermaid
graph LR
A[Chatbot.jsx] -->|trackEvent('demand_signal', payload)| B[track_event.php]
B --> C[Validación + Enrichment]
C --> D[Almacenamiento en demand_signals]
D --> E[Trigger: journey_rebuild(contact_id)]
E --> F[Admin API: /api/journey/{contact_id}]
```

## Implementación Técnica

### Backend
- ✅ `init_crm_db.php`: Script idempotente con `CREATE TABLE IF NOT EXISTS demand_signals` y `CREATE INDEX ...`.
- ✅ `track_event.php`: Soporte extendido para `event_type = 'demand_signal'`, con validación de esquema JSON y mapeo automático de `taxonomy_path` desde el catálogo cargado.
- ✅ `admin/api.php`: Endpoints seguros bajo autenticación JWT:
  - `GET /demand/signals?limit=100&filter=high_confidence` → devuelve señales clasificadas
  - `GET /journey/{contact_id}` → reconstruye timeline ordenado sin duplicados (usando `DISTINCT ON (event_type, signal_type) ORDER BY timestamp DESC`)
  - `POST /demand/batch` → ingesta masiva con rollback transaccional

### Frontend & Web Workers
- ✅ `Chatbot.jsx`: 
  - Carga síncrona del catálogo de taxonomía (`/assets/taxonomy.json`) dentro del Web Worker de Xenova.
  - Envío de `demand_signal` con payload estandarizado:
    ```json
    {
      "signal_type": "intent",
      "confidence_score": 0.87,
      "taxonomy_path": "B2B/SaaS/Lead/FreeTrial/ChurnRisk",
      "context": { "chat_session_id": "...", "page_url": "..." }
    }
    ```
- ✅ `index.php`: Pestaña lateral `Demanda & Journey` con acceso directo a `/admin/journey-viewer`.

### Gestión de Especificaciones
- ✅ `ESTADO-SPECS.md`: Fila añadida con estado `✅ Implementado`, responsable `CRM Team`, fecha `2026-08-06`.
- ✅ `specsStatus.json`: Entrada `{"specId":"020","status":"active","version":"1.2.0","lastDeploy":"2026-08-06"}`.
- ✅ `Fases.md`: Incluida en *Fase 3: Analítica Avanzada* bajo el hito *Journey Intelligence Engine*.

## Plan de Verificación

### 2.1 Pruebas de Clasificación
- **Objetivo**: Validar precisión del clasificador taxonómico.
- **Método**: Dataset de 500 frases de chat etiquetadas manualmente → comparación contra predicciones del modelo en `XenovaWorker.classify()`.
- **Éxito**: ≥92% de coincidencia exacta en `taxonomy_path` y ≥88% en `confidence_score` ±0.05.

### 2.2 Pruebas de Guardarraíl
- **Objetivo**: Garantizar resiliencia ante datos insuficientes.
- **Escenario**: Llamada a `GET /api/journey/{contact_id}` cuando `SELECT COUNT(*) FROM demand_signals WHERE contact_id = ?` retorna `0`.
- **Éxito**: Respuesta HTTP 400 con cuerpo `{"error":"Datos insuficientes","code":"JOURNEY_INCOMPLETE"}`.

### 2.3 Pruebas de Reconstrucción de Journey
- **Objetivo**: Asegurar integridad temporal y semántica del timeline.
- **Método**: Ingesta de 10K eventos simulados con timestamps solapados y duplicados intencionales → consulta `/journey/{contact_id}`.
- **Éxito**: Timeline devuelto con:
  - Orden estricto descendente por `timestamp`
  - Cero duplicados (mismo `signal_type` + `taxonomy_path` en ventana de 5s)
  - Todos los eventos mapeados a su categoría taxonómica válida

## Notas de Producción
- La tabla `demand_signals` se replica en tiempo real a `analytics.demand_signals` para BI.
- El endpoint `/journey/{contact_id}` aplica rate limiting (10 req/min/IP) y caché LRU de 5 min.
- %%IGNORE_BLOCK_1%%
- %%IGNORE_BLOCK_2%%