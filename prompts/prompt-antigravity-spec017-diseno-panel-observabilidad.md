# Spec 017 (DISEÑO) — Panel de Observabilidad Interna (Ops)

**Tarea de DISEÑO SDD, NO implementación.** Escribe los artefactos de la nueva **Spec 017**
(`specs/017-panel-observabilidad/`) con los **5 bloques** (WHY/WHAT/CONSTRAINTS/OUT-OF-SCOPE/TASKS) +
`data-model.md` + `plan.md` + `tech_debt.md`. **Entrégalos para que Claude los audite ANTES de implementar.**
No toques runtime todavía.

## Contexto
- La **Spec 016 está live** en `app.datanestiq.com`: existe la tabla `chat_metrics` (session_id, backend_used,
  latency_ms, success, tokens_est, created_at) y el endpoint `admin/api.php?action=analytics_llm_metrics`
  (latencia/éxito por backend). El panel `/admin/` con `auth.php` (password-only, ver `tech_debt.md` 014) ya opera.
- La 016 cubre la **analítica de NEGOCIO** (funnel de conversión, journey, qué convierte). La **017 es la lente de
  OPS/SISTEMA** — audiencia distinta (tú/dev), datos de salud del sistema y estado del proyecto, **no** de leads.

## WHAT (alcance — 2 pilares, ligero)
### Pilar 1 · Salud del sistema (ops)
- **Salud del failover LLM:** ¿Groq responde o está cayendo a DashScope/Gemini? Tasa de error por backend,
  latencia p50/p95, **tendencia** (últimos 7/30 días). **REUTILIZA `chat_metrics` y el endpoint existente de la
  016** — no dupliques la tabla ni la query base; el ángulo nuevo es **salud/alerta/tendencia**, no conversión.
- (Opcional, si es trivial y seguro) señales de salud del sitio: último build/deploy, health-check de que
  `app.datanestiq.com` responde 200. **NO** APM pesado, NO CPU/RAM del hosting.

### Pilar 2 · Status del portafolio de specs (lo que pediste: "monitorear los specs")
- Vista read-only con el estado de las 15+ specs: **fase, deuda abierta, próximo hito**.
- **Decisión de diseño a resolver en `data-model.md`:** la fuente de verdad del estado. `ESTADO-SPECS.md` es una
  tabla markdown escrita a mano (frágil de parsear). Evalúa: (a) **generar un `specs-status.json`** machine-readable
  como SSOT (build-time, desde los `specs/*/` + un campo de estado) y renderizarlo; o (b) parsear `ESTADO-SPECS.md`.
  Propón la más robusta y **honesta** (que refleje el estado real, sin inventar "✅" donde hay deuda).

### Ubicación
- Pestaña "Observabilidad" (o `/admin/ops`) dentro del panel `/admin/`, **detrás de `auth.php`**. Vanilla CSS/HTML,
  sin librerías pesadas de gráficos.

## CONSTRAINTS (declararlas en el spec)
- **§2 Honestidad:** todo dato es real; si no hay muestra suficiente (ej. pocas requests), muestra "sin datos
  suficientes", **nunca** cifras/tendencias fabricadas (mismo criterio que el `learn` de la 016).
- **§6 PII:** el panel de ops muestra **métricas de sistema**, cero PII de leads. Datos desde `chat_metrics`/estado
  del build, no desde `leads`.
- **Control de acceso:** todo tras `auth.php` (no endpoints públicos).
- **Free-tier / 0 costo recurrente**; Vanilla, sin deps pesadas; Claude solo audita.
- **No duplicar la Spec 016:** reutiliza `chat_metrics` y sus endpoints; el valor de la 017 es el **ángulo ops +
  status de specs**, no repetir la analítica de conversión.

## OUT-OF-SCOPE (declararlo)
- Analítica de conversión/negocio (es de la Spec 016). GraphRAG, MCP endpoint, buscador sobre blog (backlog).
- Monitoreo de infraestructura pesada (CPU/RAM/uptime del hosting) / APM comercial.
- Auto-remediación o cambios automáticos: el panel **observa**, no actúa.

## Entregables (solo diseño)
- `specs/017-panel-observabilidad/{spec.md (5 bloques), data-model.md (fuente del status de specs + reuso de
  chat_metrics + queries de tendencia/p95), plan.md (fases + gate de deploy), tech_debt.md}`.
- Doc-sync: fila 017 en `ESTADO-SPECS.md` (🟠 "diseñada, pendiente de implementación") + `Fases.md`.
- **NO implementes runtime.** Espera la auditoría de Claude.

---
**Nota:** Claude (Opus 4.8) auditará el diseño: que NO duplique la 016 (reuse de `chat_metrics`), que el status de
specs tenga una fuente robusta y honesta (§2), cero PII (§6), todo tras auth, y alcance ops acotado (sin APM ni
auto-acciones). Tras mi visto bueno se pasa a build.
