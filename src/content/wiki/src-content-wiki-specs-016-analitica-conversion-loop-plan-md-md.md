---
title: "Spec 016: Analítica de Conversión y Loop chat → lead → learn"
description: "Documentación técnica completa del plan de implementación para la analítica de conversión, instrumentación de métricas en tiempo real, embudo de leads y opti"
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["spec","analytics","crm","llm-ops","conversion-funnel","prompt-optimization"]
seoScore: 100
---
# Spec 016: Analítica de Conversión y Loop `chat → lead → learn`

> **Estado**: Plan aprobado — En fase de implementación (Gate 1 superado)
> 
> **Objetivo estratégico**: Cerrar el *feedback loop* entre interacción humana (chat), captura estructurada (lead), y mejora continua del comportamiento del agente LLM mediante análisis cuantitativo y sugerencias basadas en evidencia.

---

## 🧭 Visión General

Este spec define un sistema de *documentación viva* que transforma cada interacción de chat en una fuente de métricas accionables, permitiendo:

- Medir con precisión la eficacia del flujo `chat → lead → cita → cliente`.
- Detectar cuellos de botella técnicos (latencia, fallos) y conductuales (abandonos, desalineación de contexto).
- Generar propuestas auditables de mejora del `SYSTEM_PROMPT`, derivadas exclusivamente de casos reales de éxito (`ganado`) y fracaso (`perdido`/`no_interesado`).

El sistema opera bajo tres capas:

| Capa | Componente | Responsabilidad |
|--------|-------------|------------------|
| **Instrumentación** | `chat.php`, `save_wizard.php`, `chat_metrics` | Captura de latencia, proveedor, éxito y contexto estructurado (`sector`, `rol`) |
| **Analítica** | `/admin/api.php`, `/admin/index.php` | Agregación segura (sin PII), visualización de embudo y KPIs en tiempo real |
| **Aprendizaje** | `learn_prompt_optimizer.mjs` | Análisis offline, clasificación semántica y generación de PRs en borrador para revisión humana |

---

## 🛠️ Arquitectura Técnica

### Base de Datos

- Tabla `chat_metrics` (idempotente):
  ```sql
  CREATE TABLE IF NOT EXISTS chat_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    backend_used TEXT CHECK(backend_used IN ('groq', 'gemini', 'ollama')),
    latency_ms INTEGER NOT NULL CHECK(latency_ms >= 0),
    success BOOLEAN NOT NULL DEFAULT 1,
    tokens_est INTEGER NOT NULL CHECK(tokens_est > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );
  CREATE INDEX IF NOT EXISTS idx_session_id ON chat_metrics(session_id);
  CREATE INDEX IF NOT EXISTS idx_backend_latency ON chat_metrics(backend_used, latency_ms);
  ```
- Extensión de `leads` con columnas opcionales `sector` y `rol`, verificadas antes de `ALTER TABLE` mediante `PRAGMA table_info(leads)`.

### API Pública (`public/api/chat.php`)

- Inyección de métricas al final de la respuesta:
  ```php
  $start_time = microtime(true);
  // ... lógica de chat ...
  $latency_ms = (int)((microtime(true) - $start_time) * 1000);
  $metrics = [
    'session_id' => $session_id,
    'backend_used' => $backend,
    'latency_ms' => $latency_ms,
    'success' => $success,
    'tokens_est' => $tokens_est
  ];
  // → insertado en chat_metrics via PDO
  ```

### API Administrativa (`public/admin/api.php`)

Soporta tres endpoints protegidos por `auth.php`:

| Acción | Método | Respuesta Ejemplo | Restricción PII |
|--------|--------|-------------------|-----------------|
| `analytics_funnel` | GET | `{"stages":[{"name":"nuevo","count":124},{"name":"ganado","count":37}]}` | ✅ Solo conteos y nombres de estado |
| `analytics_by_dimension` | POST (JSON) | `{"dimension":"sector","data":[["SaaS",28],["Edu",19]]}` | ✅ Valores anonimizados; sin emails/nombres |
| `analytics_llm_metrics` | GET | `{"by_provider":{"groq":{"avg_latency_ms":421,"success_rate":0.97}}}` | ✅ Solo agregados numéricos |

