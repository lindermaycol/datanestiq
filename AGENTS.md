# AGENTS.md — Overview de Agentes IA en Datanestiq

Este documento describe la estructura, convenciones, arquitectura y especificaciones fundamentales que rigen el ecosistema de agentes IA en el repositorio de **Datanestiq**. Está diseñado como punto de entrada para nuevos agentes (IA o humanos) que operen dentro del sistema: deben leerlo antes de interactuar con cualquier spec, workflow, prompt o artefacto de implementación.

---

## 🌐 Arquitectura General de Agentes

Los agentes en Datanestiq no son entidades aisladas, sino **instancias ejecutables de una fábrica de agentes**, cuya metodología está formalizada en la **Spec 004 — Metodología de Desarrollo Digital Premium**, y refinada en `specs/004-metodologia-desarrollo-digital/spec_004_refined.md`.

La arquitectura se basa en tres capas interconectadas:

1. **Capa de Constitución y Gobernanza**  
   - Define los principios éticos, límites operativos y reglas de interacción.  
   - Ubicación: `.specify/memory/constitution.md`  
   - Plantilla base: `.specify/templates/constitution-template.md`

2. **Capa de Especificación y Planificación**  
   - Cada agente opera bajo una *spec* numerada (ej. `001`, `004`, `005`, etc.), con su propio conjunto de artefactos:  
     - `spec.md`: definición funcional y alcance  
     - `plan.md`: plan de implementación (cronología, dependencias, validaciones)  
     - `tasks.md`: lista detallada de tareas ejecutables  
     - `tech_debt.md`: deuda técnica asociada (si aplica)  
   - Todas las specs residen bajo `specs/`, organizadas por nombre canónico (ej. `001-elevacion-premium/`).

3. **Capa de Ejecución y Orquestación**  
   - Los agentes se activan mediante workflows registrados en `.specify/workflows/workflow-registry.json`.  
   - Se integran con herramientas externas mediante manifiestos declarativos:  
     - `.specify/integrations/agy.manifest.json`  
     - `.specify/integrations/speckit.manifest.json`  
   - La orquestación conversacional (ruteo híbrido, chaining contextual, tool-calling) está especificada en la **Spec 002 — Microexperiencias de IA**, con soporte técnico en `Walkthrough/Walkthrough Context-Aware Chaining (Spec 002).md` y `Walkthrough/Walkthrough Arquitectura Conversacional (Spec 002).md`.

---

## 📁 Estructura de Directorios Clave para Agentes

| Ruta | Propósito | Notas |
|------|-----------|-------|
| `.specify/` | **Núcleo operativo de agentes**: memoria, plantillas, workflows, integraciones y configuración de contexto. | Contiene `constitution.md`, `workflow-registry.json`, `templates/`, `integrations/`. |
| `specs/` | **Catálogo autorizado de funcionalidades**: cada subdirectorio es una spec numerada con sus artefactos (`spec.md`, `plan.md`, `tasks.md`, `tech_debt.md`). | Es la fuente única de verdad para alcance, priorización y estado de implementación. |
| `prompts/` | **Biblioteca de instrucciones ejecutables**: prompts especializados por rol, tarea o fase (ej. `01-premium-website-builder.md`, `prompt-antigravity-fix-chatbot-textura-spec002.md`). | Muchos están vinculados explícitamente a specs (ej. `add_user_stories_spec_002.md`). |
| `Walkthrough/` | **Registro ejecutivo de validaciones y cierres**: documentos que confirman la ejecución completa de una spec o componente. | Ejemplos: `Walkthrough Implementación Completada Panel de Sectores Dinámico...`, `Walkthrough Dogfooding y Arquitectura LangGraph (Spec 004).md`. |
| `planes/` | **Documentos estratégicos de planificación**: planes de implementación, auditorías, correcciones y gobernanza. | Incluye `Plan de Ejecución`, `Informe-Auditoria-UX.md`, `Gobernanza de Costos de IA (Spec 002).md`. |
| `.claude/` | **Configuración operativa de entornos de agente**: scripts y lanzadores específicos para despliegue y operaciones. | Contiene `launch.json` y `agents/deploy-ops.md`. |

> ⚠️ **Convención crítica**: Ningún agente debe asumir comportamiento implícito. Todo debe derivarse de artefactos versionados en `specs/`, `.specify/` o `Walkthrough/`. Si un comportamiento no está documentado allí, **no existe operativamente**.

---

## 🧩 Convenciones de Nombre y Numeración

- **Specs**: Numeración secuencial (`001`, `002`, ..., `016`) con nombres descriptivos en kebab-case:  
  `001-elevacion-premium/`, `005-openwiki-agentes/`, `013-conversion-consultiva-rol-sector/`.  
  El número refleja orden de priorización y dependencia lógica (no cronológica necesariamente).

