# DISEÑO SDD — Spec 020: Inteligencia de Demanda + Journey de Conversión

**Tarea de DISEÑO, NO implementación.** Escribe los artefactos de la **Spec 020**
(`specs/020-inteligencia-demanda-journey/`): 5 bloques (WHY/WHAT/CONSTRAINTS/OUT-OF-SCOPE/TASKS) + `data-model.md` +
`plan.md` + `tech_debt.md`. **Entrégalos para que Claude los audite ANTES de construir.** Cero runtime todavía.

## WHY (la pregunta del negocio)
El usuario quiere que **cada texto que un visitante escribe en el chatbot** se clasifique en uno de estos estados, y
que el panel `/admin/` los muestre agregados + el **journey completo por cliente**:

1. **Demanda NO ofrecida (gap / oportunidad):** el visitante pide algo que Datanestiq **no** ofrece hoy →
   señal de mercado para **un servicio nuevo**. ("N personas pidieron X que no tenemos.")
2. **Ofrecida pero NO capturada (fuga):** preguntó por algo que **sí** ofrecemos, mostró interés, pero **no** dejó datos
   ni agendó → **fuga de conversión** (teníamos el servicio y el interés, y lo perdimos).
3. **Capturado (lead):** dejó contacto / agendó → se volvió `lead`.
4. **Vendido (cliente ganado) + journey:** el lead avanzó a `status = ganado` → **cliente**, con su **recorrido completo**
   (primer toque → consultas → intenciones → servicios mostrados → lead → interacciones → cambios de estado → ganado/perdido).

Es la evolución natural del **loop chat→lead→learn** (Spec 016) + micro-analítica (018) + router 0-LLM (019): ahora no
solo medimos *cuánto* 0-LLM, sino **qué pide el mercado, dónde fugamos, y el journey de cada cliente**.

## WHAT (alcance — diseño)

### 1. Clasificación de demanda (0-LLM, reusa Xenova)
Al rutear cada texto libre (extiende la decisión del router 019), calcula además:
- **`matched_service`**: similitud coseno de la consulta contra el **catálogo de servicios real** (embeddings de
  `pillars` + `soluciones` de la taxonomía, mismo worker Xenova — **sin LLM, sin modelo nuevo**). El servicio más
  cercano y su score.
- **`offered` (bool):** `true` si `max_score ≥ umbral_catálogo`; `false` → **demanda no ofrecida** (bucket 1).
- Esto **reutiliza** la infraestructura de la 019 (worker, embeddings cacheados). La clasificación de demanda es 0-LLM.

### 2. Registro de la señal de demanda (reusa la observabilidad existente)
Cada decisión se registra vía el beacon existente (`track_event.php` / Spec 018) con un evento nuevo `demand_signal`:
`{ session_id, query_redacted, intent, confidence, matched_service|null, offered, resolved (0llm|llm), ts }`.
- **PII redactada** (reusa `redactPii` de 018): la query se guarda **sin** correos/teléfonos.
- `session_id` es la **llave de costura**: cuando el visitante deja datos, el `lead` guarda ese `session_id` (ya existe
  en 016/014 el vínculo sesión↔lead vía `interactions`), permitiendo unir la conversación con el lead y su journey.

### 3. Los 4 buckets, derivados de datos reales (no nuevos si ya existen)
- **Bucket 1 (no ofrecida):** `demand_signal.offered = false`, agregado por clúster semántico → "temas de demanda no
  atendida" + conteo + ejemplos redactados. **Solo si N ≥ umbral** (ver §2), si no → "datos insuficientes".
- **Bucket 2 (fuga):** sesiones con `demand_signal.offered = true` (interés en algo ofrecido) que **no** tienen `lead`
  asociado al cerrar. Métrica de fuga por servicio: interés vs captura.
- **Bucket 3 (capturado):** existe `lead` con ese `session_id` (tabla `leads` de 014/016 — **no** se reinventa).
- **Bucket 4 (ganado + journey):** `lead.status = 'ganado'`; el journey se arma con lo que **ya existe**: `interactions`
  + `status_history` (016) + los `demand_signal`/`intent_routing` de la sesión. **Coexistencia:** `interactions` sigue
  siendo la fuente confiable del journey del lead (lección de 018); 020 **añade** la capa de demanda encima, no la reemplaza.

