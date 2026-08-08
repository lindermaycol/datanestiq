# Spec 022: File-Free Storage & Persistence Unification (Fuente Única SQLite)

## 1. Visión General y Propósito (WHY)
Actualmente, tras la Spec 021, la infraestructura de monitoreo e inteligencia utiliza un esquema de **escritura dual (Dual-Write)** en el que la información se persiste tanto en la base de datos SQLite (`secure_leads/crm.sqlite`) como en archivos planos `.jsonl` y `.csv` (`chat_logs.jsonl`, `other_logs.jsonl`, `chat_raw.jsonl`, `daily_usage_*.json`, `leads_datanestiq.csv`, `alerts.jsonl`, `usage_metrics.jsonl`). Además, dos capacidades críticas carecen de visibilidad nativa en el panel `/admin/`: el **historial de conversaciones** y el **consumo diario acumulado de tokens vs el tope diario (Daily Cap)**.

La Spec 022 consolida a **SQLite como la única fuente de verdad operacional**, eliminando por completo la dependencia y la escritura síncrona/asíncrona en archivos funcionales. Esto simplifica la arquitectura, mejora la seguridad (§6), elimina el riesgo de fuga por descargas no autorizadas de archivos planos en el sistema de archivos y ofrece visores nativos en el panel de control.

---

## 2. Alcance (WHAT)

### 2.1 Componentes e Inventario de Migración
| Entidad / Dato | Almacenamiento Actual | Proceso Emisor | Destino SQLite Definitivo | Visor en Panel / Endpoint |
|---|---|---|---|---|
| **Conversaciones Redactadas** | `chat_logs.jsonl`, `other_logs.jsonl` | `public/api/chat.php` | Tabla `conversations` | Nuevo Visor en Pestaña Conversaciones / `api.php?action=conversations` |
| **Conversaciones Crudas (PII)** | `secure_leads/chat_raw.jsonl` | `public/api/chat.php` | Tabla `chat_raw` (PRIVADA) | **Sin Visor** (Solo backend para `extraer_leads.php`) |
| **Uso Diario vs Cap** | `secure_leads/daily_usage_YYYY-MM-DD.json` | `public/api/chat.php` | Tabla `usage_daily` | Pestaña Ops / `api.php?action=usage_daily` |
| **Leads Detectados por LLM** | `secure_leads/leads_datanestiq.csv` | `scripts/extraer_leads.php` | Tabla `leads_extracted` | Sub-pestaña Leads Detectados / `api.php?action=leads_detected` |
| **Leads de Wizard (Fallback)** | `secure_leads/leads_wizard.csv` | `public/api/save_wizard.php` | **Retirado** (Tabla `leads` es primaria) | Pestaña Leads & Citas / `api.php?action=leads` |
| **Alertas & Métricas LLM** | `alerts.jsonl`, `usage_metrics.jsonl` | `public/api/chat.php` | Tablas `alerts` y `chat_metrics` | Pestaña Ops / `api.php?action=alerts_ops` y `usage_ops` |

### 2.2 Lo que NO se modifica (Out-of-Scope)
- **Archivos de Taxonomía y SSOT del Sitio**: `src/data/*.json`, `api/services.json`, `sectorsCorpus.json` (artefactos de pre-compilación de `build-taxonomy.mjs`).
- **Documentación y Wiki**: Páginas de Astro y `AGENTS.md` generadas por `docs-generator.mjs`.
- **Contadores de Rate-Limiting Efímeros**: `sys_get_temp_dir() . '/rate_limit_*.json'` (plumbing transitorio con auto-expiración de 10 min).

---

## 3. Especificación Funcional

### 3.1 Nuevo Visor de Conversaciones (`conversations`)
- Se añade un visor navegable dentro del panel de administración (`public/admin/index.php`) bajo la pestaña o sub-sección de Conversaciones.
- Muestra el listado de conversaciones sostenidas en el chatbot, paginadas (20 por página), agrupadas por `session_id`, mostrando fecha, número de mensajes, backend utilizado y previsualización del último mensaje redactado.
- Al seleccionar una conversación, se reconstruye la burbuja del diálogo interactivo.
- **Alcance**: Registra los intercambios de texto libre procesados por el backend `chat.php` (interacciones LLM y fallbacks). Las micro-interacciones 0-LLM puras (flujos guiados y FAQ directas sin llamada a servidor) se visualizan de forma complementaria en el Reconstructor de Journeys (Spec 020).
- **Garantía §2**: Todo el contenido mostrado en esta vista proviene de la tabla `conversations`, donde la PII ha sido redactada previamente en `chat.php`.

### 3.2 Visor de Uso Diario vs Cap (`usage_daily`)
- Se integra en la pestaña "Observabilidad Ops" una tarjeta dinámica que compara los tokens consumidos en el día en curso contra el límite diario configurado (ej: 500,000 tokens o $0.50 USD).
- Muestra barra de porcentaje de llenado, alertas de aproximación al tope (80% y 100%) y desglose acumulado de prompt/completion tokens del día.

### 3.3 Extracción de Leads sobre SQLite (`leads_extracted`)
- El script `scripts/extraer_leads.php` lee directamente de la tabla privada `chat_raw` en lugar del archivo `chat_raw.jsonl`.
- Inserta los leads detectados en la tabla `leads_extracted` de SQLite.
- El endpoint `public/admin/api.php?action=leads_detected` consulta la tabla `leads_extracted`, deduplicando contra la tabla `leads` del CRM.

---

## 4. Requerimientos de Seguridad y Privacidad (§2 y §6)
1. **Aislamiento Estricto de PII en `chat_raw`**: La tabla `chat_raw` almacena las transcripciones completas sin redactar para permitir que el script offline de NLP extraiga leads. **Esta tabla NO posee ningún endpoint en `api.php` y jamás se expone en la interfaz de administración ni al público**.
2. **Protección de Datos en `conversations` y `leads_extracted`**: Todos los visores y endpoints que leen `conversations` y `leads_extracted` requieren estar resguardados tras el middleware `requireAuth()` (`auth.php`).
3. **Manejo Fail-Safe**: Las escrituras en `chat.php` hacia SQLite (`conversations`, `chat_raw`, `usage_daily`, `chat_metrics`) se ejecutan dentro de bloques `try/catch`. En caso de falla en SQLite, la respuesta al usuario final del chatbot **nunca se bloquea**.

---

## 5. Estrategia de Transición y Cutover
1. **Fase de Backup e Importación Histórica**:
   - Antes de desactivar la escritura en archivos, se ejecuta el script CLI `scripts/migrate_file_free_history.php`.
   - Lee el contenido histórico de `chat_logs.jsonl`, `other_logs.jsonl`, `chat_raw.jsonl`, `leads_datanestiq.csv` y `daily_usage_*.json`, y los inserta de forma idempotente en sus respectivas tablas SQLite (`conversations`, `chat_raw`, `leads_extracted`, `usage_daily`).
   - Los archivos procesados se mueven al directorio de resguardo comprimido `secure_leads/_archive/`.
2. **Desactivación de Escrituras en Archivo**:
   - Se eliminan las funciones `@file_put_contents` dirigidas a los archivos funcionales en `chat.php`, `extraer_leads.php` y `save_wizard.php`.
3. **Verificación Pre y Post Cutover**:
   - Conteo de registros `COUNT(*)` en tablas SQLite vs número de líneas/filas en los archivos de origen.
