---
title: "Spec 020 — Inteligencia de Demanda y Journey de Conversión"
description: "Especificación técnica para la clasificación 0-LLM de demanda del mercado y reconstrucción auditada del journey de conversión, integrada con el CRM local y v"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["demanda","journey","0-llm","xenova","crm","analytics","privacy-by-design"]
seoScore: 100
---
# Spec 020 — Inteligencia de Demanda y Journey de Conversión

**Estado:** 🟠 Diseñada · Pendiente de Auditoría Claude  
**Fecha:** 2026-08-06  
**Rama:** `007-multi-pagina`  
**Dependencias:** Spec 002 (Chatbot), Spec 003 (Taxonomía), Spec 014 (Mini-CRM), Spec 016 (chat_metrics), Spec 018 (interaction_events), Spec 019 (Router 0-LLM)

---

## 1. WHY — Objetivos de Negocio y Alineación de Estrategia

Hoy el chatbot de Datanestiq recopila e instrumenta métricas operativas y comportamiento general. Sin embargo, no sabemos con precisión **qué es lo que el mercado está pidiendo**, **dónde se pierden oportunidades** ni **cómo se comporta el journey completo de un cliente** (desde su primera interacción en la web hasta que el lead se marca como "ganado").

Esta especificación evoluciona la conversión consultiva, clasificando cada interacción de texto libre de los visitantes en uno de estos estados para visualizarlos en `/admin/`:

1.  **Demanda NO ofrecida (gap / oportunidad de mercado):** Consultas de visitantes sobre temas o tecnologías que Datanestiq no ofrece hoy. Permite detectar de manera proactiva qué nuevos servicios demanda el mercado.
2.  **Ofrecida pero NO capturada (fuga de conversión):** Consultas sobre servicios que sí ofrecemos, donde se muestra interés evidente, pero el visitante abandona la web sin dejar sus datos de contacto ni agendar una cita.
3.  **Capturado (lead):** Sesiones donde el visitante registra sus datos de contacto o agenda a través de la interfaz.
4.  **Vendido (cliente ganado) + Journey Reconstructor:** El lead pasa a `status = ganado` en el CRM. Se reconstruye su recorrido completo combinando los logs de comportamiento anteriores a su conversión y las interacciones posteriores del CRM.

---

## 2. WHAT — Alcance de Diseño

### 2.1 Clasificación de Demanda 0-LLM (Reuso de Xenova)
- Al procesar cualquier mensaje de texto libre mediante el worker de Xenova (`worker.js` cargando `paraphrase-multilingual-MiniLM-L12-v2`), se realiza una búsqueda de similitud coseno de la consulta del usuario contra el catálogo de servicios de Datanestiq.
- El catálogo de servicios se compone de las descripciones y nombres del SSOT de soluciones y pilares (`src/data/taxonomyCorpus.json`).
- Se definen dos campos en el resultado de la clasificación de demanda:
  - `matched_service`: El ID del servicio más cercano obtenido por similitud coseno.
  - `offered` (boolean): `true` si el score de la coincidencia máxima supera el umbral de catálogo `CATALOG_MATCH_THRESHOLD` (definido inicialmente en `0.60`). Si el score está por debajo del umbral, se asume `offered = false`, categorizándolo como **Demanda no ofrecida**.
- **Cero LLM**: Este cálculo se realiza en cliente usando el Web Worker Xenova existente y sus embeddings locales, optimizando recursos y tiempos de carga.

### 2.2 Registro de la Señal de Demanda
- Cada decisión se registra a través del beacon de eventos existente (`track_event.php` / Spec 018).
- Se crea el tipo de evento `demand_signal` con el siguiente payload:
  `{ session_id, query_redacted, intent, confidence, matched_service, offered, resolved, ts }`
