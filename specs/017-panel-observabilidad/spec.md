# Spec 017: Panel de Observabilidad Interna (Ops & Gobernanza de Specs)

**Estado:** 🟠 DISEÑADA, PENDIENTE DE AUDITORÍA Y LUZ VERDE (2026-08-02)

---

## 1. WHY (Motivación y Contexto)

Mientras que la **Spec 016** proporciona visibilidad sobre la **analítica de NEGOCIO** (embudo de conversión, origen de leads y journey del cliente), el equipo de desarrollo e ingenieros de sistema requieren una vista **operativa (Ops)** sobre la **salud técnica del ecosistema** y la **gobernanza del portafolio de especificaciones (Specs)**.

Actualmente:
1. No existe un punto central para monitorear el comportamiento del pool de failover de modelos de lenguaje (`Groq` → `DashScope` → `Gemini`), las latencias `p50/p95` o los errores de degradación técnica.
2. El estado del portafolio de 17+ especificaciones reside únicamente en documentos markdown distribuidos (`ESTADO-SPECS.md`), lo que dificulta su verificación estructurada y la visibilidad de deuda técnica abierta.

La **Spec 017** soluciona esto proporcionando un **Panel de Observabilidad Ligero (Ops)** dentro del dashboard `/admin/` (detrás de `auth.php`), divido en dos pilares: Salud del Sistema y Estado del Portafolio de Specs.

---

## 2. WHAT (Alcance Funcional — 2 Pilares Operativos)

### Pilar 1 · Telemetría y Salud del Sistema (Ops)
- **Salud del Failover LLM:** Monitoreo en tiempo real del uso de proveedores en `chat_metrics` (`Groq`, `DashScope`, `Gemini`). Métricas de volumen de llamadas, porcentaje de éxito/failover y latencias (promedio, p50, p95).
- **Tendencias de Rendimiento:** Agregación diaria/semanal de peticiones para detectar picos de latencia o degradación de APIs externas.
- **Salud del Sitio y Despliegue:** Muestra del timestamp del último build estático, volumen de páginas compiladas y estado de respuesta HTTP del subdominio de producción (`app.datanestiq.com`).
- **Reutilización Eficiente:** Utiliza la infraestructura existente de `chat_metrics` en `secure_leads/crm.sqlite` creada en la Spec 016, evitando la duplicación de tablas o consultas pesadas.

### Pilar 2 · Gobernanza y Estado del Portafolio de Specs
- **Vista Estructurada de Specs:** Visualización read-only del estado de las 17+ especificaciones del proyecto (`LIVE`, `DESIGNED`, `SUPERSEDED`, `IN_PROGRESS`).
- **SSOT Estandarizado:** Fuente de verdad mediante `src/data/specsStatus.json` respaldada y validada en tiempo de compilación por `scripts/build-specs-status.mjs`.
- **Visibilidad de Deuda Técnica:** Muestra clara de ítems de deuda técnica abiertos por spec, últimos hitos alcanzados y fecha de auditoría en vivo.

---

## 3. CONSTRAINTS (Guardarraíles Duros de la Constitución)

1. **Constitución §2 (Honestidad Radical):** Prohibido fabricar datos, tendencias o estados simulados. Si el volumen de peticiones o métricas es escaso, la interfaz mostrará explícitamente "Datos insuficientes para tendencia". El estado de las specs reflejará con transparencia la deuda técnica real sin simular aprobaciones ficticias.
2. **Constitución §6 (Seguridad y Cero Fuga de PII):** El panel de observabilidad opera exclusivamente con **métricas de sistema** e identificadores anónimos. Cero datos personales de leads expuestos en las vistas de Ops.
3. **Control de Acceso Estricto:** Todos los endpoints y vistas de observabilidad están protegidos por `auth.php` dentro de `/admin/`. Prohibido exponer métricas técnicas en endpoints públicos sin autenticación.
4. **Cero Costo Recurrente & Vanilla CSS:** Construcción en HTML5 y Vanilla CSS ligero sin dependencias de gráficos pesados JS (Chart.js / D3) ni servicios comerciales de APM (Datadog / New Relic).
5. **No Duplicar Spec 016:** Reutiliza `chat_metrics` y la base de datos de producción `crm.sqlite`. La Spec 017 aborda exclusivamente la telemetría Ops y el estado de la arquitectura.

---

## 4. OUT-OF-SCOPE (Fuera de Alcance)

- Analítica de conversión, calificación de leads o métricas de negocio (pertenecen a la Spec 016).
- Agentes de APM de infraestructura pesada (monitoreo de CPU/RAM/Disco a nivel de kernel de hosting).
- Mecanismos de auto-remediación o ejecución de comandos automáticos desde la UI (el panel es estrictamente de lectura y observabilidad).
- Endpoints o integraciones MCP/GraphRAG públicas.

---

## 5. TASKS (Plan de Tareas de Diseño)

- [x] Crear manifiesto funcional `specs/017-panel-observabilidad/spec.md` con los 5 bloques.
- [x] Definir esquema JSON SSOT `src/data/specsStatus.json` y consultas SQL de telemetría en `data-model.md`.
- [x] Diseñar fases de desarrollo y gates humanos de deploy en `plan.md`.
- [x] Registrar riesgos de arranque en frío y sincronización en `tech_debt.md`.
- [x] Sincronizar documentación (`ESTADO-SPECS.md` y `Fases.md`) en estado "Diseñada, pendiente de auditoría".
