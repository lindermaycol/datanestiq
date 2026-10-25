---
title: "Data Model - Spec 021: Panel-First (CRM & LLM Integration)"
description: "Define el modelo de datos para la especificación 'Panel-First', incluyendo nuevas tablas SQLite para alertas y métricas de uso de LLM, así como consultas cla"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["data model","SQLite","CRM","LLM","metrics","alerts","queries","demo data","spec 021"]
seoScore: 100
---
# Data Model — Spec 021: Panel-First

Este documento detalla el modelo de datos implementado para la especificación 'Panel-First', que busca integrar y estructurar la información proveniente de interacciones con LLMs y la gestión de leads dentro del CRM. Se enfoca en la adición de nuevas tablas a la base de datos `crm.sqlite`, consultas esenciales para la interfaz de usuario y la definición de criterios para datos de demostración.

## 1. Esquema de Base de Datos SQLite (Nuevas Tablas)

Para soportar la migración de archivos planos a una base de datos indexada y estructurada, se han añadido las siguientes tablas a `crm.sqlite`:

### Tabla `alerts`
Esta tabla almacena alertas críticas relacionadas con el consumo anómalo (burst), el cruce de umbrales de uso diario (80%) y fallos de proveedores de IA (failovers). Cada alerta incluye un tipo, un mensaje descriptivo y la marca de tiempo de su creación.

```sql
CREATE TABLE IF NOT EXISTS alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(64) NULL,
    alert_type VARCHAR(32) NOT NULL, -- 'burst', 'cap_80', 'failover'
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_alerts_session_id ON alerts(session_id);
CREATE INDEX IF NOT EXISTS idx_alerts_created_at ON alerts(created_id);
```

### Tabla `usage_metrics`
Registra métricas detalladas de cada invocación al LLM, incluyendo el modelo utilizado, el número de tokens (prompt, completion, total) y la latencia. Esto permite un seguimiento preciso del consumo y el costo indirecto.

```sql
CREATE TABLE IF NOT EXISTS usage_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(64) NOT NULL,
    model VARCHAR(64) NOT NULL,
    prompt_tokens INTEGER NOT NULL DEFAULT 0,
    completion_tokens INTEGER NOT NULL DEFAULT 0,
    total_tokens INTEGER NOT NULL DEFAULT 0,
    latency_ms INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_usage_metrics_session_id ON usage_metrics(session_id);
CREATE INDEX IF NOT EXISTS idx_usage_metrics_created_at ON usage_metrics(created_at);
```

## 2. Consultas y Filtros Clave

### Consulta de Sesiones Navegables (`journey_sessions`)
Esta consulta unifica y presenta una lista paginada de leads registrados y sesiones anónimas con actividad. Su objetivo es proporcionar una vista consolidada de todas las interacciones, ocultando identificadores reales en la UI para proteger la privacidad.

La consulta combina:
- Leads con formularios completados, extrayendo su `session_id`, `email`, `organizacion`, y la última actividad.
- Sesiones anónimas que han generado señales de demanda, inferiendo `organizacion` a partir de los últimos `sector` y `role` registrados.

Ambas partes de la unión excluyen por defecto las sesiones de demostración (`demoseed%`) a menos que se especifique lo contrario.

```sql
-- Consulta SQL detallada en el archivo original.
```

### Deduplicación de Leads en Conversaciones (`leads_detected`)
Para evitar la duplicidad de leads extraídos por el LLM en la interfaz de conversación, se implementa una lógica de deduplicación. Esta consiste en:
1.  Parsear un archivo CSV (`leads_datanestiq.csv`) que contiene posibles nuevos leads.
2.  Filtrar cada fila, descartando aquellos `SessionID` que ya existen en la tabla `leads` del CRM.
3.  Presentar los leads restantes para su revisión, sin aplicar redacción de PII hasta que se autentique el usuario.

```sql
-- Lógica de deduplicación contra CRM:
SELECT session_id FROM leads WHERE session_id IN (:session_ids_from_csv)
```

## 3. Criterio de Datos Demo (Seeding)

Para facilitar el desarrollo y las pruebas, se ha establecido un criterio claro para identificar y gestionar los registros de demostración:

-   **`session_id`**: Los registros demo utilizarán el prefijo `demoseed_` (ej: `demoseed_77f439abef02845c1920e8d1`).
-   **`query_redacted`**: El texto descriptivo de las queries demo incluirá el prefijo `[DEMO]` (ej: `[DEMO] necesito pipelines para optimizar inventario en retail`).
-   **Exclusión por Defecto**: Las consultas principales (`journey_sessions`, Bucket 1, Bucket 2) incluirán por defecto la cláusula `WHERE session_id NOT LIKE 'demoseed%'`, a menos que un parámetro `include_demo=1` sea enviado desde la UI.