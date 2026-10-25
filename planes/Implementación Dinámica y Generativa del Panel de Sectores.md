# Implementación Dinámica y Generativa del Panel de Sectores

El objetivo es transformar la sección de Sectores ("Industrias") de un listado estático a un panel reactivo impulsado por IA, logrando que el usuario perciba que la web se adapta y genera contenido comercial personalizado en tiempo real.

## User Review Required

> [!WARNING]
> La creación de sectores "on-the-fly" por el usuario implica actualizar el índice del *Worker* de Transformers.js en el navegador para que, de ahí en adelante, ese nuevo sector también participe en el buscador semántico principal. Esto puede tomar un microsegundo de recalibración, pero no afectará el rendimiento percibido.

## Proposed Changes

### 1. `app.js` (Componente de Datos y Lógica IA)

*   **[MODIFY]**: Actualizaremos el arreglo `semanticCorpus` para incluir tanto los **Servicios** (con prefijo `service-`) como las **Industrias** (con prefijo `sector-`). Se añadirán los 4 sectores actuales más los 6 nuevos solicitados (Logística, Educación, Minería, Manufactura, Seguros, Telecomunicaciones), todos con sus respectivos `keywords` y descripciones base.
*   **[MODIFY]**: Refactorizaremos el método `renderSemanticResults` para que discrimine los resultados devueltos por Transformers.js. Los resultados con ID `service-*` reordenarán el panel de Soluciones, y los de `sector-*` reordenarán el panel de Industrias, destacando la industria más afin a la búsqueda principal del usuario en la parte superior.
*   **[NEW]**: Implementaremos la función `renderSectorsGrid()` que dibujará las tarjetas dinámicamente, inyectando el `<input>` de desafío generativo en cada una.
*   **[NEW]**: Implementaremos la función `generateSectorPitch(sectorName, challenge, inputElement)` que llamará a Groq (vía nuestro proxy `api/chat.php`) enviando el *prompt* exacto para generar el argumento de ventas de 25 palabras, y reemplazará el input aplicando un efecto *typewriter* sutil.
*   **[NEW]**: Implementaremos la función para el creador "On-the-fly", de modo que si el usuario escribe un sector que no existe, se añada al estado local de sectores, se redibuje la cuadrícula colocando este nuevo elemento al final, y se añada al Worker de Transformers.js.

### 2. `index.html` (Estructura y UI)

*   **[MODIFY]**: Vaciaremos el contenido estático de `<section id="industrias">` (`grid-cols-4`). En su lugar, colocaremos un contenedor vacío `<div id="sectores-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8"></div>` que será poblado por `app.js`.
*   **[NEW]**: Añadiremos el input interactivo al final de esa sección: "¿No ves tu industria? Escríbela aquí", estilizado acorde al tema oscuro/neón, que disparará la función de creación dinámica.

## Verification Plan

### Manual Verification
1.  **Test de Filtrado Semántico Cruzado:** Escribir "Problemas en mis cadenas de suministro" en el buscador principal. Verificar que el panel de *Soluciones* destaca "IA o Datos" y el panel de *Sectores* (abajo) se reordena poniendo a **Logística** en primer lugar.
2.  **Test On-The-Fly:** Bajar al panel de sectores, escribir "Aeroespacial" en el input final y presionar *Enter*. Verificar que aparece una nueva tarjeta titulada "Aeroespacial" instantáneamente.
3.  **Test de Pitch Generativo:** En la tarjeta "Aeroespacial", escribir "Lanzamientos de cohetes retrasados por clima" y presionar *Enter*. Observar el spinner/indicador y verificar que el texto se reemplaza por el pitch de ventas provisto por Llama 3 con efecto *typewriter*.
