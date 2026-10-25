# Confirmación — Fix latencia buscador: LUZ VERDE con 2 precisiones → ejecutar

El plan (`planes/Plan de Implementación — Fix Latencia del Buscador Semántico (Spec 002).md`) es correcto: cachea los embeddings del corpus en el worker, separa mensajes (warmup/index/search) y agrega el guard defensivo de estado. Respeta la lista de falsos positivos (HeroRoleLine, ConsultativeCTA, Copiloto, Sección 0). Apruebo con 2 precisiones.

## 🔴 Precisión 1 (condición de carrera) — el mensaje `search` DEBE seguir llevando `corpusTexts` como fallback
El plan se contradice: la firma del mensaje incluye `corpusTexts` (línea 33) pero luego pide enviar solo `{query, id}` (línea 48). **Resuélvelo a favor de mantener `corpusTexts` en el `search`.**
- **Por qué:** si el usuario busca **antes** de que termine el `index` (o si el worker se reinició), el handler de `search` no tendría con qué indexar → error/resultados vacíos. Con `corpusTexts` presente, el worker indexa **solo en cache-miss** y sigue.
- **El costo real NO era transferir `corpusTexts` por `postMessage`** (un array de ~40 strings se serializa en <1ms); el costo era **re-EMBEBERLO**. Así que la "optimización" de no enviarlo aporta ~nada y agrega riesgo. Mantén el envío; el worker **embebe únicamente en cache-miss** (firma/`textCount`), y en cache-hit ignora `corpusTexts` y usa el caché.

## 🔴 Precisión 2 (regresión de rendimiento) — indexar por INTENCIÓN del usuario, NO en el mount
El plan dice "al hacer warmup **o montar el componente**, enviar `index`" (línea 46). **Quita el "o montar".**
- **Por qué:** indexar requiere cargar el modelo WASM (~grande). Dispararlo en el `mount` haría que **todo visitante descargue el modelo** aunque nunca use el buscador → regresa el lazy-load actual (hoy el warmup está gated por `handleIntent`: hover/focus, y salta en conexiones lentas/save-data).
- **Haz esto:** dispara `index` desde `handleIntent` (misma puerta que el warmup actual). Idealmente **fusiona warmup+index**: en la intención, envía `{type:'index', corpusTexts}`; el handler de `index` en el worker hace `getInstance()` (carga modelo) **y** embebe+cachea el corpus, y responde `ready`/`indexed`. Respeta el skip en conexiones limitadas.
- Mantén el UI de carga existente (skeleton + *"Cargando el modelo de IA (solo la primera vez)…"*) cubriendo también la fase de indexado.

## OK tal como está
- Guard defensivo `setStatus(prev => (prev==='searching' || prev==='done') ? prev : 'loading_model')` ✅.
- Caché a nivel de módulo con firma + coseno sobre el caché ✅.
- No tocar HeroRoleLine / ConsultativeCTA / CopilotDemo / Sección 0 ✅.

## Verificación (evidencia real)
1. **Latencia 2ª búsqueda < 1s** medida con `performance.now()` (localStorage.clear + esperar hidratación; 1ª búsqueda carga+indexa, 2ª solo embebe la query).
2. **Sin regresión de lazy-load:** cargar el home **sin** interactuar con el buscador → el modelo WASM **no** se descarga (revisa Network: sin fetch al CDN de transformers hasta hover/focus del buscador).
3. **Race:** buscar inmediatamente al enfocar (antes de que termine el index) → responde correctamente (no error, no vacío).
4. **Embudo post-búsqueda** (ahora observable con búsqueda rápida): bloque "Encontramos N soluciones" + 3 botones; sectores resaltados; input pre-llenado con `userChallenge`.
5. **0-LLM:** cero `chat.php` en el flujo del buscador.
6. `npm run build` verde; consola limpia; `ESTADO-SPECS.md` (Spec 002) actualizado.

---
**Nota:** Claude (Opus 4.8) reauditará en navegador: latencia <1s medida, **que el modelo no se descargue sin intención** (Precisión 2), robustez ante búsqueda pre-index (Precisión 1), y el embudo post-búsqueda completo.
