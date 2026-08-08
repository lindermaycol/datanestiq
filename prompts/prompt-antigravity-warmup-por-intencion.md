# Prompt para Antigravity: Refinar la precarga del modelo semántico (por intención + respetar ahorro de datos)

Actúa como **ingeniero de performance frontend**.

## Contexto (auditado por Claude / Sonnet 5)
El buscador semántico ahora usa `Xenova/paraphrase-multilingual-MiniLM-L12-v2`, cuyo `model_quantized.onnx` pesa **~113 MB** (verificado en Hugging Face). El warmup actual se dispara en `requestIdleCallback` al montar `SemanticSearch.jsx` → **descarga 113 MB en background para TODO visitante** cuyo buscador monte (casi todos, porque está arriba en el Home), incluso quienes nunca usan la búsqueda. Eso es mucho ancho de banda, especialmente en móvil/conexiones lentas.

## Objetivo
Mantener la sensación de "búsqueda instantánea" (precarga temprana) **sin** gastar 113 MB en quien no va a buscar ni en conexiones limitadas.

## Cambios en `src/components/islands/SemanticSearch.jsx`

1. **Warmup por intención, no idle-para-todos.** Reemplaza el disparo en `requestIdleCallback` por una precarga que arranque ante la **primera señal de intención** del usuario sobre el buscador, la que ocurra primero:
   - `focus` o `click` en el input de búsqueda, **o**
   - `mouseenter`/`pointerenter` sobre el contenedor del buscador (hover = intención inminente).
   Al dispararse cualquiera, envía `postMessage({ type: 'warmup' })` una sola vez (guarda un flag `warmedUp` para no repetir). Así, cuando el usuario termine de escribir su consulta, el modelo ya está bajando o listo → sigue sintiéndose instantáneo, pero solo paga el costo quien muestra interés real.

2. **Respetar ahorro de datos / conexión lenta.** Antes de precargar, consulta la Network Information API:
   ```js
   const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
   const saveData = conn?.saveData === true;
   const slow = conn && /(^|-)2g$/.test(conn.effectiveType || '');
   ```
   Si `saveData` o `slow`, **no precargues**; deja que el modelo se cargue perezosamente solo si el usuario efectivamente ejecuta una búsqueda (comportamiento actual de `handleSearch`). Esto evita quemar el plan de datos de un usuario en móvil.

3. Mantén el indicador de estado ("Cargando modelo… %" durante la carga y "IA lista" cuando termina) para que el usuario tenga feedback. El `handleSearch` sigue funcionando aunque no haya habido warmup (carga perezosa como fallback).

4. **No cambies** el modelo ni la cuantización (`quantized: true, dtype: 'q8'` en `worker.js` se quedan).

## Verificación (E2E)
En `npm run dev`:
1. Carga el Home y **no interactúes** con el buscador: confirma (Network / DevTools) que **NO** se descarga el modelo (0 tráfico a huggingface) mientras no haya intención.
2. Haz **focus/hover** en el buscador: confirma que ahí **empieza** la descarga del `.onnx` cuantizado en background.
3. Simula ahorro de datos (DevTools → Network → throttling "Slow 3G", o fuerza `navigator.connection.saveData`): confirma que NO precarga en idle/hover y que la búsqueda aún funciona (carga perezosa).
4. `npm run build` sin errores.

## Forma de respuesta
- Reporta con evidencia de Network: sin intención = 0 descarga; con focus/hover = arranca; con saveData = no precarga.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el modelo NO se descargue sin intención del usuario y que sí precargue al enfocar/hover, respetando `saveData`.
