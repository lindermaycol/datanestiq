# Prompt para Antigravity — 🔴 Fix regresión: ReferenceError (TDZ) en ContextChips

El selector de pivote de rol que añadiste **lanza excepción al hacer clic**:
```
ReferenceError: Cannot access 'handleSelect' before initialization  (ContextChips onClick)
```
El pivote de rol **no funciona** (el clic tira error).

## Causa (temporal dead zone)
En `src/components/ui/ContextChips.jsx`, el bloque del selector en línea (`if (context.rol || context.sector) { return ( … ) }`, ~líneas 13–48) usa `handleSelect(...)` en sus `onClick`, **pero `handleSelect` se declara con `const` DESPUÉS** (~línea 50). Como ese `if` hace `return` antes de llegar a la declaración, `handleSelect` queda en la **temporal dead zone** → no está inicializado cuando se dispara el clic. (Igual `handleDismiss`, línea ~65, usado por el otro bloque de return.)

## Fix (mínimo)
**Mueve las declaraciones `const handleSelect = …` y `const handleDismiss = …` ARRIBA**, justo después de `const context = useStore(userContext);` (antes del primer `if (context.dismissed)`), de modo que ambas funciones estén inicializadas antes de **cualquier** ruta de `return`. No cambies su lógica.

> Nota: `handleSelect` ya hace lo correcto en 1 clic (setea `userContext` + `semanticHighlight` top-2). Solo hay que resolver el orden de declaración.

## Guardarraíles
- No cambies la lógica de `handleSelect`/`handleDismiss` ni el resto del componente.
- `npm run build` verde; **cero errores de consola** al pivotar.

## Verificación (evidencia real)
1. Con contexto activo (ej. CFO), hacer clic en el selector **"Público (CDO)"** → **sin error de consola**, el contexto pasa a CDO **en 1 clic**, el CTA cambia y el highlight resalta los pilares del CDO (top-2). Repite pivoteando entre los 3 roles.
2. El botón de reset (✕) sigue funcionando.
3. Consola limpia; `npm run build` verde.

---
**Nota:** Claude (Opus 4.8) reauditará en navegador que el pivote entre los 3 roles funcione **sin excepción** y actualice CTA + highlight en 1 clic. También reverificará la discretización de la búsqueda semántica (Gap 1) que quedó pendiente de prueba en runtime.
