# Deuda Técnica: Spec 005 (OpenWiki Privada)

## Estado
- **Fase actual:** ✅ Motor real operativo con balanceo **Groq ⇄ DashScope ⇄ Gemini** (Google). Probado en vivo end-to-end con `gemini-2.5-flash`: generación fundamentada, IGNORE byte-idéntico, doble-frontmatter corregido, `lastUpdated` sellado a fecha de sync, build 22 páginas OK. Pendiente: secrets de GitHub.
- **Impacto:** Bajo
- **Severidad:** Baja (residual #5 mitigado empíricamente; ver nota).

## Lista de Deuda Técnica (Technical Debt)

### 1. Secretos de API en GitHub Actions
- **Estado:** 🟡 Pendiente (acción del usuario)
- **Descripción:** El motor `docs-generator.mjs` reemplazó al CLI ficticio y balancea OpenAI-compatible entre Groq, DashScope y Gemini con failover secuencial.
- **Acción (Backlog):** Agregar `GROQ_API_KEY`, `DASHSCOPE_API_KEY` y `GEMINI_API_KEY` a los secretos del repositorio de GitHub para habilitar la ejecución programada diaria. Basta con configurar al menos uno; el motor usa solo los proveedores con clave y peso > 0.

### 2. Grounding del LLM
- **Estado:** ✅ Resuelto
- **Descripción:** El modelo ya no alucina la documentación. El script lee el contenido real del archivo modificado (o el diff) y se lo pasa en el prompt al modelo.

### 3. Mapeo estable (Canonical Docs)
- **Estado:** ✅ Resuelto
- **Descripción:** El script ya no genera sufijos `-update.md` aleatorios ni colisiona. Convierte la ruta de origen en un *slug* determinista y actualiza siempre el mismo archivo, en lugar de crear uno nuevo.

### 4. Respeto a Secciones IGNORE
- **Estado:** ✅ Resuelto (mecanismo cableado y verificado)
- **Descripción:** El script ahora extrae los bloques `<!-- OPENWIKI:IGNORE:START -->...<!-- OPENWIKI:IGNORE:END -->` del documento destino, los sustituye temporalmente con placeholders, y luego los restaura de manera *byte-idéntica* tras la generación del LLM. Verificado en aislamiento: round-trip extract→restore = byte-idéntico.

### 5. Robustez del placeholder IGNORE (silent drop) - [🟡 ABIERTO / hardening]
- **Descripción:** La restauración byte-idéntica **solo se cumple si el LLM devuelve el placeholder `%%IGNORE_BLOCK_x%%` intacto**. En las pruebas en vivo `gemini-2.5-flash` lo preservó consistentemente (IGNORE byte-idéntico), pero el riesgo teórico persiste con otros modelos: si uno lo omite, `restoreIgnoreBlocks` no reinserta nada y la sección inmutable se perdería en silencio.
- **Riesgo:** Bajo en la práctica con Gemini; residual con otros modelos.
- **Acción sugerida (hardening):** Tras la generación, verificar que todos los placeholders esperados estén presentes; si falta alguno, re-anexar el bloque original (o abortar el doc con warning) en lugar de escribir la versión mutilada.

### 6. Doble frontmatter - [✅ RESUELTO]
- **Descripción:** Se reprodujo en vivo: `gemini-2.5-flash` copió el frontmatter del doc destino dentro de su `content`, generando dos bloques `---`.
- **Resolución:** Se añadió `stripFrontmatter()` aplicado en ambos lados: (a) el cuerpo del doc destino se envía al modelo **sin** frontmatter, y (b) se elimina cualquier frontmatter inicial del `content` devuelto antes de ensamblar. Verificado: tras el fix, el doc queda con un único frontmatter.

### 7. `lastUpdated` alucinado - [✅ RESUELTO]
- **Descripción:** Se observó que Gemini devolvía fechas pasadas con formato válido (ej. `2024-07-29`), que la guarda Zod aceptaba por cumplir el formato.
- **Resolución:** `validateAndFixFrontmatter` ahora **siempre** sella `lastUpdated` con la fecha real de sincronización, ignorando la del modelo.
