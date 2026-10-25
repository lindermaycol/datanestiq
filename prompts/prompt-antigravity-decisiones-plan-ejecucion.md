# Prompt para Antigravity: Decisiones confirmadas → ejecutar el Plan de Implementación completo

He revisado tu documento `planes/Plan de Ejecución Auditorías Global y Spec 007.md`. Es fiel a las dos auditorías (`prompt-antigravity-auditoria-spec007-correcciones.md` y `prompt-antigravity-auditoria-global-specs.md`) y el desglose por fases/componentes es correcto. Aquí tienes las 4 decisiones que pediste, con el razonamiento, más una corrección a un supuesto técnico y una tarea extra menor. **Tienes luz verde para ejecutar el plan completo con estas decisiones aplicadas.**

---

## Decisión 1 — Ruta `base` de Astro: ELIMINARLA (tu premisa sobre XAMPP es incorrecta, no hay trade-off real)

**Elimínala.** Pero antes de ejecutar, corrijo el supuesto de tu pregunta: no es cierto que eliminar `base` rompa "la previsualización directa en XAMPP". Ese flujo (abrir `http://localhost/datanestiq/dist/` sirviendo los archivos estáticos directamente vía Apache/XAMPP) **nunca fue la forma correcta de probar el build de Astro**, y es justamente la causa raíz del bug: `base` solo existe para cuando el sitio se despliega bajo un subpath real, no para acomodar un método de previsualización local improvisado.

Astro ya trae las dos herramientas correctas para esto, ambas ya declaradas en `package.json` y que **no dependen de XAMPP ni de `base`**:
- `npm run dev` → servidor de desarrollo con hot-reload (para trabajar en el código).
- `npm run preview` → sirve el `dist/` ya compilado, en su propio servidor, exactamente como se comportaría en producción (para validar el build final).

**Instrucción:** elimina `base` de `astro.config.mjs` como indica el Bloque 1.1 de la auditoría Spec 007, sin condiciones. Para las verificaciones manuales del plan (Fase 1 y Verification Plan), usa `npm run preview` en vez de abrir `dist/` por XAMPP. Reserva XAMPP/Apache exclusivamente para el backend de WordPress (`wp-json/...`), que es justamente el rol que le da la Spec 008 (arquitectura headless) — no debe servir el frontend Astro en ningún escenario, ni local ni productivo.

**Tarea extra menor (no estaba en la auditoría original):** `dist/` no está en `.gitignore` — aparece como `??` (untracked) en `git status`. Añade esta línea a `.gitignore`:
```
dist/
```
Es un artefacto de build, no debe versionarse.

---

## Decisión 2 — Carpeta `prototype/`: CONSERVAR, no eliminar

**No la borres.** Es una acción destructiva e irreversible sobre código que no está causando ningún daño real (Astro solo compila desde `src/`, `prototype/` no se toca en el build). Además, según el `git status` inicial de esta sesión, esos archivos tenían cambios sin commitear — bórralos y se pierde ese trabajo sin posibilidad de recuperarlo fácilmente.

**Instrucción:** conserva la carpeta tal cual. Aplica exactamente lo que ya indicaba el Bloque G de la auditoría global: desmarca T019 en `specs/006-ecosistema-astro/tasks.md` y añade la nota de que quedó pendiente de decisión. Adicionalmente, crea `prototype/README.md` con una sola línea aclarando su estado:
```md
# Prototipo Fase 0 (archivado)

Este directorio contiene el prototipo estático original (pre-Astro) de la Spec 001/002.
Se conserva como referencia histórica. **No forma parte del build de producción**
(Astro solo compila desde `/src`). No lo edites como si fuera el sitio en vivo.
```

---

## Decisión 3 — Transformers.js: POSPONER, documentar la deuda técnica (no implementar una versión mínima ahora)

**Pospón la implementación.** Meter una dependencia de ML del lado del cliente (`@xenova/transformers`, con sus modelos livianos pero no triviales de tamaño y tiempos de carga) en medio de un pase de corrección de bugs y sincronización de documentación es mezclar dos tipos de trabajo distintos: esto merece su propio ciclo de diseño (`/plan` dedicado: qué modelo, qué tamaño de bundle es aceptable, dónde se hostean los pesos, fallback si el navegador no soporta WebGPU/WASM, etc.), no un parche apurado dentro de esta auditoría.

**Instrucción:** en `specs/002-microexperiencias-ia/plan.md`, añade la sección "Complexity Tracking" (mismo formato que `specs/007-multi-pagina/plan.md`) con esta entrada:

| Violación | Por qué se necesitaría | Por qué se pospone |
|---|---|---|
| Transformers.js (Edge AI) en el cliente | La Constitution (sección 4) lo exige para clasificación/embeddings ligeros sin depender de Groq | Requiere su propio diseño (selección de modelo, tamaño de bundle, fallback sin WebGPU) que excede el alcance de esta auditoría de corrección de bugs; se planificará como iniciativa separada una vez cerrado el backlog crítico (Spec 008 incluida) |

No escribas código de Transformers.js en este ciclo.

---

## Decisión 4 — Pipeline LangGraph: DESHABILITAR el workflow de GitHub Actions (no construir el backend Python ahora)

**Deshabilítalo.** Igual que la Decisión 3: construir un pipeline LangGraph/Python real (`backend/langgraph_pipeline.py` + `requirements.txt` + lógica de generación de copy con los 10 agentes) es una iniciativa de ingeniería completa, no una corrección de bug. Dejar el workflow activo mientras está roto es peor que apagarlo: hoy se dispara solo con cualquier push a `specs/003-taxonomia-servicios/**` o `prompts/**` (¡y este mismo ciclo de auditoría está tocando esas rutas!), fallará en rojo cada vez, y genera ruido/falsos negativos en CI sin que nadie lo esté usando de verdad todavía.

**Instrucción:**
1. Renombra `.github/workflows/langgraph_pipeline.yml` a `.github/workflows/langgraph_pipeline.yml.disabled` (o comenta el bloque `on:` completo si prefieres mantenerlo visible en la misma ruta — cualquiera de las dos formas es válida, elige la que sea más clara para el equipo).
2. En `specs/004-metodologia-desarrollo-digital/tech_debt.md`, deja constancia de que el workflow fue desactivado temporalmente y por qué (script `backend/langgraph_pipeline.py` inexistente), y que su reactivación requiere construir primero el andamiaje Python real como iniciativa aparte.

---

## Confirmación final para ejecutar

Con las 4 decisiones anteriores aplicadas, **ejecuta el plan completo tal como está desglosado** en `planes/Plan de Ejecución Auditorías Global y Spec 007.md` (Fase 1 y Fase 2), con estos dos ajustes puntuales sobre lo ya escrito ahí:
- Fase 1 → Configuración e Infraestructura Visual: al eliminar `base` de `astro.config.mjs`, agrega también la línea `dist/` a `.gitignore` (Decisión 1).
- Fase 2 → Componente Arquitectura WordPress (Spec 008): se mantiene exactamente como lo escribiste — **solo `plan.md` y `tasks.md`, cero código PHP/WordPress en este ciclo.** Eso sigue pendiente de un ciclo de confirmación aparte, tal como ya anotaste correctamente.

Repórtame al terminar, igual que en los prompts anteriores: qué archivos tocaste por bloque, resultado de `npm run build` y `npm run preview`, y una sección final de "Hallazgos adicionales" si encuentras algo no cubierto aquí. Cuando termines, yo (Claude) hago la auditoría de verificación sobre el conjunto completo (Spec 007 + Global + estas 4 decisiones).
