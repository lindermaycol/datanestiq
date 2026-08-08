# DISEÑO SDD — Spec 022: File-Free (SQLite fuente única, retirar archivos funcionales)

**Tarea de DISEÑO, NO implementación.** Escribe los artefactos de la **Spec 022** (`specs/022-file-free/`): 5 bloques +
`data-model.md` + `plan.md` + `tech_debt.md`. **Entrégalos para auditoría de Claude ANTES de construir.** Cero runtime.
**Depende de la Spec 021** (ya cerrada y verificada en vivo: vistas de Ops/leads/learn, dual-write, exclusión demo §2).
Puede arrancar cuando quieras; coordina con la 023 (panel de negocio) para no chocar en `admin/api.php`/`index.php`.

## WHY
La 021 llevó varias salidas al panel pero con **dual-write** (sigue escribiendo `.jsonl`/`.csv` de respaldo), y **dos
funcionalidades siguen sin panel**: el **historial de conversaciones** y el **uso diario vs tope de LLM**. El usuario
quiere que **ninguna funcionalidad de monitoreo viva en archivos** — SQLite como **fuente única**, todo en su pestaña.
(Aceptó perder el fallback de archivo, mitigable con un backup corto de transición.)

## Inventario (auditoría de código de Claude) — qué migrar y qué NO
**Migrar a SQLite + panel (funcional/monitoreable):**
| Dato | Archivo hoy | Escrito por | Destino |
|---|---|---|---|
| Conversaciones redactadas | `chat_logs.jsonl` / `other_logs.jsonl` | `chat.php` | tabla `conversations` + **visor en panel** (NUEVO) |
| Conversaciones crudas (PII) | `secure_leads/chat_raw.jsonl` | `chat.php` | tabla **privada** `chat_raw` (alimenta extracción; NO se expone) |
| Uso diario vs tope | `secure_leads/daily_usage_*.json` | `chat.php` | tabla `usage_daily` (o derivar) + **vista "uso vs tope" en Ops** (NUEVO) |
| Leads detectados por LLM | `secure_leads/leads_datanestiq.csv` | `extraer_leads.php` | tabla `leads_extracted` (cierra TD-021-02); `leads_detected` la lee |
| Leads del wizard (fallback) | `secure_leads/leads_wizard.csv` | `save_wizard.php` | **retirar** (SQLite ya es primaria en 014) |
| Alertas / métricas de uso | `alerts.jsonl` / `usage_metrics.jsonl` | `chat.php` | ya en SQLite por 021 → **retirar el .jsonl** (dejar de dual-write) |

**NO tocar (son archivos a propósito, no "funcionalidades de monitoreo"):**
- Taxonomía/SSOT: `src/data/*.json`, `api/services.json` (build artifacts de `build-taxonomy.mjs`), yaml/personas (`apply.mjs`).
- Wiki/docs: páginas Astro + `AGENTS.md` (`docs-generator.mjs`).
- Contadores transitorios de rate-limit (`rate_limit_*.json`): plumbing efímero; **opcional** moverlos, recomiendo dejarlos.

## WHAT (alcance — diseño)
### 1. Vistas nuevas en el panel
- **Conversaciones (visor):** pestaña/sub-vista (tras `auth.php`) que lista conversaciones **redactadas** (paginado,
  por sesión/fecha), leídas de `conversations`. Reemplaza abrir `chat_logs.jsonl`.
- **Uso diario vs tope:** en "Observabilidad Ops", tarjeta que muestra **tokens/consumo de hoy vs el cap diario** (barra
  + %), leída de `usage_daily` (o derivada de `chat_metrics` + el cap configurado). Reemplaza `daily_usage_*.json`.

### 2. Migración a fuente única (SQLite)
- `chat.php`: escribir la conversación redactada a `conversations`, la cruda a `chat_raw` (privada), y el uso/cap a
  `usage_daily` — **en SQLite**, con el mismo patrón fail-safe (try/catch) de 021. Estos INSERT reemplazan los
  `@file_put_contents(...jsonl/.json)` correspondientes.
- `extraer_leads.php`: leer las conversaciones crudas de la **tabla `chat_raw`** (no del jsonl) y escribir los leads
  detectados a **`leads_extracted`** (no al CSV). Repuntar `leads_detected` (021) a esa tabla.
