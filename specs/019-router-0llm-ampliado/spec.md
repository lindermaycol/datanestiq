# Spec 019 — Router Determinístico Ampliado del Chatbot (más rutas 0-LLM)

**Estado:** 🟠 Diseñada · Pendiente auditoría Claude  
**Fecha:** 2026-08-05  
**Rama:** `007-multi-pagina`  
**Dependencias:** Spec 002 (Chatbot), Spec 006 (Islas Astro), Spec 015 (AppointmentPicker), Spec 016 (chat_metrics), Spec 018 (interaction_events)

---

## WHY — Por qué existe esta spec

El chatbot actual tiene **exactamente 2 rutas de decisión**:

1. **Flujo guiado por botones** (sector → rol → problema) — 100% 0-LLM. Determinista.  
2. **Texto libre** → llama a `chat.php` → LLM (Groq → DashScope → Gemini failover) — **100% LLM**, siempre.

**El problema:** Toda consulta de texto libre gasta tokens LLM, aunque sea:
- Una objeción clásica ya resuelta en la taxonomía (`objectionResponses`).
- Una solicitud de agendar cita.
- Una consulta que el flujo guiado resuelve exactamente.

**Resultado actual:** Latencia innecesaria (300-2000 ms de LLM), costo de tokens, y respuestas potencialmente inconsistentes con los mensajes de la taxonomía cuando el LLM parafrasea.

**El objetivo de esta spec:** Clasificar la intención del texto libre **100% en cliente** (Xenova ya cargado, 0 tokens LLM), y resolver `faq`, `cita`, y `guiado` sin tocar `chat.php`. Solo lo genuinamente complejo llega al LLM.

---

## WHAT — Alcance de diseño

### Componente 1 — Clasificador de Intención en Cliente

**Reusa el worker Xenova existente** (`public/worker.js`, modelo `Xenova/paraphrase-multilingual-MiniLM-L12-v2`, tarea `feature-extraction`). No hay modelo nuevo ni pipeline adicional.

**Proceso:**
1. Al recibir texto libre en `handleUserSubmit`, antes de llamar a `chat.php`:
2. Embebe la consulta del usuario vía el worker existente (`type: 'search'`).
3. Compara contra **prototipos de intención** precalculados: embeddings promediados de frases-ejemplo curadas por intención (definidas en `src/data/intents.json`).
4. Calcula similitud coseno → intención ganadora + confianza.
5. Si confianza < `CONFIDENCE_THRESHOLD` (parámetro configurable, valor inicial 0.72) → fallback directo a LLM, sin forzar intención.

**Intenciones definidas:**
| ID | Descripción | Acción |
|----|-------------|--------|
| `faq` | Pregunta/objeción ya resuelta en la taxonomía | Responde con `objectionResponse` real más cercana |
| `cita` | Solicita agendar, reunión, llamada, demo | Abre `<AppointmentPicker>` (Spec 015) |
| `guiado` | Quiere recomendación de servicio, diagnóstico, saber qué aplica | Retoma flujo guiado o activa `semanticHighlight` |
| `complejo` | Consulta técnica, arquitectura, caso custom | Llama a `chat.php` (LLM) |
| `unknown` | Confianza baja → umbral no alcanzado | Llama a `chat.php` (LLM) — **la duda siempre cae al LLM** |

### Componente 2 — Ruteo por Intención

#### Ruta `faq` (0-LLM)
1. Embebe la consulta (paso anterior, embedding ya disponible).
2. Búsqueda coseno en el corpus de `objectionResponses` de `personasCorpus.json` (ídem como lo hace el worker hoy con el corpus del buscador).
3. Muestra la **respuesta textual real** de la `objectionResponse` más cercana con similitud ≥ `FAQ_MATCH_THRESHOLD` (valor inicial 0.65).
4. Si similitud < `FAQ_MATCH_THRESHOLD` → aunque la intención sea `faq`, se hace fallback a LLM (conservador).
5. **Siempre** añade el escape: *"¿No era exactamente lo que buscabas? Escríbeme más detalles →"* (botón que vuelve al flujo LLM).