- **Prompts**:  
  - `01-*.md` a `10-*.md`: prompts base de la **Fábrica de Agentes (Spec 004)**.  
  - `prompt-antigravity-*`: prompts de acción inmediata, corrección crítica o validación post-implementación.  
  - `prompt-*`: prompts genéricos de auditoría, navegación, roleplay o verificación.

- **Walkthroughs**: Siempre incluyen el número de spec y el estado (`Completada`, `Elevación Premium`, `Recuperación`, `Fix`, etc.).  
  Ej: `Walkthrough Recuperación de Microexperiencias IA (Spec 002).md`.

- **Planes**: Usan formato claro: `Plan de Implementación [Nombre] (Spec XXX).md` o `Plan Estratégico...`.

---

## 🛠️ Especificaciones Centrales para Agentes

Estas specs definen los patrones fundamentales que todos los agentes deben conocer y aplicar:

| Spec | Nombre | Rol Clave para Agentes | Ubicación clave |
|--------|--------|-------------------------|-----------------|
| **002** | Microexperiencias de IA | Base para arquitectura conversacional, chaining contextual, gobernanza de costos y microexperiencias (chatbot, warmup, highlight por rol). | `specs/002-microexperiencias-ia/`, `Walkthrough/Walkthrough Microexperiencias de IA (Spec 002) Completado.md` |
| **004** | Metodología de Desarrollo Digital | Define la “Fábrica de Agentes”: 10 roles especializados, su interacción y estandarización de outputs. | `specs/004-metodologia-desarrollo-digital/spec_004_refined.md`, `prompts/01-premium-website-builder.md`–`10-objection-killing-faq.md` |
| **005** & **009** | OpenWiki Agentes / LangChain | Especifican la infraestructura de documentación viva, CI/CD de conocimiento, gateway LiteLLM y balanceo. | `specs/005-openwiki-agentes/`, `specs/009-openwiki-langchain/`, `scripts/openwiki-llm.yaml` |
| **006** | Ecosistema Web con Astro | Define el stack frontend (Astro + Content Collections), routing dinámico y fachada de difusión premium. | `specs/006-ecosistema-astro/`, `src/content.config.ts`, `src/pages/[...slug].astro` |
| **011** | Enriquecimiento Profundo de la Taxonomía | Establece la taxonomía como fuente única de verdad (SSOT) para industrias, personas, madurez analítica y equivalencias de rol (`roleEquivalents`). | `specs/011-enriquecimiento-taxonomia/`, `src/data/extendedIndustries.json`, `src/data/personas.json`, `src/lib/roleLocalization.ts` |
| **013** | Conversión Consultiva por Rol × Sector | Define el marco de personalización consultiva: cómo los agentes deben adaptar mensajes, casos de éxito y propuestas según rol (CFO, CIO, CEO) y sector (público, seguros, finanzas). | `specs/013-conversion-consultiva-rol-sector/spec.md`, `planes/insumos-conversion-consultiva/` |

---

## 🧭 Flujo Operativo Típico de un Agente

1. **Validar contexto**: Leer `.specify/memory/constitution.md` y el `workflow-registry.json` para entender restricciones y flujo esperado.  
2. **Identificar spec objetivo**: Determinar qué spec se activa (ej. `002`, `011`, `013`) y cargar su `spec.md` + `plan.md`.  
3. **Consultar artefactos complementarios**:  
   - Datos: `src/data/*.json`, `specs/XXX/data-model.md`  
   - Prompts: `prompts/` relevantes (ej. `prompt-antigravity-roleEquivalents-localizacion-roles-por-sector.md`)  
   - Validaciones pasadas: `Walkthrough/` y `planes/` correspondientes  
4. **Ejecutar y registrar**: Generar output conforme a plantillas (`spec-template.md`, `plan-template.md`, `tasks-template.md`) y registrar avances en `tasks.md` o `Walkthrough/`.  
5. **Cerrar con evidencia**: Documentar cierre en `Walkthrough/` y actualizar `ESTADO-SPECS.md` (ubicado en `planes/ESTADO-SPECS.md`).

---

## 📜 Referencias Obligatorias

- `.specify/memory/constitution.md` → Reglas de conducta, ética y límites  
- `.specify/workflows/workflow-registry.json` → Mapa de flujos autorizados  
- `planes/ESTADO-SPECS.md` → Estado actual de todas las specs (prioridad, estado, dueño)  
- `specs/004-metodologia-desarrollo-digital/spec_004_refined.md` → Especificación maestra de la Fábrica de Agentes  
- `Walkthrough/Walkthrough Dogfooding y Arquitectura LangGraph (Spec 004).md` → Validación ejecutiva de la fábrica  
- `src/lib/roleLocalization.ts` → Lógica de mapeo rol × sector × industria (fuente de verdad para personalización)

---

✅ **Este documento es autocontenida y no requiere interpretación externa. Todo lo necesario para operar como agente en Datanestiq está aquí o en los artefactos directamente referenciados.**  
🔁 Actualizaciones se realizan exclusivamente mediante PRs que modifiquen este archivo y sus dependencias directas en `.specify/` y `specs/`.