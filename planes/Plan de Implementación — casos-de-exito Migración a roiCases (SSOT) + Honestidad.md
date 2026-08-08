# Plan de Implementación — `/casos-de-exito`: Migración a `roiCases` (SSOT) + Honestidad §2

Extiende **Spec 003 / 011 (taxonomía como Fuente de Verdad)** y aplica la política de **Honestidad Radical (Constitución §2)**. Absorbe el prompt anterior `prompt-antigravity-honestidad-casos-de-exito.md`.

---

## Contexto y Diagnóstico

Actualmente, `src/pages/casos-de-exito.astro` contiene escenarios **hardcodeados** en el archivo `.astro`, lo cual viola el principio "Taxonomía = Única Fuente de Verdad". Además, la página presentaba un disclaimer que sugería clientes reales anonimizados ("proteger confidencialidad corporativa") y métricas desprovistas del marcador `[EST]`.

Este plan reestructura `/casos-de-exito` para que lea los casos **dinámicamente desde `sectorsCorpus.json`** (generado desde los YAMLs de sector), enriquece la taxonomía con `roiCases` estructurados para **finanzas, retail, salud, público y seguros**, y aplica el etiquetado `[EST]` en cada métrica durante el renderizado.

---

## User Review Required

> [!IMPORTANT]
> **🔴 `roiCases` Estructurados en Taxonomía (Fase de Poblamiento):**
> 
> 1. **`finanzas.yaml`:**
>    - Caso 1: *"Automatización de Scoring Crediticio y Detección de Fraude"* (`metrics`: `-85%` tiempo de aprobación, `-40%` tasa de fraude).
> 2. **`retail.yaml`:**
>    - Caso 1: *"Optimización Predictiva de Inventario"* (`metrics`: `+22%` rotación de activos, `98%` precisión en forecast).
> 3. **`salud.yaml`:**
>    - Caso 1: *"Agentes de IA para Análisis Clínico e IDP"* (`metrics`: `100x` velocidad de extracción, `100%` privacidad On-Premise).
> 4. **`publico.yaml`:**
>    - Caso 1: *"Automatización e Interoperabilidad de Expedientes"* (`metrics`: `-60%` tiempo de procesamiento, `100%` trazabilidad en tiempo real).
> 5. **`seguros.yaml`:**
>    - Caso 1: *"Hiperautomatización del Triage de Siniestros Menores"* (`metrics`: `<24h` liquidación fast-track, `+40%` resolución automatizada).

---

## Proposed Changes

### 1. Poblamiento de Taxonomía en `src/content/sectors/*.yaml`

#### [MODIFY] [finanzas.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/finanzas.yaml)
Ajustar y estructurar el objeto `roiCases` para coincidir con la taxonomía y métricas de scoring/fraude.

#### [MODIFY] [retail.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/retail.yaml)
Agregar la clave `roiCases` con el caso de "Optimización Predictiva de Inventario".

#### [MODIFY] [salud.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/salud.yaml)
Agregar la clave `roiCases` con el caso de "Agentes de IA para Análisis Clínico e IDP".

#### [MODIFY] [publico.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/publico.yaml)
Agregar la clave `roiCases` con el caso de "Automatización e Interoperabilidad de Expedientes".

---

### 2. Refactor de la Página `casos-de-exito.astro` (Taxonomía SSOT + Honestidad §2)

#### [MODIFY] [casos-de-exito.astro](file:///C:/xampp/htdocs/datanestiq/src/pages/casos-de-exito.astro)
- Importar `sectorsCorpus` desde `../data/sectorsCorpus.json`.
- Extraer dinámicamente los casos:
  ```javascript
  const allRoiCases = sectorsCorpus.flatMap(sector => 
    (sector.roiCases || []).map(roiCase => ({
      ...roiCase,
      sectorTitle: sector.title,
      sectorSlug: sector.slug
    }))
  );
  ```
- **Disclaimer Honesto (Línea ~22):**
  > *"Escenarios **ilustrativos** del tipo de resultado que nuestras arquitecturas pueden habilitar. **No representan clientes ni proyectos reales**; las cifras son **estimaciones de potencial `[EST]`**, no resultados medidos."*
- **Intro:** *"Descubra escenarios ilustrativos..."*
- **Renderizado Dinámico de Tarjetas:** Iterar sobre `allRoiCases`, mostrando:
  - Badge de sector: `Escenario: ${case.sectorTitle}`
  - Título del caso: `case.title`
  - Contexto y Descripción: `case.context` y `case.description`
  - Métricas: iterar sobre `case.metrics`, agregando a cada `metric.value` la insignia `[EST]` (`<span class="text-xs text-brandCyan border border-brandCyan/30 rounded px-1.5 py-0.5 font-semibold tracking-wider">[EST]</span>`).
- **CTA:** *"Diseñemos juntos su próxima arquitectura de valor"*.

---

### 3. Documentación y Estado de Specs

#### [MODIFY] [ESTADO-SPECS.md](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md)
Actualizar el estado de Spec 003, Spec 011 y pendientes transversales anotando la migración de `/casos-de-exito` a la taxonomía SSOT con la política `[EST]` de honestidad §2.

---

## Verification Plan

### Automated Verification
1. **Validación de Taxonomía:**
   - Ejecutar `node scripts/build-taxonomy.mjs` y verificar que parsea los `roiCases` de todos los sectores sin errores de Zod.
2. **Build Estático de Astro:**
   - Ejecutar `npm run build` y confirmar que compila 38 páginas sin errores.

### Manual Verification (Demostración SSOT y Honestidad)
1. **Verificación SSOT:**
   - Confirmar que `src/pages/casos-de-exito.astro` no contiene textos de casos hardcodeados en el marcado HTML.
   - Probar remover temporalmente un `roiCase` de un YAML → verificar que desaparece de `/casos-de-exito` tras reconstruir.
2. **Verificación de Honestidad §2:**
   - Verificar en la vista compilada de `/casos-de-exito` que **cada** cifra de métrica (`-85%`, `-40%`, `+22%`, `98%`, `100x`, `100%`, etc.) exhiba claramente la etiqueta `[EST]`.
   - Confirmar que el disclaimer no contenga la frase "confidencialidad corporativa" ni insinúe clientes ocultos.
