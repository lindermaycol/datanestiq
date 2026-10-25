# CIERRE — Spec 026: añadir el gráfico de Tendencia faltante (US-026.3) + marcar LIVE

Reauditoría en vivo: las **4 correcciones quedaron bien aplicadas** — telemetría con timing correcto (JSON `{q,n,top}`,
verificado en vivo: match → `n:5,top:0.71`; sin match → `n:0`), parseo PHP seguro (fallback legacy), agrupación por query
normalizada, y `zero_match_rate` sobre `known_n_total`. Endpoint `blog_analytics` y panel bien cableados (contenedores
presentes + `setHTML` null-safe + enganchado a `view-behavior`). **Solo falta cerrar un criterio de aceptación.**

## 🟡 Único pendiente — US-026.3 (Tendencia de búsquedas) no se renderiza
El endpoint `blog_analytics` **ya devuelve** `daily_trend` (`[{ "date", "total", "zero_matches" }]`), pero en el panel:
- **No existe el contenedor `#blog-search-trend`** (el `plan.md` lo listaba; en `index.php` solo están
  `#blog-analytics-summary`, `#blog-top-queries`, `#blog-content-gaps`).
- `loadBlogAnalytics()` **no pinta** el `daily_trend`.
Resultado: se muestra la tarjeta de **Zero-Match Rate %**, pero **no** el gráfico de evolución diaria que pide US-026.3.

### Fix (`public/admin/index.php`)
1. **Añadir el contenedor** `#blog-search-trend` dentro de `#view-behavior`, junto a las otras tarjetas blog-* (~L574, en
   la sub-sección "Analítica de Blog"). Como **hijo** de la vista, sin anidar en otra (regla `view-*` hermanas).
2. **Renderizar `daily_trend` en `loadBlogAnalytics()`** con `setHTML('blog-search-trend', …)` (null-safe): un **mini-gráfico
   ligero CSS/SVG** (mismo estilo que 023, sin librerías) — barras por día con `total` y las de `zero_matches` destacadas en
   ámbar, ordenadas cronológicamente. Los datos ya vienen ordenados `DESC` del endpoint; invierte para eje temporal
   ascendente si conviene.
3. **Respeta el guard §2:** en la rama `insufficient_data`, deja el contenedor de trend con el mismo mensaje de "muestra
   escasa" que las otras tarjetas (usa `setHTML`, sin crash si falta algo).

### (Opcional, precisión menor §2)
- `zero_match_rate` se etiqueta `[EST]` aunque es un **ratio exacto** calculado sobre `known_n_total`, no una estimación.
  Considera quitar el `[EST]` de ese label (o cambiarlo por "% sobre N con datos de resultados") para no marcar como
  estimado algo que es medido. Prioridad baja.

## Marcar 026 como LIVE en el SSOT (build-gate antidrift)
- `planes/ESTADO-SPECS.md` ↔ `src/data/specsStatus.json`: Spec 026 → **LIVE** (`phase` "LIVE y Operativa en Producción
  IONOS"), `openTechDebt` = TD-026.1/2/3, `lastAuditDate` = hoy, `isHonest: true`. `node scripts/build-specs-status.mjs` →
  PASSED 100%. `planes/Fases.md`: 026 entregada.

## Deploy/QA
- Solo `index.php` (+ docs SSOT). **`npm run build` ANTES de `deploy_ionos.py --confirm`** (JS gate + auto-verify de
  `blog_analytics`).
- **Reaudito EN VIVO** (skill `verificar-en-vivo`): en `/admin/` → Engagement & Comportamiento → sub-sección "Analítica de
  Blog": con muestra <15 se ve el estado honesto §2; el **gráfico de tendencia renderiza** (o su estado vacío), consola sin
  `SyntaxError`/`ReferenceError`. (El click-through final lo confirma el usuario tras re-login.)

## OK / no tocar (verificado en vivo)
- Telemetría `{q,n,top}` con `n` real (incl. `n:0`) ✅ · parseo PHP seguro + agrupación normalizada ✅ · `zero_match_rate`
  sobre `known_n` ✅ · aislamiento `blog-search` (sin colisión con `graphrag`) ✅ · guard §2 <15 ✅ · `setHTML` null-safe +
  contenedores presentes ✅ · JS syntax gate + auto-verify `blog_analytics` 200 ✅.

---
**Nota:** Claude (Opus 4.8) verificó en vivo la telemetría y el endpoint. Lo único que falta para cumplir **todos** los
criterios de aceptación es **pintar la tendencia diaria** (los datos ya los devuelve el endpoint). Con eso + LIVE en el
SSOT, la 026 queda cerrada.
