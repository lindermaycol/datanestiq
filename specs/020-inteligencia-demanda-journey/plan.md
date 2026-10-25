# Implementation Plan — Spec 020: Inteligencia de Demanda y Journey Reconstructor

Este documento describe los cambios técnicos necesarios para implementar el diseño de la Spec 020.

---

## 1. Cambios Propuestos

### 1.1 Modificaciones de Base de Datos y APIs Backend

#### [MODIFY] [`scripts/init_crm_db.php`](file:///C:/xampp/htdocs/datanestiq/scripts/init_crm_db.php)
- Añadir la instrucción `CREATE TABLE IF NOT EXISTS demand_signals` y sus índices de rendimiento asociados.
- Asegurar que la inicialización sea no destructiva para las tablas existentes (`leads`, `appointments`, `interaction_events`).

#### [MODIFY] [`public/api/track_event.php`](file:///C:/xampp/htdocs/datanestiq/public/api/track_event.php)
- Soportar el nuevo tipo de evento `demand_signal`.
- Insertar los datos desestructurados directamente en la tabla `demand_signals` en lugar de como string JSON genérico en `interaction_events`.
- Aplicar las mismas reglas de validación de `session_id`, Rate-Limiting e inyección SQL.

#### [MODIFY] [`public/admin/api.php`](file:///C:/xampp/htdocs/datanestiq/public/admin/api.php)
- Crear endpoints seguros (disponibles solo tras la sesión válida de `auth.php`):
  - `/admin/api.php?action=demand_signals` (Bucket 1) con guardarraíl de $N \ge 20$.
  - `/admin/api.php?action=leakage` (Bucket 2) con guardarraíl de $N \ge 10$.
  - `/admin/api.php?action=lead_journey&session_id=<session_id>` (Reconstructor del Journey).

---

### 1.2 Modificaciones en Frontend e Islas Interactivas

#### [MODIFY] [`src/components/islands/Chatbot.jsx`](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- Cargar e instanciar en el Web Worker de Xenova el catálogo de la taxonomía (`src/data/taxonomyCorpus.json`).
- En el método `handleUserSubmit`, tras la clasificación de intención, ejecutar una búsqueda coseno en el catálogo para obtener el servicio más cercano (`matched_service`) y su score.
- Evaluar `offered = score >= CATALOG_MATCH_THRESHOLD`.
- Enviar el evento `demand_signal` a `track_event.php` vía `trackEvent()`.

#### [MODIFY] [`public/admin/index.php`](file:///C:/xampp/htdocs/datanestiq/public/admin/index.php)
- Agregar la pestaña "Demanda & Journey" en la barra de navegación lateral.
- Diseñar la vista de demanda no atendida y fugas de conversión utilizando tablas y barras porcentuales (Vanilla CSS).
- Diseñar el visualizador cronológico de Journey con un diseño de línea de tiempo con colores diferenciados por tipo de actividad.

---

### 1.3 Registro de Especificaciones y OpenWiki

#### [MODIFY] [`planes/ESTADO-SPECS.md`](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md)
- Añadir la fila correspondiente a la Spec 020 indicando su estado de diseño.

#### [MODIFY] [`src/data/specsStatus.json`](file:///C:/xampp/htdocs/datanestiq/src/data/specsStatus.json)
- Registrar la Spec 020 para cumplir el build-gate de consistencia.

#### [MODIFY] [`planes/Fases.md`](file:///C:/xampp/htdocs/datanestiq/planes/Fases.md)
- Añadir la Spec 020 dentro del roadmap de la fase de analítica/inteligencia de conversión.

---

## 2. Plan de Verificación

### 2.1 Pruebas de Clasificación (0-LLM)
- Escribir un script de prueba offline similar a `eval_router.mjs` para testear el clasificador del catálogo contra consultas reales e inventadas, garantizando que el umbral de `0.60` clasifique correctamente como `offered=false` lo no relacionado.

### 2.2 Pruebas de Guardarraíl §2 (Muestra Mínima)
- Insertar artificialmente $M$ registros en la tabla `demand_signals` (donde $M < 20$) y verificar que la API de administración retorne `"Datos insuficientes"`.
- Incrementar la cantidad a $M \ge 20$ y verificar que exponga el reporte agregado correctamente.

### 2.3 Pruebas de Reconstrucción de Journey
- Simular el flujo de un cliente: interactuar con el chatbot seleccionando sector/rol, escribir una consulta de demanda, agendar una cita y cambiar el estado del lead a "ganado" en el panel.
- Cargar la vista del lead en `/admin/` y verificar que el timeline presente los eventos ordenados y sin duplicados.
