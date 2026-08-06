# DISEÑO SDD — Spec 021: Panel-First (Journey navegable + migrar salidas de archivos al panel)

**Tarea de DISEÑO, NO implementación.** Escribe los artefactos de la **Spec 021**
(`specs/021-panel-first/`): 5 bloques (WHY/WHAT/CONSTRAINTS/OUT-OF-SCOPE/TASKS) + `data-model.md` + `plan.md` +
`tech_debt.md`. **Entrégalos para auditoría de Claude ANTES de construir.** Cero runtime todavía.

## WHY
Dos fricciones reales detectadas en el uso del panel:
1. **El Journey Reconstructor exige pegar un `session_id` crudo** — el usuario no lo conoce. Debe haber una **lista
   legible** (con el código interno oculto) donde con un clic se vea el journey.
2. Varias specs generan salidas en **archivos planos / CSV / JSONL** que el usuario debe abrir por fuera. El usuario
   quiere **todo en el panel** para visualización y monitoreo — no en Excel/CSV/texto.

## WHAT (alcance — diseño)

### Parte 1 — Journey navegable (lista legible, sin pegar session_id)
- Nuevo endpoint admin (tras `auth.php`), ej. `action=journey_sessions`: devuelve una **lista legible** de sesiones
  recientes, ordenada por actividad, paginada/limitada. Por fila:
  - **Sesiones con lead:** email / organización / estado / fecha.
  - **Sesiones anónimas (solo demanda/comportamiento):** resumen legible — **sector/rol** (de `demand_signals`),
    **primera consulta redactada**, **nº de eventos**, **última actividad**. El `session_id` va **oculto** (atributo
    interno, nunca mostrado).
- **UI:** en la pestaña "Demanda & Journey", una **tabla/lista clickeable**; al hacer clic en una fila se carga su
  journey (pasando el `session_id` oculto). El input manual de `session_id` se conserva como opción avanzada/fallback.
- **§2/privacidad:** la lista muestra resúmenes **redactados**; el `session_id` es interno (data-attribute, no visible);
  todo tras `auth.php`. Nada público.

### Parte 2 — Migrar salidas de archivos planos → vistas del panel
Auditoría de Claude (código real) — estas salidas viven **solo en archivos** y deben visualizarse en el panel:

| Dato | Origen (archivo) | Generado por | Debe ir a (pestaña) |
|---|---|---|---|
| **Leads extraídos por LLM** de conversaciones sin formulario | `secure_leads/leads_datanestiq.csv` | `scripts/extraer_leads.php` | Vista "Leads detectados en conversaciones" (Leads & Citas) |
| **Reporte del Loop Learn** (conversión / optimización de prompt) | `planes/PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md` | `scripts/learn_prompt_optimizer.mjs` | Vista de insights en "Analítica de Conversión" |
| **Alertas** (failover, errores, señales de lead) | `secure_leads/alerts.jsonl` | `chat.php` | Vista de alertas en "Observabilidad Ops" |
| **Métricas de uso LLM** (tokens/backend/costo) | `secure_leads/usage_metrics.jsonl` | `chat.php` | KPIs de uso/costo en "Observabilidad Ops" |
| **Logs de conversación** (redactados) | `chat_logs.jsonl` | `chat.php` | Visor de conversaciones (opcional, redactado) |

Diseña para cada una:
- **Preferir migrar a SQLite** (tablas consultables con retención 180d) en vez de parsear el archivo en cada carga —
  sobre todo `alerts` y `usage_metrics` (alto volumen, se benefician de índices y agregación). Para `leads_datanestiq.csv`
  y el reporte learn (bajo volumen) puede bastar un endpoint de solo-lectura que parsee el archivo.
- Endpoint admin de **solo-lectura tras `auth.php`** por cada vista; **cero exposición pública** (lección de las fugas de
  017); **guard §2** ("datos insuficientes" con muestra baja); UI **Vanilla** (sin librerías pesadas), consistente con
  016/017/018/020.
