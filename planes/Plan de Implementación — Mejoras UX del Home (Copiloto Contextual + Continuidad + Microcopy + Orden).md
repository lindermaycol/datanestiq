# Plan de Implementación — Mejoras UX del Home (Copiloto Contextual + Continuidad + Microcopy + Orden)

Extensión de **Spec 002** (microexperiencias) y **Spec 013** (A9 — adaptación por contexto). No es spec nueva.

## Contexto y Diagnóstico

El home actual tiene una cadena de stores (`semanticHighlight`, `userChallenge`, `lastUserQuery`, `chatbotOpen`, `userContext`) que conecta las islas entre sí, pero esta **continuidad es invisible** para el usuario. Además, el `CopilotDemo` siempre muestra los mismos 3 escenarios fijos, el `DiagnosticWizard` tiene un bug de resaltado (el `maxScore = 1.0` de los servicios top sube el umbral y atenúa todos los sectores), y las secciones del embudo están dispersas.

### Orden actual del home ([index.astro](file:///C:/xampp/htdocs/datanestiq/src/pages/index.astro)):
1. Hero + ContextChips + ConsultativeCTA
2. TrustLayer (logos, credibilidad)
3. **SemanticSearch** (buscador)
4. **Services** (6 pilares estáticos)
5. **SolutionsByRoleAndIndustry** (rol/industria)
6. **CopilotDemo** (demo terminal)
7. **DiagnosticWizard** (sectores + desafío)
8. MultiStepWizard (lead capture)
9. Methodology
10. FAQ

---

## Proposed Changes

### Mejora 1a — Copiloto contextual por búsqueda

#### [MODIFY] [CopilotDemo.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/CopilotDemo.jsx)

**Cambio:** Importar `useStore` y `semanticHighlight`. Cuando haya claves `service-*` en el highlight store con score > 0, ordenar los pilares de `taxonomyCorpus` por su score y seleccionar los top-3 en vez de `slice(0, 3)`.

```diff
+import { useStore } from '@nanostores/react';
+import { semanticHighlight } from '../../store/index';

 export default function CopilotDemo() {
+    const highlights = useStore(semanticHighlight);
     ...
-    const demoItems = taxonomyCorpus.slice(0, 3).map(...)
+    // Si hay contexto de búsqueda, seleccionar pilares por relevancia
+    const hasSearchContext = Object.keys(highlights).some(k => k.startsWith('service-'));
+    const rankedPillars = hasSearchContext
+        ? [...taxonomyCorpus]
+            .map(p => ({ ...p, score: highlights[`service-${p.slug}`] || 0 }))
+            .sort((a, b) => b.score - a.score)
+            .slice(0, 3)
+        : taxonomyCorpus.slice(0, 3);
+    const demoItems = rankedPillars.map(p => ({ ... }));
```

**Encabezado contextual:** Cuando `hasSearchContext`, añadir un banner sobre los prompts:  
*"Basado en tu búsqueda, mira cómo razona nuestra IA sobre estos escenarios."*

**Subtítulo aclaratorio (Mejora 2):** Cambiar el subtítulo de L88:
- Actual: *"Visualiza cómo nuestro ecosistema asiste en decisiones complejas..."*
- Nuevo: *"Demo del tipo de análisis que produce nuestra plataforma — no es el chatbot de consulta."*

**Puente al chatbot (Mejora 2):** Tras el bloque de output, añadir un enlace:
*"¿Tu caso no está en estos escenarios? Pregúntale al AI Concierge →"* que dispare `chatbotOpen.set(true)`.

---

### Mejora 1b — CTA encadenado en SemanticSearch tras resultados

#### [MODIFY] [SemanticSearch.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/SemanticSearch.jsx)

**Cambio:** Cuando `status === 'done'`, renderizar un bloque de CTA encadenado debajo del input (después de L229), con 3 botones de scroll anclado:

```jsx
{status === 'done' && (
  <div className="mt-4 bg-brand/10 border border-brandCyan/20 rounded-xl p-4 text-center">
    <p className="text-white text-sm mb-3">
      Encontramos <strong className="text-brandCyan">{serviceCount}</strong> soluciones relevantes para tu búsqueda.
    </p>
    <div className="flex flex-wrap justify-center gap-3">
      <button onClick={() => scrollTo('#copilot-section')} className="...">
        Ver cómo razona nuestra IA →
      </button>
      <button onClick={() => scrollTo('#sectores-grid')} className="...">
        Iniciar Diagnóstico con este contexto →
      </button>
      <button onClick={openChatbotWithQuery} className="...">
        Consultar con el AI Concierge →
      </button>
    </div>
  </div>
)}
```

- `serviceCount`: conteo de claves `service-*` en `semanticHighlight`.
- `scrollTo`: `document.querySelector(id).scrollIntoView({ behavior: 'smooth' })`.
- `openChatbotWithQuery`: importar `lastUserQuery` y `chatbotOpen`, setear ambos.
- **0-LLM intacto:** son eventos de navegación/scroll, no llamadas fetch.

> [!IMPORTANT]
> Se necesitan `id` estables en las secciones destino de `index.astro` (`id="copilot-section"`, `id="diagnostic-section"`) para que el scroll anclado funcione correctamente tras el reordenamiento (Mejora 4).

---

### Mejora 1c — Fix del resaltado de sectores en DiagnosticWizard

#### [MODIFY] [DiagnosticWizard.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/DiagnosticWizard.jsx)

**Bug:** `maxScore` se calcula con TODOS los valores del highlight map (incluyendo `service-*` que tienen score `1.0`), lo que sube `highlightThreshold` a `0.75`, inalcanzable para sectores cuyos scores son ~0.2–0.5.

