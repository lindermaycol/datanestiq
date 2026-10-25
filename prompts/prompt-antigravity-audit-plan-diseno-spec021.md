# Auditoría plan de DISEÑO Spec 021 — APROBADO para redactar artefactos, con 4 precisiones a incorporar

El plan (meta: redactar los artefactos de diseño) es **fiel** al encargo y cubre todo: `journey_sessions`, migración de
`alerts.jsonl`/`usage_metrics.jsonl`/`leads_datanestiq.csv`/reporte-learn, endpoints admin, tablas SQLite para
alertas/uso, demo marcado, doc-sync + build-gate. Apruebo que **redactes los artefactos** (sigue siendo solo diseño,
cero runtime), **incorporando estas 4 precisiones** para que el diseño quede completo y no haya que rebotarlo.

## 🟠 Precisión 1 — define EXPLÍCITAMENTE el punto de escritura de `alerts`/`usage_metrics` (dual-write fail-safe)
La migración a SQLite necesita decir **quién escribe la tabla**. Diséñalo como **dual-write en `chat.php`**: inserta en la
tabla SQLite (`alerts`, `usage_metrics`) **y** mantiene el `@file_put_contents(...jsonl)` como respaldo — ambos
**fail-safe** (reusa el patrón try/catch de `track_event.php`: si la escritura a DB falla, no rompe la respuesta al
usuario). En `data-model.md`/`plan.md` especifica: DDL + índices + retención 180d + el punto exacto de inserción en
`chat.php`. **No** un import batch que dependa de un cron frágil como única vía (el cron puede ser complemento de
retención, no la fuente).

## 🟠 Precisión 2 — `journey_sessions`: paginación, índices y `session_id` estrictamente oculto
- La query lista sesiones recientes uniendo leads + sesiones anónimas (resumen de `demand_signals`/`interaction_events`).
  Puede ser pesada → diseña con **`LIMIT` + paginación** (ej. últimas 50, orden por última actividad) e **índices** que
  la soporten (ya hay `idx_*_session`/`idx_*_created`). Define la derivación del resumen: sector/rol (última no vacía),
  1ª consulta redactada, nº eventos, última actividad.
- **§2/privacidad:** el `session_id` va como **data-attribute oculto** en la fila, **nunca** en texto visible ni en la
  URL de la petición mostrada al usuario. Decláralo en el spec.

## 🟠 Precisión 3 — separación DEMO: dónde se excluye, y toggle/banner explícito
"Criterio de exclusión" debe ser concreto: por **defecto**, las queries de Bucket 1/2 (y `journey_sessions`) **excluyen**
`session_id LIKE 'demoseed%'` → el panel muestra **solo datos reales**. Un **toggle explícito** "Incluir datos DEMO"
(o un banner "Mostrando N filas DEMO") permite verlos, **siempre etiquetados**. Así la inteligencia real nunca se
confunde con lo sembrado (§2). Especifica esto en `data-model.md` (las queries llevan el filtro) y en `plan.md` (la UI).

## 🟠 Precisión 4 — `leads_detected`: son leads de contacto SIN redactar (el objetivo), solo tras auth; aclara el solape con CRM
`leads_datanestiq.csv` (de `extraer_leads.php`) contiene **contacto real extraído por LLM** (nombre/email/teléfono) de
conversaciones que **no** llenaron el formulario. Diferencia clave frente a las vistas de comportamiento:
- Esta vista **muestra el contacto sin redactar** (ese es el punto — como la vista de leads del CRM) → **solo tras
  `auth.php`, jamás público, jamás redactado-a-ciegas**. No apliques `redactPii` aquí (rompería su utilidad); protégelo
  con auth, no con redacción.
- **Aclara el solape con la tabla `leads` del CRM:** ¿se dedupe por `session_id`/email para no repetir leads ya
  capturados por formulario? Decláralo (probablemente: mostrar solo los detectados que **no** existen ya en `leads`).

## OK tal como está (no cambiar)
- Alcance solo-diseño (cero runtime) ✅; 5 bloques + data-model + plan + tech_debt ✅.
- Endpoints tras `auth.php`, sin exposición pública, guards §2, coexistencia (no remover escritura a archivos) ✅.
- tech_debt anticipa el solape con `chat_metrics` y el costo de logs grandes sin indexar ✅.
- Doc-sync (ESTADO-SPECS/specsStatus/Fases) + build-gate antidrift ✅.

## Siguiente paso
Redacta los 4 artefactos con las precisiones 1-4. **No implementes runtime.** Entrégamelos y audito el diseño
(verificaré: dual-write fail-safe definido, `journey_sessions` paginado con `session_id` oculto, exclusión DEMO por
defecto + toggle, y `leads_detected` sin redactar pero solo tras auth + dedupe con CRM). Tras mi visto bueno → build.

---
**Nota:** Claude (Opus 4.8) auditará los artefactos. Las 4 precisiones son decisiones de diseño difíciles de cambiar
luego: (1) quién escribe alerts/usage (dual-write fail-safe en chat.php), (2) journey_sessions paginado + id oculto,
(3) DEMO excluido por defecto + toggle etiquetado, (4) leads_detected sin redactar tras auth + dedupe con CRM.