- **Coexistencia:** no elimines las escrituras a archivo todavía (siguen como respaldo); el panel **lee**. Si migras a
  SQLite, define el punto de escritura (¿el propio `chat.php` escribe a tabla además del jsonl? ¿un import batch?).
- **No dupliques lo ya cubierto:** `chat_metrics` ya alimenta "Observabilidad Ops" (latencia/backend) y existe
  `lead_chat_history`. Enfócate en lo que **solo** está en archivos: `alerts.jsonl`, `usage_metrics.jsonl`,
  `leads_datanestiq.csv`, el reporte learn. Aclara el solape con lo existente.

### Parte 3 — Datos demo del panel de demanda (validar visualización sin contaminar lo real)
Hoy la pestaña "Demanda & Journey" muestra "Datos insuficientes" (N<20/N<10) porque no hay tráfico real aún. Para poder
**previsualizar** las visualizaciones de Bucket 1/2 sin fabricar demanda real:
- `scripts/seed_demand_demo.php` (correr con `/usr/bin/php8.2-cli`): inserta ~20 `demand_signals` **claramente marcadas**
  (`session_id` con prefijo `demoseed`, `query_redacted` con prefijo `[DEMO]`), idempotente, con mezcla offered/gap para
  que Bucket 1 (brechas) y Bucket 2 (fuga) rendericen.
- `scripts/purge_demand_demo.php` (o documentar `DELETE FROM demand_signals WHERE session_id LIKE 'demoseed%'`).
- **§2 (importante):** el panel debe **distinguir** lo DEMO de lo real — banner "incluye N filas DEMO" y/o un filtro de
  exclusión, para que la inteligencia de demanda real **nunca** se confunda con datos sembrados. El prefijo `[DEMO]` en
  los ejemplos ya los hace identificables; añade el conteo/filtro para claridad total.
- Deja claro en el spec que esto es **solo para validación de UI** y se purga antes de operar con datos reales.

## CONSTRAINTS (declararlas)
- **§2:** nada fabricado presentado como real; los datos demo van **marcados y separables**; guards de muestra mínima
  intactos.
- **Privacidad/Seguridad:** todo tras `auth.php`; PII redactada en cualquier texto mostrado; **cero endpoints públicos**
  de lectura; migraciones idempotentes con **backup + COUNT(*) pre/post** y `php8.2-cli`.
- **Coexistencia:** no romper CRM/telemetría existentes; archivos siguen escribiéndose; el panel lee.
- **Reuso:** UI Vanilla consistente; reusa `getCrmDb()`, patrones de endpoints admin y guards §2 ya presentes.

## OUT-OF-SCOPE (declararlo)
- **NO** exportar a CSV/Excel nuevos (el objetivo es lo contrario: llevar al panel).
- **NO** exponer nada públicamente. **NO** quitar las escrituras a archivo aún. **NO** LLM nuevo.
- GraphRAG / buscador sobre blog / MCP endpoint siguen en backlog aparte.

## data-model.md (define)
- Endpoint `journey_sessions`: query de sesiones recientes (UNION de leads + sesiones anónimas con resumen), con
  redacción y `session_id` interno.
- Para cada salida migrada: si va a SQLite, el DDL de la tabla (ej. `alerts`, `usage_metrics`) + índices + retención +
  punto de escritura; si se parsea el archivo, el contrato del endpoint.
- Demo seed: estructura de las filas marcadas + criterio de exclusión/conteo en las queries de Bucket 1/2.

## Entregables (solo diseño)
- `specs/021-panel-first/{spec.md, data-model.md, plan.md, tech_debt.md}`.
- Doc-sync: fila 021 en `ESTADO-SPECS.md` (🟠 diseñada) **y** `src/data/specsStatus.json` (build-gate antidrift) + `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará: Journey navegable con `session_id` **oculto** y resúmenes redactados tras
`auth.php`; migración de `alerts.jsonl`/`usage_metrics.jsonl`/`leads_datanestiq.csv`/reporte-learn al panel (preferente a
SQLite, sin duplicar lo ya cubierto, sin fuga pública, guards §2); y datos demo **marcados y separables** (§2) con purga.
Tras mi visto bueno se pasa a build.
