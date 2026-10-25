# Auditoría plan de BUILD Spec 020 — LUZ VERDE con 3 cautelas

El plan integra **las 5 precisiones correctamente**: P1 (journey une `interactions` por `lead_id`), P2 (worker
`cachedCorpora={}` multi-corpus), P3 (backup + COUNT(*) pre/post + `php8.2-cli`), P4 (top-5 ejemplos, no GROUP_CONCAT
ilimitado), P5 (fuga con etiqueta de atribución aproximada). Apruebo el build con 3 cautelas — la primera es la de riesgo.

## 🟠 Cautela 1 (riesgo de regresión) — el refactor del worker toca lo que YA funciona
Cambiar `cachedCorpusData` (single) → `cachedCorpora={}` (keyed por firma) es el cambio **más riesgoso**: `worker.js`
es **compartido** por el buscador semántico (SemanticSearch), el router 019 (intención + FAQ) y ahora el catálogo. Un
error aquí rompe features en vivo que costó sangre dejar sanos.
- Actualiza **todos** los sitios que leen/escriben el cache: `indexCorpus`, el handler `index`, y **la lógica de
  búsqueda** (el fallback cache-miss y el producto punto deben leer de `cachedCorpora[signature]`, no del viejo single).
- Cada búsqueda debe resolver su corpus por **firma** (`computeSignature(corpusTexts)`) → sigue siendo compatible con
  cómo el 019 pasa `corpusTexts` inline. No cambies el contrato de mensajes del worker sin necesidad.
- **Regresión obligatoria tras el refactor (reaudito en vivo):** (a) el **buscador semántico** sigue devolviendo
  resultados; (b) la **FAQ 0-LLM del 019** sigue resolviendo (objeción verbatim → sin `chat.php`); (c) `cita`/`guiado`
  intactos. No basta con probar el catálogo nuevo.

## 🟠 Cautela 2 (0-LLM / latencia) — la emisión de `demand_signal` debe ser NO bloqueante en CADA texto libre
La clasificación de demanda corre para **toda** consulta de texto libre (no solo las que van al LLM). Asegura que:
- El cálculo de `matched_service`/`offered` + el `trackEvent('demand_signal', …)` **no retrasen** la respuesta al
  usuario: dispáralo **fire-and-forget** (como los beacons de 018) o **después** de renderizar la respuesta del router.
  El usuario nunca espera por la telemetría de demanda.
- Reusa `session.ts`/`trackEvent` (sendBeacon) → sin bloqueo, sin PII (redacción en cliente antes de enviar).
- Con el cache multi-corpus (Cautela 1) la búsqueda de catálogo es barata; verifícalo (la prueba de latencia del plan,
  `<50ms` reusando `cachedCorpora`, es el criterio correcto).

## 🟠 Cautela 3 (migración en DB viva) — remoto con `php8.2-cli` y deploy gateado
El Plan de Verificación muestra `php scripts/init_crm_db.php` (local). En **producción (IONOS)**:
- Ejecuta la migración remota con **`/usr/bin/php8.2-cli`** (el php por defecto del server es 4.4.9), como dice tu nota
  de User Review. Si `sqlite3` CLI no está en el shared host, haz el `COUNT(*)` pre/post también vía `php8.2-cli`.
- Deploy **dry-run → `--confirm` del usuario** (no unilateral). Backup `crm.sqlite.bak` antes; COUNT(*) de `leads`
  idéntico pre/post (cero pérdida) + `demand_signals` existe.

## OK tal como está (no cambiar)
- Tabla `demand_signals` no destructiva en `init_crm_db.php`; `track_event.php` extendido con `redactPii()` + inserción
  desestructurada + misma validación/rate-limit (write-only) ✅.
- Endpoints admin tras `auth.php`: `demand_signals` (N≥20 + hipótesis), `leakage` (N≥10 + atribución aproximada),
  `lead_journey` (con `interactions` por `lead_id`) ✅.
- Retención 180d como `DELETE` idempotente en la consulta admin (autenticada, baja frecuencia) — declarada ✅.
- Panel Vanilla (sin librerías pesadas) ✅; eval offline del clasificador de catálogo (umbral 0.60) ✅.
- `specsStatus.json` 020→LIVE **solo tras** el build, coincidiendo con `ESTADO-SPECS.md` (build-gate) ✅.

## Siguiente paso
Build con las 3 cautelas. `php -l` en cada PHP. Al terminar, entrégame el reporte con evidencia y **reaudito EN VIVO**:
1. **Sin regresión** del worker: buscador + FAQ 0-LLM (objeción verbatim sin `chat.php`) + cita/guiado.
2. **Demanda 0-LLM:** una consulta fuera de catálogo ("¿venden repuestos de autos?") → `offered=false` registrado; una
   dentro de catálogo → `offered=true` + `matched_service`. Beacon a `track_event.php`, **no** a `chat.php`.
3. **Journey completo** de un lead ganado incluyendo los toques del CRM (`interactions`), ordenado y sin duplicados.
4. **Guards §2:** con muestra < N → "datos insuficientes"; demanda no-ofrecida como **hipótesis**; ejemplos limitados.
5. **Sin fuga pública** (curl a rutas públicas: nada de demanda/journey expuesto); sitio sano; migración sin pérdida de leads.

---
**Nota:** Claude (Opus 4.8) reauditará EN VIVO. La cautela de fondo es el **refactor del worker** (Cautela 1): es
compartido y toca features sanos — la regresión de buscador + FAQ 0-LLM es obligatoria, no opcional. Deploy gateado con
backup. Tras el build, verifico demanda/fuga/journey reales con guards §2 y cero fuga pública.
