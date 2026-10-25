---
title: "Spec 016: Loop Learn Offline — Optimizador de Prompts"
description: "Documentación técnica del script `learn_prompt_optimizer.mjs`, que implementa el análisis offline de conversaciones reales para optimización ética y estadíst"
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["spec-016","prompt-optimization","offline-learning","radical-honesty","crm-integration","data-governance"]
seoScore: 100
---
## Spec 016: Loop Learn Offline — Optimizador de Prompts
Este documento describe el comportamiento, garantías y protocolos del script CLI `scripts/learn_prompt_optimizer.mjs`, parte fundamental del **Loop Learn** en Datanestiq.

### Objetivo Principal
Optimizar prompts de interacción asistida mediante análisis estadístico *offline* de historiales de conversación anónimos y auditables, respetando:
- **Integridad semántica**: sin distorsión del significado original.
- **Ética operativa**: bloqueo automático de patrones sesgados, discriminatorios o no consentidos.
- **Gobernanza de datos**: cumplimiento estricto de políticas de retención, anonimización (ISO/IEC 20889) y trazabilidad CRM.

### Flujo Operativo
1. **Ingesta controlada**: carga desde `data/ingest/loop-learn/offline/` con validación de firma SHA-256 y metadatos de origen (CRM ID, timestamp UTC, nivel de consentimiento).
2. **Preprocesamiento ético**: tokenización con enmascaramiento de PII (usando `@datanestiq/anonyma-core`), segmentación por intención conversacional (ICL + reglas heurísticas).
3. **Análisis multicanal**: correlación entre prompt inicial, respuesta generada, feedback explícito (ej. 👍/👎) y métricas implícitas (tiempo de lectura, scroll depth, reenvío).
4. **Optimización iterativa**: ajuste de peso de *prompt templates*, *role directives* y *context windows* basado en A/B testing simulado con Monte Carlo sobre cohortes estratificadas.
5. **Salida certificada**: generación de `output/optimized-prompts/v%%VERSION%%/` con hash criptográfico, reporte de impacto ético (`ethics_audit.json`) y delta de desempeño (`perf_delta.csv`).

### Garantías Técnicas
- ✅ **Reproducibilidad total**: cada ejecución es determinista bajo mismo seed y dataset firmado.
- ✅ **No conexión a producción**: cero acceso a APIs en tiempo real ni bases de datos activas durante ejecución.
- ✅ **Auditoría humana obligatoria**: cualquier cambio >5% en tasa de rechazo o sesgo detectado activa `%%IGNORE_BLOCK_1%%`.
- ✅ **Compatibilidad CRM**: soporte nativo para Salesforce, HubSpot y Zoho via adaptadores estandarizados (`/adapters/crm/`).

### Integración en CI/CD
El script se ejecuta diariamente en entorno air-gapped como paso crítico de `ci/loop-learn-offline.yml`. Su éxito es condición necesaria para la liberación de nuevas versiones de `@datanestiq/prompt-engine`.

> 📌 **Nota de diseño**: Este módulo no entrena modelos LLM. Solo refina *cómo se formulan los prompts* ante escenarios observados — manteniendo la separación clara entre *aprendizaje de uso* y *entrenamiento de modelo*.