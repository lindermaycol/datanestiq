# DISEÑO — Spec 027: Experience Bank (banco de experiencia — reuso 0-LLM de respuestas probadas)

Diseña la Spec 027 (SDD completo: `spec.md`, `data-model.md`, `plan.md`, `tech_debt.md` en `specs/027-experience-bank/`).
**NO implementes todavía** — primero el diseño; Claude lo audita antes del build. Al terminar, registra la 027 en
`planes/ESTADO-SPECS.md` ↔ `src/data/specsStatus.json` como `DESIGNED` (build-gate antidrift).

## 1. Objetivo y valor
Cierra el "learn" de la Spec 016 (`chat → lead → learn`): construir un **banco de experiencias** (interacciones **resueltas
con éxito**, curadas) que el chatbot **reutiliza 0-LLM** por similitud semántica **antes** de gastar el free-tier LLM.
Beneficio: **más 0-LLM** (menos latencia/costo/variabilidad del free-tier), **consistencia** (respuestas ya probadas que
convierten) y **auto-mejora** honesta. Reusa el patrón que ya funciona en el router 019 (`matchFAQ` + worker Xenova).

## 2. 🔴 El punto que hace o rompe la spec (§2 Honestidad Radical): CURACIÓN OBLIGATORIA — cero bucle de basura
**Prohibido auto-servir respuestas del LLM como verdad.** Si se ingieren respuestas crudas del free-tier y se re-sirven como
canónicas, un error/alucinación se **fosiliza** y se repite → viola §2. El diseño DEBE:
- **Ingesta solo de CANDIDATOS con outcome positivo** (outcome-gated): la interacción derivó en **lead capturado** o **cita
  agendada** (señal real de que la respuesta sirvió). Nada de "toda respuesta del LLM entra".
- **Aprobación humana en el panel antes de servir:** un candidato **NUNCA** se sirve hasta que un humano lo **apruebe** (y
  pueda **editar** la respuesta) en `/admin/`. Estados: `pendiente` → `aprobado`/`rechazado`. Solo `aprobado` es servible.
- **Provenance y no sobre-venta:** al servir, marca la respuesta como basada en una consulta previa resuelta (isFAQ-like),
  sin presentarla como más autoritativa de lo que es.
- **Freshness/TTL:** las respuestas caducan (precios/taxonomía cambian) → campo de revisión/vencimiento y re-aprobación.
Sin este gate, la spec no pasa auditoría.

## 3. Almacenamiento (tabla `experience_bank` en SQLite CRM — patrón de `init_crm_db.php`)
Propón el esquema (justifícalo), p. ej.:
```sql
CREATE TABLE IF NOT EXISTS experience_bank (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  question_redacted TEXT NOT NULL,     -- consulta PII-redactada (§6)
  answer TEXT NOT NULL,                -- respuesta curada/editada
  source_route VARCHAR(20),            -- 'llm' | 'graphrag' | 'manual'
  outcome VARCHAR(20),                 -- 'lead' | 'cita' | 'manual' (por qué es candidato)
  status VARCHAR(12) DEFAULT 'pendiente', -- pendiente|aprobado|rechazado
  usage_count INTEGER DEFAULT 0,       -- veces servida (analítica)
  created_at DATETIME DEFAULT (datetime('now')),
  approved_at DATETIME, reviewed_by VARCHAR(50), expires_at DATETIME
);
```
§6: `question_redacted` pasa por `redactPii()`; la respuesta aprobada no debe contener PII (el humano lo verifica al aprobar).

## 4. Recuperación 0-LLM (reusar el patrón `matchFAQ` / worker Xenova)
- **Punto de inserción exacto en el router (`src/components/islands/Chatbot.jsx`):** entre el **FAQ** y `_callLLM` — es decir,
  tras `matchFAQ` fallar y **antes** de la ruta compleja (GraphRAG+chat.php de la 025). Preserva el 0-LLM de `cita`/`guiado`/`faq`
  (esos **retornan antes**, no tocar).
- Reusa el worker con el **contrato real** (`corpusTexts` strings, resultados por índice, id único, `status:'complete'`), como
  024/025. El corpus = preguntas **aprobadas** del banco.
- **Umbral ALTO** (más estricto que el FAQ `0.65`, sugiere ~`0.75-0.80` a calibrar): servir una respuesta enlatada exige alta
  confianza de que aplica a la nueva consulta. Bajo el umbral → cae a la ruta compleja (025), sin forzar.
