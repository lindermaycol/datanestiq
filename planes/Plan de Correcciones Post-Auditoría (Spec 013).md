# Plan de Correcciones Post-Auditoría (Spec 013)

## Objetivo
Resolver los bugs y gaps detectados en la auditoría externa, asegurando la calidad y funcionalidad de los componentes de navegación guiada, resaltado semántico y captura de leads.

## Cambios Propuestos

### Componente Chatbot (Bug 1 y Gap 4)
#### [MODIFY] src/components/islands/Chatbot.jsx
- Remover el `.slice(0, 4)` en la lista de sectores para mostrar los 10 sectores (incluido Sector Público).
- Agregar `max-h-48 overflow-y-auto` al contenedor de sectores para mantener la UI limpia.
- Enriquecer la tarjeta de captura de lead (`showLeadCard`) con los campos "Empresa/Entidad" y "Reto principal/Sistemas", y el texto: *"Un arquitecto revisará tu caso y te enviará un diagnóstico inicial en 48 horas. Sin compromiso."*
- Actualizar `saveLead` para enviar estos nuevos campos al backend.

### Componente ContextChips (Bug 2 y Bug 3)
#### [MODIFY] src/components/ui/ContextChips.jsx
- Corregir los IDs enviados en `handleSelect`: usar `cdo`, `cfo`, `ceo` (eliminando `_publico`, `_economico`, `_estrategico`).
- Implementar la funcionalidad de reset de contexto: Si el contexto ya existe, mostrar una píldora discreta *"Viendo como: Rol · Sector ✕"* en lugar de retornar `null`. Al hacer clic, se limpiará el `userContext` y `semanticHighlight`.

### Componente ConsultativeCTA (Bug 2)
#### [MODIFY] src/components/ui/ConsultativeCTA.jsx
- Actualizar las condicionales para coincidir con los IDs reales (`cdo`, `cfo`, `ceo`).

### Base de Datos Personas (Gap 5)
#### [MODIFY] src/data/personas.json
- Agregar `"hiperautomatizacion"` e `"ai-data-science"` al CFO.
- Agregar `"hiperautomatizacion"` al CIO y CDO.

### Contenido Sector Público (P2 - Contenido 6)
#### [MODIFY] src/content/sectors/publico.yaml
- Añadir el caso de uso "Calidad de padrones ciudadanos" en el array de `problems`.

### Nuevos Posts de Blog (P2 - Contenido 7)
#### [NEW] src/content/blog/roi-ia-evaluacion-cfo.md
#### [NEW] src/content/blog/data-ia-ebitda.md
#### [NEW] src/content/blog/ventaja-competitiva-datos-ceo.md
- Se generarán mediante `docs-generator.mjs` o se escribirán manualmente (si el LLM falla) siguiendo la temática y el campo `pubDate: 2026-...` sin comillas y `draft: false`.

## Verificación Planificada
- Reconstruir la taxonomía con `npm run build-taxonomy` y ejecutar `npm run build`.
- Validar visualmente el comportamiento de los chips y el resaltado semántico.
- Validar en Network que el Chatbot no hace requests HTTP en su flujo guiado (solo al capturar lead).
