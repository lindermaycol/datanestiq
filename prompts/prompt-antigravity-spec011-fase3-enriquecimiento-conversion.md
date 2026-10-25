# Prompt para Antigravity — Spec 011 Fase 3: Enriquecimiento de la Taxonomía para Conversión por Rol × Sector (SDD) → ENTREGAR PLAN, NO implementar aún

> **En una frase:** **profundiza la taxonomía** (fuente única de verdad) con los campos que hoy están finos, para que los dos prompts siguientes (fundación de páginas y Spec 013 consultiva) sean **puros consumidores** que solo renderizan/componen, sin autoría de datos. **Este prompt va PRIMERO.** Plan-first: entrega `plan.md`, no implementes aún.

Sigue **Spec-Driven Development**. Esto es **capa de datos**: todo se autoría en la **fuente** (`src/content/pillars/*.yaml`, `src/content/sectors/*.yaml`, `src/data/personas.json`) y se valida con `scripts/build-taxonomy.mjs` (Zod + integridad de aristas). Si un campo nuevo es necesario, se declara primero en `src/lib/schemas.js` (esto toca la Spec 003).

---

## 0. Por qué este prompt existe y va primero

Los prompts de **fundación** ([prompt-antigravity-profundidad-paginas-y-recorridos-por-rol.md](prompt-antigravity-profundidad-paginas-y-recorridos-por-rol.md)) y **Spec 013** ([prompt-antigravity-spec013-conversion-consultiva-rol-sector.md](prompt-antigravity-spec013-conversion-consultiva-rol-sector.md)) **asumen una taxonomía rica**. Ya trié el estado real contra la fuente:

**Ya poblado (Spec 011 Fases 1–2):** 7 personas (CFO/CIO/CDO/CTO/COO/CISO/CEO) con `objections`/`decisionCriteria`/`triggers`; sectores con `kpis`/`regulations`/`subSectors` (`[EST]`); pilares con `techStack`/`proofPoints`/`competitivePositioning`. **No re-crees esto.**

**Fino (el trabajo de esta fase):**
1. **Profundidad técnica de pilares.** `sistemas-digitales.techStack` es una lista genérica (React/Astro/AWS) — **falta** arquitectura/despliegue: cloud/**híbrido**/**on-prem/VPC**, patrones de **integración con legacy/ERP/core**, **residencia/soberanía de datos**, garantías de **uptime**, control de **TCO**. `estrategia-datos-ia` **carece** de **catálogo de datos, linaje, stewardship, calidad, interoperabilidad, gobierno de metadatos**. Enriquece `techStack`/`competitivePositioning`/`proofPoints` de los pilares donde estén finos.
2. **KPIs por sector.** Los sectores financieros tienen ~2 KPIs `[EST]`; el buyer CFO pide **4–6**. Expande `kpis` `[EST]` en finanzas/seguros/minería (fraude, morosidad/NPL, scoring, siniestralidad, reservas, payback) y donde falte densidad.
3. **Objeciones → respuestas por rol.** Las `objections` existen pero **sin respuesta**. Añade, de forma estructurada, la **respuesta honesta** a cada objeción clave del comité (CIO: "contrato con Microsoft", "datos sensibles en nube", TCO; CDO: baja calidad/silos/interoperabilidad; CFO: ROI abstracto/ERP; CEO: "IA es moda"/"no somos tech"; público: continuidad/contratación/riesgo reputacional). **Requiere decidir un campo nuevo** (ver §2).
4. **Modelos de despliegue/seguridad** para el sector público (on-prem/VPC/nube privada/LLM privado/segmentación/trazabilidad) como dato reutilizable.
5. **Modalidades de contratación** del sector público (diagnóstico/piloto/fases/consultoría) como dato.

**Fuente de requerimientos:** los 4 insumos en `planes/insumos-conversion-consultiva/` (`sector-publico-auditoria-ux.md`, `sector-publico-matices.md`, `cfo-finanzas-seguros.md`, `ceo-estrategico.md`). Úsalos para saber QUÉ profundizar.

---

## 1. Alcance del enriquecimiento

- **Pilares** (`src/content/pillars/*.yaml`): profundizar `techStack`, `competitivePositioning`, `proofPoints` con las nociones de §0.1. Prioriza `sistemas-digitales` y `estrategia-datos-ia`; revisa los otros 4.
- **Sectores** (`src/content/sectors/*.yaml`): expandir `kpis` `[EST]` (4–6 en financieros), completar `regulations`/`subSectors`; para `publico`, añadir modelos de despliegue/seguridad y modalidades de contratación (§0.4–0.5).
- **Personas** (`src/data/personas.json`): añadir respuestas a `objections` (§0.3) y afinar `decisionCriteria` por rol donde el insumo lo exija.
- **Regenerar** los JSON de `src/data/*` vía `build-taxonomy.mjs`; `contentAngles` sigue **fuera** del corpus cliente (en `contentAngles.json`).

