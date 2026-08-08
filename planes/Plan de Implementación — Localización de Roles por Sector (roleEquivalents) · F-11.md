# Plan de Implementación — Localización de Roles por Sector (`roleEquivalents`) · F-11

Extiende **Spec 011 (enriquecimiento de taxonomía)** como fuente de datos y **Spec 013 / 002** como consumidores UI.

## Contexto y Diagnóstico

Actualmente, el sitio utiliza siglas corporativas anglosajonas (`cio`, `cto`, `cdo`, `cfo`, `ceo`, `coo`, `ciso`). En sectores como el **Sector Público** o instituciones tradicionales en Perú, los funcionarios no se identifican con siglas como "CIO/CDO" y seleccionan "Otro / Detallar" en el AI Concierge, perdiendo el ruteo contextualizado.

Este plan introduce el mapa opcional `roleEquivalents` a nivel de **Sector** (el sector es el dueño de cómo se nombran sus cargos), preservando la semántica y los `id` de las personas (`cfo`, `cio`, `cdo`, etc.), e integrando la localización en la UI sin hardcodear etiquetas en JSX/Astro.

---

## User Review Required

> [!IMPORTANT]
> **🔴 Propuesta de Equivalencias por Sector para Revisión:**
> 
> 1. **`publico.yaml` (Canónico - Estado Peruano):**
>    - `cio`: "Jefe / Director de TI (OTI)"
>    - `cto`: "Responsable de Infraestructura y Sistemas"
>    - `cdo`: "Director de Datos / Estadística e Informática"
>    - `cfo`: "Director de Administración y Finanzas"
>    - `ceo`: "Director General / Titular de la entidad"
>    - `coo`: "Secretario General"
>    - `ciso`: "Oficial de Seguridad de la Información"
> 
> 2. **`salud.yaml` (Sector Salud / Hospitalario):**
>    - `ceo`: "Director General / Director del Hospital"
>    - `cio`: "Jefe de la Unidad de Estadística e Informática"
>    - `cdo`: "Responsable de Gestión de la Información en Salud"
>    - `cfo`: "Director de Administración y Finanzas"
>    - `ciso`: "Oficial de Seguridad de la Información"
> 
> 3. **`educacion.yaml` (Instituciones Educativas / UGEL / Universidad):**
>    - `ceo`: "Director / Titular de la Institución (UGEL/DRE/Universidad)"
>    - `cio`: "Jefe / Coordinador de Informática"
>    - `cdo`: "Responsable de Estadística Educativa"
>    - `cfo`: "Director de Administración y Finanzas"
> 
> 4. **`manufactura.yaml` (Industria / Planta):**
>    - `cio`: "Gerente / Jefe de Sistemas (TI)"
>    - `cto`: "Gerente de Operaciones e Ingeniería"
>    - `cdo`: "Jefe de Analítica y Gestión de Datos"
>    - `coo`: "Gerente de Planta / Operaciones"
>    - `ciso`: "Jefe de Ciberseguridad e Infraestructura"
> 
> 5. **`mineria.yaml` (Operaciones Mineras):**
>    - `cio`: "Gerente / Jefe de TI y Telecomunicaciones"
>    - `cto`: "Gerente de Automatización e Ingeniería Mina"
>    - `cdo`: "Jefe de Analítica y Sistemas de Información"
>    - `coo`: "Gerente de Operaciones Mineras"
>    - `ciso`: "Jefe de Seguridad de la Información y Control"
> 
> 6. **Sectores que omiten `roleEquivalents` (Fallback a títulos C-Level globales):**
>    - `finanzas`, `retail`, `telecomunicaciones`, `seguros`, `logistica`.
>    - *Justificación:* Títulos corporativos como CFO, CIO, CDO son la jerga nativa estándar en la banca, e-commerce, telcos y logística enterprise bajo regulaciones SBS/Basilea.

---

## Proposed Changes

### 1. Esquema Zod y Validación de Integridad

#### [MODIFY] [schemas.js](file:///C:/xampp/htdocs/datanestiq/src/lib/schemas.js)
Añadir el campo opcional `roleEquivalents` a `sectorSchema`:
```javascript
roleEquivalents: z.record(
  z.enum(['cfo', 'cio', 'cdo', 'cto', 'coo', 'ciso', 'ceo']),
  z.string().min(1)
).optional()
```

