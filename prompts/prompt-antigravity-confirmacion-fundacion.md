# Confirmación — Fundación (Profundidad de páginas + hubs + legal): LUZ VERDE con 1 corrección 🔴 + 3 precisiones → ejecutar

Revisé el plan (`planes/Plan de Implementación Profundidad de Páginas y Navegación (SDD).md`). Buen posicionamiento como **consumidor puro** (no tocas datos de la taxonomía). Verifiqué tus supuestos contra el código real: **el bloque de `techStack`/`competitivePositioning`/`proofPoints` ya está cableado en `src/pages/soluciones/[id].astro`**, así que el enriquecimiento de F3 fluirá solo (tu supuesto era correcto). **Procede** con esta corrección y precisiones:

## 🔴 Corrección — el bloque de objeciones en páginas de SOLUCIÓN no puede usar `relatedRoles`
Tu plan mapea `relatedRoles` en los pilares. **Ese campo NO existe** (el schema del pilar tiene `relevantPersonas`) **y además está vacío** en los YAML de pilares. Verificado. Construir el bloque tal como está descrito **fallaría / quedaría vacío**. Usa esta mecánica en su lugar:

- **Páginas de solución (`/soluciones/[id].astro`) → INVIERTE la relación:** para el pilar actual, filtra las personas cuyo **`pillarsOfInterest`** incluya el `id` del pilar, y renderiza sus `objectionResponses`. Este campo **sí está 100% poblado** (verificado: cfo→BI/estrategia; cio→data-engineering/sistemas-digitales; etc.). No necesitas modificar datos.
- **Páginas de sector (`/sectores/[slug].astro`) → directo:** usa **`sector.relevantPersonas`** (poblado, 3 por sector: p. ej. finanzas→cfo/ciso/cdo) y renderiza sus `objectionResponses`. Es el mismo campo que ya consume el flujo guiado del chatbot, así que es fiable.
- En ambos casos muestra: **rol (título de la persona) · su objeción · nuestra respuesta** — un FAQ consultivo honesto. Deduplica si una persona aparece por varias vías.

> Si en algún momento decides que sería más limpio tener `pillar.relevantPersonas` explícito, eso es **capa de datos** (Spec 011 Fase 3) y no lo autoríes aquí: repórtalo. Para esta ronda, la inversión por `pillarsOfInterest` es suficiente y no requiere tocar datos.

## Precisión 1 — Corrige "Gaps detectados: Ninguno" (hay uno de datos, conocido)
En mi auditoría de F3 quedó **un gap de datos**: `estrategia-datos-ia` **no menciona "catálogo de datos"** (sí linaje/stewardship/calidad/interoperabilidad/metadatos/gobernanza). No es tu trabajo autorarlo (eres consumidor), pero **no lo declares como "sin gaps"**: repórtalo para que se resuelva en la capa de datos. Si ya se aplicó el retoque de catálogo en F3, verifica que aparezca; si no, la página de esa solución saldrá sin esa noción.

## Precisión 2 — Blog Sector Público: evita duplicar con la Spec 013 Fase 1
La Spec 013 Fase 1 también generará un **cluster de blog público (4–6 artículos)**. Para no duplicar: aquí genera **solo 2 posts** de temas **distintos** (p. ej. "interoperabilidad institucional" y "gobierno del dato en el Estado"), y **anótalos** para que la Fase 1 haga *top-up* sin repetir tema. No cubras los 6 temas aquí.

## Precisión 3 — Páginas legales: honestidad
`/privacidad` y `/terminos` con "contenido de borrador profesional" está bien, pero **márcalas visiblemente como borrador/plantilla** (no afirmes cumplimientos legales específicos ni cláusulas vinculantes que no han sido revisadas). El objetivo es eliminar el `href="#"` muerto y dar seriedad, no simular un documento legal validado.

## Confirmaciones que ya validé (no re-audites)
- `[id].astro` ya renderiza `techStack`/`competitivePositioning`/`proofPoints` ✅
- Hubs vs catch-all `[...slug].astro`: tu mitigación (carpetas + `index.astro`) es correcta; `/soluciones/index.astro` gana por especificidad. Confirma que el catch-all no genere también `/soluciones` o `/sectores`.
- `deploymentModels`/`engagementModels` solo existen en Público → tu optional chaining (`{sector.engagementModels && …}`) es lo correcto.

## Verificación (evidencia real al reportar)
1. `grep` en `dist/`: `/soluciones/sistemas-digitales` muestra arquitectura (cloud/híbrido/on-prem/legacy/TCO/uptime); `/soluciones/estrategia-datos-ia` muestra gobierno del dato.
2. Bloque "Resolvemos tus dudas" **renderiza objeciones reales** en al menos una página de solución (vía `pillarsOfInterest`) y una de sector (vía `relevantPersonas`), con rol+objeción+respuesta.
3. `/soluciones` y `/sectores` existen, están en el **nav** y en el **sitemap**; sin rutas duplicadas.
4. `/privacidad` y `/terminos` resuelven (200); **cero `href="#"`** en el footer.
5. 2 posts de blog público (temas distintos, anotados para la Fase 1); `npm run build` **verde**; islas (chatbot 0-LLM, wizard, buscador) intactas; consola limpia.
6. Sección final "Hallazgos adicionales".

## Recordatorio de pipeline
Eres **consumidor**: renderiza/compón, **no autoríes datos**. Tras tu implementación yo audito en el navegador (servido por PHP). Luego sigue la **Spec 013** (capa consultiva) encima de esta fundación.

---
**Nota:** Claude (Opus 4.8) auditará en `dist/` + navegador que las páginas rendericen la profundidad y las objeciones desde la taxonomía (vía la mecánica correcta, no `relatedRoles`), que hubs/legal/nav funcionen y que las islas queden intactas.
