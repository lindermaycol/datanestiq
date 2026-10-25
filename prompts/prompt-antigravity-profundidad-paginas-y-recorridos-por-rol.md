# Prompt para Antigravity — Profundidad de páginas de Solución/Sector + IA de navegación + recorridos por rol (SDD) → ENTREGAR PLAN PARA MI REVISIÓN

> **En una frase:** produce un `plan.md` (+ actualizaciones de Spec 003/007/012) para **profundizar `/soluciones/*` y `/sectores/*` renderizando la taxonomía ya enriquecida** (por el prompt previo, Spec 011 Fase 3), crear hubs `/soluciones` y `/sectores`, arreglar navegación y páginas legales. Eres **consumidor de datos**, no autor. **NO toques la conversión consultiva por rol/sector (Spec 013)** ni implementes todavía. El output es la **fundación reutilizable** sobre la que se construirá la Spec 013.
>
> **Va DESPUÉS del enriquecimiento** ([prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md](prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md)) y ANTES de la Spec 013.

Sigue **Spec-Driven Development**: primero **spec → plan → tasks**, luego implementación. Este trabajo toca la arquitectura de contenido del sitio (páginas `/soluciones/*`, `/sectores/*`, hubs, legal, blog), así que **NO implementes todavía**: entrega el `plan.md` (y las actualizaciones de spec) en `/planes` para MI revisión. Después de mi luz verde, implementas y verificas con evidencia real.

---

## 0. Contexto y estado verificado (lee esto: evita perseguir fantasmas)

Tres auditorías externas (Perplexity) recorrieron el sitio adoptando roles del comité de compra (**CDO Sector Público**, **CIO Sector Público**, y una auditoría general de home/nav). **Ya trié los hallazgos contra el HTML construido (`dist/`).** No repitas el triaje; parte de esto:

