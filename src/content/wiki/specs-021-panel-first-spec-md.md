---
title: "Spec 021: Panel-First — Journey Navegable y Unificación de Monitoreo"
description: "Documentación técnica completa de la especificación 021, que introduce una arquitectura 'panel-first' para el monitoreo integrado, navegabilidad de journeys "
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["admin-panel","journey-reconstruction","sqlite-migration","observability","demo-data-isolation","auth-guarded"]
seoScore: 100
---
# Spec 021: Panel-First — Journey Navegable y Unificación de Monitoreo

> **Estado**: Especificación activa — Implementación en curso.
> **Versión**: 1.0
> **Ámbito**: Backend + Admin UI (`/admin/`)

---

## 🎯 Objetivo Estratégico

Eliminar fricciones operativas en el panel de administración mediante:
- **Journey navegable**: Reemplazar la reconstrucción manual por sesión ID con una lista interactiva y contextualizada de sesiones.
- **Unificación de monitoreo**: Migrar métricas, alertas y leads desde archivos planos locales hacia una base de datos SQLite consultable *en vivo*, manteniendo redundancia y seguridad.
- **Control explícito de datos demo**: Aislar, excluir por defecto y visualizar bajo toggle los registros de prueba sin afectar métricas reales.

---

## 🔐 Requisitos de Seguridad y Privacidad

| Categoría | Regla |
|-----------|--------|
| **Autenticación** | Todos los endpoints (`action=journey_sessions`, `leads_detected`, `learn_insights`, `usage_metrics`, `alerts`) requieren validación obligatoria vía `auth.php`. Sin excepciones. |
| **PII (Datos Personales)** | En listas de sesiones anónimas y logs crudos: redacción automática de correos, nombres y teléfonos (`redactPii()`). Excepción explícita: vista `leads_detected`, donde se muestra PII *solo* para fines comerciales autorizados y tras autenticación. |
| **Exposición de IDs** | El `session_id` nunca se renderiza visible ni aparece en la URL. Se transfiere exclusivamente como atributo HTML `data-session-id` en filas clickeables. |
| **Fail-Closed** | Cualquier fallo en escritura a SQLite o en carga de datos debe dejar intacta la funcionalidad crítica (ej. chat sigue operando) y registrar el error sin exponer detalles sensibles. |

---

## 🧱 Arquitectura de Datos

### Tablas SQLite nuevas (en `crm.sqlite`)

| Tabla | Propósito | Claves | Notas |
|-------|-----------|--------|-------|
| `usage_metrics` | Registro en tiempo real de consumo de tokens, latencia y contexto de llamada LLM | `id`, `session_id`, `model`, `input_tokens`, `output_tokens`, `latency_ms`, `timestamp`, `prompt_hash` | Dual-write: también se escribe en `usage_metrics.jsonl` (append-only). |
| `alerts` | Alertas operativas (burst, failover, rate-limit, etc.) generadas por `chat.php` | `id`, `session_id`, `type`, `severity`, `message`, `context_json`, `timestamp` | Dual-write: también se escribe en `alerts.jsonl`. |
| `leads` *(existente)* | CRM centralizado; usada como fuente de deduplicación semántica | `id`, `email`, `name`, `session_id`, `form_submitted_at`, `created_at` | Referenciada por `action=leads_detected` para evitar duplicados. |
| `demand_signals` *(existente, extendida)* | Almacena señales de demanda detectadas (sector, rol, intención); ahora incluye `session_id` como FK | `id`, `session_id`, `sector`, `role`, `intent`, `confidence`, `created_at` | Usada para generar resúmenes legibles en la lista de sesiones. |

> ✅ **Idempotencia garantizada**: Las migraciones se ejecutan mediante CLI (`php init_crm_db.php --migrate`) con backup previo y validación de conteo post-migración.

---

## 🌐 Endpoints Administrativos

Todos accesibles únicamente vía `public/admin/api.php?action=...` y protegidos por `auth.php`.

| Endpoint | Método | Respuesta | Comportamiento clave |
|----------|--------|-----------|----------------------|
| `journey_sessions` | `GET` | JSON paginado (`limit=20`, `offset=0`, `sort=last_activity DESC`) | Filtra `session_id NOT LIKE 'demoseed%'` por defecto. Incluye campos calculados: `last_sector`, `last_role`, `first_query`, `event_count`, `last_activity`. |
| `leads_detected` | `GET` | JSON paginado + deduplicación | Lee `leads_datanestiq.csv`, parsea, y omite entradas cuyo `email` o `session_id` ya exista en `leads` (CRM). **No aplica `redactPii`.** |
| `learn_insights` | `GET` | Markdown procesado | Carga y renderiza `PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md` con soporte básico de sintaxis (encabezados, listas, bloques de código). |
| `usage_metrics` | `GET` | JSON paginado + filtros | Soporta `?from=2024-06-01&to=2024-06-15&model=gpt-4o`. Ordenado por `timestamp DESC`. |
| `alerts` | `GET` | JSON paginado + filtros | Soporta `?severity=high&from=...`. Incluye `context_json` deserializado para inspección rápida. |

