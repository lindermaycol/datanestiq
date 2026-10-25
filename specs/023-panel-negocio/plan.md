# Plan de Implementación — Spec 023: Panel de Negocio (Refocus Business-First)

## 1. Resumen Ejecutivo
Este plan define la ejecución secuencial para reenfocar el panel de administración `/admin/` hacia el valor de negocio: visibilizar la eficiencia y ahorro del Router 0-LLM (`$ [EST]`), introducir la Agenda Global de Citas, depurar el panel de componentes internos de desarrollo, capturar sector/rol en los leads y añadir visualizaciones ligeras en HTML/CSS/SVG con protección estricta §2 de muestra mínima.

---

## 2. Fases de Ejecución (Secuencial post Spec 022)

### Fase 1: Backend de Eficiencia 0-LLM y Agenda de Citas (`public/admin/api.php`)
1. Implementar el endpoint `ai_efficiency` en `api.php`:
   - Agrega eventos `intent_routing` por `route`.
   - Calcula ahorro estimado con sufijo `[EST]`.
   - Incluye el guardarraíl §2 para muestras menores a 20 peticiones.
2. Implementar el endpoint `agenda` en `api.php`:
   - `appointments` JOIN `leads` ordenado por fecha.
   - Permite invocar `update_appointment` para cambios de estado.
3. Actualizar la función de registro de leads en `save_wizard.php` y en las APIs del chatbot para persistir `sector` y `rol` recuperándolos del contexto de sesión.

### Fase 2: Script CLI de Backfill de Personas en CRM (`scripts/backfill_lead_personas.php`)
1. Crear el script CLI idempotente para rellenar retroactivamente `leads.sector` y `leads.rol` utilizando la información histórica acumulada en `demand_signals`.
2. Verificar actualización de registros e informar en consola.

### Fase 3: Rediseño UX y Limpieza de "Observabilidad Ops" (`public/admin/index.php`)
1. Eliminar la tabla del portafolio de 21 especificaciones, compilación Astro, ESLint y `.htaccess` de la pestaña Ops.
2. Renombrar la sección a "Salud & Costos IA", manteniendo únicamente indicadores de negocio (HEALTHY status, SLA p50/p95, alertas de consumo y desglose de tokens).
3. Añadir la vista de **Agenda Global de Citas** en la sub-pestaña de "Leads & Citas".
4. Integrar la sección de **Costo y Eficiencia de la IA** en "Analítica de Conversión".

### Fase 4: Componentes de Visualización Ligeros (CSS/SVG)
1. Desarrollar el gráfico de embudo de conversión horizontal en Vanilla CSS.
2. Desarrollar la dona SVG inline para el porcentaje 0-LLM vs LLM.
3. Integrar los guards de muestra mínima (§2) en todos los gráficos: si $N < \text{Umbral}$, oculta el gráfico y despliega el aviso de "Datos insuficientes".

---

## 3. Despliegue y Verificación en Producción
1. **Recompilación Estática**: Ejecutar `npm run build` tras forzar la limpieza de `dist/`.
2. **Despliegue a IONOS**: Ejecutar `python scripts/deploy/deploy_ionos.py --confirm`.
3. **Ejecución de Backfill Remoto**: Ejecutar `scripts/backfill_lead_personas.php` en el servidor de producción vía SSH.
4. **Verificación de Endpoints**: Validar que `ai_efficiency` y `agenda` responden con 200 y resguardados tras `auth.php`.
