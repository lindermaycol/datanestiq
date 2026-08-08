---
title: "Spec 016: Loop Learn Offline — Optimizador de Prompts"
description: "Documentación técnica del script `learn_prompt_optimizer.mjs`, que implementa el análisis offline de conversaciones reales para optimización ética y estadíst"
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["spec-016","prompt-optimization","offline-learning","radical-honesty","crm-integration","data-governance"]
seoScore: 100
---
## 🧠 Spec 016: Loop Learn Offline — Optimizador de Prompts

Este documento describe el comportamiento, garantías y protocolos del script CLI `scripts/learn_prompt_optimizer.mjs`, parte fundamental del **Loop Learn** en Datanestiq. Su propósito es generar propuestas de mejora del `SYSTEM_PROMPT` únicamente a partir de **datos reales de producción**, sin simulación ni extrapolación.

---

### ✅ Objetivo Principal

Automatizar la generación de *PR Drafts* (`content-pr.yml`) con hallazgos cuantificables derivados exclusivamente de interacciones reales almacenadas en `secure_leads/crm.sqlite`, cumpliendo estrictamente:

> **Constitución §2 — Honestidad Radical**: *Ninguna métrica, patrón o recomendación será generada si la muestra no alcanza el umbral mínimo estadístico válido.*

---

### ⚙️ Comportamiento Operativo

| Componente | Descripción |
|------------|-------------|
| **Entrada** | Base de datos SQLite local: `secure_leads/crm.sqlite` (tabla `leads`, `interactions`, `chat_metrics`) |
| **Umbral Mínimo** | `MIN_CONVERSIONS = 10` leads con status `'ganado'` o `'cita_solicitada'` |
| **Salida** | Un archivo Markdown en `planes/PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md`, listo para revisión humana |
| **Modo de Ejecución** | Exclusivamente `PR Draft`: **0 auto-merge**, requiere revisión explícita por arquitecto |
| **Seguridad de Datos** | No se envía ni procesa información fuera del entorno local; no hay llamadas a APIs externas ni LLMs en tiempo real |

---

### 📉 Flujo de Decisión (Guardarrail §2)

```mermaid
graph TD
  A[Inicio] --> B{¿Existe crm.sqlite?}
  B -->|No| C[Emitir reporte 'Datos insuficientes']
  B -->|Sí| D[Consultar COUNT(*) de leads]
  D --> E{¿totalLeads >= 10?}
  E -->|No| C
  E -->|Sí| F[Calcular métricas reales: tasa de citas, tasa de ganados, latencia promedio]
  F --> G[Extraer hasta 20 interacciones de leads 'ganado']
  G --> H[Generar PR Draft con hallazgos 100% reales]
```

- Si el umbral **no se cumple**, el script emite un reporte transparente etiquetado como `insufficient-data`, **sin sugerencias ni diffs**.
- Si el umbral **sí se cumple**, solo se incluyen métricas calculadas directamente mediante SQL (`AVG`, `COUNT`, `ROUND`) y transcripciones reales — **nunca modelos de lenguaje generando inferencias**.

---

### 📄 Formato de Salida

El archivo generado (`PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md`) sigue el estándar de GitHub PR templates y contiene:

- Encabezado YAML con `name`, `about`, `title`, y `labels` (incluyendo `draft-pr`, `human-review-required`, `spec-016`).
- Sección `📊 Resumen de Hallazgos` con valores numéricos reales y contexto exacto (ej. `Tasa de Clientes Ganados Real: 32.50% (13 leads)`).
- Bloque `💡 Análisis de Interacciones Reales` indicando cantidad de muestras analizadas (ej. `Se han analizado 13 muestras de recorrido de clientes ganados.`).
- Nota de advertencia clara: `REVISIÓN HUMANA REQUERIDA`.

⚠️ **Nunca incluye**:
- Sugerencias de redacción para el `SYSTEM_PROMPT`.
- Comparaciones con versiones anteriores.
- Análisis cualitativo, clustering semántico o embeddings.
- Cualquier forma de "mejora automática" del prompt.

---

### 🔐 Garantías Éticas y Técnicas

| Garantía | Implementación |
|----------|----------------|
| **Cero fabricación de datos** | El script aborta con `process.exit(0)` si `totalLeads < 10`; no ejecuta lógica de cálculo ni genera texto inferencial. |
| **Auditoría completa** | Todas las consultas SQL están explícitas y reproducibles; los valores mostrados en consola coinciden 1:1 con los escritos en el reporte. |
| **Aislamiento de entorno** | No depende de variables de entorno sensibles ni de credenciales; carga `.env` solo para compatibilidad opcional. |
| **No interferencia en producción** | Solo lectura (`SELECT`); cero escritura, cero modificación de base de datos o archivos de configuración. |

---

### 🛠️ Uso

```bash
node scripts/learn_prompt_optimizer.mjs
```

- Se ejecuta manualmente o como paso en pipelines CI/CD *solo para generación de drafts*.
- No forma parte de flujos de producción en tiempo real.
- Requiere acceso local al archivo `crm.sqlite` (no compatible con bases remotas o en memoria).

---

### 📚 Relación con Especificaciones

- **Spec 016**: Define el alcance del Loop Learn Offline y su vinculación con el ciclo `observe → learn → adapt`.
- **Constitución §2 (Honestidad Radical)**: Fundamento ético que prohíbe la simulación de evidencia y exige transparencia sobre límites estadísticos.
- **Spec 007 (Data Sovereignty)**: Refuerza que todos los datos usados pertenecen al cliente y permanecen bajo su control físico.

---

> 💡 **Nota para Arquitectos**: Este script no sustituye la revisión humana. Su valor radica en **eliminar el ruido de la intuición** y ofrecer una base objetiva para decisiones de diseño de prompts. Cualquier cambio al `SYSTEM_PROMPT` debe validarse con A/B testing en entornos controlados, nunca por extrapolación.

%%IGNORE_BLOCK_1%%