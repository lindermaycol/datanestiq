---
title: "Spec 016: Analítica de Conversión y Loop `chat → lead → learn`"
description: "Diseño de la arquitectura para la analítica de conversión y el bucle de optimización offline de prompts para el motor de conversión consultiva de Datanestiq."
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["analytics","conversion","chat","lead generation","LLM","prompt engineering","CRM","data model","security","PII","system design"]
seoScore: 100
---
# Spec 016: Analítica de Conversión y Loop `chat → lead → learn`

## 1. WHY (Propósito y Problema de Negocio)
Datanestiq cuenta con un motor de conversión consultiva (Specs 013, 014, 015) desplegado en producción (`app.datanestiq.com`). Los leads son capturados en `secure_leads/crm.sqlite` y sus interacciones registradas en archivos de log.

Sin embargo, el sistema carece de una capa de inteligencia de conversión agregada:
1. No se mide cuantitativamente la latencia (`latency_ms`) ni el backend LLM efectivo (`Groq`, `DashScope`, `Gemini`) consumido por cada interacción.
2. No existe una vista analítica en el panel `/admin/` que permita visualizar el embudo de conversión (`Chat/Visita → Lead → Cita Solicitada → Cliente Ganado`), el tiempo de permanencia por etapa (`status_history`) ni la tasa de conversión desglosada por Rol, Sector o Journey.
3. No existe un loop de retroalimentación (`learn`) que analice qué patrones de interacción correlacionan con la conversión exitosa para sugerir mejoras al `SYSTEM_PROMPT` del chatbot.

El objetivo de la **Spec 016** es diseñar la arquitectura de analítica de conversión y el bucle de optimización offline de prompts sin generar costos de licencias ni exponer PII.

---

## 2. WHAT (Alcance y Piezas Principales)

El diseño comprende 3 componentes integrados:

### Componente 1: Instrumentación de Invocaciones Chat (`chat_metrics`)
- Registro estructurado en la tabla `chat_metrics` dentro de `crm.sqlite` por cada llamada de texto libre a `public/api/chat.php`.
- Campos registrados: `session_id`, `backend_used` (`groq`, `dashscope`, `gemini`), `latency_ms` (milisegundos transcurridos), `success` (1 o 0), `tokens_est` (estimado de tokens), `created_at`.
- Integración nativa con el sistema de enmascaramiento PII preexistente.

### Componente 2: Analítica de Conversión en Panel CRM (`/admin/`)
- **Embudo de Conversión (Funnel Agregado):** Conteo de leads por estado (`nuevo`, `contactado`, `cita_solicitada`, `ganado`, `perdido`, `no_interesado`), porcentaje de conversión de etapa a etapa y tiempo promedio en cada fase derivado de `status_history`.
- **Análisis por Dimensión (¿Qué convierte?):** Desglose de tasa de conversión por Sector (`publico`, `finanzas`, `salud`, etc.), por Rol (`cfo`, `cio`, `cdo`, `ceo`) almacenados en las columnas `sector` y `rol` de `leads` (pobladas desde el `journey` por `save_wizard.php`).
- **Trazabilidad Individual de Journey:** Vista de detalle que vincula los datos del lead con su secuencia de interacción (chips de contexto + historial de mensajes `chat_logs`) usando el `session_id` como clave de unión.
- **Endpoints:** Extensión de `public/admin/api.php` protegida por el middleware `auth.php`.

### Componente 3: Loop `learn` Offline (Optimización Asistida del `SYSTEM_PROMPT`)
- Script CLI offline (`scripts/learn_prompt_optimizer.mjs`) diseñado para ejecutarse de forma periódica/semanal.
- Analiza las conversaciones de leads que convirtieron (`ganado`) vs. leads abandonados (`perdido`/`no-interesado`).
- Emplea modelos free-tier (`Groq` / `Gemini`) o heurísticas deterministas para extraer objeciones frecuentes y patrones de respuesta exitosos.
- **Generación de PR Draft:** Emite las propuestas de mejora al `SYSTEM_PROMPT` exclusivamente en forma de Pull Request en borrador (flujo `content-pr.yml`).
- **PROHIBICIÓN ESTRICTA:** El script NUNCA aplica cambios directamente a `public/api/chat.php` en producción.

---

## 3. CONSTRAINTS (Guardarraíles Duros de la Constitución)

1. **Constitución §6 (Seguridad y PII):** Cero nuevos canales de captura de datos personales. Las métricas de chat se almacenan en `secure_leads/crm.sqlite` (fuera del document root público). Las vistas agregadas del embudo no muestran PII cruda.
2. **Constitución §5 (0-LLM en Flujo Guiado):** El recorrido por botones de la web y el chatbot guiado siguen siendo 0-LLM. La instrumentación es puramente de registro (logging) y la optimización es un proceso batch offline.
3. **Constitución §2 (Honestidad Radical):** El panel de analítica muestra datos 100% reales extraídos de la BD de producción. Prohibido incluir datos o métricas simuladas/ficticias.
4. **Gobierno del `SYSTEM_PROMPT`:** El prompt del chatbot es crítico para la seguridad y calidad de la marca. Cualquier modificación sugerida por el loop `learn` requiere validación sintáctica (`php -l`), PR draft y auditoría humana/Claude antes del merge.
5. **Cero costo recurrente:** El script offline utiliza exclusivamente los endpoints free-tier configurados (`Groq` / `Gemini` / `DashScope`). Prohibido invocar APIs comerciales de pago en tareas en segundo plano.
6. **Control de Acceso:** La API de analítica reside bajo `public/admin/` y está resguardada por el middleware de autenticación `auth.php`.

---

## 4. OUT-OF-SCOPE

- **Observabilidad Técnica de Operaciones (Spec 017):** No monitorea la salud técnica global del servidor (uso de CPU, memoria RAM, uptime de Apache o espacio en disco). La Spec 017 abordará el tablero de ops de forma independiente.
- **Buscador Semántico Avanzado / GraphRAG:** No altera el worker de búsqueda vectorial `public/worker.js` ni crea nodos de grafos de conocimiento.
- **Modificación Automática de Código:** No auto-aplica prompts ni edita archivos en producción de forma desatendida.

---

## 5. TASKS & ENTREGABLES DE DISEÑO

- [x] Crear `specs/016-analitica-conversion-loop/spec.md` (Documento marco de 5 bloques).
- [x] Crear `specs/016-analitica-conversion-loop/data-model.md` (Esquema de BD + queries agregadas de funnel y joins).
- [x] Crear `specs/016-analitica-conversion-loop/plan.md` (Fases de implementación y gates humanos).
- [x] Crear `specs/016-analitica-conversion-loop/tech_debt.md` (Registro de deudas/riesgos previstos).
- [x] Actualizar `planes/ESTADO-SPECS.md` y `planes/Fases.md` marcando la Spec 016 en 🟠 *"Diseñada, pendiente de implementación"*.