- `save_wizard.php`: quitar el fallback `leads_wizard.csv` (SQLite ya es primaria).

### 3. Retiro de archivos (transición segura)
- Estrategia de **cutover**: (a) un deploy con **dual-write** para validar que las tablas se pueblan correctamente en
  prod; (b) una vez verificado en vivo, **quitar las escrituras a archivo** (`chat_logs.jsonl`, `chat_raw.jsonl`,
  `alerts.jsonl`, `usage_metrics.jsonl`, `daily_usage_*.json`, `leads_datanestiq.csv`, `leads_wizard.csv`).
- **Backup corto de una vez**: antes de retirar, exporta/mueve los archivos existentes a un `secure_leads/_archive/`
  (o impórtalos a las tablas) para no perder el histórico. Declara qué histórico se importa vs se archiva.

## CONSTRAINTS (declararlas)
- **§2/Privacidad:** `conversations` (redactada) y el visor → PII redactada; **`chat_raw`** guarda PII cruda → tabla
  **privada, solo backend/extracción, jamás expuesta** ni siquiera en el panel; `leads_extracted` sin redactar **solo
  tras `auth.php`** (contacto comercial, como `leads_detected`). Cero endpoints públicos (curl-verificar 404).
- **Sin pérdida de datos en el cutover:** backup + `COUNT(*)` pre/post; import/archivo del histórico documentado;
  migración idempotente con `/usr/bin/php8.2-cli`; deploy **dry-run → `--confirm`**.
- **⚠️ Deploy del backend (lección 021):** el **PHP se sirve desde `dist/`** → **recompila `npm run build` ANTES** de
  `deploy_ionos.py --confirm` (esta spec toca mucho `chat.php`/`extraer_leads.php`/`save_wizard.php`/`api.php`; sin
  recompilar se re-sube la versión vieja). **Limpia opcache** si IONOS tiene `validate_timestamps=0`, y **verifica contra
  el endpoint/archivo real en prod**, no solo local (un marcador en la respuesta ayuda a confirmar que el deploy tomó).
- **Retención 180d** en `conversations`/`chat_raw`/`usage_daily` (alineado con 018/020).
- **No romper la tubería de extracción:** `extraer_leads.php` debe seguir funcionando leyendo de `chat_raw` tabla.
- **Fail-safe:** los INSERT en `chat.php` van en try/catch — si SQLite falla, no rompe la respuesta al usuario. (Al
  retirar el archivo, evalúa un fallback mínimo: p. ej., si el INSERT crítico falla, un log de error, no un jsonl completo.)

## OUT-OF-SCOPE (declararlo)
- **NO** migrar taxonomía/SSOT, wiki/docs ni build artifacts (son archivos por diseño).
- **NO** exponer `chat_raw` (PII) en ninguna vista. **NO** nuevas exportaciones CSV/Excel.
- Contadores de rate-limit: fuera de alcance (plumbing) salvo que el usuario lo pida.

## data-model.md (define)
- DDL + índices + retención de `conversations` (session_id, role, content_redacted, ts), `chat_raw` (session_id,
  content_raw, ts — privada), `usage_daily` (date UNIQUE, total_tokens, request_count, cap, …) o la derivación desde
  `chat_metrics` + cap configurado, y `leads_extracted` (session_id, nombre, email, telefono, intencion, created_at).
- Endpoints admin: `conversations` (paginado, redactado), `usage_daily`/uso-vs-tope. Repunte de `leads_detected` a tabla.
- **Mapa de retiro:** qué `@file_put_contents` se elimina, en qué orden (post-validación), y el plan de archivo/import del histórico.

## Entregables (solo diseño)
- `specs/022-file-free/{spec.md, data-model.md, plan.md, tech_debt.md}`.
- Doc-sync: fila 022 en `ESTADO-SPECS.md` (🟠 diseñada) + `src/data/specsStatus.json` (build-gate) + `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará: fuente única real (conversaciones/uso-diario/raw/leads en tablas, `.jsonl`/`.csv`
funcionales **retirados** tras validar), visores nuevos (Conversaciones redactadas, uso-vs-tope) tras `auth.php` sin
fuga, `chat_raw` PII **nunca expuesta**, cutover **sin pérdida** (backup + COUNT + archivo del histórico), y que NO se
toquen los archivos de build/SSOT/wiki. Primero cierra los 3 arreglos de la 021; luego esto.
