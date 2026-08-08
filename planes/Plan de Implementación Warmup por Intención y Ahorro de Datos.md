# Plan de Implementación: Warmup por Intención y Ahorro de Datos

Este plan documenta la refactorización del componente `SemanticSearch.jsx` para evitar la descarga de 113MB del modelo de inteligencia artificial en usuarios que no utilizan el buscador o que se encuentran en conexiones limitadas.

## Cambios Propuestos

### [MODIFY] [SemanticSearch.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/SemanticSearch.jsx)

- **Eliminar `requestIdleCallback` masivo**: Se removerá la lógica que ejecutaba el `warmup` del Worker automáticamente al montar el componente.
- **Detección de Ahorro de Datos (Network Information API)**:
  Se implementará una validación antes de realizar el warmup:
  ```javascript
  const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  const saveData = conn?.saveData === true;
  const slow = conn && /(^|-)2g$/.test(conn.effectiveType || '');
  if (saveData || slow) return; // No precargar, esperar a ejecución explícita
  ```
- **Warmup por Intención (Event Listeners)**:
  - Se añadirá un flag `hasWarmedUp` (mediante `useRef(false)`).
  - Se interceptarán los eventos `onMouseEnter` en el contenedor principal y `onFocus` en el `<input>`.
  - Al dispararse cualquiera de estos eventos (y siempre que la red lo permita), se enviará el mensaje `warmup` al Web Worker.

## Verification Plan

### Verificación E2E Local
- **Sin Intención**: Al cargar el proyecto (`npm run dev`), la pestaña Network no registrará descargas de Hugging Face.
- **Con Intención**: Al hacer "hover" sobre el buscador con el cursor, o al hacer click en el input de búsqueda, comenzará instantáneamente la descarga del `.onnx` cuantizado.
- **Conexión Limitada (Slow 3G / Save-Data)**: Al forzar esta condición en las DevTools, el hover no iniciará la precarga, pero realizar la búsqueda efectivamente activará la descarga perezosa (`handleSearch`).
