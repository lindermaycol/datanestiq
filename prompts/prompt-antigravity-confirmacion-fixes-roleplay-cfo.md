# Confirmación — Fixes de conversión CFO: LUZ VERDE con 2 precisiones → ejecutar

El plan (`planes/Plan de Implementación Fixes de Conversión para CFO.md`) ataca bien los 3 fixes en el lugar correcto (CTA/landing → calculadora, `formatText` con listas + XSS, `SYSTEM_PROMPT` valor-antes-de-contacto). **Procede** con estas 2 precisiones:

## 🔴 Precisión 1 — Que las listas del chatbot rendericen con MARCADOR VISIBLE (no `<li>` huérfano)
Ojo con el enfoque de "envolver en `<li>`": `formatText` procesa **línea por línea** (`text.split('\n').map(...)`, cada línea → un `<span dangerouslySetInnerHTML>` + `<br>`). Un `<li>` **suelto, fuera de un `<ul>/<ol>`**, no muestra viñeta ni numeración (y `list-outside` sin lista padre no hace nada) → seguirías viendo las opciones "planas".

**Haz que la lista se vea de verdad**, con una de estas:
- **(recomendada)** Detecta bloques de líneas de lista consecutivas y **agrúpalas en un `<ul>`/`<ol>` real** con sus `<li>`; o
- Renderiza cada ítem como **línea con marcador explícito e indentación** (ej. `<span class="ml-4">• {texto}</span>` para viñetas, o `<span class="ml-4">{n}. {texto}</span>` para numeradas), sin depender de `list-style`.

**Verifícalo visualmente:** una respuesta con 3 opciones debe verse como una lista clara (con viñetas/números e indentación), no como texto corrido. Mantén el escape de `<`/`>` (anti-XSS) **antes** de inyectar el formato.

## 🔴 Precisión 2 — Tras editar `SYSTEM_PROMPT`, corre `php -l` (ya nos rompió una vez)
El `SYSTEM_PROMPT` es una **cadena PHP entre comillas dobles**. Un `"` sin escapar dentro rompió `chat.php` por completo en el pasado (parse error → el chatbot devolvía HTML de error en vez de JSON). Tu nuevo texto de la regla 2 no tiene comillas dobles internas — **mantenlo así** (o escápalas con `\"`). **Obligatorio:** después de editar, ejecuta `C:/xampp/php/php.exe -l public/api/chat.php` y confirma *"No syntax errors detected"* antes de dar por hecho el fix.

## Precisión 3 (menor) — Coherencia CTA ↔ destino
Cuando el CTA del CFO pase a ser `<a href="/business-case">`, verifica que el **texto** siga teniendo sentido con el nuevo destino (la calculadora). "Auditoría de ROy y pérdidas evitables" → llevar a la calculadora es coherente; solo confirma que no quede un texto que prometa "agendar" y lleve a un cálculo. Aplica el cambio en las dos instancias de `<ConsultativeCTA>` (nav + hero).

## Guardarraíles (sin cambios)
- **0-LLM** del flujo guiado intacto (el cambio de system prompt solo afecta el modo texto libre; el lead-card del flujo guiado sigue igual). PII redactada; sin endpoints nuevos.
- No reintroduzcas prueba social; testimonio sigue retirado. No inventes datos de la empresa.
- `npm run build` verde; consola limpia.

## Verificación (evidencia real)
1. `/sectores/finanzas` y `/sectores/seguros`: enlace visible a `/business-case`; con contexto CFO el CTA lleva a `/business-case` (no abre chatbot). `grep`/captura en `dist/`.
2. Chatbot: respuesta con lista numerada/viñetas se ve **como lista con marcadores**, no en crudo. Captura en navegador.
3. Chatbot texto libre: una pregunta concreta recibe **respuesta primero**; pide contacto después. Ejemplo de conversación.
4. **`php -l public/api/chat.php` sin errores**; `npm run build` verde; islas/consola limpias.
5. Sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador (servido por PHP): CTA/landing financiero → calculadora, listas del chatbot con marcadores visibles, secuencia valor-antes-de-contacto, y `chat.php` parseando OK.
