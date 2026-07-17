# Prompt para Antigravity: Confirmación del Plan de Taxonomía v3 → ejecutar con 3 precisiones

Revisé `planes/Plan de Implementación Enriquecimiento de Taxonomía v3 (Industrias, Personas y Madurez).md`. Cubre fielmente las 5 partes. **Luz verde para ejecutar**, con 3 precisiones.

## Precisión 1 (IMPORTANTE) — No sobrecargues el Home
El Home ya tiene muchas secciones: Hero, TrustLayer, Buscador Semántico, Services (6 pilares), CopilotDemo, DiagnosticWizard (sectores), MultiStepWizard, Metodología, FAQ. Añadir dos bloques grandes más ("por Industria" + "por Rol") lo vuelve larguísimo y diluye la sensación premium.
- **Consolida** los dos accesos en **un solo bloque compacto** con toggle/pestañas ("Explora soluciones **por Industria** | **por Rol**") en lugar de dos secciones separadas y extensas.
- **Ubícalo con criterio** (ej. justo después de `Services`, que es la sección de oferta), no al final apilando más scroll.
- Mantén el sistema de diseño (`.glass-card`, tokens actuales) y que sea **aditivo y sobrio**. Si sientes que compite con `Services` o con el panel de sectores del Wizard, propón la ubicación en tu reporte antes de duplicar contenido.

## Precisión 2 — Define el comportamiento de "Soluciones por Rol"
Al hacer clic en un rol (ej. CFO), define qué pasa (elige lo más limpio):
- **Opción recomendada:** enlaza/hace scroll a los pilares de su `pillarsOfInterest` resaltándolos (reutiliza el patrón `semanticHighlight` o anclas a `/soluciones/[slug]`), mostrando la meta/dolor del rol como contexto.
- No crees páginas por rol nuevas en este ciclo; es navegación/filtrado sobre los pilares existentes.

## Precisión 3 — Verificación E2E con evidencia (incluye las páginas de pilar)
Tu plan verifica Home + búsqueda + build. Añade la verificación de los enriquecimientos de **página de pilar**:
1. **Cobertura de industria:** busca "tractor agricultura" / "planta de energía" → sin rediseño del Home, badge sutil en el buscador y **highlight correcto de los `relatedPillars`**.
2. **Página de pilar** `/soluciones/[id]`: renderiza la insignia de **madurez (Gartner)**, el bloque **"Casos de Uso por Industria"** (3-5 sectores) y el bloque de **roles** (meta/dolor desde `personas.json`).
3. **Home:** el bloque compacto "por Industria / por Rol" aparece, es sobrio y enlaza correctamente.
4. **No-regresión (con evidencia):** chatbot guiado = **0 llamadas** (Network); buscador semántico sigue reordenando con el corpus ampliado (industrias extendidas incluidas); wizard/copilot OK.
5. **Coherencia de datos:** `personas.pillarsOfInterest`, `industry.relatedPillars` y `pillar.maturityStage` referencian pilares válidos existentes.
6. `npm run build` sin errores.

---

Procede con las 5 partes aplicando estas precisiones. Recuerda: todos los campos/fuentes nuevos (`extendedIndustries`, `personas`, `maturityStage`) deben quedar anotados para el schema Zod de la migración a Content Collections (prompt 003).

No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que la taxonomía se refleje en las páginas (madurez, por industria, por rol) sin sobrecargar el Home, que una búsqueda de industria fuera de las 10 resalte los pilares correctos, y que nada se rompa (chatbot 0 llamadas).
