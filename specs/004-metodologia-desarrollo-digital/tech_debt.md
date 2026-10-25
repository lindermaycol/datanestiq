# Deuda Técnica: Spec 004 (Metodología de Desarrollo)

## Estado
- **Fase actual:** Automatización Parcial (Frontend SSG operando)
- **Impacto:** Alto
- **Severidad:** Alta (Pipeline LangGraph Backend roto)

## Lista de Deuda Técnica (Technical Debt)

### 1. Ejecución Manual de los Agentes / Pipeline LangGraph
- **Descripción:** La "Fábrica de Agentes" fue planificada en `backend/langgraph_pipeline.py`.
- **Riesgo:** Complejidad innecesaria.
- **Solución Implementada:** **ABANDONADO**. Se ha decidido oficialmente abandonar el pipeline Python/LangGraph y los agentes multi-turno. Toda la generación de contenido offline se ha migrado a scripts directos en Node.js (patrón single-shot de la Spec 010).
- **Estado:** ✅ [RESUELTO por abandono estratégico]