### 4. Panel `/admin/` (tras `auth.php`, UI Vanilla como 016/017/018)
- **"Demanda no atendida"** (bucket 1): temas más pedidos que no ofrecemos, con conteo y ejemplos → input para roadmap
  de servicios. Con guard §2 (< N → "datos insuficientes").
- **"Fugas de conversión"** (bucket 2): por servicio ofrecido, cuántas sesiones con interés no capturaron lead.
- **"Journey por cliente"** (buckets 3-4): timeline por lead — primer toque (sector/rol), consultas e intenciones,
  servicios mostrados, creación de lead, interacciones, cambios de estado, resultado (ganado/perdido/en curso).
- **Funnel:** visitantes con interés → leads → citas → ganados, reusando `chat_metrics`/`interactions`.

## CONSTRAINTS (declararlas en el spec)
- **§2 (Honestidad radical):** todo agregado sale de **eventos reales logueados**; con muestra `< N` (define N, p. ej.
  20 para demanda, 10 para conversión) → **"datos insuficientes"**, nunca demanda/fuga/ROI fabricados. Los ejemplos de
  demanda se muestran **redactados**. La clasificación `offered=false` es una **hipótesis** ("posible demanda no
  atendida"), no una verdad — etiquétala así.
- **§5 (0-LLM):** la clasificación de demanda es **100% cliente** (Xenova, catálogo de servicios embebido) — **cero
  tokens LLM**. No se llama al LLM para clasificar demanda. Reusa worker + embeddings cacheados de la 019.
- **Privacidad:** query redactada (sin PII) antes de persistir; nada de PII en logs/paneles agregados; el journey por
  cliente (que sí muestra el lead) vive **solo** tras `auth.php`. Nada público (lección de las fugas de 017).
- **Seguridad:** si se necesita endpoint de escritura, es **write-only + same-origin (`app.datanestiq.com`) + rate-limit**
  (patrón de 018); lecturas de panel **solo tras `auth.php`**. Migración de DB con **backup + COUNT(*) pre/post** (016).
- **Coexistencia, no reemplazo:** `leads`/`interactions`/`status_history`/`chat_metrics`/`interaction_events` existentes
  **intactos**; 020 los **lee** y añade `demand_signal`. Cero pérdida de leads históricos.

## OUT-OF-SCOPE (declararlo)
- **NO** LLM para clasificar demanda (es Xenova/cliente). **NO** inventar demanda, fugas ni conversiones.
- **NO** reemplazar el journey de 016 (lo extiende). **NO** tocar el failover de `chat.php` ni el router 019 (solo
  extiende su salida con `matched_service`/`offered`).
- **NO** scraping ni enriquecimiento externo de leads. GraphRAG/MCP/buscador-blog siguen en backlog aparte.

## data-model.md (define)
- **Evento/tabla `demand_signal`** (o columna nueva en `interaction_events`): campos arriba; índices por `session_id`,
  `offered`, `ts`; retención (p. ej. 180d como 018).
- **Costura sesión↔lead:** confirma cómo `leads`/`interactions` guardan `session_id` hoy y cómo se une al journey
  (si falta la columna, `ALTER TABLE` idempotente vía `PRAGMA table_info`, como en 016 — con backup).
- **Umbral de catálogo** (`offered`) y de clustering de demanda, como parámetros versionados (junto a `intents.json` o
  un `demand.json`).
- **Queries del panel:** demanda no atendida (clúster + conteo), fuga por servicio, journey por lead, funnel — todas con
  el guard de muestra mínima.

## Entregables (solo diseño)
- `specs/020-inteligencia-demanda-journey/{spec.md, data-model.md, plan.md, tech_debt.md}`.
- Doc-sync: fila 020 en `ESTADO-SPECS.md` (🟠 diseñada) **y** `src/data/specsStatus.json` (**build-gate antidrift** de la
  017: deben coincidir). `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará: clasificación de demanda **100% cliente** (0 tokens, reuso del worker/catálogo de
019), buckets derivados de **datos reales** con guard de muestra mínima (§2, "datos insuficientes" cuando aplique),
demanda `offered=false` etiquetada como **hipótesis** (no verdad), **coexistencia** con el journey de 016 (leads
históricos intactos), PII redactada, panel tras `auth.php` sin fuga pública, y endpoints write-only con anti-abuso.
Tras mi visto bueno pasa a build.
