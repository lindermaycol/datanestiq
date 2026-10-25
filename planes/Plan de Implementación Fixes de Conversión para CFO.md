# Plan de Implementación: Fixes de Conversión para CFO

Se resolverán tres brechas de conversión de código diagnosticadas en el recorrido en primera persona del CFO, centradas en la descubribilidad del calculador de ROI, la presentación visual (Markdown) del chatbot, y el momento óptimo para captar leads.

## User Review Required
No hay decisiones de diseño críticas que requieran revisión especial, los cambios se limitan a seguir el prompt de requerimientos con el objetivo de optimizar la experiencia de usuario y la conversión.

## Proposed Changes

### Componente Sectorial y CTA Dinámico

#### [MODIFY] [src/pages/sectores/[slug].astro](file:///C:/xampp/htdocs/datanestiq/src/pages/sectores/[slug].astro)
- En la sección del hero (o inmediatamente después), inyectar condicionalmente un banner/botón que enlace hacia `/business-case`. 
- **Condición**: El bloque aparecerá sólo si el sector es `finanzas`, `seguros` o si sus roles relevantes incluyen a `cfo` (`sector.relevantPersonas?.includes('cfo')`).
- **Copy**: "Calcula tu Business Case de ROI →"

#### [MODIFY] [src/components/ui/ConsultativeCTA.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/ui/ConsultativeCTA.jsx)
- Actualizar la lógica del componente para que su renderización sea dinámica según el contexto:
  - **Para contextos financieros/CFO**: Renderizará un elemento `<a>` nativo apuntando a `/business-case`.
  - **Para el resto de roles (o neutro)**: Seguirá renderizando un `<button>` que dispara el evento `open-chatbot`.

---

### Mejora del Renderizado Markdown en Chatbot

#### [MODIFY] [src/components/islands/Chatbot.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- Mejorar la función `formatText` para interpretar listas correctamente y proteger contra inyecciones XSS.
- Reemplazar `<` y `>` por entidades HTML.
- Aplicar expresiones regulares que detecten líneas comenzando por `\d+.` o `[-*]` para envolverlas en etiquetas `<li>` con las clases adecuadas (`ml-6 list-outside`).
- Ajustar la regla de itálicas para evitar falsos positivos con los marcadores de listas (asteriscos).

---

### Afinación de Estrategia de Captación en Prompt

#### [MODIFY] [public/api/chat.php](file:///C:/xampp/htdocs/datanestiq/public/api/chat.php)
- Modificar la cadena `SYSTEM_PROMPT`.
- **Nuevo texto para regla 2**: "PRIMERO aporta valor respondiendo a su pregunta concreta. SOLO DESPUÉS de haber entregado una respuesta útil, o cuando el usuario muestre explícitamente intención de avanzar (agendar, diagnóstico), pide un correo electrónico y un teléfono para contactarlo. NO pidas datos de contacto prematuramente si el usuario acaba de hacer una consulta de negocio."

## Verification Plan

### Manual Verification
1. Visitar `/sectores/finanzas` y verificar la presencia visible del enlace a la calculadora de ROI.
2. Hacer click en el selector de rol "CFO" y verificar que el CTA en la navegación (o hero) cambia para enlazar al estimador `/business-case` en lugar de disparar el chatbot.
3. Interactuar con el chatbot pidiendo "3 ejemplos de casos de uso". Verificar que la respuesta renderice listas numeradas/viñetas correctamente en lugar de código crudo.
4. Interactuar con el chatbot haciendo una pregunta estratégica y constatar que aporta valor *antes* de solicitar PII (correo/teléfono).
5. Correr el comando de build de Astro y verificar que completa sin errores.