**Fix:** Calcular `maxScore` solo con las claves de sector (excluyendo `service-*`):

```diff
- const maxScore = hasHighlights ? Math.max(...Object.values(highlights)) : 0;
+ // Solo scores de sectores (excluir service-*) para calcular el umbral
+ const sectorScores = Object.entries(highlights)
+     .filter(([k]) => !k.startsWith('service-'))
+     .map(([, v]) => v);
+ const maxScore = sectorScores.length > 0 ? Math.max(...sectorScores) : 0;
```

**Pre-llenado del input con `userChallenge`:** Importar `userChallenge` y, cuando haya valor, usarlo como valor por defecto de los inputs de desafío:

```diff
+import { userChallenge } from '../../store/index';
 ...
+const challenge = useStore(userChallenge);
 ...
 value={inputs[sector.id] || challenge || ''}
```

---

### Mejora 3 — Microcopy del hero por rol activo

#### [NEW] [HeroRoleLine.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/HeroRoleLine.jsx)

Isla React ligera (`client:load`) que lee `userContext` y, cuando hay un `rol` activo, muestra una micro-línea derivada de `personas.json`:

```jsx
import { useStore } from '@nanostores/react';
import { userContext } from '../../store/index';
import personas from '../../data/personas.json';

export default function HeroRoleLine() {
  const ctx = useStore(userContext);
  if (!ctx.rol) return null;

  const persona = personas.roles.find(r => r.id === ctx.rol);
  if (!persona) return null;

  const line = persona.goals?.[0] || persona.decisionCriteria?.[0] || '';
  return (
    <div className="mt-4 text-sm text-brandCyan/80 animate-in fade-in">
      Para el <strong>{persona.title}</strong>: {line}
    </div>
  );
}
```

#### [MODIFY] [Hero.astro](file:///C:/xampp/htdocs/datanestiq/src/components/ui/Hero.astro)

Añadir `<HeroRoleLine client:load />` debajo de `<ContextChips>`:

```diff
+import HeroRoleLine from '../islands/HeroRoleLine.jsx';
 ...
     <ContextChips client:load />
+    <HeroRoleLine client:load />
```

**Progressive enhancement:** Sin JS/contexto no renderiza nada (return null).

---

### Mejora 4 — Reordenamiento del embudo en index.astro

#### [MODIFY] [index.astro](file:///C:/xampp/htdocs/datanestiq/src/pages/index.astro)

**Orden objetivo (zonas del embudo):**

| Zona | Secciones | IDs |
|---|---|---|
| **Hero + Credibilidad** | Hero, TrustLayer | — |
| **EXPLORA** (describe tu problema) | SemanticSearch, Services, SolutionsByRoleAndIndustry, **DiagnosticWizard** (subido aquí) | `id="diagnostic-section"` |
| **PROFUNDIZA** (credibilidad técnica) | CopilotDemo | `id="copilot-section"` |
| **CONVIERTE** | MultiStepWizard | — |
| **Cierre** | Methodology, FAQ, Footer | — |

```diff
 <SemanticSearch ... />
 <Services ... />
 <SolutionsByRoleAndIndustry ... />
+<section id="diagnostic-section" ...>
+  <DiagnosticWizard ... />
+</section>
-<!-- DiagnosticWizard estaba aquí abajo, tras el Copiloto -->
+<section id="copilot-section" ...>
   <CopilotDemo ... />
+</section>
 <MultiStepWizard ... />
 <Methodology />
 <Faq />
```

> [!WARNING]
> Al mover `DiagnosticWizard` arriba del `CopilotDemo`, verificar que los scroll anchors del CTA encadenado (Mejora 1b) apunten a los IDs correctos. Los stores no dependen del orden DOM — funcionan por suscripción reactiva.

---

## Open Questions

1. **Subtítulo del CopilotDemo:** ¿Preferís la redacción propuesta (*"Demo del tipo de análisis que produce nuestra plataforma — no es el chatbot de consulta."*) o algo más premium?
2. **Microcopy por rol — contenido:** El microcopy usa `goals[0]` de la persona. ¿Preferís que muestre una combinación de `goals` + `decisionCriteria`, o solo el primer goal como línea de valor?

## Verification Plan

### Automated
- `npm run build` verde (38 páginas, consola limpia)
- `build-taxonomy.mjs` verde (taxonomía no se modifica)

### Manual
1. **Copiloto contextual:** Buscar "eficiencia" → los 3 escenarios del Copiloto son los de los pilares resaltados (no los fijos). Sin búsqueda → vuelve a los 3 por defecto.
2. **CTA encadenado:** Tras buscar, aparece "Encontramos N soluciones…" con 3 botones. "Diagnóstico" y "Copiloto" hacen smooth-scroll a su destino. "AI Concierge" abre el chatbot con la consulta cargada. Network: 0 fetch en el flujo guiado.
3. **Sectores reactivos:** Tras buscar "eficiencia", los sectores relevantes se resaltan (no se atenúan todos). Los inputs vienen pre-llenados con la consulta.
4. **Copiloto vs chatbot:** Subtítulo aclara que es demo; puente abre el chatbot.
5. **Microcopy por rol:** Elegir chip "CFO" muestra micro-línea del CFO (de taxonomía). Sin contexto → nada. Reset la quita.
6. **Orden:** DiagnosticWizard está en zona EXPLORA, CopilotDemo en zona PROFUNDIZA. Los anchors funcionan.
7. Sección "Hallazgos adicionales".
