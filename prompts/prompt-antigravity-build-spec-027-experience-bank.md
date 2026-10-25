# Prompt AntiGravity — BUILD Spec 027: Experience Bank (Banco de Experiencia Curada 0-LLM)

## Estado y autoridad
El **diseño de la 027 está APROBADO** (re-auditoría 2026-08-10). Los 4 artefactos en
`specs/027-experience-bank/{spec.md, data-model.md, plan.md, tech_debt.md}` son el **SSOT**:
respétalos al pie de la letra. Este prompt autoriza la **construcción**. No re-diseñes ni cambies
contratos ya acordados; si encuentras un conflicto real con el código, **detente y repórtalo**,
no improvises.

Contratos reales ya verificados (NO re-descubrir):
- **Worker** (`public/worker.js`): indexar con `postMessage({ type:'index', corpusTexts, id })`;
  buscar con `postMessage({ query, corpusTexts, id })` (path por defecto). El worker responde
  `{ status:'complete', id, results:[{index, score}, ...] }` **ordenado desc por score**. Usa
  `results[0].score` y `results[0].index` (mapea a `experiences[index]`). El cache por
  `computeSignature(corpusTexts)` mantiene FAQ + blog + experience coexistiendo.
- **CSRF admin**: `generateCsrfToken()` / `verifyCsrfToken()` ya existen en `public/admin/api.php`
  y `auth.php`. Reúsalos; el bloque CSRF de POST ya está al inicio de `api.php`.
- **PII**: `redactPii()` hoy está inline en `public/api/track_event.php` — hay que extraerla.
- **Seam del router**: en `src/components/islands/Chatbot.jsx`, `matchFAQ(...)` (~L382) corre antes
  de `_callLLM(...)` (~L418). El Experience Bank va **entre** ambos.

---

## Fases de construcción (según plan.md)

### Fase 1 — Tabla `experience_bank` (`scripts/init_crm_db.php`)
- Añadir `CREATE TABLE IF NOT EXISTS experience_bank (...)` **exactamente** con el esquema de
  `data-model.md §1`, incluida la columna `cite_url VARCHAR(200) NULL` y los índices
  `idx_exp_bank_status` e `idx_exp_bank_expires`.
- Ejecutar la init para actualizar el esquema en local y (tras deploy) en producción.

### Fase 2 — Helper `redactPii()` compartido + Ingesta Passthrough
- **Extraer** `redactPii()` a `public/api/lib/pii.php`. **Elimina** la definición inline de
  `track_event.php` y añade `require_once __DIR__.'/lib/pii.php';` allí (evita *redeclare fatal*).
- `save_wizard.php` y `book_appointment.php`: `require_once __DIR__.'/lib/pii.php';`. Si el payload
  trae `last_complex_query` **y** `last_answer` (con `source_route`), insertan un candidato en
  `experience_bank` con `question_redacted = redactPii($last_complex_query)`, `answer`,
  `source_route`, `outcome` (`'lead'` para wizard, `'cita'` para appointment), `status='pendiente'`.
- **Guard outcome-gated**: si `last_complex_query` viene vacío, **no** se inserta nada.
- `Chatbot.jsx`: mantener en estado de sesión `last_complex_query`, `last_answer`, `source_route`
  al completarse una respuesta GraphRAG/LLM, y **enviarlos** en el payload de wizard/appointment.

### Fase 3 — Endpoints backend
- Crear `public/api/experience_corpus.php` (solo-lectura), filtro
  `WHERE status='aprobado' AND expires_at > datetime('now')`, respuesta con `id, question, answer,
  cite_url` (omite `cite_url` si es NULL — §2, sin URLs ficticias). Usa el mismo patrón de conexión
  SQLite que `chat.php`/`track_event.php`.
- En `public/admin/api.php` (switch por `action`), añadir:
  - `experience_pending` (GET): lista candidatos `status='pendiente'` (ver contrato §2.3.a).
  - `experience_review` (POST): valida CSRF con `verifyCsrfToken()`, aplica `status`
    (`aprobado`/`rechazado`), `answer` opcional editada, `cite_url` opcional, `expires_days`
    (recalcula `expires_at`), setea `approved_at` y `reviewed_by`. Usa **placeholders únicos**
    (no reutilizar `:id`).

### Fase 4 — Integración 0-LLM en `Chatbot.jsx`
- Al montar la isla: `fetch('/api/experience_corpus.php')` → `experiences`. Guardar
  `experienceTexts = experiences.map(e => e.question)`.
- Indexar: `postMessage({ type:'index', corpusTexts: experienceTexts, id })`.
- **Entre** `matchFAQ` fallido y `_callLLM`: buscar con
  `postMessage({ query: userText, corpusTexts: experienceTexts, id })`. Si `results[0].score >= 0.75`
  → entregar `experiences[results[0].index].answer` **0-LLM**, con distintivo transparente
  *"Respuesta de Experiencia Curada Datanestiq"* y, si hay `cite_url`, el enlace.
