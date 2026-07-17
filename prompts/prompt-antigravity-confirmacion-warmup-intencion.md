# Prompt para Antigravity: Confirmación del Plan de Warmup por Intención → ejecutar

Revisé `planes/Plan de Implementación Warmup por Intención y Ahorro de Datos.md`. Es correcto y bien acotado. **Luz verde para ejecutar**, con 2 precisiones menores.

## Precisión 1 — Umbral de conexión: considera incluir 3G
Tu condición `slow` solo excluye 2g/slow-2g. Dado que el modelo pesa **~113 MB**, incluso en **3G** la descarga es dolorosa. Amplía el skip para no precargar en 3G tampoco:
```js
const et = conn?.effectiveType || '';
const slow = conn && (et === 'slow-2g' || et === '2g' || et === '3g');
if (saveData || slow) return; // no precargar; queda la carga perezosa en handleSearch
```
(En 3G el usuario todavía puede buscar: `handleSearch` carga el modelo bajo demanda. Solo evitamos gastar 113 MB proactivamente en una conexión lenta.)

## Precisión 2 — Sin doble descarga si el usuario busca justo tras el hover
Confirma que si el usuario hace hover (warmup arranca, 113 MB descargando) y **enseguida** ejecuta una búsqueda antes de que termine, NO se dispare una segunda descarga. Debe funcionar solo si `PipelineSingleton.getInstance()` en `worker.js` cachea la **promesa en vuelo** (retorna la misma instancia/promesa mientras carga). Verifica que `getInstance` no reinstancie el pipeline en llamadas concurrentes (el patrón actual `if (this.instance === null)` lo cubre, pero confírmalo). El flag `hasWarmedUp` en el componente ayuda, pero la garantía real está en el singleton del worker.

## Verificación (E2E con evidencia real, no asumida)
En `npm run dev`, con la pestaña Network abierta:
1. **Sin intención:** carga el Home, no toques el buscador → **0** descargas a huggingface.
2. **Con intención:** hover/focus en el buscador → arranca la descarga del `.onnx` **cuantizado** (confirma que es `model_quantized.onnx`, no el fp32).
3. **Ahorro/lento:** DevTools → throttling "Slow 3G" (o fuerza `navigator.connection.saveData`) → hover NO precarga; pero ejecutar la búsqueda sí carga el modelo (fallback perezoso).
4. **Sin doble descarga:** hover e inmediatamente buscar → una sola descarga del modelo.
5. `npm run build` sin errores.

## Forma de respuesta
- Reporta con evidencia de Network para cada caso (sin intención = 0; con hover = arranca; con saveData/3G = no precarga).
- No toques el core WP ni el modelo/cuantización. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) re-ejecutará el E2E: que el modelo NO baje sin intención, que sí baje al enfocar/hover, que respete saveData/3G, y que no haya doble descarga.