---

## 🖥️ Interfaz de Usuario (Admin Panel)

### Pestaña: **Demanda & Journey**
- Lista tabular de sesiones con columnas: `Último sector`, `Rol detectado`, `Primera consulta`, `Eventos`, `Última actividad`, `Acción`.
- Cada fila es clickeable → abre el *Journey Reconstructor* con el `session_id` cargado (sin exponerlo).
- Toggle **"Mostrar datos demo"**: Activa/desactiva la inclusión de `session_id LIKE 'demoseed%'`. Cuando está activo, muestra banner amarillo: `⚠️ Vista preliminar: conteniendo datos de prueba.`

### Pestaña: **Observabilidad Ops**
- Dos pestañas internas: `Métricas de Uso` y `Alertas`.
- Filtros persistentes por rango de fechas y modelo (para métricas) o severidad (para alertas).
- Paginación nativa (20 ítems/página), sin carga infinita.

### Pestaña: **Analítica de Conversión**
- Renderizado en vivo del reporte `PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md`.
- Estilo consistente con el tema del panel (tipografía, colores, espaciado).
- Soporte para anclajes (`#sección`) y copia de bloques de código.

### Pestaña: **Leads Detectados**
- Tabla con columnas: `Email`, `Nombre`, `Sector`, `Rol`, `Sesión`, `Detectado en`, `Acción`.
- Botón `Enviar a CRM` (disparará sincronización con `leads` si no existe).
- **Restricción absoluta**: No accesible sin autenticación válida; no indexable ni cachéable.

---

## 🧪 Gestión de Datos Demo

| Script | Ubicación | Función |
|--------|-----------|---------|
| `seed_demand_demo.php` | `scripts/` | Inserta 50+ sesiones simuladas con `session_id LIKE 'demoseed%'`, señales de demanda y eventos de interacción. |
| `purge_demand_demo.php` | `scripts/` | Elimina *solo* registros con `session_id LIKE 'demoseed%'` de `demand_signals`, `usage_metrics`, `alerts` y `interactions`. No afecta CRM ni producción. |

> 💡 **Consejo de uso**: Ejecutar `purge_demand_demo.php` antes de cada entorno de producción o staging limpio.

---

## 🚫 Fuera de Alcance (Out-of-Scope)

- Exportaciones manuales a CSV/Excel desde el panel.
- Retiro de tuberías de escritura a archivos planos (`*.jsonl`, `*.csv`). Estas permanecen como respaldo *fail-safe*.
- API pública de lectura de logs, leads o métricas. Todo es privado y admin-only.
- Integración con herramientas externas (Datadog, Grafana, etc.).

---

## 📋 Tareas Pendientes (Checklist)

```markdown
- [ ] Implementar migración DB: `init_crm_db.php` → tablas `alerts`, `usage_metrics`
- [ ] Modificar `public/api/chat.php`: dual-write (SQLite + JSONL), try-catch autónomo
- [ ] Crear `scripts/seed_demand_demo.php` y `scripts/purge_demand_demo.php`
- [ ] Desarrollar endpoints en `public/admin/api.php` con exclusión por defecto de `demoseed%`
- [ ] Construir UI interactiva en `public/admin/index.php`: lista clickeable, toggle demo, vistas de leads e insights
```

---

## 📚 Referencias

- [Spec 016: Loop Learn — Prompt Optimization Report](%%IGNORE_BLOCK_1%%)
- [Spec 007: Auth Guard — auth.php implementation](%%IGNORE_BLOCK_2%%)
- [Spec 012: Demand Signals Schema](%%IGNORE_BLOCK_3%%)
- [Diseño de Observabilidad (Figma)](%%IGNORE_BLOCK_4%%)

---

> ✨ **Nota de implementador**: Este spec define un *contrato de interfaz de datos y comportamiento*. Cualquier cambio en los esquemas de tablas, formatos de respuesta o reglas de exclusión debe actualizarse aquí primero y notificarse al equipo de QA y frontend.

*Documento generado automáticamente por OpenWiki — Datanestiq Engineering.*