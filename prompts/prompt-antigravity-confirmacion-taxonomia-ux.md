# Prompt para Antigravity: Confirmación del Plan consolidado (Taxonomía-UX + deuda) → ejecutar con correcciones

Revisé el `implementation_plan.md`. Está **fiel y bien priorizado**. **Luz verde**, pero con **2 correcciones concretas (una evita romper el chatbot)** y 2 precisiones. Mantén el orden por prioridad y reporta evidencia real por parte.

## 🔴 Corrección 1 (BLOQUEANTE) — NO elimines `problems` del Chatbot
El plan dice *"Eliminar la lectura del campo legacy `problems`"*. **`problems` NO es legacy** — es el mecanismo ACTIVO de selección problema→solución del chatbot. Verificado en `Chatbot.jsx`:
- línea 185-186: `sector.problems.find(p => p.code === problemCode)`
- línea 269: `sectorsCorpus.find(...)?.problems?.map(...)` (renderiza las opciones de problema)
Eliminarlo **rompe el flujo de selección de problema**. `problems` (retos del SECTOR con su solución) y `objections` (pushback de la PERSONA) son **complementarios, no sustitutos**. → **Conserva `problems`** y **añade** encima: `objections` de la persona + `kpis`/`regulations` del sector. Aumentar, no reemplazar.

## 🔴 Corrección 2 — Ruta REAL de la Spec 004
El plan apunta a `specs/004-metodologia-y-agentes/` — **esa carpeta no existe**. La real es **`specs/004-metodologia-desarrollo-digital/`** (tiene `spec.md`, `tasks.md`, `tech_debt.md`, `spec_004_refined.md`). Documenta el abandono **ahí**, no crees una carpeta nueva.

## Precisión 3 — Parte C (lint/Lighthouse): tooling real, pero sin bloquear por deuda preexistente
Verifiqué: **no hay eslint configurado** (los scripts son stubs `echo`). Convertir `lint:strict` en `eslint . --ext .js,.jsx,.ts,.astro` requiere **instalar y configurar** eslint + `eslint-plugin-astro` desde cero, y **aflorará muchos errores preexistentes**.
- Instala/configura el tooling **real** (no otro stub), pero **no bloquees el build** con toda la deuda de lint de golpe: genera un **reporte/baseline** (conteo de issues) sin `--max-warnings 0` al inicio. La limpieza masiva es un esfuerzo aparte.
- **Lighthouse:** `@lhci/cli`/`unlighthouse` necesita un **navegador headless** y servir el `dist/`. Asegúrate de que el comando **corra de verdad** y **pega los scores reales** — no lo dejes en otro `echo`. Al inicio reporta scores, sin forzar umbrales.

## Precisión 4 — Confirmaciones de rutas y alcance
- **Página de pilares/soluciones:** es `src/pages/soluciones/[id].astro` (confirmado). Surfacea ahí `techStack`/`proofPoints`/`competitivePositioning`.
- **Parte F (menores 011):** el plan la omite — está bien **diferirla** (era opcional); déjalo dicho, no la des por hecha.
- Renderizado de Parte B **condicional** (`if (campo)`), para que sectores/pilares aún sin ese campo no rompan.

## Verificación (evidencia REAL, no visual vaga)
- **A:** `npm run build` verde (24+ páginas); **chatbot con 0 llamadas LLM** (traza de red real al navegar el flujo guiado); muestra **1 interacción concreta** usando los campos nuevos (ej. el wizard/chatbot mencionando una **objeción + KPI + regulación reales** de un combo sector-persona, ej. finanzas+CISO). Confirma que `problems` sigue funcionando.
- **B:** una página de sector pinta KPIs/regulaciones; una de solución pinta techStack/proofPoints. Build verde.
- **C:** salida real de `lint:strict` (conteo) y **scores reales de Lighthouse** (pega el reporte).
- **D/E:** solo documentación (abandono 004 en la carpeta correcta; recordatorio de rotación SSH en 008). Sin tocar credenciales ni construir LangGraph.

## Forma de respuesta
- Implementa por prioridad, parte por parte. Evidencia real por parte (build, traza de red del chatbot, scores). No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará **en el navegador (preview)** que las microinteracciones usen de verdad los campos nuevos y que **`problems` no se rompió** (chatbot 0 llamadas), que el surfacing no rompa el build, que lint/Lighthouse sean runners **reales** (no stubs), que la 004 se documente en `004-metodologia-desarrollo-digital`, y que no se toquen credenciales. **Pendiente del usuario:** rotar la SSH de IONOS.
