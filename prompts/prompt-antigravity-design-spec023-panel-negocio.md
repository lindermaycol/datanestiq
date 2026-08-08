# DISEÑO SDD — Spec 023: Panel de Negocio (refocus business-first del /admin/)

**Tarea de DISEÑO, NO implementación.** Escribe los artefactos de la **Spec 023** (`specs/023-panel-negocio/`): 5
bloques + `data-model.md` + `plan.md` + `tech_debt.md`. **Entrégalos para auditoría de Claude ANTES de construir.** Cero
runtime. La **021 ya está cerrada y verificada en vivo**. **Coordinación con la 022 (file-free):** diseña **primero la
022 y luego la 023 en la misma sesión**, con **una sola actualización consistente** de `ESTADO-SPECS.md` /
`specsStatus.json` / `Fases.md` que incluya **ambas filas** (022 y 023), para no romper el build-gate antidrift.
Mantén carpetas/artefactos **separados** (`specs/022-file-free/` y `specs/023-panel-negocio/`). La 023 **consume** datos
que la 022 unifica (`usage_daily`/`conversations`/`leads_extracted`), así que el **BUILD será secuencial: 022 → 023**
(ambas tocan `admin/api.php`/`index.php`). No arranques runtime hasta la auditoría de Claude de ambos diseños.

## WHY
Reauditoría en vivo del panel (Claude, pestaña por pestaña) con la lente **"el panel es de NEGOCIO, no backend"**.
Hallazgos: (a) el mayor valor de la IA —el **ahorro por 0-LLM del Router (019)**— **no está** en ningún lado; (b)
"Observabilidad Ops" mezcla monitoreo de negocio con **contenido de desarrollador** (tabla de 21 specs con deuda técnica,
build/ESLint/.htaccess); (c) faltan una **agenda de citas** y el **sector/rol en el lead** (todo sale "no_especificado");
(d) hay duplicación de vistas. Esta spec **reenfoca el panel a negocio**.

## WHAT (alcance — diseño; partes separables, en orden de prioridad)

### Parte 1 (🔴 prioridad) — Panel de Eficiencia/Costo de la IA (surface el valor del Router 0-LLM, Spec 019)
Hoy los eventos `intent_routing` (en `interaction_events`, `event_value` JSON con `resolved`/`route`/`faqScore`) se
registran pero **no se visualizan**. Diseña una vista/tarjeta de **eficiencia de la IA**:
- **% de consultas de texto libre resueltas 0-LLM vs LLM** (agregado) + **desglose por ruta** (`faq`/`cita`/`guiado`/`llm`).
- **$ ahorrado estimado**: `(consultas resueltas 0-LLM) × (costo promedio real por llamada LLM)` — usa el costo real de
  `chat_metrics`/tokens; marca la cifra como **`[EST]`** (§2, es estimación).
- Únelo con el **consumo de tokens/costo** que ya existe (021) → una sola historia "**Costo y eficiencia de la IA**".
- **Ubicación:** "Analítica de Conversión" (o una sección "IA & Eficiencia"). **Guard §2:** muestra < N → "datos insuficientes".

### Parte 2 (🔴 prioridad) — Depurar "Observabilidad Ops" del contenido backend/dev
Sacar del panel de **negocio** lo que es interno de desarrollo:
- **Quitar** (o mover a una vista dev separada, p. ej. gateada aparte y no en la barra principal): la **tabla del
  portafolio de 21 specs + deuda técnica**, "Páginas Estáticas Compiladas / Último Build", ESLint, ".htaccess activa".
- **Conservar** lo relevante de negocio: **sitio arriba (200 / HEALTHY)**, **SLA & latencia** de la IA (p50/p95),
  **último incidente/failover**, **alertas**, y **costo/uso de tokens**. Renombra la pestaña si aplica (ej. "Salud & Costo IA").
- Declara **dónde** va lo dev (recomiendo: fuera del panel de negocio; el `specsStatus.json` sigue siendo SSOT del build-gate,
  solo se retira su **vista** del panel).

### Parte 3 (🟠) — Agenda global de citas (Spec 015)
- Nueva vista (sub-tab en "Leads & Citas" o "Agenda"): **todas las próximas citas** ordenadas por fecha, con
  lead/contacto, tipo, duración, estado, y **acciones confirmar/reagendar/cancelar** (reusa `update_appointment`, ya existe).
- Query: `appointments` JOIN `leads`, filtrando futuras/pendientes. Tras `auth.php`; contacto visible solo autenticado.
- Cierra la deuda de la 015 ("confirmación manual de citas desde panel").

### Parte 4 (🟠) — Capturar sector/rol en el lead (hoy todo "no_especificado")
- Al crear el lead (`save_wizard.php` y el form del chatbot), **persistir `sector`/`rol`** conocidos del contexto
  (chip/guiado/`demand_signal` de la sesión) en las columnas `leads.sector`/`leads.rol` (ya existen).
- **Backfill** de leads existentes: derivar sector/rol por `session_id` desde `demand_signals` (última no vacía) donde
  falte. Script CLI idempotente + backup.
- Resultado: el CRM y "Conversión por Sector×Rol" muestran la **persona real**, no "no_especificado".