> ⚠️ **Política de privacidad**: Ningún endpoint devuelve `session_id`, `transcript`, `email`, `phone` o campos identificables. Validación estática y dinámica obligatoria.

### Panel Administrativo (`public/admin/index.php`)

- Navegación por pestañas: `Leads` \| `Citas` \| `Analítica de Conversión`
- Tarjetas de KPI (actualizadas cada 60s vía `fetch()`):
  - `Tasa de Conversión a Citas`: `(cita_solicitada / contactado) * 100`
  - `Tasa de Clientes Ganados`: `(ganado / cita_solicitada) * 100`
  - `Latencia Promedio LLM`: `AVG(latency_ms)` filtrado por `success = 1`
  - `Volumen por Proveedor`: conteo agrupado por `backend_used`
- Embudo visual interactivo (Mermaid.js): representa las 6 etapas del lead con porcentajes acumulados.
- Modal de detalle de lead incluye sección `Journey Timeline`: chips con `contexto + timestamp`, vinculados a transcripción original (solo accesible tras autenticación explícita y dentro del mismo `session_id`).

### Optimizador Offline (`scripts/learn_prompt_optimizer.mjs`)

- Flujo CLI ejecutable:
  ```bash
  node scripts/learn_prompt_optimizer.mjs --min-samples 50 --provider groq
  ```
- Entrada: transcripciones de `leads` con `status IN ('ganado', 'perdido', 'no_interesado')` y `chat_metrics.success = 1`.
- Procesamiento:
  1. Clasificación binaria (ganado vs. no-ganado) usando Groq/Gemini (free-tier, rate-limited).
  2. Extracción de razones de cierre comunes (ej. "precio", "tiempo", "falta de integración") mediante prompting estructurado.
  3. Comparación de prompts iniciales y respuestas asociadas para identificar patrones de desalineación.
- Salida: archivo `PR_DRAFT.md` con formato compatible con `content-pr.yml`, conteniendo:
  - Resumen ejecutivo de hallazgos.
  - Fragmentos de transcripción anónimos (ej. `[transcript_abc123]`).
  - Propuesta de inserción en `SYSTEM_PROMPT` (con diff-like).
  - Advertencia explícita: `⚠️ Este PR es un DRAFT. Requiere revisión humana y aprobación manual.`

---

## 🧪 Verificación y Calidad

### Pruebas Estáticas

```bash
C:/xampp/php/php.exe -l public/api/chat.php
C:/xampp/php/php.exe -l public/admin/api.php
node scripts/build-taxonomy.mjs
npm run build
```

### Pruebas Funcionales

- `curl -H "Authorization: Bearer $TOKEN" "https://app.datanestiq.com/admin/api.php?action=analytics_funnel" | jq '.stages'`
- Validación de ausencia de PII: `grep -r -i "email\|phone\|name\|contacto" public/admin/api.php` → debe devolver vacío.
- Latencia máxima aceptable: ≤ 1200 ms (95º percentil) para `chat.php` en entorno staging.

### Migración de Producción

- Backup previo: `cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak`
- Ejecución remota idempotente: `/usr/bin/php8.2-cli init_crm_db.php`
- Despliegue atómico: `python scripts/deploy/deploy_ionos.py --confirm --init-crm`
- Sincronización de documentación: `npm run docs:sync` (activa Constitución §11)

---

## 📜 Referencias

- [Constitución Datanestiq §11 — Doc-Sync](https://docs.datanestiq.com/constitucion#seccion-11-doc-sync)
- [Spec 007 — Sistema de Leads Estructurados](https://docs.datanestiq.com/specs/007-leads-estructurados)
- [Spec 012 — Seguridad de Datos y Política de PII](https://docs.datanestiq.com/specs/012-pii-policy)

%%IGNORE_BLOCK_1%%
%%IGNORE_BLOCK_2%%
%%IGNORE_BLOCK_3%%