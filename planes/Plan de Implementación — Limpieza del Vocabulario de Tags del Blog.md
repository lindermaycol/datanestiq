# Plan de Implementación — Limpieza del Vocabulario de Tags del Blog

Extiende **Spec 007 (multi-página) / Spec 012 (fábrica de contenido)**. Define un **vocabulario controlado** (canónico y consistente en Title Case / Siglas en Español) y normaliza el frontmatter `tags:` de los archivos Markdown en `src/content/blog/*.md`.

---

## Contexto y Diagnóstico

Actualmente, los 16 artículos del blog (11 activos + 5 borradores) utilizan 45 variaciones de etiquetas con inconsistencias de formato (`kebab-case`, `Title Case`, tildes y duplicados semánticos como `gobernanza-datos`, `gobernanza-de-datos` y `Gobierno del Dato`).

Esto generaba 45 páginas de tag estáticas, de las cuales muchas contenían únicamente 1 artículo. Al normalizar a un **vocabulario controlado de 18 etiquetas canónicas**, reduciremos el ruido SEO, ampliaremos la cobertura de artículos por etiqueta (idealmente ≥2 artículos por tag activo) y mantendremos entre 3 y 5 tags relevantes por artículo.

---

## User Review Required

> [!IMPORTANT]
> **🔴 Propuesta de Vocabulario Controlado (18 Tags Canónicos):**
> 
> 1. **Roles C-Level:** `CFO`, `CIO`, `CDO`, `CEO`
> 2. **Sectores:** `Sector Público`, `Finanzas y Banca`, `Salud`, `Educación`
> 3. **Temas / Soluciones:** `Estrategia de Datos`, `Gobierno del Dato`, `Ingeniería de Datos`, `Inteligencia Artificial`, `Business Intelligence`, `Analítica Predictiva`, `Interoperabilidad`, `Compliance`, `Soberanía de Datos`, `ROI`
> 
> *Comprobación de Slugs:* Todos los tags generan slugs URL-safe únicos mediante `slugifyTag` (ej. `sector-publico`, `gobierno-del-dato`, `finanzas-y-banca`), garantizando 0 colisiones de rutas.

---

### Tabla de Mapeo Completa (Tag Actual → Tag Canónico Propuesto)

| Tag Actual (Frontmatter .md) | Tag Canónico Propuesto | Categoría / Justificación |
|---|---|---|
| `sector-público` / `sector-gobierno` / `Administración Pública` / `Gobierno` / `eficiencia-gubernamental` / `Gestión Ciudadana` | `Sector Público` | Sector (Fusión canónica) |
| `Finanzas` / `finanzas` / `fraude` | `Finanzas y Banca` | Sector |
| `healthcare` | `Salud` | Sector (Español) |
| `educación` | `Educación` | Sector |
| `gobernanza-de-datos` / `gobernanza-datos` / `Gobierno del Dato` / `Transparencia` / `transparencia` / `Calidad de Datos` / `Trazabilidad de Datos` / `mdm` | `Gobierno del Dato` | Tema (Pilar de Gobernanza) |
| `Estrategia de Datos` / `estrategia-datos-ia` / `estrategia` / `innovación` / `Ventaja Competitiva` / `Transformación Digital` | `Estrategia de Datos` | Tema |
| `Integración de Datos` / `data-engineering` / `Data Engineering` / `Bases de Datos Legacy` / `streaming-datos` / `Arquitectura` / `tecnología` | `Ingeniería de Datos` | Tema |
| `Inteligencia Artificial` / `inteligencia artificial` / `IA` / `ai-data-science` / `soberania-datos-llms` / `LLMOps` / `LLM` | `Inteligencia Artificial` | Tema |
| `Business Intelligence` / `Métricas en Tiempo Real` | `Business Intelligence` | Tema |
| `analítica-predictiva` / `ia-predictiva` / `modelos predictivos` / `contrataciones-estatales` | `Analítica Predictiva` | Tema |
| `interoperabilidad` | `Interoperabilidad` | Tema |
| `Compliance` / `compliance` / `pci-dss` / `aml` / `risk-management` / `Seguridad` / `fhir` / `hipaa` | `Compliance` | Tema |
| `Soberanía de Datos` / `soberania-de-datos` | `Soberanía de Datos` | Tema |
| `TCO` / `optimización-presupuestaria` / `EBITDA` / `ROI` / `Business Case` | `ROI` | Tema |
| `cfo` / `CFO` | `CFO` | Rol |
| `cio` / `CIO` | `CIO` | Rol |
| `cdo` / `CDO` | `CDO` | Rol |
| `CEO` | `CEO` | Rol |

