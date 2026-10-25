# Prompt para Antigravity: Enriquecimiento v3 — benchmarking multi-líder + cobertura total de industrias + personas + taxonomía en las PÁGINAS del sitio

Actúa como **arquitecto de taxonomías B2B y estratega de contenido**. Entrega primero un breve **plan** para mi revisión y luego implementa. Va después del enriquecimiento avanzado y antes de la migración a Content Collections.

## Benchmarking (investigado por Claude / Sonnet 5 sobre los líderes del mercado)
Se estudió cómo estructuran su taxonomía de soluciones las firmas top. Aprendizajes clave a incorporar:

- **IBM** (~19 industrias) y **Oracle** (~22 industrias): organizan por **Industria**, con modelos/arquitecturas de referencia por vertical. → Nos falta cobertura de industrias.
- **Microsoft (Azure/Fabric):** organiza explícitamente **por ROL** (data engineer, data scientist, data analyst, business user) × capacidad (BI, Data Engineering, Data Integration, Lakehouse/Warehousing, Data Science) × industria. → Fuerte modelo "por rol/persona".
- **Deloitte:** soluciones **sector-specific** con **modelos de industria, taxonomías regulatorias, arquitecturas de referencia de dominio y glosarios de negocio** por vertical (ej. banca: riesgo/finanzas/compliance). → Enriquecer cada sector con referencias de dominio.
- **Gartner — Modelo de Madurez Analítica:** el **value ladder** de 4 niveles: **Descriptivo → Diagnóstico → Predictivo → Prescriptivo** ("¿qué pasó?" → "¿por qué?" → "¿qué pasará?" → "¿qué hacer?"). Solo ~13% de organizaciones llegan a prescriptivo. → Marco potente para posicionar servicios y para el Puntaje de Madurez del wizard.

**Conclusión estructural:** los líderes cruzan **4 ejes**: Industria × Rol/Persona × Necesidad/Capacidad × Madurez. Datanestiq hoy tiene 2 (Pilares × Sectores). Este ciclo añade **Industria extendida**, **Persona/Rol** y el **eje de Madurez (Gartner)**, y los refleja en las **páginas del sitio**.

## Restricción CRÍTICA — cero rupturas
Buscador semántico, chatbot guiado (0 llamadas), wizard, copilot y páginas existentes deben seguir funcionando. Verifica E2E.

---

## PARTE 1 — Cobertura total de industrias (searchable, no todas mostradas)

Cualquier industria que un usuario escriba debe hacer match y **enrutar a los pilares/soluciones relevantes**, aunque no tenga tarjeta.

1. **[NEW] `src/data/extendedIndustries.json`** — corpus ampliado searchable-only (NO se renderiza como tarjeta), ~15–20 industrias que completan la economía (base: listas de IBM/Oracle + secciones CIIU). Cada entrada:
   ```json
   { "id": "industry-agricultura", "title": "Agricultura y Agroindustria",
     "ciiu": "01", "keywords": ["agro", "cultivos", "riego", "cadena de frío", "trazabilidad agrícola"],
     "relatedPillars": ["ai-data-science", "data-engineering", "hiperautomatizacion"] }
   ```
   Industrias a incluir (ajusta/completa desde IBM/Oracle): Agricultura & Agroindustria, Energía & Utilities, Petróleo & Gas, Química, Construcción & Ingeniería, Automotriz, Aeroespacial & Defensa, Medios & Entretenimiento, Turismo & Hotelería, Bienes de Consumo (CPG), Alimentos & Bebidas, Farmacéutica & Life Sciences, Servicios Profesionales, Real Estate/Inmobiliario, Transporte (aéreo/marítimo), Textil/Moda, Pesca, ONG/Tercer Sector.

2. **[MODIFY] `SemanticSearch.jsx`** — incluye `extendedIndustries.json` en el corpus (prefijo `industry-`). En `handleResults`, si el mejor match es un `industry-*` (sin tarjeta), **resalta sus `relatedPillars`**. Muestra un mensaje sutil: "Para tu industria (Agricultura), estas soluciones aplican:". Mantén el comportamiento actual para `service-*` y `sector-*`.

