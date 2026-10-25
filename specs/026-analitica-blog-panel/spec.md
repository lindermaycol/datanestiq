# Especificación Funcional — Spec 026: Analítica de Contenido/Blog en el Panel (Cierre de Loop Spec 024)

## 1. Resumen Ejecutivo & Contexto
La Spec 024 implementó la búsqueda semántica en el blog (`/blog`), la cual registra telemetría de intenciones mediante `trackEvent('search_query', 'blog-search', ...)`. Sin embargo, actualmente dichos datos no son consumidos ni sintetizados visualmente en el panel de administración.

La **Spec 026** cierra el ciclo `search -> analytics -> editorial_decisions` agregando un módulo de **Analítica de Contenido y Gaps del Blog** en el panel `/admin/`. Este módulo permite a los equipos de Marketing, SEO y Contenido identificar las intenciones reales de los visitantes, cuáles temas no tienen cobertura en el blog (gaps de contenido con 0 resultados) y cuáles son las tendencias de búsqueda en el tiempo.

---

## 2. Historias de Usuario & Escenarios

### US-026.1: Visualización de Top Consultas del Blog
**Como** Editor de Contenido / SEO Strategist,  
**Quiero** visualizar las palabras clave y temas más buscados por los usuarios en el blog,  
**Para** priorizar la actualización de artículos existentes y la creación de nuevo contenido de alto interés.

- **Criterios de Aceptación**:
  - Muestra un ranking ordenado de las top búsquedas realizadas en `/blog` durante la ventana de retención (hasta 180 días).
  - Incluye el volumen total de búsquedas y el número de sesiones únicas por cada término.
  - Normaliza los términos de búsqueda (minúsculas, trim) para agrupar variaciones idénticas.

### US-026.2: Identificación de Gaps de Contenido (Consultas con 0 Resultados)
**Como** Director de Contenido,  
**Quiero** identificar rápidamente qué búsquedas devolvieron 0 resultados (`n = 0`),  
**Para** descubrir vacíos de información en la taxonomía del blog y escribir artículos que respondan a la demanda no satisfecha.

- **Criterios de Aceptación**:
  - La telemetría se emite en el handler de respuesta del Web Worker tras calcular la lista filtrada de resultados (`filtered.length`), garantizando que búsquedas con 0 resultados capturen `n: 0` de manera exacta.
  - Filtra y destaca las búsquedas cuyo contador de resultados (`n`) fue igual a `0`.
  - Muestra la frecuencia con la que se solicita cada término sin cobertura.

### US-026.3: Tendencia de Búsquedas y Tasa de "Sin Match"
**Como** Analista de Marketing Digital,  
**Quiero** consultar la evolución diaria/semanal del volumen de búsquedas en el blog y la proporción de búsquedas no satisfechas (`Zero-Match Rate %`),  
**Para** medir la efectividad de las campañas de contenido y la mejora de la cobertura tras publicar nuevos artículos.

- **Criterios de Aceptación**:
  - Gráfico y métricas resumen que muestran el total de búsquedas y el porcentaje de búsquedas con 0 resultados (`Zero-Match Rate %`).
  - La tasa de "sin match" se calcula honestamente (§2) dividiendo únicamente sobre el universo de búsquedas con `n` conocido (`known_n_count`), excluyendo eventos legacy.

---

## 3. Garantías de la Constitución Operativa

### 📜 §2 Honestidad Radical (Transparencia & Muestra Mínima)
- **Muestra Mínima < 15 búsquedas**: Si el volumen de búsquedas registradas en el periodo es menor a 15, el panel no mostrará rankings ni gráficos especulativos; en su lugar desplegará un estado defensivo honesto:
  > *"Datos Insuficientes: Se requieren al menos 15 búsquedas registradas en el blog para generar patrones de tendencia de contenido. (Actual: N búsquedas)."*
- **Denominador Transparente**: El porcentaje `Zero-Match Rate %` se calcula estrictamente sobre eventos con contador `n` conocido (`known_n_count`).

### 🛡️ §6 Privacidad y Redacción de PII
- Toda búsqueda emitida por `BlogSearch.jsx` se procesa a través de `track_event.php`, el cual aplica la función `redactPii()` sobre el valor del evento antes de persistirlo en SQLite.
- La analítica expone únicamente datos agregados en formato JSON sanitizado, prohibiendo endpoints que listen datos personales o IPs de usuarios.

### ⚡ 0-LLM en el Edge y Rendimiento Backend
- Las agregaciones se realizan mediante procesamiento estructurado en PHP sobre SQLite (`GROUP BY`, `COUNT`, `COALESCE`). Cero tokens de modelos de lenguaje consumidos para la analítica.

---

## 4. Requerimientos No Funcionales & Reglas de Panel
1. **Aislamiento Estricto de `event_target`**: El backend filtra exclusivamente `event_type = 'search_query'` AND `event_target = 'blog-search'`, impidiendo cualquier colisión con búsquedas emitidas por GraphRAG (`event_target = 'graphrag'`).
2. **Defensa contra Nodos Faltantes**: La UI utiliza helpers defensivos `setHTML(id, html)` para evitar `TypeError` en el DOM de la administración.
3. **Estructura de Vistas Hermanas**: La sub-vista de Analítica de Blog se integra de forma limpia como sub-pestaña de `view-behavior` en `/admin/index.php`.