- **PII Redaction**: Al igual que en la Spec 018, la consulta se pasa por `redactPii()` antes de enviarla o guardarla en el servidor (enmascarando emails, teléfonos y nombres).
- `session_id` actúa como la **clave de costura** para reconstruir el recorrido completo de conversión, vinculándose con el lead a través de la tabla `leads` y la tabla `interactions` del CRM.

### 2.3 Procesamiento de los 4 Buckets
El panel administrativo `/admin/` consumirá las bases de datos para calcular:
- **Bucket 1: Demanda No Atendida (offered = false):** Clúster semántico de consultas no atendidas con conteo de frecuencia y ejemplos. Con guardarraíl §2 (muestra mínima $N \ge 20$, de lo contrario "datos insuficientes").
- **Bucket 2: Fuga de Conversión:** Sesiones que mostraron interés en servicios ofrecidos (`offered = true` o intención `faq`/`guiado`) pero que no tienen ningún `lead_id` asociado en el CRM al finalizar el periodo de retención.
- **Bucket 3: Leads Capturados:** Sesiones que generaron un lead en el CRM (vinculadas mediante `session_id` en las interacciones o leads).
- **Bucket 4: Clientes Ganados & Journey Reconstructor:** Leads con `status = 'ganado'`. El reconstruccionador de journeys combina la tabla de eventos de interacción (`interaction_events`), el historial de estados de leads (`status_history`) y las métricas de chat (`chat_metrics`) por `session_id`.

### 2.4 Panel `/admin/` (Vanilla UI)
Se añaden vistas en el panel de control seguro `/admin/`:
- **Pestaña "Inteligencia de Demanda":** Gráficos y tablas de demanda no atendida (Bucket 1) y fugas por servicio (Bucket 2).
- **Visualizador de Journey:** Un timeline ordenado cronológicamente para cada lead que muestra: sector/rol inicial seleccionado, consultas hechas, intenciones detectadas, servicios visitados, evento de conversión y cambios de estado del lead en el CRM.
- **Métricas de honestidad §2:** En caso de que la muestra de datos sea inferior a los límites establecidos ($N = 20$ para demanda, $N = 10$ para conversión), el panel mostrará de forma transparente el mensaje *"Datos insuficientes"* para evitar inducir a conclusiones falsas.

---

## 3. CONSTRAINTS — Restricciones Técnicas y de Negocio

- **#2 Honestidad Radical**: Queda estrictamente prohibido simular o extrapolar gráficas o reportes en frío. Si la muestra es baja ($< N$), se indicará "Datos insuficientes". Toda hipótesis de demanda no atendida (`offered=false`) se etiquetará visualmente como *"Hipótesis de demanda no atendida"*, nunca como un hecho absoluto.
- **#5 0-LLM**: El procesamiento de categorización e indexado de demanda contra la taxonomía se ejecuta al 100% en el cliente de forma local (Xenova). Cero llamadas a APIs de LLM externas.
- **Privacidad y Seguridad**: Las consultas registradas en `demand_signal` deben estar sanitizadas de PII en el cliente. La visualización de Journeys que exponga datos específicos de leads vive exclusivamente detrás del sistema de autenticación seguro (`auth.php`).
- **Coexistencia**: Las tablas del CRM SQLite actual (`crm.sqlite`) y los datos de telemetría existentes permanecen intactos. Se utiliza una migración no destructiva con respaldo de seguridad pre y post validado.

---

## 4. OUT-OF-SCOPE — Fuera de Alcance

- Clasificación de demanda del chatbot mediante APIs de LLM comerciales.
- Enriquecimiento automático de leads consultando redes sociales o servicios externos.
- Creación de campañas de re-targeting automatizadas basadas en las fugas de conversión (Bucket 2).
- Sincronización con bases de datos SQL externas que no pertenezcan al sistema `crm.sqlite`.

%%IGNORE_BLOCK_1%%
%%IGNORE_BLOCK_2%%
%%IGNORE_BLOCK_3%%

NUEVO DOC