- Si `< 0.75` o corpus vacío → caer limpiamente a GraphRAG + `_callLLM` (arranque en frío TD-027.3).

### Fase 5 — Módulo de Curación en `public/admin/index.php` (`view-behavior`)
- Crear contenedores hermanos `#experience-pending-table` y `#experience-bank-grid` dentro de
  `view-behavior` (vistas **hermanas**; `switchTab`/`switchLeadsSubTab` deben manejar **todas** las
  ramas — regla de panel del proyecto).
- `loadExperienceBank()` usando el helper defensivo `setHTML(id, html)`. Botones aprobar/editar/
  rechazar que hagan POST a `experience_review` con el `csrf_token` de sesión.
- Badge con contador de pendientes (TD-027.1).

### Fase 6 — Build-Gate, Deploy y Verificación
- **NO marcar `027` como COMPLETED/LIVE hasta que la verificación EN VIVO (abajo) pase.** Mientras
  tanto, mantener SSOT honesto. Solo tras verde real, actualizar `planes/ESTADO-SPECS.md` y
  `src/data/specsStatus.json` (`status` a LIVE/COMPLETED, `lastAuditDate` a la fecha real,
  `isHonest: true`), añadir el marcador de auto-verificación `experiences` en
  `scripts/deploy/deploy_ionos.py`, y correr `node scripts/build-specs-status.mjs` (Antidrift 100%).
- **Orden de deploy obligatorio** (PHP se sirve desde `dist/`): `npm run build` **antes** de
  `python scripts/deploy/deploy_ionos.py --confirm`. Sin recompilar se sube la versión vieja.

---

## Residuales del diseño a resolver en el build (integrados)

### R1 — Endpoint para `usage_count` (no dejarlo como métrica muerta)
`data-model §3.2` promete incrementar `usage_count`, pero el cliente JS no ejecuta SQL. Añade una
acción **`experience_hit`** en `public/admin/api.php` **o** un pequeño endpoint público dedicado que
haga `UPDATE experience_bank SET usage_count = usage_count + 1 WHERE id = ?` (placeholder único).
`Chatbot.jsx` lo invoca en background al servir una experiencia 0-LLM. Si decides no exponerlo,
entonces **elimina `usage_count` del contrato** (§2: no prometer una métrica que no se puebla).

### R2 — `demand_signal`: emisión única, no duplicada
En el flujo real ya se emite un `demand_signal` por consulta compleja. Al resolver por experiencia,
**no** emitas un segundo `demand_signal` disperso (crearía una fila sin intent/confidence en
`demand_signals`). Fija `resolved_route='experience'` sobre la emisión existente de esa consulta, o
carga el mismo contexto de query en la emisión. Verifica el insert real en `track_event.php`.

---

## §2 / Seguridad (obligatorio)
- Cero datos fabricados: `[EST]` solo con base real; nunca inventar `cite_url`.
- Ningún candidato `pendiente` se expone al público ni al worker.
- `experience_corpus.php` solo devuelve `aprobado` y vigente. `experience_pending`/`experience_review`
  solo tras `auth.php` (admin) + CSRF en el POST.
- La respuesta 0-LLM debe ir con provenance visible ("Experiencia Curada Datanestiq").

## Criterios de aceptación — VERIFICACIÓN EN VIVO (no local, no "confío en el reporte")
1. **Endpoint público desplegado de verdad**: `curl` a
   `https://app.datanestiq.com/api/experience_corpus.php?cb=<rand>` → 200 + JSON con el shape real
   (`experiences[]`, y `cite_url` omitido cuando NULL). Marcador que solo emite el código nuevo.
2. **Privacidad**: `curl` a cualquier ruta que intente leer candidatos `pendiente` sin sesión admin
   → **no** los devuelve (404/403/no expuestos).
3. **Admin click-through**: en `/admin/` → `view-behavior`, la sub-sección de curación **carga datos**
   (no solo el encabezado), y aprobar/editar/rechazar un candidato refleja el cambio en la BD.
   Consola **sin** `ReferenceError`.
4. **0-LLM en el chatbot**: con al menos 1 experiencia aprobada, una consulta que supere 0.75 se
   sirve 0-LLM con el distintivo; una que no lo supere cae a GraphRAG/LLM sin error. Corpus vacío =
   fallback limpio.
5. **`usage_count`** sube al servir (o el campo fue removido del contrato — coherente con R1).
6. **Reporte honesto**: 200/500 reales, datos/vacío reales, JS Syntax Gate (`node --check`) y
   `php -l` de `experience_corpus.php`, `lib/pii.php`, `admin/api.php` en verde. No declarar LIVE si
   algo falla.

> Yo (auditor) re-verifico en vivo con el checklist `verificar-en-vivo` antes de dar el cierre.
> "Verde" del maker ≠ realidad hasta consultar el endpoint real y hacer click-through.
