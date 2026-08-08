# Prompt para Antigravity — Refinar el modelo del Business Case Estimator (creíble y defendible)

Contexto: el `<BusinessCaseEstimator>` (`src/components/islands/BusinessCaseEstimator.jsx`, ruta `/business-case`) funciona y respeta los guardarraíles (client-side, badge `[EST] Escenario Ilustrativo`, opt-in vía `save_wizard.php`). **Pero el modelo produce cifras no creíbles:** con ingresos $5M / costos $2M / eficiencia 25% dio **ROI 1775% y Payback 0.5 meses**. Aunque está etiquetado como ilustrativo, un ROI de 1775% **alimenta exactamente la objeción del CFO** ("el ROI de datos es abstracto/inflado") y resta credibilidad. Objetivo: que las salidas caigan en **rangos defendibles**, sin romper nada.

## Causa raíz (en el cálculo actual, líneas ~40–46)
```
annualSavings = costBase * (efficiencyGain/100)            // aplica la eficiencia al 100% de TODOS los costos
newRevenue    = revenue * (1 + efficiencyGain/100 * 0.5)   // crecimiento de ingresos sobre TODA la facturación
totalCost     = 50000 + 50000*0.2 = 60000                  // costo fijo diminuto vs P&L del cliente
roi           = (benefit - 60000) / 60000 * 100            // → explota para cualquier empresa grande
paybackMonths = 50000 / (benefit/12)                       // → sub-mes
```
Cuatro defectos: (1) la eficiencia se aplica a **toda** la base de costos, no a la parte **direccionable**; (2) asume crecimiento de ingresos **inmediato** sobre toda la facturación; (3) el **costo de implementación es fijo** ($50k) sin importar el tamaño del cliente; (4) **no hay curva de adopción/ramp** — cuenta el beneficio pleno en el año 1.

## Refinamiento (principios, no fórmula rígida — usa tu criterio pero cumple los rangos)
1. **Alcance direccionable.** La eficiencia se aplica solo a una **fracción direccionable** de la base de costos (los costos que datos/IA realmente pueden tocar), no al 100%. Introduce un supuesto explícito (p. ej. *"% de costos direccionables por datos/IA"*, default conservador ~25–35%, idealmente ajustable por el usuario).
2. **Curva de adopción (ramp).** El beneficio del **año 1** se realiza parcialmente (p. ej. factor de ramp ~40–60%), alcanzando el estado estable después. No cuentes el beneficio pleno desde el mes 1.
3. **Costo de implementación realista.** Que **escale** con el tamaño del proyecto (p. ej. función de la base de costos/ingresos o un rango por tramos), no un fijo de $50k. Alternativamente, presenta el ROI sobre un **horizonte de 3 años** (beneficio acumulado vs TCO a 3 años) en vez del año 1, que es más honesto para inversión en datos.
4. **Uplift de ingresos MUY conservador (u opcional).** Un salto de ingresos de 12.5% instantáneo por IA no es creíble. Redúcelo drásticamente (fracción pequeña de la eficiencia, aplicada solo a un margen, con ramp) o hazlo un input opcional desactivado por defecto.
5. **Presenta rangos, no un número heroico.** Idealmente muestra el ROI/payback como **rango** (conservador–optimista) y redondea. Mantén el badge `[EST]` y **actualiza la caja "Supuestos del Modelo"** para que refleje el nuevo modelo (direccionable, ramp, horizonte).

## Rangos objetivo (autochequeo de credibilidad)
Con el escenario de prueba (ingresos $5M, costos $2M, eficiencia 25%), las salidas refinadas deben caer en rangos **defendibles**, del orden de:
- **ROI:** decenas a bajos-cientos por ciento (aprox. **30%–250%**), **no** miles.
- **Payback:** **~6–24 meses**, no fracciones de mes.
(No son valores exactos obligatorios; son el sanity check de que ya no hay cifras fantásticas.)

## Guardarraíles (no romper)
- **Client-side** (sin fetch para calcular); PII solo en el opt-in vía `save_wizard.php` (no cambies ese flujo).
- Badge `[EST] Escenario Ilustrativo` + supuestos visibles **actualizados** al nuevo modelo.
- Honestidad: cero cifras presentadas como cliente real; el modelo es ilustrativo pero **defendible**.
- `npm run build` verde; sin errores de consola; no toques otras islas.

## Verificación (evidencia real al reportar)
Corre 3 escenarios y pega las salidas **antes/después** para confirmar credibilidad:
1. **SMB:** ingresos $500k, costos $300k, eficiencia 15%.
2. **Mid-market:** ingresos $5M, costos $2M, eficiencia 25%.
3. **Enterprise:** ingresos $50M, costos $20M, eficiencia 30%.
En los tres, ROI y payback deben ser creíbles (sin miles de % ni sub-mes). Incluye captura de la caja de supuestos actualizada. `npm run build` verde. Sección final "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en el navegador que las cifras caigan en rangos defendibles con los 3 escenarios, que los supuestos visibles reflejen el nuevo modelo, y que el badge `[EST]`, el cálculo client-side y el opt-in seguro sigan intactos.
