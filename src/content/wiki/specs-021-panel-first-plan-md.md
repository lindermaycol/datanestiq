---
title: "Plan de Implementación — Spec 021: Panel-First"
description: "Este documento detalla los pasos de construcción requeridos para materializar el alcance de diseño de la Spec 021"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["Spec 021","Panel-First","Implementación"]
seoScore: 100
---
# Plan de Implementación — Spec 021: Panel-First

Este documento detalla los pasos de construcción requeridos para materializar el alcance de diseño de la Spec 021.

## 1. Migraciones de Base de Datos e Ingesta
### Paso 1.1: Modificación de `init_crm_db.php`
- Incorporar la creación de las tablas `alerts` y `usage_metrics` con sus índices respectivos.
- Configurar la retención de 180 días auto-ejecutable dentro del flujo de inicialización y en las purgas periódicas.

### Paso 1.2: Dual-Write Fail-Safe en `public/api/chat.php`
- Modificar el flujo de llamadas de `chat.php`:
  1. Al escribir una alerta (burst de IPs o cap del 80%), insertar el registro en la tabla SQLite `alerts` dentro de un bloque `try-catch` independiente, manteniendo la escritura tradicional de respaldo a `alerts.jsonl`.
  2. Al producirse un failover de proveedores de IA, insertar el evento en `alerts`.
  3. Al recibir una respuesta exitosa del LLM, registrar latencia y tokens en la tabla `usage_metrics`, manteniendo la escritura a `usage_metrics.jsonl`.

## 2. Desarrollo de Scripts de Datos Demo (Seed/Purge)
### Paso 2.1: `scripts/seed_demand_demo.php`
- Script de consola PHP que:
  - Genera 20 registros consistentes en `demand_signals`.
  - Distribuye las señales: 12 señales como ofrecidas (`offered=1`) y 8 señales no atendidas (`offered=0`).
  - Utiliza session IDs con prefijo `demoseed_` y consultas con el prefijo `[DEMO]`.
  - Asegura que los campos `resolved_route`, `sector`, `role`, y `score` sean simulados de forma coherente con la taxonomía del negocio.

### Paso 2.2: `scripts/purge_demand_demo.php`
- Script de consola PHP que ejecuta la eliminación de cualquier registro cuyo `session_id` empiece por `demoseed_`.

## 3. Desarrollo de Endpoints en `public/admin/api.php`
### Paso 3.1: Endpoint `journey_sessions`
- Implementar la consulta UNION descrita en `data-model.md` para agrupar leads y sesiones anónimas.
- Soportar parámetros `limit` (default 50), `offset` (default 0) e `include_demo` (default 0, controlado por el toggle de la interfaz).

### Paso 3.2: Endpoint `leads_detected`
- Leer el CSV `secure_leads/leads_datanestiq.csv`.
- Deducir y cruzar los IDs contra la tabla `leads` del CRM para evitar duplicación.
- Retornar el listado sin redactar (restringido bajo `auth.php`).

### Paso 3.3: Endpoints `alerts_ops` y `usage_ops`
- Retornar datos agregados y paginados de las nuevas tablas SQLite para alimentar la pestaña de Observabilidad Ops.

## 4. Interfaces de Usuario en `/admin/index.php`
### Paso 4.1: Lista clickeable de Journeys
- En la pestaña "Demanda & Journey", agregar un bloque lateral izquierdo con la lista de sesiones recientes obtenida de `journey_sessions`.
- Al hacer clic en un elemento de la lista, leer el `data-session-id` oculto de la fila y llamar a `reconstructJourney(sessionId)` automáticamente.
- Agregar un banner toggle visible: **`[ ] Mostrar Datos Demo`** que recargue la lista incluyendo o excluyendo las filas demo de forma clara.

### Paso 4.2: Pestaña "Leads Detectados" y reportes
- Añadir la sub-pestaña "Leads en Conversaciones" dentro de "Leads & Citas" para renderizar la tabla de leads parseados y dedupados.
- Incorporar una sub-sección en "Observabilidad Ops" que muestre un panel de consumo tokens/costos promedio y el listado cronológico de alertas críticas de la infraestructura.