# Spec 023: Panel de Negocio (Refocus Business-First del /admin/)

## 1. Visión General y Propósito (WHY)
Auditorías recientes del panel de administración `/admin/` revelaron una desviación conceptual: la interfaz combina métricas estratégicas de negocio con elementos de ingeniería de software e infraestructura interna de desarrollo (tales como la tabla de 21 especificaciones con su deuda técnica, el estado del compilador Astro, el linter ESLint y reglas `.htaccess`). Al mismo tiempo, el mayor valor económico generado por la arquitectura de IA —el **ahorro directo en costos de LLM logrado por el Router Determinístico 0-LLM (Spec 019)**— carece de visibilidad ejecutiva.

La Spec 023 reenfoca el panel de administración hacia una **perspectiva orientada al negocio (Business-First)**. Elimina la sobrecarga de información de desarrollo del usuario administrativo, destaca el retorno de inversión y la eficiencia del Router 0-LLM con estimados monetarios honestos (`$ [EST]`), introduce la **Agenda Global de Citas**, captura con precisión la persona comercial (`sector` y `rol`) en el CRM, y enriquece la toma de decisiones mediante **gráficos ligeros y accesibles (Vanilla CSS/SVG inline)** protegidos por el estándar de **Honestidad Radical (§2)**.

---

## 2. Alcance (WHAT)

### 2.1 Reestructuración y Nuevas Capacidades

#### Parte 1: Panel de Eficiencia y Costo de la IA (Impacto 0-LLM)
- Visualización de la distribución de consultas atendidas por el Router 0-LLM vs LLM (`faq`, `cita`, `guiado` vs `llm`).
- Métrica principal de Eficiencia: **Llamadas a LLM Evitadas (Conteo 0-LLM)** y **% de Eficiencia 0-LLM**.
- **Cálculo del Ahorro Estimado `$ [EST]` (Constitución §2)**:
  $$\text{Ahorro [EST]} = \text{Consultas 0-LLM evitadas} \times \left( \frac{\text{Promedio Tokens por Consulta LLM}}{1,000,000} \times \text{Tarifa Ref. } \$0.30/1\text{M} \right)$$
  *(Basado estrictamente en tokens reales de `chat_metrics` y una tarifa de mercado de referencia declarada, aclarando en la UI que los proveedores actuales operan en free-tier. Jamás se deriva el costo a partir de latencias o milisegundos).*
- Integración en una tarjeta unificada de "Costo y Eficiencia de la IA" protegida por guardarraíl §2 (Muestra $N < 20$ &rarr; "datos insuficientes").

#### Parte 2: Depuración de "Observabilidad Ops" (Enfoque Salud & Costo IA)
- **Eliminación de Vistas de Desarrollo**: Se remueve de la interfaz del panel `/admin/` la tabla de 21 specs con su deuda técnica, la fecha de compilación de Astro, el estado de ESLint y las directivas de `.htaccess`. (El archivo `specsStatus.json` se mantiene intacto como SSOT del build-gate antidrift en scripts de desarrollo).
- **Renombramiento a "Salud & Costos IA"**: Conserva únicamente indicadores operativos de negocio: Estado del servicio (HEALTHY / 200), SLA y latencia $p_{50}/p_{95}$, registro de alertas de infraestructura, failovers y consumo de tokens.

#### Parte 3: Agenda Global de Citas (Spec 015)
- Nueva vista dentro de "Leads & Citas": tabla interactiva de próximas citas ordenadas por fecha cronológica (`appointments` JOIN `leads`).
- Esquema real alineado con la Spec 015 (`id`, `lead_id`, `session_id`, `requested_date`, `duration_minutes`, `type`, `status`).
- Muestra datos del cliente, servicio/tipo solicitado, fecha/hora `requested_date`, estado (`'solicitada'`, `'confirmada'`, `'cancelada'`) y botones de acción rápida para **Confirmar**, **Reagendar** o **Cancelar** cita (escribiendo los estados reales vía `update_appointment`).

#### Parte 4: Captura de Sector y Rol en Leads (Atribución Comercial)
- Persistencia de `sector` y `rol` capturados en el contexto de navegación (chips, guiado, demand signals) hacia las columnas `leads.sector` y `leads.rol` al guardar formularios o interacciones CRM.
- Script CLI de backfill idempotente (`scripts/backfill_lead_personas.php`) que deriva el sector/rol de leads existentes mediante `session_id` desde `demand_signals`.

#### Parte 5: Deduplicación de Vistas y Acciones Directas
- Eliminación del widget duplicado de Rendimiento LLM entre "Analítica" y "Ops".
- Unificación del visor de "Qué preguntan los clientes" alineado con el Visor de Conversaciones de la Spec 022.
- Exposición de acciones directas en la modal de detalle del lead (cambio de estado, notas, marcas de ganado/perdido y confirmación de cita).

#### Parte 6: Visualizaciones y Gráficos Ligeros (Vanilla CSS / SVG)
- Incorporación de gráficos ligeros **sin librerías pesadas** (sin Chart.js ni D3), implementados con HTML/CSS/SVG nativo:
  1. **Embudo de Conversión Horizontal**: Muestra la progresión de leads de *Nuevo* &rarr; *Contactado* &rarr; *Cita* &rarr; *Ganado* con porcentaje de drop-off.
  2. **Gráfico Apilado/Dona de Ruteo 0-LLM**: Proporción visual de intenciones resueltas localmente vs LLM.
  3. **Barras de Tendencia Diaria**: Peticiones, costo y latencia a lo largo del tiempo.
  4. **Barras Horizontales de Demanda y Fugas**: Visualización del Top de Servicios con brechas y fugas.
- **Guardarraíl §2 Estricto**: Si la muestra es menor al umbral $N$, los gráficos **nunca** se renderizan de forma engañosa; en su lugar despliegan el contenedor de "Datos insuficientes".

---

## 3. Especificación de Seguridad y Privacidad (§2 y §6)
1. **Honestidad Radical (§2)**: Toda cifra monetaria derivada del ahorro 0-LLM lleva el sufijo **`[EST]`** explícito.
2. **Control de Acceso**: Todas las consultas a la agenda, leads y métricas están resguardadas tras `auth.php`.
3. **Cero Dependencias Externas en UI**: Gráficos construidos con CSS/SVG nativo para mantener latencia ultra-baja (< 50ms) y cero consumo de CDNs o JavaScript pesado.
