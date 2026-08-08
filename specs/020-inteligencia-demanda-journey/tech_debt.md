# Tech Debt — Spec 020: Inteligencia de Demanda y Journey Reconstructor

Este documento detalla la deuda técnica anticipada y las simplificaciones asumidas para la v1 de la Spec 020.

---

## TD-020-01 — Sobrecarga en la inicialización de múltiples corpus (Xenova)

**Severidad:** 🟡 Baja  
**Descripción:** Al montar el Chatbot, se indexan tres corpus en el worker cliente: intenciones (pequeño), FAQ (pequeño) y ahora el catálogo de servicios de la taxonomía (mediano, ~20 entradas). Aunque el indexado en Xenova es rápido, el cliente realiza tres llamadas sucesivas de inicialización, lo que puede demorar de 100 a 300ms en dispositivos móviles de gama baja.

**Mitigación en v1:** Se mantiene la inicialización asíncrona secuencial. No bloquea la UI de chat.
**Solución a largo plazo:** Unificar la indexación en un único payload estructurado o delegar el indexado del catálogo a un estado inactivo de fondo.

---

## TD-020-02 — Crecimiento lineal de la tabla `demand_signals`

**Severidad:** 🟠 Media  
**Descripción:** La tabla `demand_signals` almacena una fila por cada interacción de texto libre de todos los visitantes. En escenarios de alto tráfico, esta tabla crecerá rápidamente, impactando el almacenamiento del servidor compartido de IONOS y el rendimiento de las consultas del panel admin.

**Mitigación en v1:** Se asume un volumen moderado en el prototipo y se crean índices de rendimiento clave en las columnas de filtrado.
**Solución a largo plazo:** Implementar un script cron de limpieza automática que purgue los registros que excedan los 180 días de antigüedad (alineado con la política de retención de `interaction_events` de la Spec 018).

---

## TD-020-03 — Agrupación semántica simple en base a matched_service

**Severidad:** 🟡 Baja  
**Descripción:** Para el Bucket 1 (Demanda no atendida), la v1 agrupa las consultas no ofrecidas en base al campo `matched_service` más cercano. Si la consulta del usuario es totalmente ajena a Datanestiq (ej. "¿venden repuestos de autos?"), se asociará erróneamente al servicio más cercano a nivel vectorial, aunque se marque correctamente como `offered=false`. Esto puede generar agrupaciones ruidosas en el panel admin.

**Mitigación en v1:** Se muestra el listado crudo de ejemplos representativos para que el administrador humano filtre el ruido visualmente.
**Solución a largo plazo:** Utilizar un algoritmo de agrupamiento (clustering) client-side simple (ej: K-Means ligero) sobre las consultas no atendidas para detectar clusters reales de palabras clave antes de renderizar.

---

## TD-020-04 — Umbral de coincidencia de catálogo bajo (0.40)

**Severidad:** 🟡 Baja  
**Descripción:** El umbral de coincidencia contra el catálogo de servicios (`CATALOG_MATCH_THRESHOLD`) se redujo de `0.60` a `0.40`. Esto fue necesario para evitar falsos negativos (consultas de negocio reales que se asociaban con similitud baja debido a la asimetría de tamaño entre la consulta corta del usuario y las descripciones del catálogo). Sin embargo, un umbral de `0.40` incrementa la posibilidad de falsos positivos en la clasificación, asociando consultas vagas como "ofrecidas".

**Mitigación en v1:** Mitigado por el Fix A, que marca como ofrecido (`offered=1`) todo lo resuelto localmente por las rutas 0-LLM (`faq`, `cita`, `guiado`), independientemente del score del catálogo. Esto aísla el Bucket 1 ("gap real") de ruidos de ruteo.
**Solución a largo plazo:** Enriquecer las descripciones de los servicios en la taxonomía con sinónimos y oraciones de consulta típicas cortas, permitiendo elevar nuevamente el umbral a `0.55+`.

---

## Resumen de Deuda Técnica

| ID | Descripción | Severidad | Esfuerzo | Prioridad |
|----|-------------|-----------|----------|-----------|
| TD-020-01 | Latencia por inicialización de múltiples corpus Xenova | 🟡 Baja | 2h | P3 |
| TD-020-02 | Crecimiento lineal y almacenamiento en SQLite | 🟠 Media | 1.5h | P2 (antes del lanzamiento comercial) |
| TD-020-03 | Ruido en agrupamiento semántico de demanda no ofrecida | 🟡 Baja | 4h | P3 |
| TD-020-04 | Umbral de coincidencia de catálogo bajo (0.40) | 🟡 Baja | 3h | P3 |
