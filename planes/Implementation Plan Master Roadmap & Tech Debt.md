# Implementation Plan: Master Roadmap & Tech Debt

El objetivo es consolidar el estado actual del proyecto (Fase Prototipo validada) y trazar el puente arquitectónico hacia la Fase de Escalamiento (Astro + OpenWiki). Además, se inicializarán los documentos de Deuda Técnica (`tech_debt.md`) para cada Spec, garantizando trazabilidad para futuras refactorizaciones.

## Proposed Changes

### 1. Documentación del Master Roadmap
Se creará un nuevo archivo central:
**[NEW]** `c:/xampp/htdocs/datanestiq/planes/ROADMAP.md`
Este archivo contendrá:
- **Gráfico Mermaid:** Diagrama visual del pipeline de las 7 Specs, mostrando dependencias lineales.
- **Tabla de Estados:** Detalle del estatus de implementación (COMPLETADO vs PENDIENTE).

### 2. Creación de Directorios para Nuevas Specs
- **[NEW]** Carpeta `specs/006-ecosistema-astro/`
- **[NEW]** Carpeta `specs/007-multi-pagina/`

### 3. Registro de Deuda Técnica (Technical Debt)
Se generará un archivo `tech_debt.md` por cada Spec para documentar atajos tomados en fase prototipo que deben resolverse a futuro.

- **[NEW]** `specs/001-elevacion-premium/tech_debt.md` (Deuda: Clases CSS hardcodeadas, falta de purgado).
- **[NEW]** `specs/002-microexperiencias-ia/tech_debt.md` (Deuda: LocalStorage temporal para estado global, falta de base de datos remota para leads).
- **[NEW]** `specs/003-taxonomia-servicios/tech_debt.md` (Deuda: Corpus en archivo JSON estático en lugar de CMS Headless).
- **[NEW]** `specs/004-metodologia-desarrollo-digital/tech_debt.md` (Deuda: Ejecución manual de agentes, requiere LangGraph CI/CD).
- **[NEW]** `specs/005-openwiki-agentes/tech_debt.md` (Pendiente de implementación).
- **[NEW]** `specs/006-ecosistema-astro/tech_debt.md` (Pendiente de implementación).
- **[NEW]** `specs/007-multi-pagina/tech_debt.md` (Pendiente de implementación).

## Verification Plan
1. Validar que Mermaid renderice correctamente el gráfico en GitHub/Markdown.
2. Asegurar que las 7 carpetas de Specs existen en el árbol de directorios con sus respectivos archivos de deuda técnica.

> [!IMPORTANT]
> **User Review Required:**
> Revisa si hay alguna deuda técnica específica de las primeras 4 Specs (HTML/JS/CSS) que quieras que añada a los registros antes de crearlos, o si te parece bien que los inicialice con las deudas "estándar" de un prototipo (uso de LocalStorage, JSON estáticos, etc.).