- **Corpus dinámico:** a diferencia de `FAQ_CORPUS` (estático en build), el banco **crece**. Sirve las experiencias
  **aprobadas** vía un endpoint (p. ej. `experience_corpus`, solo-lectura, **solo `status='aprobado'`**, redactado) que el
  chatbot carga al iniciar (como `intentsConfig`), y matchea en cliente (0-LLM). Cachea; mantén el payload pequeño.

## 5. Curación e ingesta
- **Ingesta (candidatos):** cuando una interacción de la ruta compleja deriva en lead/cita (señal ya disponible en el flujo:
  `logDemandSignal`/`save_wizard`/`appointments`), inserta un candidato `pendiente` con `question_redacted` + `answer` +
  `outcome`. **No** servible aún.
- **Panel de curación (`/admin/`):** vista para revisar candidatos `pendiente`: **aprobar / editar / rechazar**, con la consulta
  y la respuesta a la vista. Solo `aprobado` entra al `experience_corpus`. (Reusa el patrón de las vistas del panel; **contenedores
  presentes + `setHTML` null-safe + `view-*` hermanas** — lecciones `view-ops`.)
- Endpoints admin: `experience_pending` (listar candidatos), `experience_review` (aprobar/editar/rechazar, POST con CSRF).

## 6. Telemetría (Spec 018)
- Nuevo `resolved_route = 'experience'` → cuenta como **0-LLM** (junto a faq/cita/guiado) en `demand_signals`/analítica.
- Incrementa `usage_count` al servir; opcional: tarjeta de "experiencias más reutilizadas" en el panel.

## 7. OUT OF SCOPE
- **Sin auto-servir respuestas no aprobadas** (jamás). Sin ingesta indiscriminada de respuestas del LLM.
- Sin LLM/embeddings de pago; solo Xenova (edge) + SQLite. Sin infraestructura nueva pesada.
- Sin exponer PII ni datos privados; el `experience_corpus` público solo trae experiencias aprobadas y redactadas.
- No reemplaza FAQ/GraphRAG; es una **capa previa** de reuso de respuestas probadas.

## 8. Entregables SDD (`specs/027-experience-bank/`)
- `spec.md` (problema, usuarios: visitante + curador/admin; escenarios: reuso 0-LLM de una respuesta probada, curación,
  criterios §2 con el gate de aprobación).
- `data-model.md` (esquema `experience_bank`; contrato del worker `corpusTexts`; esquema de `experience_corpus`/`experience_pending`/
  `experience_review`; formato del match y umbral).
- `plan.md` (tabla + init_crm; ingesta outcome-gated; endpoints; recuperación en el router entre FAQ y LLM; panel de curación;
  telemetría; build/deploy).
- `tech_debt.md` (honesto: dependencia de curación humana, freshness/TTL, arranque en frío del banco vacío, calibración del umbral).

## 9. Reglas de deploy/QA (recordatorio de campo)
- Backend PHP se sirve desde `dist/` → **`npm run build` ANTES de `deploy_ionos.py --confirm`** (JS gate + auto-verify).
- **"endpoints ✅ ≠ panel/feature ✅":** exige **click-through en vivo** — servir una experiencia aprobada es **0-LLM**
  (sin `chat.php`); un candidato `pendiente` **no** se sirve; el panel de curación **carga datos** y aprobar/editar funciona;
  consola sin `SyntaxError`/`ReferenceError`. Considera añadir `experience_*` a la auto-verificación del deploy.

## 10. Decisiones abiertas para que resuelvas (justifícalas)
- Umbral de match (calibrar; > FAQ 0.65).
- ¿Auto-aprobación nunca, o un modo "sugerido" con revisión rápida? (recomiendo **nunca auto-aprobar**; §2).
- TTL/vencimiento y política de re-revisión.
- Estrategia de ingesta exacta (qué outcome dispara el candidato: lead vs cita vs ambos) y de-duplicación de preguntas.
- Dónde vive la curación (pestaña propia vs sub-sección de un tab existente del panel).

---
**Nota:** Claude (Opus 4.8) auditará el diseño y luego en vivo. Reusa `matchFAQ`+worker (contrato real `corpusTexts`) e insértalo
**entre FAQ y `_callLLM`** sin romper el 0-LLM del router 019. El **gate de curación** (outcome-gated + aprobación humana +
provenance + TTL) es **innegociable** por §2: el banco reutiliza lo **probado y aprobado**, nunca fosiliza respuestas crudas del
LLM. §6: PII redactada y solo experiencias aprobadas en el corpus público.