#### [MODIFY] [build-taxonomy.mjs](file:///C:/xampp/htdocs/datanestiq/scripts/build-taxonomy.mjs)
Añadir validación de integridad referencial en la fase 3 de `build-taxonomy.mjs`:
- Verificar que cada clave en `roleEquivalents` sea un `roleId` válido en `personas.json`.
- En caso de clave inválida o string vacío -> `console.error` + `process.exit(1)`.

---

### 2. Poblamiento de Datos YAML

#### [MODIFY] [publico.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/publico.yaml)
#### [MODIFY] [salud.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/salud.yaml)
#### [MODIFY] [educacion.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/educacion.yaml)
#### [MODIFY] [manufactura.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/manufactura.yaml)
#### [MODIFY] [mineria.yaml](file:///C:/xampp/htdocs/datanestiq/src/content/sectors/mineria.yaml)

Agregar la sección `roleEquivalents` según la propuesta aprobada.

---

### 3. Helper de Localización en UI

#### [NEW] [roleLocalization.ts](file:///C:/xampp/htdocs/datanestiq/src/lib/roleLocalization.ts)
Crear función utilitaria reutilizable:
```typescript
/**
 * Resolves localized role title if sector has roleEquivalents for role.id.
 * Format: "<Etiqueta local> (<SIGLA>)" if localized title exists, else persona.title.
 */
export function getLocalizedRoleTitle(persona: any, sectorObj: any): string {
  if (!persona) return '';
  if (!sectorObj || !sectorObj.roleEquivalents || !sectorObj.roleEquivalents[persona.id]) {
    return persona.title;
  }
  const localTitle = sectorObj.roleEquivalents[persona.id];
  const acronym = persona.id.toUpperCase();
  if (localTitle.toLowerCase().includes(`(${persona.id})`) || localTitle.toLowerCase().includes(`(${acronym})`)) {
    return localTitle;
  }
  return `${localTitle} (${acronym})`;
}
```

---

### 4. Consumo en Componentes UI (0-LLM)

#### [MODIFY] [Chatbot.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- En la etapa de selección de rol (cuando ya hay un sector seleccionado `chatState.sector`):
  - Obtener `matchedSectorObj` de `sectorsCorpus`.
  - En los botones de selección de rol: renderizar `getLocalizedRoleTitle(roleObj, matchedSectorObj)`.
  - En los mensajes del bot y saludos heredados: mostrar la etiqueta localizada en lugar de la sigla seca.
  - Mantener la opción *"Otro / Detallar"*.
- **Guardarraíl 0-LLM:** Mutación de estado React y lectura de JSONs sin realizar llamadas HTTP.

#### [MODIFY] [HeroRoleLine.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/HeroRoleLine.jsx)
- Cuando `userContext` tenga `sector` y `rol` activos, buscar `matchedSectorObj` en `sectorsCorpus` y mostrar `getLocalizedRoleTitle(persona, matchedSectorObj)` en el badge del Hero.

#### [MODIFY] [SolutionsByRoleAndIndustry.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/SolutionsByRoleAndIndustry.jsx)
- Usar `getLocalizedRoleTitle` cuando haya un sector seleccionado en las pestañas/filtros.

---

## Verification Plan

### Automated Verification
1. **Verificación de Error de Integridad (Exit 1):**
   - Introducir temporalmente una clave inválida (ej. `roleEquivalents: { invalid_role: "Test" }`) en `publico.yaml`.
   - Ejecutar `node scripts/build-taxonomy.mjs` y confirmar que falla con **exit code 1**.
   - Remover la clave de prueba y verificar que pasa con **exit code 0**.
2. **Build de Producción:**
   - Ejecutar `npm run build` para asegurar compilación limpia de 38+ páginas estáticas.

### Manual Verification (Navegador)
1. **AI Concierge — Sector Público:**
   - Seleccionar "Sector Público" en el Chatbot y verificar que las opciones de rol muestren *"Jefe / Director de TI (OTI)"*, *"Director de Datos / Estadística e Informática (CDO)"*, etc.
   - Confirmar que la opción *"Otro / Detallar"* sigue presente.
2. **AI Concierge — Finanzas (Fallback):**
   - Seleccionar "Finanzas y Banca" y verificar que las opciones muestren los títulos C-Level globales (CFO, CIO, CDO...).
3. **HeroRoleLine:**
   - Activar el chip "Sector Público" en el Hero y verificar que el badge muestre la etiqueta localizada.
4. **Inspección de Red (0-LLM):**
   - Confirmar 0 llamadas `POST /api/chat.php` en el flujo guiado.
