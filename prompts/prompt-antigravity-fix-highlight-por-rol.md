# Prompt para Antigravity: Fix — "Soluciones por Rol" no resalta las tarjetas de servicio

La auditoría E2E de Claude (Sonnet 5) encontró que el componente `SolutionsByRoleAndIndustry.jsx` (Taxonomía v3) **no resalta visualmente los pilares** al hacer clic en un rol, aunque el reporte lo afirmaba. El resto del componente (pestañas, roles con meta/dolor, scroll) funciona.

## Causa raíz (verificada)
`handleRoleClick` hace:
```js
role.pillarsOfInterest.forEach(p => { highlightMap[`service-${p}`] = 1.0; });
semanticHighlight.set(highlightMap);   // ← nadie consume claves service-*
```
El nanostore `semanticHighlight` **solo lo consume `DiagnosticWizard.jsx`** para sus tarjetas de **sector** (claves `sector-*`). Las tarjetas de **servicio** (`Services.astro`, estáticas) **NO están suscritas** a ese store; solo las estiliza `SemanticSearch.jsx` mediante manipulación **directa del DOM** (`document.querySelectorAll('.service-card')` → opacity/scale/borde). Por eso al clic en un rol se hace scroll a `#services` pero las tarjetas quedan sin diferenciar.

## Corrección
En `handleRoleClick` de `src/components/islands/SolutionsByRoleAndIndustry.jsx`, **replica el mecanismo de resaltado por DOM** que ya usa `SemanticSearch.jsx` en su `handleResults` (fuente única de la lógica), aplicándolo a las `.service-card` según los `pillarsOfInterest` del rol:
```js
const handleRoleClick = (role) => {
    setSelectedRole(role);
    const interest = new Set(role.pillarsOfInterest); // slugs de pilar

    const servicesSection = document.getElementById('services');
    if (servicesSection) servicesSection.scrollIntoView({ behavior: 'smooth' });

    // Resaltar por DOM las tarjetas de servicio de interés, atenuar el resto
    const cards = document.querySelectorAll('.service-card');
    cards.forEach(card => {
        const slug = card.getAttribute('data-service-id');
        if (interest.has(slug)) {
            card.style.opacity = '1';
            card.style.transform = 'scale(1.02)';
            card.style.borderColor = 'var(--brand-cyan)';
            card.style.boxShadow = '0 0 20px rgba(0, 240, 255, 0.1)';
        } else {
            card.style.opacity = '0.3';
            card.style.transform = 'scale(0.98)';
            card.style.borderColor = 'rgba(255,255,255,0.1)';
            card.style.boxShadow = 'none';
        }
    });
};
```
Notas:
- Usa **los mismos estilos exactos** que `SemanticSearch.handleResults` para consistencia visual (opacity 1 / 0.3, scale 1.02 / 0.98, borde brand-cyan). Si esos valores están centralizados en una función, reutilízala; si no, considéralo (evita duplicar la lógica en dos sitios; una util compartida sería ideal).
- Puedes **mantener** el `semanticHighlight.set(highlightMap)` adicionalmente si quieres que también resalte sectores relacionados en el Wizard, pero el resaltado de las **tarjetas de servicio** debe hacerse por DOM (arriba), porque el store no las alcanza.
- **Reset:** provee una forma de limpiar el resaltado (ej. al cambiar de pestaña a "Industria" o al deseleccionar), reutilizando el `clearSearch`/reset de estilos que ya existe en `SemanticSearch.jsx` (opacity 1, transform none, borde/sombra vacíos), para no dejar tarjetas atenuadas permanentemente.

## (Opcional, menor) Pestaña "por Industria"
Hoy `handleIndustryClick` solo hace focus/scroll al buscador. Es aceptable, pero mejora la UX **prefilando** el buscador con el nombre del sector (o disparando la búsqueda) para que el usuario vea resultados de inmediato. Si lo haces, no rompas el flujo del buscador semántico.

## Verificación (E2E con evidencia)
En `npm run dev`:
1. Home → pestaña "Soluciones por Rol" → clic en **CFO**: confirma (DOM) que las `.service-card` de `pillarsOfInterest` del CFO (`business-intelligence`, `estrategia-datos-ia`) quedan **resaltadas** (scale 1.02, borde cyan) y el resto **atenuadas** (opacity 0.3), y que hubo scroll a `#services`.
2. Clic en otro rol (CIO) → el resaltado cambia a sus pilares.
3. Cambiar a pestaña "Industria" o reset → las tarjetas vuelven a su estado normal (sin atenuación residual).
4. **No-regresión:** el buscador semántico sigue resaltando correctamente (su `handleResults` no se rompió); chatbot guiado = 0 llamadas.
5. `npm run build` sin errores.

## Forma de respuesta
- Reporta con evidencia DOM del resaltado por rol (qué tarjetas quedan resaltadas/atenuadas para CFO vs CIO).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) re-ejecutará el E2E: clic en CFO debe resaltar sus pilares de interés en las tarjetas de servicio (no solo hacer scroll).
