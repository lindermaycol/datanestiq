---
title: "Spec 016: Analítica de Conversión y Loop `chat → lead → learn`"
description: "Diseño de la arquitectura para la analítica de conversión y el bucle de optimización offline de prompts para el motor de conversión consultiva de Datanestiq."
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["analytics","conversion","chat","lead generation","LLM","prompt engineering","CRM","data model","security","PII","system design"]
seoScore: 100
---
# Spec 016: Analítica de Conversión y Loop `chat → lead → learn`

Este documento detalla la **Spec 016**, que aborda la creación de una arquitectura de analítica de conversión y un bucle de optimización offline de prompts para el motor de conversión consultiva de Datanestiq.

## 1. Propósito (WHY)

Actualmente, el motor de conversión consultiva de Datanestiq carece de una capa de inteligencia de conversión agregada. No se miden métricas clave como la latencia o el backend LLM utilizado por interacción, no existe una vista analítica del embudo de conversión en el panel `/admin/`, y no hay un bucle de retroalimentación (`learn`) para optimizar el `SYSTEM_PROMPT` del chatbot basándose en patrones de conversión exitosos.

El objetivo es resolver estas deficiencias sin incurrir en costos de licencias adicionales ni exponer Información Personal Identificable (PII).

## 2. Alcance y Componentes Principales (WHAT)

La Spec 016 se estructura en tres componentes interconectados:

### Componente 1: Instrumentación de Invocaciones Chat (`chat_metrics`)

*   Registro estructurado de cada llamada a `public/api/chat.php` en la tabla `chat_metrics` dentro de `crm.sqlite`.
*   Captura `session_id`, `backend_used`, `latency_ms`, `success`, `tokens_est`, y `created_at`.
*   Integración con el sistema de enmascaramiento PII existente.

### Componente 2: Analítica de Conversión en Panel CRM (`/admin/`)

*   **Embudo de Conversión:** Visualización agregada de leads por estado, porcentajes de conversión entre etapas y tiempo promedio en cada fase.
*   **Análisis por Dimensión:** Desglose de la tasa de conversión por `Sector` y `Rol`.
*   **Trazabilidad Individual:** Vista detallada que vincula el lead con su historial de interacción (`chat_logs`) usando `session_id`.
*   Extensión de `public/admin/api.php` protegida por `auth.php`.

### Componente 3: Loop `learn` Offline (Optimización Asistida del `SYSTEM_PROMPT`)

*   Script CLI offline (`scripts/learn_prompt_optimizer.mjs`) para ejecución periódica.
*   Analiza conversaciones de leads convertidos (`ganado`) vs. abandonados para identificar patrones.
*   Utiliza modelos free-tier (`Groq` / `Gemini`) o heurísticas.
*   **Genera propuestas de mejora al `SYSTEM_PROMPT` exclusivamente como Pull Request en borrador (flujo `content-pr.yml`).**
*   **Prohibición estricta:** NUNCA aplica cambios directamente a `public/api/chat.php` en producción.

## 3. Restricciones y Principios Fundamentales (CONSTRAINTS)

El diseño se adhiere estrictamente a la Constitución de Datanestiq:

*   **Seguridad y PII:** Cero nuevos canales de captura de PII. Las métricas se almacenan en `crm.sqlite` (fuera del document root público) y las vistas agregadas no exponen PII.
*   **0-LLM en Flujo Guiado:** La instrumentación es solo de registro; la optimización es un proceso batch offline.
*   **Honestidad Radical:** El panel de analítica muestra datos 100% reales de la BD de producción.
*   **Gobierno del `SYSTEM_PROMPT`:** Cualquier sugerencia del loop `learn` requiere validación sintáctica, PR draft y auditoría humana antes del merge.
*   **Cero Costo Recurrente:** El script offline usa exclusivamente endpoints free-tier.
*   **Control de Acceso:** La API de analítica está bajo `public/admin/` y protegida por `auth.php`.

## 4. Fuera de Alcance (OUT-OF-SCOPE)

Esta especificación no cubre:

*   Observabilidad técnica de operaciones (Spec 017).
*   Buscador semántico avanzado / GraphRAG.
*   Modificación automática de código en producción.

## 5. Tareas y Entregables de Diseño

*   [x] Crear `specs/016-analitica-conversion-loop/spec.md` (Documento marco de 5 bloques).
*   [x] Crear `specs/016-analitica-conversion-loop/data-model.md` (Esquema de BD + queries agregadas de funnel y joins).
*   [x] Crear `specs/016-analitica-conversion-loop/plan.md` (Fases de implementación y gates humanos).
*   [x] Crear `specs/016-analitica-conversion-loop/tech_debt.md` (Registro de deudas/riesgos previstos).
*   [x] Actualizar `planes/ESTADO-SPECS.md` y `planes/Fases.md` marcando la Spec 016 en 🟠 *"Diseñada, pendiente de implementación"*.