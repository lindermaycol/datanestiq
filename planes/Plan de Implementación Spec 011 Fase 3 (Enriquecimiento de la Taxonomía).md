# Plan de Implementación: Spec 011 Fase 3 (Enriquecimiento de la Taxonomía)

Este documento contiene el plan arquitectónico y de datos (SDD) para profundizar la taxonomía de Datanestiq, preparándola para ser una fuente de verdad pura y enriquecida para los motores de renderizado y conversión consultiva.

## 1. Overview y Deliverables
**Objetivo:** Enriquecer la taxonomía (pilares, sectores, personas) con profundidad técnica y métricas específicas, manteniendo los principios de honestidad y separación de responsabilidades (Spec-Driven Development).
**Entregables:**
- Actualización de `src/lib/schemas.js` con los nuevos tipos de datos.
- Archivos YAML en `src/content/pillars/` enriquecidos (especialmente Sistemas Digitales y Estrategia de Datos).
- Archivos YAML en `src/content/sectors/` con KPIs ampliados (Finanzas, Seguros, Minería) y nuevos modelos para el sector Público.
- Archivo `src/data/personas.json` enriquecido con respuestas formales a las objeciones del comité de compras.

## 2. Decisión de Schema (Opción A)
Se seleccionó la **Opción A**, modificando `src/lib/schemas.js` para mantener los datos fuertemente tipados y parseables:
- **En `personaSchema` (dentro de roles):** 
  `objectionResponses: z.array(z.object({ objection: z.string(), response: z.string() })).optional()`
  *Justificación: Empareja perfectamente con las `objections` existentes y permite a las UI iterar objetos claros.*
- **En `sectorSchema`:**
  `deploymentModels: z.array(z.string()).optional()`
  `engagementModels: z.array(z.string()).optional()`
  *Justificación: Permite inyectar esta metadata específicamente al sector público (u otros en el futuro).*

## 3. Enriquecimiento de Pilares
Se modificarán los YAML de `src/content/pillars/`:
- **Sistemas Digitales (`sistemas-digitales.yaml`):** 
  - `techStack` y `competitivePositioning`: Se añadirán explícitamente opciones de arquitectura Cloud, Híbrido, On-Premise, VPC.
  - Se sumarán patrones de integración Legacy/ERP/Core, garantías de uptime, residencia/soberanía de datos y TCO.
- **Estrategia de Datos e IA (`estrategia-datos-ia.yaml`):**
  - Se inyectarán conceptos fundamentales de Data Governance: catálogo de datos, linaje, data stewardship, calidad de datos e interoperabilidad.
- **Otros Pilares:** Revisión menor de `techStack` y `proofPoints` `[EST]` para mantener consistencia y densidad B2B.

## 4. Enriquecimiento de Sectores
Se modificarán los YAML de `src/content/sectors/`:
- **Finanzas (`finanzas.yaml`) y Seguros (`seguros.yaml`):** 
  - Expansión de la matriz `kpis` de ~2 a 4–6 KPIs marcados estrictamente con `[EST]` (ej. prevención de fraude, reducción de morosidad/NPL, siniestralidad, reservas técnicas).
- **Minería (`mineria.yaml`):**
  - Refuerzo de KPIs `[EST]` enfocados en payback y OEE.
- **Sector Público (`publico.yaml`):**
  - Incorporación de los nuevos campos estructurales `deploymentModels` (ej. "On-Premise", "Nube Privada Segura") y `engagementModels` (ej. "Fase Piloto", "Licitación / Diagnóstico Consultivo").

## 5. Enriquecimiento de Personas
Se modificará `src/data/personas.json`:
- Mapeo 1:1 de `objectionResponses` para cada rol (CFO, CIO, CDO, CTO, COO, CISO, CEO) basado en sus `objections`.
- Redacción de respuestas honestas, centradas en las capacidades reales de Datanestiq (ej. coexistencia de stack, mitigación de TCO, ROI concreto).
- Ajuste fino de `decisionCriteria` para alinear con el tono consultivo.

## 6. Validación (Criterios de Aceptación)
- Ejecución de `node scripts/build-taxonomy.mjs` sin errores (Zod).
- Verificación visual de los JSON de salida (solo los campos deseados, sin arrastrar metadata cruda).
- Ejecución de `npm run build` para garantizar que las páginas existentes (islas y rutas) sigan funcionando perfectamente con el esquema ampliado.

## 7. Riesgos y Tareas Preliminares
- **Riesgo:** Ruptura de referencialidad si los esquemas fallan. 
  **Mitigación:** Los nuevos campos serán declarados como `.optional()` en Zod para asegurar la retrocompatibilidad.
- **Riesgo:** Hallucination en `[EST]`.
  **Mitigación:** Asegurar programáticamente (o manual) que los proofPoints y KPIs siempre tengan la etiqueta `[EST]`.

> [!IMPORTANT]
> **Revisión Requerida:** Por favor, revisa el plan, en especial la **Decisión de Schema (Opción A)**. Si te parece correcto, haz clic en **Proceed** y ejecutaré todas las modificaciones en la base de datos de taxonomía.