3. No agregues tarjetas nuevas al Home; las 10 verticales destacadas siguen igual. Esto es solo cobertura de **búsqueda**.

## PARTE 2 — Taxonomía de Personas / Tipos de Cliente (eje "por Rol", estilo Microsoft)

**[NEW] `src/data/personas.json`** — comité de compra B2B + tipos de organización:
```json
{
  "roles": [
    { "id": "cfo", "title": "CFO / Director Financiero", "goals": ["ROI medible","control de costos","forecasting","gestión de riesgo"], "pains": ["decisiones sin datos confiables","cierres contables lentos"], "pillarsOfInterest": ["business-intelligence","estrategia-datos-ia"] },
    { "id": "cio", "title": "CIO", "goals": ["uptime","seguridad","reducción de costos IT","compliance"], "pains": ["sistemas legacy","silos"], "pillarsOfInterest": ["data-engineering","sistemas-digitales"] },
    { "id": "cdo", "title": "CDO / Chief Data Officer", "goals": ["valor del dato","gobernanza","calidad de datos"], "pains": ["datos fragmentados","baja calidad"], "pillarsOfInterest": ["data-engineering","ai-data-science","estrategia-datos-ia"] },
    { "id": "cto", "title": "CTO", "goals": ["escalabilidad","arquitectura","innovación"], "pains": ["deuda técnica"], "pillarsOfInterest": ["sistemas-digitales","ai-data-science"] },
    { "id": "coo", "title": "COO / Director de Operaciones", "goals": ["eficiencia operativa","automatización"], "pains": ["procesos manuales"], "pillarsOfInterest": ["hiperautomatizacion","business-intelligence"] },
    { "id": "ciso", "title": "CISO", "goals": ["seguridad","gestión de riesgo","compliance"], "pains": ["superficie de ataque","fuga de datos"], "pillarsOfInterest": ["estrategia-datos-ia","data-engineering"] }
  ],
  "orgTypes": [
    { "id": "corporacion", "title": "Gran Corporación / Enterprise" },
    { "id": "gobierno", "title": "Gobierno / Sector Público" },
    { "id": "mid-market", "title": "Mediana Empresa" },
    { "id": "scaleup", "title": "Startup en Escalamiento" }
  ]
}
```
Ajusta/expande con criterio (CEO, CMO, VP de línea de negocio si aportan).

## PARTE 3 — Eje de Madurez Analítica (Gartner) en la taxonomía

Añade a cada pilar en `taxonomyCorpus.json` un campo `maturityStage` (o `valueLadder`) que ubique la capacidad en el value ladder de Gartner: **descriptivo / diagnóstico / predictivo / prescriptivo** (ej. BI & Dashboards → descriptivo/diagnóstico; AI & Data Science → predictivo/prescriptivo; Estrategia & Governance → transversal/habilitador). Esto:
- Da sustento real al **Puntaje de Madurez** del `MultiStepWizard` (mapea las respuestas a un nivel Gartner y recomienda el pilar del siguiente escalón).
- Permite comunicar en las páginas "dónde estás vs. dónde puedes llegar".

## PARTE 4 — Reflejar la taxonomía en las PÁGINAS del sitio (usando IBM/Oracle/Microsoft/Deloitte/Gartner como referencia)

Este es el foco nuevo: que la estructura taxonómica se **vea y navegue** en el sitio, como en los líderes.

### 4.1 Páginas de pilar `/soluciones/[id]` — enriquecer con los ejes
Además del "Ideal para:" (targetRoles) y "Marcos de Trabajo:" (standards) que ya existen, añade de forma sobria:
- **Nivel de madurez (Gartner):** una franja/etiqueta que indique en qué escalón del value ladder ubica esta capacidad ("Lleva tu organización de lo Descriptivo a lo Predictivo").
- **Aplicaciones por industria (estilo IBM/Oracle):** una sección "Aplicaciones por industria" que liste 3–5 sectores con un caso de uso de una línea cada uno (tomados de `sectorsCorpus`/`extendedIndustries`) — cross-linking hacia esas industrias.
- **Para quién (estilo Microsoft "por rol"):** breve bloque de los roles/personas que más se benefician (desde `personas.json`, con su meta/dolor).

