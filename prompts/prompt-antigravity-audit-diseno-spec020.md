# Auditoría DISEÑO Spec 020 (Inteligencia de Demanda + Journey) — APROBADO con 5 precisiones para el BUILD

Diseño **sólido y bien fundamentado**. Verifiqué contra el esquema real y **las premisas se sostienen** (a diferencia
de rondas anteriores): `leads.session_id UNIQUE`, `appointments/chat_metrics/interaction_events.session_id`,
`status_history.old_status/new_status` y `src/data/taxonomyCorpus.json` **existen todos**. §2 (guards N=20/N=10 +
"hipótesis de demanda no atendida"), 0-LLM cliente (Xenova sobre `taxonomyCorpus`), privacidad (redactPii + journeys
tras `auth.php`), seguridad (track_event.php write-only con rate-limit) y coexistencia (tabla dedicada `demand_signals`,
CRM intacto) están bien planteados. Retención cubierta como deuda honesta (TD-020-02: cron 180d alineado con 018).
Apruebo para build con 5 precisiones.

## 🟠 Precisión 1 (la importante) — el Journey Reconstructor OMITE la tabla `interactions` (fuente confiable del CRM)
La query de journey (`data-model.md §4.3`) une `interaction_events` (018, **best-effort**) + `demand_signals` +
`status_history`, pero **no incluye `interactions`** (014/016) — el log **confiable** de interacciones del lead
(contactos, notas del CRM). Para un lead **convertido** (buckets 3/4), ese es justamente el tramo más fiable del
recorrido. Es la lección de coexistencia de la 018: `interactions` es la fuente confiable del journey, `interaction_events`
es el complemento best-effort. **No la omitas.**
- **Fix:** añade un `UNION ALL` desde `interactions`. **Ojo:** `interactions` **no tiene `session_id`** (verificado en
  `init_crm_db.php`) → únela por `lead_id`, igual que `status_history`:
  ```sql
  UNION ALL
  SELECT 'crm_interaction' as source, i.type as activity, NULL as target, i.notes as detail, i.created_at as ts
  FROM interactions i JOIN leads l ON i.lead_id = l.id
  WHERE l.session_id = :session_id
  ```
  (ajusta los nombres de columna a los reales de `interactions`). Así el journey del cliente ganado incluye los toques
  reales del CRM, no solo los beacons.

## 🟠 Precisión 2 (rendimiento) — el catálogo como 3er corpus por-consulta agrava el cache de UN solo corpus del worker
Hoy el worker cachea **un** corpus (`cachedCorpusData`, keyed por firma). Con la 020, cada texto libre hará **tres**
búsquedas por consulta: intención + FAQ + **catálogo**. Con el cache de un solo corpus, alternar entre los tres provoca
**re-embedding** (thrash) en cada consulta → latencia. TD-020-01 solo menciona el costo de **inicialización**, no el
**por-consulta**. Esto es exactamente TD-019-01 (worker singleton multi-corpus) amplificado.
- **Fix en build:** implementa **cache multi-corpus** en `worker.js` (cierra TD-019-01): mantener embeddings de
  intención, FAQ y catálogo cacheados **simultáneamente** por id/firma, sin re-embeber por consulta. O, si prefieres v1
  simple, indexa el catálogo una vez (como intención) y documenta explícitamente que las tres búsquedas reusan cache.
  No dejes que el catálogo re-embeba en cada mensaje.

## 🟠 Precisión 3 (rigor de migración en la DB viva) — súbelo al Plan de Verificación
`plan.md §1.1` dice "no destructiva", pero el **Plan de Verificación (§2)** no operacionaliza el protocolo de DB viva que
exigimos en 016/018. Añádelo explícito:
- **Backup** `cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak` antes.
- **`COUNT(*)` de `leads` pre/post** migración → prueba de cero pérdida.
- Migración remota con **`/usr/bin/php8.2-cli`** (el php por defecto del server es 4.4.9); deploy **dry-run → `--confirm`**
  del usuario (no unilateral).

## 🟡 Precisión 4 (§2/privacidad en Bucket 1) — limita ejemplos y reconoce que redactPii es imperfecto
`GROUP_CONCAT(query_redacted, ' | ')` concatena **todas** las consultas del grupo → puede volcar cientos de textos y
contexto de negocio identificable (redactPii es regex: quita emails/teléfonos/nombres, **no** "soy el CFO de ⟨empresa⟩").
- **Fix:** limita a **top 3-5 ejemplos** por grupo (subconsulta con `LIMIT`), no un GROUP_CONCAT sin tope. Declara en el
  spec que los ejemplos son redactados-imperfectos y por eso se muestran pocos y solo tras `auth.php`.

## 🟡 Precisión 5 (consistencia Bucket 2) — alinea prosa y SQL, y declara la atribución aproximada
`spec.md §2.3` dice fuga = "`offered=true` **o** intención `faq`/`guiado`", pero la SQL (`data-model §4.2`) filtra solo
`offered = 1`. Alinea ambos (elige el criterio y aplícalo en los dos). Además, `converted_leads` por `matched_service`
vía `LEFT JOIN leads ON session_id` **atribuye un lead a cada servicio** en que la sesión mostró interés (un lead no está
atado a un servicio) → la fuga por-servicio es **aproximada**. Decláralo como tal en el panel (no como cifra exacta, §2).

## OK tal como está (no cambiar)
- Tabla `demand_signals` dedicada + índices (session/offered/created) ✅; costura por `session_id` verificada ✅.
- `demand_signal` vía `track_event.php` con misma validación/rate-limit/PII (plan §1.1) ✅; endpoints admin tras `auth.php` ✅.
- §2: guards N=20/N=10, "datos insuficientes", `offered=false` como **hipótesis** ✅; 0-LLM cliente ✅; retención 180d (TD-020-02) ✅.
- Deudas honestas (TD-020-01/02/03) incl. ruido de agrupamiento por `matched_service` ✅.
- Doc-sync: `specsStatus.json` 020=DESIGNED + `ESTADO-SPECS.md` + `Fases.md`; build pasó (build-gate antidrift verde) ✅.

## Siguiente paso
Procede al **BUILD** con las 5 precisiones (P1 y P2 son las de fondo: journey con `interactions`, y cache multi-corpus
del worker). `php -l` en cada PHP; script de eval offline del clasificador de catálogo (plan §2.1); deploy gateado con
backup + COUNT(*). Entrégame el reporte con evidencia y **reaudito en vivo** (demanda no-ofrecida hipótesis, fuga,
journey completo de un lead ganado incluyendo toques del CRM, 0-LLM sin regresión de latencia, guards §2, sin fuga pública).

---
**Nota:** Claude (Opus 4.8) verificó que las premisas de datos del diseño **existen de verdad** (session_id en leads,
taxonomyCorpus.json, campos de status_history). El diseño es aprobable; las 2 precisiones de fondo son (1) incluir la
tabla `interactions` en el journey (fuente confiable del CRM, unida por lead_id) y (2) resolver el thrash de cache al
sumar el catálogo como 3er corpus por-consulta (cerrar TD-019-01). Tras el build, reaudito en vivo.
