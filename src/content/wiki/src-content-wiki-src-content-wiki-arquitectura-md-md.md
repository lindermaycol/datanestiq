---
title: "Arquitectura Web de Datanestiq"
description: "Documentación técnica que describe la arquitectura basada en Astro, su estructura de seed mode, componentes clave y estrategia de gobernanza operativa."
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["astro","seed-mode","content-collections","openwiki","spec-driven-development"]
seoScore: 100
---
# Arquitectura Web de Datanestiq

La arquitectura web de Datanestiq se fundamenta en **Astro** como framework principal, aprovechando su arquitectura de islas (Island Architecture). Esto permite la creación de sitios estáticos (SSG) de alto rendimiento, combinados con interactividad dinámica en el cliente para funcionalidades como el Chatbot y microexperiencias.

## Principios
1. **Rendimiento primero:** Zero JS por defecto en la carga inicial de las landings comerciales.
2. **Interactividad aislada:** Uso de directivas (ej. `client:load`) únicamente donde es estrictamente necesario.

## Componentes Clave
- **Colecciones (Content Collections):** Utilizamos colecciones fuertemente tipadas (Zod) para la gestión de contenido, incluyendo el blog (`/src/content/blog`) y la documentación técnica de OpenWiki (`/src/content/wiki`).
- **Integraciones:** Se emplean integraciones clave como React (para la implementación de islas), Tailwind CSS (actualmente bajo revisión para optimización), y soporte nativo para Markdown/MDX.
- **APIs Serverless:** Para flujos de trabajo seguros y dinámicos, como el Chatbot, se utilizan endpoints PHP o funciones Edge.

## Estructura de Repositorio y Semilla (Seed Mode)
El repositorio opera bajo un modelo de *seed* estructurado en capas:

- **`.specify/`:** Contiene la memoria operativa del proyecto: `constitution.md`, `workflows/workflow-registry.json`, plantillas de especificaciones (`spec-template.md`), planes (`plan-template.md`) y manifestos de integraciones (`agy.manifest.json`, `speckit.manifest.json`).
- **`specs/`:** Directorio organizado por número de especificación (ej. `005-openwiki-agentes/`, `009-openwiki-langchain/`) con `spec.md`, `plan.md`, `tasks.md` y `tech_debt.md` para cada iniciativa.
- **`Walkthrough/`:** Documentación ejecutiva de implementaciones completadas, como *Walkthrough Implementación de Spec 005 (OpenWiki)* o *Walkthrough Generador Multi-Destino de Documentación (Spec 010)*, validando el cumplimiento de las especificaciones.
- **`planes/`:** Planes de implementación detallados, alineados con las specs y los walkthroughs, ej. *Plan de Implementación OpenWiki Real Motor* o *Plan de Implementación Taxonomía de Servicios (Spec 003)*.
- **`prompts/`:** Biblioteca de prompts especializados para generación, refinamiento y validación de artefactos técnicos (ej. `prompt-antigravity-spec005-openwiki-real.md`, `refine_spec_005.md`).
- **`src/content.config.ts`:** Configuración centralizada de colecciones de contenido, definiendo esquemas para `blog`, `wiki`, y futuras colecciones.
- **`src/data/`:** Fuentes de datos estructurados para taxonomía (`taxonomyCorpus.json`, `extendedIndustries.json`), localización por rol (`roleLocalization.ts`, `personas.json`) y corpus sectorial (`sectorsCorpus.json`).
- **`scripts/`:** Automatización crítica: `deploy_ionos.py`, `docs-generator.mjs`, `validate-taxonomy.js`, `openwiki-local-sync.sh`.

## Estrategia de Despliegue y Operaciones
- **Despliegue:** Soportado por scripts específicos (`deploy/deploy_ionos.py`) y planes documentados (`planes/DESPLIEGUE-IONOS.md`).
- **Gobernanza:** Integrada mediante `lighthouserc.json`, `eslint.config.mjs`, y auditorías técnicas (`auditorias/Auditoría de Ingeniería Inversa — Datanestiq.md`).
- **Observabilidad:** Validación continua mediante `test-specs.sh`, `PRUEBAS-SPECS.md` y reportes de QA (`UX/reporte-qa-datanestiq-home-2026-07-23.md`).

Esta arquitectura refleja un sistema vivo: cada cambio en `specs/` se propaga a `Walkthrough/`, `planes/`, `prompts/` y `scripts/`, asegurando coherencia entre diseño, implementación y gobernanza.