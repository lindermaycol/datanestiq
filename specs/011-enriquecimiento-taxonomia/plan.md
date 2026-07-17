# Plan de Implementación: Spec 011 (Enriquecimiento de Taxonomía)

## 1. Diseño y Modificación de Zod Schemas (`src/lib/schemas.js`)
El primer paso será actualizar los esquemas de Zod de manera aditiva.

### A. Sector Schema (`sectorSchema`)
Nuevos campos opcionales:
- `subSectors`: array de strings.
- `kpis`: array de objetos `{ metric: string, expectedRoi: string }`.
- `regulations`: array de strings (leyes o normativas aplicables).
- `contentAngles`: array de objetos `{ id: string, title: string, brief: string }`.

### B. Persona Schema (`personaSchema`)
Nuevos campos opcionales en cada `role`:
- `buyerRole`: enum (`economic`, `technical`, `user`).
- `objections`: array de strings (objeciones de ventas frecuentes).
- `decisionCriteria`: array de strings.
- `triggers`: array de strings (eventos que provocan la compra).
- `contentAngles`: array de objetos `{ id: string, title: string, brief: string }`.

### C. Pillar Schema (`pillarSchema`)
Nuevos campos opcionales:
- `techStack`: array de strings.
- `proofPoints`: array de objetos `{ client: string, result: string }`.
- `contentAngles`: array de objetos `{ id: string, title: string, brief: string }`.

### D. Nuevo Schema (Opcional en esta fase): Benchmarking
- Por simplicidad de inyección, se puede crear una colección `benchmarks.yaml` o anexar un campo `competitivePositioning` en los pilares. Lo haremos como campo `competitivePositioning` en `pillarSchema`.

## 2. Enriquecimiento del Contenido YAML
Una vez que el esquema valida los nuevos campos, procederemos a enriquecer manualmente (o mediante procesamiento de IA seguro) al menos:
- 1 Sector (`salud.yaml` o `gobierno.yaml`).
- 1 Persona (`cio` u `operaciones`).
- 1 Pilar.

Se incluirán marcas para separar estimaciones de datos reales (ej. usando prefijos `[EST]`).

## 3. Validación y Pruebas E2E
1. Inyectar un campo de error intencional (integridad referencial) para verificar que `scripts/build-taxonomy.mjs` lo detecte (Falla `exit 1`).
2. Remover el error y compilar con `npm run build` (Asegurar No-Regresión).
3. Prueba del Flujo de Generación de Blog: 
   - Ejecutar un script CLI o bash que extraiga un `brief` desde `contentAngles` del YAML enriquecido.
   - Pasárselo a `node scripts/docs-generator.mjs --target=blog --brief="<el brief extraido>"`.
   - Verificar la creación correcta del post.

## Decisión de Numeración
La Spec 003 se centraba en la transición técnica de JSON a YAML y Content Collections (Astro). Dado que la 003 ya está consolidada y cumplió su ciclo de desarrollo estructural, la evolución del modelo ontológico y la sinergia comercial con el LLM del blog es lo suficientemente grande como para justificar una Especificación Independiente (Spec 011). Mantendrá separada la estructura (003) del contenido profundo (011).