### Parte 5 (🟡) — Deduplicar vistas + acciones visibles en el lead
- **Rendimiento LLM** aparece en "Analítica" **y** "Ops" → dejar **un** hogar (Ops/Salud IA) y quitar el duplicado.
- **"Qué preguntan"** (texto libre) aparece en Engagement (Top Claves), Demanda (Bucket 1) y el futuro visor de
  Conversaciones (022) → unifica el relato o cruza-enlaza para no repetir.
- **Acciones en el detalle del lead** bien visibles: cambiar estado, agregar nota, marcar ganado/perdido, confirmar cita
  (reusa `update_lead`/`update_appointment`). Verifica que estén expuestas en la UI, no solo en la API.

### Parte 6 (🟡) — Visualizaciones/gráficos donde el dato lo amerita (hoy el panel es casi todo tablas)
El panel es muy **tabular**; algunos datos "cuentan la historia" mejor como gráfico. Agrega gráficos **solo** donde
aporten, **ligeros** (CSS/SVG inline, como el desglose de tokens actual — **sin Chart.js ni librerías pesadas**), y con
el **mismo guard §2** (muestra < N → "datos insuficientes", **nunca** un gráfico engañoso de 1-2 barras).
- **Embudo de conversión** (nuevo→contactado→cita→ganado): pasar de la lista de texto a **barras de embudo horizontales**
  con el drop-off por etapa (en "Analítica de Conversión").
- **Ruteo 0-LLM vs LLM** (Parte 1): **dona o barra apilada** por ruta (`faq`/`cita`/`guiado`/`llm`) — la eficiencia de la IA de un vistazo.
- **Tendencia diaria** (peticiones / errores-failovers / latencia / costo): **línea o barras en el tiempo** (reemplaza la
  tabla de una fila de "Ops"); aparece solo cuando hay ≥ N días con datos.
- **Demanda no atendida y Fugas por servicio** (Buckets 1/2): **barras horizontales** (top por frecuencia / % de fuga)
  para priorizar — respetando la exclusión demo por defecto (021).
- **Dejar como tabla** (NO forzar gráfico): listas de leads, agenda de citas, timeline del journey, log de alertas, leads
  detectados — son registros; y las KPI sueltas siguen como tarjetas.

## CONSTRAINTS (declararlas)
- **§2:** estimaciones marcadas `[EST]`; guards de muestra mínima ("datos insuficientes") en cada agregado nuevo y en
  **cada gráfico** (sin visuales engañosos con muestra chica); nada fabricado.
- **Gráficos ligeros:** CSS/SVG inline sin librerías pesadas; consistentes con el estilo Vanilla del panel.
- **Privacidad/Seguridad:** todo tras `auth.php`; sin endpoints públicos (curl-verificar 404); PII redactada donde aplique
  (la agenda/lead muestran contacto solo autenticado, como el CRM).
- **Reuso, no reinventar:** usa `interaction_events` (intent_routing), `chat_metrics`/tokens, `appointments`, columnas
  `leads.sector/rol` y los endpoints `update_lead`/`update_appointment` existentes. Migración solo si hace falta (backfill).
- **Coexistencia:** no rompas las pestañas actuales ni el CRM; cambios son sobre todo de **presentación** + pocos writes.
- **UI Vanilla** consistente; el panel sigue siendo de **negocio** (no reintroducir contenido dev).

## OUT-OF-SCOPE (declararlo)
- **NO** analítica de contenido/blog (rendimiento por artículo/página) — futuro opcional, no ahora.
- **NO** la migración file-free (es la 022). **NO** nuevas herramientas de dev en el panel.
- **NO** eliminar `specsStatus.json` como SSOT (solo se retira su **vista** del panel de negocio).

## data-model.md (define)
- Endpoint/agregación de **eficiencia 0-LLM**: parseo de `event_value` de `intent_routing` → conteos por `resolved`/`route`
  + fórmula de `$ [EST]` ahorrado; guard §2.
- Query de **agenda** (`appointments` JOIN `leads`, futuras, orden por fecha) + acciones.
- Lógica de **captura/backfill** de `sector`/`rol` en `leads` (fuente: contexto de sesión / `demand_signals`).
- Mapa de **retiro** de la vista de specs/build/ESLint de "Ops".

## Entregables (solo diseño)
- `specs/023-panel-negocio/{spec.md, data-model.md, plan.md, tech_debt.md}`.
- Doc-sync: fila 023 en `ESTADO-SPECS.md` (🟠 diseñada) + `src/data/specsStatus.json` (build-gate) + `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará: el panel 0-LLM con `$ [EST]` honesto y guard §2 (Parte 1), que "Ops" quede **de
negocio** sin la tabla de specs/deuda/ESLint (Parte 2, la clave del "no es backend"), la agenda con acciones reales
(Parte 3), sector/rol capturado + backfill sin pérdida (Parte 4), la deduplicación (Parte 5), y los **gráficos ligeros
con guard §2** (Parte 6: embudo, dona 0-LLM, tendencias, barras de demanda/fuga — sin librerías pesadas, sin visuales
engañosos con muestra chica) — todo tras `auth.php`, sin fuga, reusando datos/endpoints existentes. Primero cierra la
021; luego esto.