#### Ruta `cita` (0-LLM)
1. Muestra mensaje: *"¡Perfecto! Te muestro la disponibilidad de nuestros arquitectos de datos."*
2. Activa `setShowScheduler(true)` — el `<AppointmentPicker>` (Spec 015) ya está integrado en el Chatbot.

#### Ruta `guiado` (0-LLM)
1. Si el usuario no tiene sector/rol definido: reinicia el flujo guiado desde el paso `sector`.
2. Si tiene contexto parcial: continúa desde donde estaba.
3. Muestra mensaje: *"Déjame guiarte por los servicios que aplican a tu situación."*

#### Ruta `complejo` / `unknown` (LLM)
Exactamente igual al flujo actual: llama a `chat.php`. **Sin cambios en el failover.**

### Componente 3 — Instrumentación de Decisiones de Ruteo

Cada decisión del router se registra vía `trackEvent` (Spec 018):
```
event_type:   'intent_routing'
event_target: 'chatbot-router'
event_value:  JSON.stringify({ intent, confidence, resolved: '0llm' | 'llm' })
```

También se registra en `chat_metrics` (Spec 016) cuando la ruta es LLM, igual que hoy.

---

## CONSTRAINTS — Restricciones no negociables

### §5 — 0-LLM como objetivo
- La clasificación de intención es **100% cliente** (Xenova, 0 tokens LLM).
- Las rutas `faq`, `cita`, `guiado` son 0-LLM.
- Solo `complejo` y baja confianza llaman a `chat.php`.
- Esta spec **aumenta** las rutas 0-LLM; no las reduce.

### §2 — Honestidad radical
- Las respuestas `faq` salen de `objectionResponses` de la **taxonomía real**. Jamás fabricadas.
- Umbral de confianza alto (0.72 por defecto). Ante duda → **LLM**, nunca FAQ forzada.
- Una respuesta enlatada errónea es peor que la del LLM.
- **Doble umbral**: primero de intención (≥ 0.72 para `faq`), luego de coincidencia FAQ (≥ 0.65). Ambos deben cumplirse.

### §UX — El usuario nunca queda atrapado
- Toda ruta 0-LLM incluye un **escape explícito** a texto libre/LLM.
- El usuario puede siempre escribir libremente; el router no bloquea.

### §REUSO — Sin duplicar infraestructura
- Mismo worker Xenova (`public/worker.js`) — sin modelo nuevo.
- Mismo `<AppointmentPicker>` (Spec 015) — ya integrado en Chatbot.
- `objectionResponses` de `personasCorpus.json` — fuente de verdad existente.
- Free-tier. Claude solo audita.

### §CONSERVAR — No romper lo existente
- El flujo guiado por botones (sector → rol → problema) sigue **0-LLM intacto**.
- El failover `chat.php` (Groq → DashScope → Gemini) no se toca.
- El `chatState` y la State Machine (FR-015) se preservan.
- El telemetry de Spec 018 (`trackEvent`) sigue funcionando igual.

---

## OUT-OF-SCOPE — Lo que esta spec NO hace

- **NO** usar un LLM para clasificar (la clasificación es cliente/Xenova, nunca LLM).
- **NO** fabricar respuestas FAQ inventadas.
- **NO** reemplazar el flujo guiado por botones; lo complementa.
- **NO** tocar el failover Groq→DashScope→Gemini de `chat.php`.
- **NO** implementar GraphRAG, MCP endpoint, o buscador sobre blog.
- **NO** añadir un nuevo modelo Xenova; reusa el existente.
- **NO** hacer clasificación en servidor; todo en cliente.

---

## Consideraciones de implementación futura

- Los prototipos de intención (`intents.json`) se cargarán al montar el Chatbot y se pasarán al worker como corpus estático.
- El embedding del query ocurre igualmente en texto libre; el costo Xenova es ~15-40 ms adicionales por mensaje (ya asumido).
- La cache de embeddings del worker evita re-calcular el corpus de intenciones en cada mensaje.
- El patrón de llamada al worker usa `postMessage` / `onmessage` ya conocido del `SemanticSearch.jsx`.
