# Tareas: Spec 011 (Enriquecimiento de Taxonomía)

## Fase 1: Modificación Estructural (Schemas)
- `[ ]` Actualizar `src/lib/schemas.js` para añadir `subSectors`, `kpis`, `regulations` y `contentAngles` en `sectorSchema` (como opcionales).
- `[ ]` Actualizar `src/lib/schemas.js` para añadir `buyerRole`, `objections`, `decisionCriteria`, `triggers` y `contentAngles` en `personaSchema`.
- `[ ]` Actualizar `src/lib/schemas.js` para añadir `techStack`, `proofPoints`, `competitivePositioning` y `contentAngles` en `pillarSchema`.

## Fase 2: Enriquecimiento Controlado (YAML)
- `[ ]` Enriquecer 1 Sector (`src/content/sectors/`) con datos reales, KPIs marcados con `[EST]` si son estimaciones y al menos un `contentAngle`.
- `[ ]` Enriquecer 1 Persona (`src/data/personas.json` o su equivalente YAML si fue migrado) con `buyerRole`, objeciones y `contentAngle`.
- `[ ]` Enriquecer 1 Pilar con el posicionamiento vs. Big Four/Tech (Gartner/Oracle).

## Fase 3: Pruebas y Validación (Integridad)
- `[ ]` Test negativo: introducir error de llave foránea a propósito y confirmar que `node scripts/build-taxonomy.mjs` aborte con `exit 1`.
- `[ ]` Ejecutar `node scripts/build-taxonomy.mjs` con data limpia y confirmar regeneración exitosa de JSON.
- `[ ]` Validar no-regresión en Astro UI corriendo `npm run build`.

## Fase 4: Sinergia de Blog (Spec 010)
- `[ ]` Extraer mediante script o manualmente el `brief` del nuevo `contentAngles` creado en la Fase 2.
- `[ ]` Llamar a `node scripts/docs-generator.mjs --target=blog --brief="..."`.
- `[ ]` Confirmar la creación del nuevo borrador del blog fundamentado en la taxonomía enriquecida.
