# MEJORA (P1) — Cerrar el punto ciego del self-guard: gate de sintaxis JS del panel antes de desplegar

## Contexto (incidente real que motiva esto)
El fix de contenedores de `view-ops` introdujo un bloque **copy-paste duplicado** en `loadOpsTelemetry()` de
`public/admin/index.php`:
```js
const sla = telemetry.sla_global || {};
const sys = telemetry.system_info || {};
const sla = telemetry.sla_global || {};   // ← duplicado
const sys = telemetry.system_info || {};
```
`const sla` dos veces en el mismo scope = **`SyntaxError: Identifier 'sla' has already been declared`** en tiempo de
parseo → **todo el `<script>` inline del panel no cargó** → `switchTab`/`setHTML`/todos los loaders indefinidos → el panel
de admin quedó **completamente inoperante en producción** (ninguna pestaña cambiaba).

**Lo grave:** el deploy mostró `🎉 [SELF-GUARD PASSED]` con **todos los endpoints ✅**. El auto-verify actual solo invoca
endpoints de `api.php` (backend PHP vía CLI) y **nunca ejerce el JavaScript del panel**. Un `SyntaxError` en el
`<script>` inline de `index.php` **no afecta** a los endpoints, así que pasó el guard estando el panel muerto. Verde
falso clásico: "endpoints OK" ≠ "panel funciona".

## Objetivo
Añadir un **gate de sintaxis JavaScript** que valide el/los `<script>` inline de `public/admin/index.php` (y de cualquier
`.php`/`.astro`/`.html` con JS embebido relevante) **antes** de construir/desplegar, de modo que un error de parseo
**falle el deploy** en vez de llegar "verde" a producción.

## Implementación pedida
1. **Nuevo helper `verify_inline_js_syntax()` en `scripts/deploy/deploy_ionos.py`**, invocado dentro del
   **PRE-DEPLOY GUARD** (junto a `check_and_fresh_build`, antes de subir archivos). Debe:
   - Extraer el contenido entre `<script>` y `</script>` de `public/admin/index.php` (y opcionalmente un allowlist de
     archivos con JS inline). Si hay varios bloques `<script>`, validar cada uno.
   - **Neutralizar el PHP embebido** (no es JS válido para el parser): reemplazar `<?= ... ?>` por un literal placeholder
     y eliminar bloques `<?php ... ?>`. Referencia (equivalente al que usé para diagnosticar en vivo):
     ```bash
     sed -E "s/<\?=[^?]*\?>/PHP_ECHO/g" \
       | awk 'BEGIN{skip=0} /<\?php/{skip=1} skip==0{print} /\?>/{if(skip==1) skip=0}'
     ```
   - Escribir el JS saneado a un temporal y correr **`node --check <tmp>`**. Si `node` retorna != 0 → **`fail()`** con el
     mensaje de error de node (línea/columna) y **abortar el deploy** (no subir nada).
   - Si `node` no está disponible, degradar con un warning claro (no bloquear), pero preferir que exista (ya se usa en el
     build).
   - Implementarlo en **Python puro** (subprocess a `node --check`), sin depender de bash, para que corra en Windows igual
     que el resto del script.
2. **Reflejar el gate en la tabla del POST-DEPLOY** o en el log del PRE-DEPLOY: una línea tipo
   `🧪 [JS SYNTAX GATE] public/admin/index.php → OK` / `→ FALLÓ (SyntaxError …)`.
3. **(Opcional, defensa temprana)** replicar el mismo check en el hook `pre-commit` para cazarlo antes incluso de construir.
   Si se hace, que sea el mismo helper/lógica para no duplicar criterio.

## Criterio de "hecho" (verificar de verdad)
- Introducir a propósito un `const x=1; const x=2;` en el `<script>` de `index.php` y correr el deploy en **dry-run** →
  el guard **debe fallar** con el `SyntaxError` y **no** llegar a subir archivos. Quitar el duplicado → pasa.
- Con el árbol actual (ya corregido), el gate imprime `OK` y el deploy procede normal.
- No falsos positivos por el PHP embebido (`<?= htmlspecialchars($csrf) ?>` y bloques `<?php … ?>` deben quedar
  neutralizados, no romper el parser).

## Notas
- **No** cambies el comportamiento existente de `check_and_fresh_build` ni del auto-verify de endpoints; esto **suma** una
  verificación, no reemplaza ninguna.
- Regla de campo a reforzar en `AGENTS.md §4` / skill `verificar-en-vivo`: **"endpoints ✅" ≠ "panel ✅"**; el JS del panel
  se valida con `node --check` en el guard y con **click-through en el navegador** (consola sin `SyntaxError`/`ReferenceError`,
  cada pestaña Y sub-pestaña con **datos**). Este incidente (panel entero caído pese a SELF-GUARD PASSED) es el caso de uso.

---
**Nota:** Claude (Opus 4.8) ya restauró producción (quitó el `const sla` duplicado + corrigió `specsData is not defined`
cableándolo a `ops_specs_status`), desplegó y verificó en vivo (0 errores de consola; view-ops completo: KPIs, Estado del
Sistema, Specs 24 filas, Tendencia, Alertas, Tokens). Este prompt es **solo** para institucionalizar el gate que habría
atrapado el `SyntaxError` antes de desplegar.