### 4.2 Home — navegación por ejes (estilo Oracle/IBM "Solutions by…")
Añade dos accesos de navegación (secciones o pestañas ligeras), sin romper el diseño premium actual:
- **"Soluciones por Industria"** — grid/lista de las 10 verticales (y enlace a "ver todas las industrias" que abra el buscador semántico para el long-tail).
- **"Soluciones por Rol"** — accesos por persona (CFO, CIO, CDO…) que filtren/enlacen a los pilares de su `pillarsOfInterest`.
Reutiliza `.glass-card` y el sistema de diseño; que sea aditivo y coherente, no un rediseño.

### 4.3 Página "Nosotros" / Metodología — el value ladder como narrativa
Refuerza en `nosotros.astro` o la sección de Metodología el marco de madurez (Gartner) y los estándares (DAMA-DMBOK/CRISP-DM) como prueba de rigor consultivo — el "cómo trabajamos", al estilo de las páginas de capacidades de Deloitte.

### 4.4 (Cuando existan) Landing de sector — referencias de dominio (estilo Deloitte)
Nota para el prompt 003 (que creará `/sectores/[id]`): cada landing de sector debe incluir referencias de dominio (retos regulatorios/operativos típicos del sector, ej. Finanzas → scoring, fraude, RegTech; Salud → EHR, triaje) y su código CIIU, como hace Deloitte con sus taxonomías regulatorias por vertical. Déjalo documentado como requisito de esas páginas.

---

## PARTE 5 — Documentación
Actualiza `specs/003-taxonomia-servicios/tech_debt.md`: registra la cobertura extendida de industrias, el eje de personas, el eje de madurez (Gartner) y el reflejo en páginas. **Anota que el schema Zod de Content Collections debe contemplar `extendedIndustries`, `personas`, `maturityStage` y los ejes de navegación.** Referencia el benchmarking multi-líder (fuentes al pie).

## Verificación (E2E, con evidencia)
1. `npm run build` sin errores; conteo de páginas estable (o +las nuevas secciones/navegación).
2. **Cobertura de industria:** busca "trazabilidad en agricultura" o "eficiencia en plantas de energía" → resalta los **pilares relevantes** (no queda sin resultados).
3. **Páginas de pilar:** `/soluciones/[id]` muestra el nivel de madurez, aplicaciones por industria y roles.
4. **Home:** aparecen "Soluciones por Industria" y "por Rol" y enlazan correctamente.
5. **No-regresión:** Logística sigue resaltándose; chatbot guiado = 0 llamadas; wizard/copilot OK.
6. Coherencia de datos: `personas.pillarsOfInterest`, `industry.relatedPillars` y `pillar.maturityStage` referencian pilares válidos.

## Forma de respuesta
- Plan primero (industrias extendidas, personas, maturityStage por pilar, y bocetos de las secciones "por Industria/por Rol") para mi revisión, luego implementa.
- Reporta la verificación E2E con evidencia.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Fuentes del benchmarking:**
- [IBM Industries](https://www.ibm.com/industries)
- [Oracle Industry Cloud Solutions](https://www.oracle.com/industries/)
- [Microsoft Azure — Data Analytics & AI](https://azure.microsoft.com/en-us/solutions/data-analytics-ai/) · [Microsoft Fabric (roles)](https://azure.microsoft.com/en-us/blog/introducing-microsoft-fabric-data-analytics-for-the-era-of-ai/)
- [Deloitte — Artificial Intelligence & Data](https://www.deloitte.com/global/en/services/consulting/services/artificial-intelligence-and-data.html)
- [Gartner Analytics Maturity Model (descriptivo→prescriptivo)](https://digital.ai/catalyst-blog/it-decision-making-through-the-lens-of-gartners-analytics-maturity-model/)

**Nota:** Claude (Sonnet 5) auditará que la taxonomía se refleje en las páginas (madurez, por industria, por rol), que una búsqueda de industria fuera de las 10 resalte los pilares correctos, y que nada se rompa.
