# Confirmación — Plan de Correcciones Post-Auditoría: LUZ VERDE con 1 decisión 🔴 + 3 precisiones

Revisé el plan (`planes/Plan de Correcciones Post-Auditoría (Spec 013).md`). Ataca los 3 bugs P0 en el lugar correcto (slice del chatbot, IDs reales en `ContextChips` **y** `ConsultativeCTA`, píldora de reset) y cubre los gaps. **Procede** con esto:

## ✅ Buena noticia — NO toques el backend del formulario (Gap 4)
Verifiqué `public/api/save_wizard.php`: **ya acepta y sanea** exactamente los campos que necesitas, con `strip_tags` + límites de longitud, escribiendo a `secure_leads/leads_wizard.csv` (fuera de `public/`):
```
email (req) · nombre · organizacion · telefono · reto (500) · stack (500) · score · session_id
```
Mapea los campos nuevos a **esos nombres exactos**:
- "Empresa / Entidad" → **`organizacion`**
- "Reto principal" → **`reto`**
- "Sistemas actuales / situación de datos" → **`stack`**

**No modifiques `save_wizard.php`, no inventes campos nuevos ni endpoints.** Solo amplía el `body` del `fetch` en `saveLead` (hoy manda `session_id/email/telefono/organizacion`). El pipeline seguro ya está probado.

## 🔴 Decisión requerida — la ampliación de `pillarsOfInterest` degrada el highlight
`pillarsOfInterest` se usa para **dos cosas a la vez**: (a) la cobertura del bloque de objeciones en las páginas de solución, y (b) el **mapa de resaltado** de los chips de contexto (`ContextChips` publica `semanticHighlight` desde ese array).

Tu plan añade `hiperautomatizacion` + `ai-data-science` al CFO → pasaría de **2 a 4 de 6 pilares**. Si al elegir "CFO" se resaltan 4 de 6 servicios, **el resaltado deja de discriminar** (casi todo iluminado = nada destacado). Arreglarías el bug 2 y romperías su utilidad.

**Resuelve así (elige y déjalo explícito):**
- **Opción A (recomendada):** mantén las aristas honestas que propones (CFO↔hiperautomatización y CFO↔ai-data-science son legítimas por ROI/eficiencia), **pero haz que el highlight discrimine**: que `ContextChips` resalte solo los **top 2** pilares (usa el orden del array como prioridad — el más relevante primero). Así (a) y (b) conviven.
- **Opción B:** sé conservador con las aristas (solo las imprescindibles) y acepta bloques de objeciones más finos.

**Honestidad (recordatorio):** añade una arista **solo si el rol realmente tiene ese interés** — no infles el grafo para llenar UI. CFO↔hiperautomatización (costos/ROI) y CIO↔hiperautomatización (integración) son defendibles; revisa CDO↔hiperautomatización antes de añadirla.

## Precisión 1 — El nuevo `problem` de público necesita `solution`
Al añadir "Calidad de padrones ciudadanos" a `publico.yaml`, dale **`code`, `label` Y `solution`**. El chatbot usa `getSolutionMessage()` → `problem.solution`; si falta, cae al mensaje genérico y el intent queda hueco.

## Precisión 2 — Blogs de CFO/CEO: máximo riesgo de honestidad
Son los más tentadores para inventar cifras de ROI o casos. **Cero clientes/casos/testimonios inventados**; toda métrica estimada va con **`[EST]`** y presentada como modelo. Si los escribes a mano por fallo del LLM, aplica el mismo estándar. `pubDate` **sin comillas** y como fecha válida (`z.date()`), `draft: false`.

## Precisión 3 — El reset de contexto debe limpiar TODO
Al pulsar la ✕ de "Viendo como: …", limpia **`userContext` y `semanticHighlight`** (tu plan ya lo dice — confírmalo), de modo que la grilla vuelva a su estado neutro y los chips reaparezcan.

---

## Verificación (evidencia real)
1. **Chatbot:** los **10 sectores** como botón (Sector Público incluido) con scroll; Network del flujo guiado = **cero `chat.php`** (la llamada a `save_wizard.php` al capturar lead **sí es esperada y correcta**).
2. **Highlight (el bug silencioso):** elegir "Finanzas / CFO" **resalta de verdad** los servicios del CFO y **atenúa el resto** — pega evidencia de que ahora sí discrimina (indica cuántos pilares se resaltan).
3. **Reset:** cambiar/limpiar contexto funciona sin recargar; grilla vuelve a neutro.
4. **Formulario:** captura `organizacion`/`reto`/`stack` + el texto de "qué recibes"; llega a `secure_leads/leads_wizard.csv`; **`save_wizard.php` sin modificar**.
5. **Taxonomía:** `build-taxonomy.mjs` verde tras tocar `personas.json`/`publico.yaml`; objeciones más completas en hiperautomatización/ai-data-science.
6. Posts CFO/CEO publicados y honestos; `npm run build` verde; consola limpia.
7. Sección final "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: los 10 sectores + 0-LLM en Network, que el **highlight realmente funcione Y discrimine**, el reset de contexto, que el formulario use los campos existentes sin tocar el backend, y el build verde.
