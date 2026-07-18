# Prompt para Antigravity — Arreglar de verdad el highlight por contexto (sigue roto)

Reauditoría: **9 de 10 ítems quedaron bien** (10 sectores + scroll, IDs reales, top-2, píldora de reset, lead card con `organizacion`/`reto`/`stack` sin tocar `save_wizard.php`, `calidad_padrones` con `solution`, personas honesto, 3 blogs `[EST]`, builds verdes). **Pero el bug estrella sigue vivo.**

## 🔴 Evidencia: el highlight NO aplica
Prueba en navegador: con las `.service-card` visibles, hice clic en el chip **"Finanzas / CFO"** → **las 6 tarjetas quedaron en `opacity: 1`, cero atenuadas**. Tu afirmación *"ilumina BI y estrategia-datos-ia y atenúa el resto"* **no ocurre**. El fix de IDs y el top-2 son correctos, pero el efecto visual nunca aterriza.

## Causa raíz (dos problemas que se suman — verificados)

**1. El subscriber vive en una isla hidratada perezosamente.**
- `ContextChips` → `<ContextChips client:load />` (en `Hero.astro`, se hidrata de inmediato).
- El `useStore(semanticHighlight)` + `useEffect` que pinta/atenúa `.service-card` está **dentro de `SemanticSearch.jsx`** → `<SemanticSearch client:visible />` (`index.astro` línea 39).
- Al hacer clic en el chip (arriba del todo), **`SemanticSearch` todavía no está montado** → nadie escucha el store → el `semanticHighlight.set()` cae al vacío.

**2. GSAP pisa el atenuado.**
- Cada `.service-card` lleva `data-animate="fade-up"` y `style="opacity: 0"`; el script GSAP anima **todas** a `opacity: 1` con ScrollTrigger.
- El highlight escribe `opacity` **inline** (`0.3`), y GSAP también escribe `opacity` inline → **GSAP gana** y borra el atenuado.

**3. (Olor de diseño) La lógica está duplicada en 3 sitios:** `SemanticSearch.handleResults`, `SolutionsByRoleAndIndustry.handleRoleClick` y el subscriber. Cada productor manipula el DOM por su cuenta y el store queda casi decorativo.

## Fix requerido

**A. Un solo consumidor, siempre montado.**
Extrae la aplicación del highlight a **un único punto que esté siempre vivo** — p. ej. un componente mínimo `<HighlightSync client:load />` en el layout/home (o muévelo a `ContextChips`, que ya es `client:load`). Ese consumidor:
- se suscribe a `semanticHighlight` (`useStore`),
- aplica resaltado/atenuado a `.service-card` según el mapa,
- y **es el ÚNICO** que toca el DOM de esas tarjetas.

**B. Los productores solo SETEAN el store (no tocan el DOM).**
Refactoriza para que `SemanticSearch.handleResults`, `SolutionsByRoleAndIndustry.handleRoleClick` y `ContextChips` **solo hagan `semanticHighlight.set(...)`**. Elimina sus manipulaciones directas de `.service-card` (y `resetServiceCards` → `semanticHighlight.set({})`). Así el store pasa a ser la única fuente de verdad del resaltado y desaparece la triplicación.
> `SolutionsByRoleAndIndustry` hoy **importa** `semanticHighlight` pero **no lo usa** — ese import muerto es señal del problema.

**C. Deja de pelear con GSAP por el `opacity` inline.**
No uses estilos inline para atenuar. Usa **clases CSS** que GSAP no pueda pisar:
```css
.service-card.is-dimmed { opacity: .3 !important; transform: scale(.98); }
.service-card.is-highlighted { border-color: var(--brand-cyan); box-shadow: 0 0 20px rgba(0,240,255,.1); transform: scale(1.02); }
```
(Una regla de hoja de estilos con `!important` **sí** gana a un `style="opacity:1"` inline de GSAP.) El consumidor solo hace `classList.toggle('is-dimmed'/'is-highlighted')`. Verifica que la animación de entrada de GSAP siga viéndose bien.

## Guardarraíles
- **No rompas** lo que ya está OK: 10 sectores + scroll, top-2, píldora de reset, lead card, `save_wizard.php` intacto, blogs.
- **0-LLM**: el flujo guiado del chatbot sigue sin `fetch`.
- El sitio debe funcionar **sin contexto elegido** (estado neutro, sin nada atenuado).
- `npm run build` verde; consola limpia.

## Verificación (evidencia real — esta vez pruébalo de verdad)
1. **Sin scroll previo**: cargar el home, hacer clic en "Finanzas / CFO" **desde el hero** y comprobar que **inmediatamente** se resaltan `business-intelligence` + `estrategia-datos-ia` y **se atenúan las otras 4**. Pega el conteo real (`resaltadas` vs `atenuadas`) — antes daba 6/0.
2. **Tras scroll (GSAP ya animó)**: el atenuado **persiste** (GSAP no lo pisa).
3. **Reset**: la ✕ de la píldora vuelve todo a neutro (nada atenuado) y reaparecen los chips.
4. **Buscador semántico y "Soluciones por Rol"**: siguen resaltando igual que antes (ahora vía el store, sin DOM propio).
5. `npm run build` verde; consola limpia; sección "Hallazgos adicionales".

---
**Nota:** Claude (Opus 4.8) reauditará en navegador midiendo `opacity`/clases de las 6 `.service-card` tras el clic del chip **sin scroll previo**. El criterio es binario: si no se atenúa ninguna, sigue roto.
