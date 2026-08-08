# ✅ CONFIRMADO EN VIVO — Spec 024 (Buscador semántico del blog) FUNCIONA en producción

Reauditoría en vivo en `https://app.datanestiq.com/blog` (skill `verificar-en-vivo`, instrumentando el `Worker` real y
`fetch`). El fix del contrato con `worker.js` quedó **bien aplicado y desplegado**. El buscador que antes no devolvía nada
ahora lista artículos reales.

## Evidencia en vivo (no el reporte — el runtime real)
- **Contrato correcto:** cada mensaje al worker lleva `corpusTexts` (16 strings). Capturado: `{type:'index', n:16}` y
  `{type:'search', n:16, id:'blog-search-N-<ts>'}`. Ya **no** se envía el `corpus:[{id,text}]` inventado.
- **Resultados reales:** consultas en lenguaje natural devuelven artículos afines con % de match (p. ej. "ciberseguridad y
  protección de datos" → *Gobierno del Dato en el Sector Público*; "automatización con IA generativa" → *Ventaja
  competitiva desde los datos* 49%). Máx. 5, con excerpt y tags.
- **Estado vacío honesto §2:** "recetas de cocina italiana" → *"No encontramos artículos relevantes… consulta con nuestro
  AI Concierge."* ✅
- **0-LLM confirmado:** **0** llamadas a `chat.php` en múltiples búsquedas (`__chat: []`).
- **Concurrencia:** ids únicos por petición (`blog-search-1`, `blog-search-2`…) y guard que descarta respuestas de id
  viejo. ✅
- **Worker desplegado = correcto:** `GET /worker.js` (cache-buster) 200, contiene el handler `type:'index'` y el fallback
  de indexado on-demand. Con warm-up (focus→índice→búsqueda): `complete` y **0 errores** de worker.

## 🟡 Observación menor (NO bloqueante) — pulir para que el reporte sea 100% exacto
El reporte afirmó *"la consola queda limpia sin excepciones"*. Casi: hay **una** condición de carrera de **primer
tecleo** — si el usuario escribe en el instante del primer render (modelo aún descargando, índice en vuelo), la primera
búsqueda puede emitir **una vez** `BlogSearch Worker error: Corpus not indexed yet…`. **Los resultados igual aparecen**
(la búsqueda siguiente ya está indexada), así que es cosmético. Con warm-up NO ocurre (verificado: 0 errores).
- **Fix sugerido (opcional):** encolar la primera búsqueda hasta recibir `status:'indexed'` (o `'complete'` del índice), o
  no emitir el `error` transitorio cuando aún se está indexando (tratar "índice en curso" distinto de "sin corpusTexts").
  Prioridad baja — no rehacer nada, solo endurecer el arranque.

## Veredicto
**Spec 024 LIVE y funcional.** El bug de contrato (`corpus` vs `corpusTexts`) que reporté quedó resuelto. Solo queda la
mejora cosmética del arranque (opcional).

---
**Nota:** Claude (Opus 4.8) verificó en el runtime real (Worker + fetch instrumentados), no en el reporte. El auto-verify
del deploy sigue sin cubrir islas cliente → este tipo de feature siempre exige QA de navegador; esta vez pasó.
