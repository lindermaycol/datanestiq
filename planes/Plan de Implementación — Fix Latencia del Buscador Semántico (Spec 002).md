# Plan de Implementación — Fix Latencia del Buscador Semántico (Spec 002)

## Contexto y Diagnóstico

En `public/worker.js`, el event listener de búsqueda ejecutaba la inferencia de vectorización sobre la consulta **Y sobre todos los ~40 textos del corpus** (`extractor(corpusTexts, ...)`) en **CADA** búsqueda.
Aunque el modelo WASM de transformers.js se cachea en IndexedDB, re-calcular los embeddings de todo el corpus en cada intento provocaba latencias excesivas (15s a >50s por consulta). Además, `SemanticSearch.jsx` reenviaba todo el corpus por `postMessage` en cada petición.

---

## User Review Required

> [!IMPORTANT]
> **⚠️ ÍTEMS RE-VERIFICADOS — PROHIBIDO TOCAR (Falsos Positivos QA):**
> 1. **HeroRoleLine:** Funciona 100% en caliente (renderiza propuesta de valor para CDO, CFO, CIO, CEO). **No se modifica.**
> 2. **ConsultativeCTA:** Funciona 100% con variantes por rol. **No se modifica.**
> 3. **CopilotDemo:** Se adapta a las búsquedas semánticas (`semanticHighlight`), no al chip de rol. **Comportamiento correcto de diseño, no modificar.**
> 4. **Servidor PHP single-thread (`php -S`):** Causa bloqueos en dev si se acumulan llamadas a `chat.php`. El modelo WASM Edge AI corre en el navegador (CDN de jsdelivr) y no afecta a PHP.

---

## Proposed Changes

### Componente Worker — `public/worker.js`

#### [MODIFY] [worker.js](file:///C:/xampp/htdocs/datanestiq/public/worker.js)

1. **Caché a Nivel de Módulo:**
   - Crear la variable `cachedCorpusData` (`{ embeddingsData, textCount, dim, hash }`).
   - Implementar `indexCorpus(extractor, corpusTexts)`: calcula la firma/hash del corpus y solo vectoriza si la firma cambia o no existe caché.
2. **Separación de Mensajes:**
   - `{ type: 'warmup' }`: Inicia el modelo WASM en fondo.
   - `{ type: 'index', corpusTexts }`: Fuerza la vectorización e indexación del corpus una sola vez; responde `{ status: 'indexed' }`.
   - `{ type: 'search', query, id, corpusTexts }`:
     - Embebe **únicamente la consulta** (1 texto en ~30ms).
     - Si el corpus no está cacheado en el worker, lo indexa primero.
     - Calcula el producto punto (similitud coseno) sobre `cachedCorpusData` en memoria.
     - Devuelve `{ status: 'complete', id, results }` ordenado.

---

### Isla React — `SemanticSearch.jsx`

#### [MODIFY] [SemanticSearch.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/SemanticSearch.jsx)

1. **Indexación Temprana:**
   - Al hacer `warmup` o montar el componente, enviar `{ type: 'index', corpusTexts: corpusRef.current.map(c => c.text) }` al worker.
2. **Búsquedas Ligeras:**
   - En `handleSearch()`, enviar solo `{ type: 'search', query, id: Date.now() }` (evitando transferir el arreglo masivo `corpusTexts` por `postMessage`).
3. **Defensa de Estado (Listener Guard):**
   - Prevenir que un evento `progress` tardío de carga pise el estado `'done'` o `'searching'`:
     `setStatus(prev => (prev === 'searching' || prev === 'done') ? prev : 'loading_model')`

---

## Verification Plan

### Automated Verification
1. **Build de Producción:** Ejecutar `npm run build` para garantizar 0 errores de compilación o empaquetado.

### Manual Verification (Performance & Latencia)
1. **Medición de Latencia (2ª+ Búsqueda):**
   - Limpiar `localStorage` y recargar.
   - Ejecutar 1ª búsqueda: carga modelo e indexa corpus.
   - Ejecutar 2ª búsqueda (ej: "seguridad"): Medir con `console.time` / `performance.now()`. **Meta: < 1 segundo.**
2. **Funcionalidad del Embudo:**
   - Verificar que al terminar la búsqueda (`status === 'done'`) aparezca el bloque *"Encontramos N soluciones..."* con sus 3 botones (Copiloto, Diagnóstico, Concierge).
   - Verificar que en *"Descubre el Impacto en tu Sector"* los sectores relevantes queden **resaltados** y el input pre-llenado con `userChallenge`.
3. **Red (Network Tab):**
   - Confirmar que **no hay peticiones a `chat.php`** durante las búsquedas semánticas.
