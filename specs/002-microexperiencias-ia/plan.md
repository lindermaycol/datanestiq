# Implementation Plan: Microexperiencias de IA (Spec 002)

Este plan detalla la implementación técnica en el frontend (prototipo) de las 3 microexperiencias de IA que elevarán la propuesta de valor de Datanestiq al permitir al usuario interactuar directamente con elementos "smart".

## Propuesta Técnica por Componentes

### 1. Componente: Demo de Copiloto Interactivo

Se diseñará una sección dedicada debajo de "Soluciones" o dentro de ella.
#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Añadir sección `<section id="copilot-demo">`.
- Diseño UI: Un contenedor tipo "Terminal/Chat" con look glassmorphism premium.
- Tres botones de *prompts* pre-definidos (ej. "Analizar cartera de riesgo").
- Un área de renderizado de resultados.
#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- Función `runCopilotDemo(promptId)` que al hacer clic simule:
  1. Estado `loading` (typing indicator o barra de progreso).
  2. Uso de `setTimeout` (1500ms) para simular latencia de LLM/API.
  3. Renderizado del resultado (HTML mockeado simulando un gráfico simple en CSS o una tabla de datos generada por IA).

### 2. Componente: Formulario de Diagnóstico Dinámico (Wizard)

Sustituirá al formulario clásico en la sección Metodología/Contacto.
#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Añadir un contenedor `<div id="diagnostic-wizard">`.
- El contenedor tendrá pasos (steps) ocultos inicialmente, excepto el primero.
#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- Variables de estado `diagnosticState`.
- Funciones `nextDiagnosticStep()` que avancen al siguiente panel en función de la selección actual.
- Al final, un formulario para recolectar Nombre/Email y enviar todo a `formspree` (el mismo flujo del chatbot pero extendido con las respuestas del diagnóstico).

### 3. Componente: Recomendador de Soluciones

Widget interactivo rápido.
#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- En la sección Industrias o sobre ella, agregar dos select boxes nativos (estilizados) para Industria y Rol.
- Asegurar que las tarjetas de industria/soluciones tengan atributos `data-industry` y `data-role`.
#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- Función `filterSolutions()` disparada en el `onchange` de los selects, que modifique la opacidad/escala de las tarjetas no relevantes, destacando las coincidentes.

## Verification Plan

### Manual Verification
1. **Copilot Demo**: Probar clics en todos los prompts pre-definidos. Confirmar que el estado de carga aparece y desaparece correctamente.
2. **Diagnostic Wizard**: Avanzar paso a paso, seleccionar diferentes opciones y confirmar que el resumen final recolecta la información.
3. **Recommender**: Seleccionar "Salud" y verificar que las demás tarjetas se oscurecen o desaparecen.
  4. **Performance**: Validar que la inyección de este JavaScript no cause lag al hacer scroll ni afecte el `IntersectionObserver` de la Spec 001.

## Complexity Tracking

| Violación | Por qué se necesitaría | Por qué se pospone |
|---|---|---|
| Transformers.js (Edge AI) en el cliente | La Constitution (sección 4) lo exige para clasificación/embeddings ligeros sin depender de Groq | Requiere su propio diseño (selección de modelo, tamaño de bundle, fallback sin WebGPU) que excede el alcance de esta auditoría de corrección de bugs; se planificará como iniciativa separada una vez cerrado el backlog crítico (Spec 008 incluida) |

## Roadmap de Implementación IA (Fasado) - [COMPLETADO]

El roadmap estructural ha sido ejecutado en su totalidad, levantando la deuda técnica y cumpliendo al 100% las Historias de Usuario (US1 a US4) de la Spec 002. Los componentes operativos actuales son:

### Fase A: Copilot Demo (US1) - [✔]
Implementado con `CopilotDemo.jsx` y ruteado al modelo `llama-3.3-70b-versatile` en `chat.php`.

### Fase B: Buscador Semántico + Transformers.js + Context-Aware Chaining (US3 & US4) - [✔]
Implementado usando `@xenova/transformers` en un Web Worker dedicado. Se aplica `semanticHighlight` dinámico a `Services.astro` y al `DiagnosticWizard`.

### Fase C & D: Wizard Adaptativo con Puntaje de Madurez (US2) - [✔]
Migración lograda en `MultiStepWizard.jsx` suscribiéndose al store reactivo. Guarda leads tabulados con mitigación de riesgos de seguridad.

### Fase E & F: Metodología, Ruteo y Extracción de Leads - [✔]
Implementada metodología estandarizada. Extractor batch integrado en PHP y todos los logs bloqueados con `.htaccess`.