---

### Asignación de Tags Normalizados por Artículo

#### Artículos Activos (`draft: false`):
1. **`analitica-contrataciones-estado.md`**: `tags: ["Sector Público", "Analítica Predictiva", "Gobierno del Dato", "ROI"]`
2. **`calidad-padrones-ia.md`**: `tags: ["Sector Público", "Gobierno del Dato", "Inteligencia Artificial", "CDO"]`
3. **`cio-modernizacion-estado.md`**: `tags: ["Sector Público", "Ingeniería de Datos", "Compliance", "CIO"]`
4. **`dashboards-ejecutivos-gobierno.md`**: `tags: ["Sector Público", "Business Intelligence", "Gobierno del Dato"]`
5. **`data-ia-ebitda.md`**: `tags: ["Finanzas y Banca", "ROI", "CFO", "Estrategia de Datos"]`
6. **`gobierno-dato-estado.md`**: `tags: ["Sector Público", "Gobierno del Dato", "Estrategia de Datos", "CDO"]`
7. **`ia-corporativa-produccion.md`**: `tags: ["Inteligencia Artificial", "Estrategia de Datos", "Ingeniería de Datos"]`
8. **`ia-segura-estado.md`**: `tags: ["Sector Público", "Soberanía de Datos", "Inteligencia Artificial", "Compliance"]`
9. **`interoperabilidad-institucional.md`**: `tags: ["Sector Público", "Interoperabilidad", "Gobierno del Dato", "Ingeniería de Datos"]`
10. **`roi-ia-evaluacion-cfo.md`**: `tags: ["CFO", "ROI", "Estrategia de Datos"]`
11. **`ventaja-competitiva-datos-ceo.md`**: `tags: ["CEO", "Estrategia de Datos", "Inteligencia Artificial"]`

#### Artículos Borradores (`draft: true`):
12. **`data-mesh-lakehouse.md`**: `tags: ["Ingeniería de Datos", "Gobierno del Dato"]`
13. **`el-futuro-ia.md`**: `tags: ["Inteligencia Artificial", "Estrategia de Datos"]`
14. **`fraude-tiempo-real-banca.md`**: `tags: ["Finanzas y Banca", "CFO", "Compliance", "Analítica Predictiva", "Ingeniería de Datos"]`
15. **`interoperabilidad-salud-fhir.md`**: `tags: ["Salud", "Interoperabilidad", "Compliance", "Ingeniería de Datos", "CIO"]`
16. **`prediccion-desercion-estudiantil.md`**: `tags: ["Educación", "Analítica Predictiva", "Inteligencia Artificial"]`

---

## Proposed Changes

### 1. Frontmatter en Archivos Markdown de Blog

#### [MODIFY] Todos los archivos en `src/content/blog/*.md`
Actualizar únicamente la propiedad `tags:` en el frontmatter YAML de cada uno de los 16 archivos `.md` según la asignación aprobada.

---

### 2. Estado de Specs y Documentación

#### [MODIFY] [ESTADO-SPECS.md](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md)
Actualizar Spec 007 / Spec 012 registrando la consolidación del vocabulario controlado de tags del blog.

---

## Verification Plan

### Automated Verification
1. **Validación de Taxonomía y Build:**
   - Ejecutar `node scripts/build-taxonomy.mjs` (Verde).
   - Ejecutar `npm run build` (Verde).
2. **Conteo de Páginas de Tag:**
   - Verificar que el número de páginas estáticas `/blog/tag/*` se reduzca de 45 a aproximadamente 14 páginas activas (correspondientes a los tags utilizados por posts no borradores).

### Manual Verification
1. **Comprobar Cobertura Multi-Post:**
   - Abrir `/blog/tag/sector-publico` y verificar que liste los 6 artículos del sector público.
   - Abrir `/blog/tag/gobierno-del-dato` y verificar que agrupe los 4 artículos de gobernanza.
   - Confirmar que ningún tag contenga caracteres mal formateados o inconsistencias de caja.