## 2. Decisión de schema (para mi revisión, en el plan)

Las "objeciones → respuestas" y los "modelos de despliegue" necesitan una **forma de datos**. Propón en el `plan.md` la opción y su impacto en `src/lib/schemas.js` (Spec 003):
- **Opción A (recomendada):** campo estructurado `objectionResponses: [{ objection, response }]` en `personaSchema` (empareja con las `objections` existentes), y `deploymentModels: [...]`/`engagementModels: [...]` en `sectorSchema` (o pillar). Zod validado.
- **Opción B:** reutilizar campos de texto existentes (menos limpio, más frágil para renderizar).
Elige A salvo que veas un bloqueo; **no** inventes el schema sin dejarlo explícito en el plan.

---

## 3. Guardarraíles (críticos — NO negociables)
- **🔴 HONESTIDAD.** Los `proofPoints`/KPIs son **estimaciones/modelos** marcados **`[EST]`** — **prohibido** presentar clientes reales, logos, testimonios o certificaciones inexistentes. Las respuestas a objeciones = **capacidades reales** ("desplegamos en VPC/on-prem", "coexistimos con tu stack"), no credenciales falsas.
- **Fuente única de verdad.** Todo en YAML/JSON fuente + `build-taxonomy.mjs`; **nada** hardcodeado en `.astro`. `contentAngles` fuera del corpus cliente.
- **Integridad.** `build-taxonomy.mjs` debe pasar **verde** (Zod + aristas `relevantPersonas`/`relevantSectors`, exit 1 ante inválido). No rompas referencias existentes.
- **No romper consumidores.** Las islas ya leen estos JSON (`SemanticSearch`, `Chatbot`, wizards): mantén las claves existentes; solo **añade**. `npm run build` verde.
- **Seguridad.** No tocar core WordPress; PII redactada; no exponer `.env`/`secure_leads`/claves.

## 4. Formato esperado de `plan.md`
1. **Overview** + deliverables.
2. **Decisión de schema** (§2, Opción A/B con impacto en `schemas.js`).
3. **Enriquecimiento de pilares** — qué campos se profundizan por pilar.
4. **Enriquecimiento de sectores** — KPIs `[EST]` nuevos, regulaciones, despliegue/contratación del público.
5. **Enriquecimiento de personas** — objection→response y criterios por rol.
6. **Validación** — cómo queda `build-taxonomy.mjs` (conteos antes/después).
7. **Riesgos** + **tareas** preliminares.

## 5. Verificación (tras mi aprobación)
1. `sistemas-digitales` incluye on-prem/híbrido/integración-legacy/residencia/TCO/uptime en `techStack`/`competitivePositioning`.
2. `estrategia-datos-ia` incluye catálogo/linaje/stewardship/calidad/interoperabilidad/gobierno.
3. Finanzas/seguros con **4–6 KPIs `[EST]`**; `publico` con modelos de despliegue + modalidades de contratación.
4. Cada persona clave tiene `objectionResponses` honestas emparejadas a sus `objections`.
5. `build-taxonomy.mjs` **verde** (Zod + aristas); JSON regenerados; `contentAngles` fuera del corpus cliente.
6. `npm run build` verde; islas intactas; **cero** dato fabricado; todo estimado marcado `[EST]`.

## 6. Forma de respuesta
- **Entrega `plan.md`** (estructura §4) + la **decisión de schema** para mi revisión. **NO implementes aún.**
- Reporta con evidencia (conteos de campos antes/después, `build-taxonomy` verde). Sección final "Hallazgos adicionales".

---

**Orden de ejecución (nuevo):** (1) **este** enriquecimiento (Spec 011 Fase 3) → (2) **fundación** (renderiza la taxonomía) → (3) **Spec 013** (compone la capa consultiva, Fases Público/CFO/CEO). Los prompts (2) y (3) quedan como **consumidores puros**: renderizan/componen, y si detectan un campo faltante lo **reportan**, no lo autoría inline. **Pendientes del usuario (sin cambios):** desplegar `chat.php` corregido a IONOS + claves `DASHSCOPE`/`GEMINI`; rotar SSH de IONOS.