### ✅ Hallazgos CONFIRMADOS (reales — son el trabajo)
1. **Enlaces legales muertos.** El footer tiene `Privacidad` y `Términos` como `href="#"` (2 anclas muertas). Daña la percepción de completitud/confianza institucional.
2. **Las páginas de solución no tienen profundidad técnica.** Verificado por grep en `dist/`:
   - `/soluciones/sistemas-digitales`: **cero** menciones a arquitecturas cloud/híbrido/**on-prem**, patrones de integración con **legacy**, multi-cloud, residencia/soberanía de datos, VPC. Solo aparecen sueltos "api", "legacy", "TCO", "uptime".
   - `/soluciones/estrategia-datos-ia`: tiene "gobernanza" pero **cero** menciones a **catálogo de datos, linaje, stewardship, calidad de datos, interoperabilidad, metadatos**.
   - Diagnóstico: los campos enriquecidos que **ya existen en la taxonomía** (Spec 011) —`techStack`, `proofPoints`, `competitivePositioning` en pilares; `subSectors`, `kpis`, `regulations` en sectores; `objections`, `decisionCriteria`, `triggers` en personas— **no se renderizan** en las páginas, o están vacíos para esos pilares. Esta es exactamente la deuda pendiente marcada en `ESTADO-SPECS.md` fila 011: *"Opcional: surfacing de campos nuevos en las páginas del sitio (UI)."*
3. **No hay páginas hub.** No existen `/soluciones/index` ni `/sectores/index`. El nav superior es `Nosotros · Servicios(/#servicios ancla) · Casos ROI · Blog`; **sectores y soluciones no tienen entrada de navegación propia** (solo tarjetas en el home + footer). Un buyer de alta intención tarda en llegar a "qué hacen para MI caso".
4. **El manejo de objeciones no aflora en las páginas.** La taxonomía tiene `objections`/`decisionCriteria` por persona, pero hoy **solo los consume el chatbot**. Las páginas de solución/sector no responden objeciones típicas (CIO: "ya tenemos contrato con Microsoft", "datos sensibles para la nube", TCO; CDO: baja calidad de datos, poca cultura del dato, interoperabilidad compleja).

### ❌ DESCARTADOS (limitación de Perplexity — NO son bugs, no los toques)
- *"`/soluciones/*`, `/sectores/*` y `/blog` devuelven el mismo contenido del home."* **FALSO.** Verifiqué en `dist/`: títulos, tamaños y contenido diferenciados (p. ej. `/sectores/publico` renderiza OECE, `[EST]`, subsectores, expedientes/transparencia). Perplexity **no ejecuta JS ni hidrata islas**, por eso "vio" un shell. No existe bug de routing/clonado.
- *"No puedo confirmar que el chatbot sea 0-LLM / el wizard / el buscador semántico."* Limitación de Perplexity; **ya verificado en vivo por mí** (chatbot guiado = 0 llamadas; failover del texto-libre operativo). No re-audites esto.

---

## Alcance SDD — qué specs actualizar / crear

| Spec | Acción | Contenido |
|------|--------|-----------|
| **003 (Taxonomía de Servicios)** | **ACTUALIZAR** | Las páginas `/soluciones/*` y `/sectores/*` deben **renderizar los campos enriquecidos** de la taxonomía. Cierra la deuda de surfacing de la Spec 011. |
| **007 (Expansión Multi-Página)** | **ACTUALIZAR** | Páginas legales reales (`/privacidad`, `/terminos`) + hubs `/soluciones` y `/sectores` + entradas de navegación. |
| **011 (Enriquecimiento Taxonomía)** | **YA HECHO EN EL PROMPT PREVIO** | La profundidad de datos (`techStack`/`proofPoints`/`competitivePositioning`/objection-responses/KPIs) la autoría **antes** [prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md](prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md). Aquí **solo consumes**; reporta gaps, no autoríes. |
| **012 (Fábrica de Contenido)** | **USAR** | Generar 2–3 posts de blog con foco Sector Público (gobierno del dato en entidades públicas, analítica de contrataciones, interoperabilidad/madurez). |
| **013 (Conversión Consultiva por Rol × Sector)** | **YA TIENE PROMPT PROPIO** | La capa consultiva por buyer (marco reutilizable + fases: Público, CFO Económico, y futuros roles) se especifica en [prompt-antigravity-spec013-conversion-consultiva-rol-sector.md](prompt-antigravity-spec013-conversion-consultiva-rol-sector.md). **No la abordes aquí.** Esta fundación va primero; la Spec 013 se construye encima. |

---

## PARTE 1 (P1 — máxima prioridad) — Profundizar páginas de Solución y Sector renderizando la taxonomía

Es el hallazgo que los **tres** roles reclamaron. La taxonomía es la **fuente única de verdad**: la mayor parte es **renderizar campos que ya existen**, no inventar contenido.

**1.1 Páginas `/soluciones/[slug]` (pilares).** Añade secciones que rendericen, cuando existan:
- `techStack` → bloque "Stack y arquitectura" (para `sistemas-digitales`: cloud / **híbrido** / **on-prem/VPC**, patrones de **integración con legacy/ERPs**, APIs, **residencia/soberanía de datos**, garantías de **uptime**; para `estrategia-datos-ia`: **catálogo de datos, linaje, stewardship, calidad, interoperabilidad, gobierno de metadatos**).
- `proofPoints` → bloque "Resultados esperables" con métricas marcadas **`[EST]`** (honestidad; ver guardarraíl).
- `competitivePositioning` → bloque "Por qué Datanestiq / cómo convivimos con tu stack actual" (coexistencia con contratos vigentes: Microsoft/Oracle, componentes nativos, modernización incremental, control de **TCO**).
- **Objeciones → respuestas.** Cruza el pilar con sus personas relevantes y muestra un bloque "Resolvemos tus dudas" que responda honestamente sus `objections`.

**1.2 Páginas `/sectores/[slug]`.** Asegura que rendericen `subSectors`, `kpis` (con **`[EST]`**), `regulations`, y retos/soluciones específicos. Para Sector Público, cubre casos reales: **interoperabilidad institucional, analítica de contratación (OECE), calidad de padrones, trazabilidad documental**.

**1.3 La autoría de datos NO es de este prompt.** La taxonomía ya viene enriquecida por el **prompt previo** ([prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md](prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md), Spec 011 Fase 3), que va **antes** que este. Aquí eres **consumidor puro**: renderiza los campos existentes. **No hardcodees contenido en los `.astro`.** Si encuentras un campo genuinamente faltante, **repórtalo** en "Hallazgos adicionales" (no lo autoríes inline) para que se resuelva en la capa de datos.

---

## PARTE 2 (P2) — IA de navegación + páginas legales

**2.1 Hubs.** Crea `/soluciones` (índice de los 6 pilares con enlace a cada landing) y `/sectores` (índice de los 10 sectores). Genéralos desde la taxonomía, no a mano.

**2.2 Navegación.** Expón "Soluciones" y "Sectores" en el nav superior (enlazando a los hubs o como mega-menú ligero). Mantén el resto del nav. Confirma que el sitemap incluye los hubs.

**2.3 Páginas legales.** Reemplaza los `href="#"` de `Privacidad` y `Términos` por páginas reales `/privacidad` y `/terminos` con contenido base honesto (política de privacidad y términos genéricos, adaptables). Si prefieres no redactar legal definitivo, crea la página con un placeholder claramente marcado "borrador legal" — pero **elimina el `#` muerto**.

---

## PARTE 3 (P3) — Blog Sector Público (vía Spec 012)

Genera con `docs-generator.mjs` (target `blog`) **2–3 artículos** con foco Sector Público. Recuerda: `pubDate` **sin comillas** con `new Date().toISOString()` (no rompas `z.date()`); los artículos deben pasar `npm run build`.

---

## PARTE 4 — (movida) Spec 013 tiene prompt propio

La capa consultiva por buyer (marco reutilizable + fases: Sector Público, CFO Económico, y futuros roles) se especifica por separado en [prompt-antigravity-spec013-conversion-consultiva-rol-sector.md](prompt-antigravity-spec013-conversion-consultiva-rol-sector.md). **No lo abordes en este prompt.** Ejecuta primero esta fundación (P1–P3); la Spec 013 se construye encima.

---

## Guardarraíles (críticos — no negociables)
- **Honestidad radical.** **Prohibido inventar** certificaciones, sellos, logos de clientes o casos de éxito falsos. Toda métrica estimada va marcada **`[EST]`**. Las respuestas a objeciones deben ser **capacidades reales** ("desplegamos en VPC/on-prem", "coexistimos con tu stack actual"), no credenciales inventadas.
- **Taxonomía = fuente única de verdad, y aquí solo la CONSUMES.** La autoría de datos es del prompt previo (Spec 011 Fase 3); **no enriquezcas ni hardcodees en `.astro`** — renderiza lo que la fuente ya tiene. **Antes de escribir el plan, inspecciona los nombres de campo REALES** en `src/lib/schemas.js` (Zod: `pillarSchema`/`sectorSchema`/`personaSchema`) y los JSON generados en `src/data/*.json` — usa esos campos exactos (`techStack`, `proofPoints`, `competitivePositioning`, `subSectors`, `kpis`, `regulations`, `objections`, `decisionCriteria`…), no inventes nombres. Mantén `contentAngles` **fuera** del corpus cliente.
- **No romper.** Islas intactas (chatbot **0-LLM** guiado, wizard, buscador semántico); `npm run build` **verde**; markers `[EST]` conservados; sin errores de consola.
- **Seguridad.** No toques el core de WordPress (`wp-admin/`, `wp-includes/`, `wp-content/`). PII redactada (Constitución §5). No expongas `.env`, `secure_leads/`, claves.
- **Sin duplicar rutas.** Cuida que los hubs `/soluciones` y `/sectores` no colisionen con el catch-all ni generen rutas duplicadas.

---

## Verificación (evidencia real, tras mi aprobación del plan)
1. `/soluciones/sistemas-digitales` muestra secciones de arquitectura (cloud/híbrido/on-prem), integración con legacy, TCO y uptime — **con `[EST]`** donde aplique. `grep` en `dist/` lo confirma.
2. `/soluciones/estrategia-datos-ia` muestra catálogo/linaje/stewardship/calidad/interoperabilidad/gobierno.
3. Cada página de solución/sector muestra un bloque de **objeciones respondidas** honesto, derivado de la taxonomía.
4. `/soluciones` y `/sectores` existen como hubs, están en el **nav** y en el **sitemap**.
5. `/privacidad` y `/terminos` resuelven (200); **cero `href="#"`** en el footer.
6. 2–3 posts de blog Sector Público, build verde.
7. `build-taxonomy.mjs` valida (Zod + integridad de aristas, exit 1 ante error); ningún dato hardcodeado en `.astro`.
8. Islas y chatbot 0-LLM intactos; sin regresiones de consola/responsive.

## Formato esperado de `plan.md` (estructura obligatoria)
Estructura el plan en este orden para que no salga desordenado:
1. **Overview** — objetivo en 2–3 líneas + deliverables.
2. **Impacto en specs** — qué se actualiza en Spec 003/007/011/012 y por qué.
3. **Diseño de páginas de solución** (`/soluciones/*`) — qué secciones/campos de taxonomía se renderizan, componente por componente.
4. **Diseño de páginas de sector** (`/sectores/*`).
5. **Hubs y navegación** — `/soluciones`, `/sectores`, cambios de nav, sitemap.
6. **Páginas legales** — `/privacidad`, `/terminos`.
7. **Consumo de taxonomía** — qué campos (nombres reales del schema) renderiza cada página; lista de gaps detectados a reportar (no se autoran aquí).
8. **Riesgos y mitigaciones.**
9. **Tareas** (`tasks.md` preliminar) por bloque.

## Forma de respuesta
- **Entrega primero el `plan.md`** (en `/planes`, con la estructura de arriba) + el resumen de qué specs actualizas/creas y qué campos de taxonomía enriqueces. **NO implementes aún.**
- Reporta con evidencia real (grep/`dist/`, capturas, build verde). No des por bueno lo no probado.
- Sección final "Hallazgos adicionales" para lo que descubras fuera de alcance.
- **Recuerda:** este output es la **fundación reutilizable**; escribe el plan asumiendo que la Spec 013 se construirá encima **sin redefinir** estos componentes.

---

**Nota:** Claude (Opus 4.8) auditará **en el navegador** (servido por PHP/XAMPP para que las islas y `chat.php` corran) que las páginas de solución/sector rendericen la profundidad técnica y las objeciones desde la taxonomía, que los hubs/legal/nav funcionen, que el build quede verde y que las islas (chatbot 0-LLM incluido) sigan intactas. **Pendientes del usuario (sin cambios):** desplegar el `chat.php` corregido a IONOS + claves `DASHSCOPE`/`GEMINI` en el `.env` de producción; rotar la SSH de IONOS.
