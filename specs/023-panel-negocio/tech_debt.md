# Deuda Técnica y Gestión de Riesgos — Spec 023: Panel de Negocio

## 1. Registro de Deuda Técnica

### TD-023-01: Derivación Aproximada del Ahorro 0-LLM (`$ [EST]`)
- **Riesgo**: El cálculo monetario de ahorro asigna una tarifa de mercado de referencia estándar ($0.30 USD por millón de tokens) multiplicada por el promedio de tokens reales consumidos en las llamadas exitosas de `chat_metrics`. No contempla variaciones en proveedores que utilicen esquemas de precios marcadamente superiores o inferiores.
- **Mitigación**: Exposición obligatoria del badge **`[EST]` Escenario Estimado** y nota transparente aclarando que los proveedores actuales operan en free-tier ($0 real). El indicador primario se basa en las **llamadas al LLM evitadas** (recuento directo de intenciones 0-LLM), cumpliendo estrictamente con la Constitución §2.

### TD-023-02: Retiro de la Vista de Specs del Panel `/admin/`
- **Impacto**: Los usuarios o desarrolladores ya no podrán inspeccionar la lista de specs de SDD desde el navegador web en `/admin/`.
- **Mitigación**: El archivo `src/data/specsStatus.json` se mantiene intacto como la única fuente de verdad (SSOT) para el script `scripts/build-specs-status.mjs` y la compilación estática de la Wiki. Los desarrolladores consultan el estado mediante CLI (`npm run build`).

### TD-023-03: Backfill de Sector y Rol en Leads Antiguos
- **Riesgo**: Leads que interactuaron sin seleccionar chips de sector/rol ni ingresar queries de demanda podrían quedar clasificados como `'no_especificado'`.
- **Mitigación**: El script de backfill es totalmente seguro e idempotente; no sobreescribe datos existentes si ya han sido completados en el CRM.

---

## 2. Matriz de Cumplimiento de Honestidad Radical (§2) y Seguridad

| Característica | Regla §2 Aplicada | Mecanismo de Seguridad |
|---|---|---|
| Ahorro 0-LLM | Badge **`[EST]`** en interfaz y JSON API. | Calculado sobre agregaciones de `interaction_events`. |
| Agenda de Citas | Muestra datos reales resguardados. | Requiere sesión activa (`auth.php`). |
| Gráficos CSS/SVG | Ocultos automáticamente si $N < \text{Umbral}$. | Reemplazados por aviso de "Datos insuficientes". |
| Deduplicación Vistas | 0 vistas redundantes entre Analítica y Ops. | Resguardado tras IP Whitelist / Bcrypt auth. |
