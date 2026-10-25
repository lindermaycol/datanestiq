# Prompt para Antigravity: Enriquecimiento avanzado de la taxonomía (multilingüe + CIIU + keywords IA + marcos DAMA/CRISP-DM)

Actúa como **arquitecto de datos/taxonomías e ingeniero de IA en el navegador**. Sigue el patrón: entrega un breve **plan** para mi revisión y luego implementa. Este ciclo consolida la taxonomía (ya fuente única) llevándola a estándar de industria e interoperable.

Va **después** del enriquecimiento base (ya hecho: keywords/copilotPrompts/problems/seo/hero/contrast) y **antes** de la migración a Content Collections (prompt 003). Todos los campos nuevos deberán incluirse en el schema Zod de esa migración — déjalo anotado.

## Restricción CRÍTICA — cero rupturas
Buscador semántico, chatbot guiado (0 llamadas), wizard, copilot y páginas de solución deben seguir funcionando. Verifica E2E en `npm run dev`.

---

## PARTE 1 — Buscador semántico multilingüe (mayor impacto)

El modelo actual `Xenova/all-MiniLM-L6-v2` está entrenado en **inglés**; el contenido de Datanestiq es **español**, lo que degrada la precisión de recuperación.

- En `public/worker.js`, cambia el modelo a uno **multilingüe** con port Xenova/ONNX cuantizable (mantén `quantized: true, dtype: 'q8'` y la caché IndexedDB). Orden de preferencia:
  1. `Xenova/paraphrase-multilingual-MiniLM-L12-v2` (probado, ligero, buen español)
  2. `Xenova/multilingual-e5-small` (fuerte en retrieval multilingüe)
- **Verifica** que el port exista realmente en Hugging Face antes de fijarlo (revisa que el repo `Xenova/<modelo>` tenga `onnx/model_quantized.onnx`). Si el primero no está disponible como ONNX cuantizado, usa el segundo; si ninguno, **mantén MiniLM y repórtalo** (no rompas la feature).
- Nota: e5 requiere prefijos `query:` / `passage:` en el texto para óptimo rendimiento. Si eliges e5, aplica esos prefijos en `worker.js` (query del usuario con `query:`, textos del corpus con `passage:`). Para paraphrase-multilingual no hacen falta prefijos.
- **Verificación:** en `npm run dev`, busca "optimización de rutas y cadena de suministro" y confirma que ahora discrimina mejor (idealmente destaca Logística y atenúa claramente los no relevantes — antes resaltaba 5/6). Reporta el antes/después.

## PARTE 2 — Códigos CIIU por sector (estándar INEI/Perú)

Añade a cada uno de los 10 sectores en `sectorsCorpus.json` un campo `ciiu` (código de la Clasificación Industrial Internacional Uniforme, Rev.4, que usa INEI en Perú) y opcional `gics`. Punto de partida (verifica/ajusta cada código contra CIIU Rev.4 antes de fijarlo):

| Sector | CIIU (división aprox.) |
|---|---|
| Sector Público | 84 (Administración pública y defensa) |
| Salud | 86 (Atención de la salud humana) |
| Finanzas y Banca | 64 (Servicios financieros) |
| Retail y B2B | 46/47 (Comercio mayorista/minorista) |
| Logística | 52 (Almacenamiento y transporte) |
| Educación | 85 (Enseñanza) |
| Minería | 07/08 (Extracción de minerales) |
| Manufactura | 10–33 (Industrias manufactureras, sección C) |
| Seguros | 65 (Seguros y reaseguros) |
| Telecomunicaciones | 61 (Telecomunicaciones) |

- Refleja el código en la **landing de sector** (que creará el prompt 003) como señal de especialización, y opcionalmente en el JSON-LD del sector (`Service.areaServed` o una propiedad `industry`). No lo muestres de forma intrusiva; es dato de autoridad/SEO.

## PARTE 3 — Enriquecer `keywords[]` con IA (long-tail)

Expande los `keywords[]` de los 6 pilares y 10 sectores para mejorar el matching semántico y el SEO long-tail:
- Genera 6–10 keywords por elemento, incluyendo **sinónimos, términos técnicos y variantes en español** (ej. para Data Engineering: "pipelines de datos", "ETL", "ELT", "data lakehouse", "orquestación", "ingesta de datos", "modern data stack"). Cubre términos que un CTO/CDO peruano realmente buscaría.
- **Método:** puedes usar tu propio proxy (`/api/chat.php`) para generar candidatos por elemento, o redactarlos directamente con criterio experto; en ambos casos **revisa** que sean pertinentes (no relleno) y sin duplicar. Documenta en un comentario que esto se puede automatizar a futuro con KeyBERT (`scripts/`), pero no instales infra Python nueva en este ciclo.
- Estos keywords ya los consume el buscador semántico (mejoran el corpus de embeddings) y servirán para meta-keywords/SEO.

## PARTE 4 — Anclar pilares a marcos de industria (DAMA-DMBOK / CRISP-DM / madurez)

Añade a cada pilar en `taxonomyCorpus.json` un campo `standards` (array de referencias a marcos reconocidos) para dar rigor consultivo y sustentar el "Puntaje de Madurez" del wizard. Mapeo sugerido (ajusta con criterio):
- **AI & Data Science** → `CRISP-DM`, `MLOps` (ciclo de vida de modelos).
- **Data Engineering** → `DAMA-DMBOK: Data Architecture`, `Data Integration & Interoperability`, `Data Modeling`.
- **BI & Dashboards** → `DAMA-DMBOK: Data Warehousing & Business Intelligence`.
- **Hiperautomatización** → `BPM`, `IPA` (Intelligent Process Automation).
- **Sistemas Digitales** → `TOGAF`, `Arquitecturas Headless/Microservicios`.
- **Consultoría Estratégica / Governance** → `DAMA-DMBOK: Data Governance`, `Data Quality`, `Data Security & Privacy`.

- Refleja 1–2 de estos marcos en el copy de la landing de cada pilar (`SolutionContrast` o una sección nueva "Metodología/Estándares") como señal de autoridad — **sin** convertirlo en jerga vacía; una frase que ancle la capacidad a un estándar reconocido.
- Para el **MultiStepWizard**, usa estos marcos para dar sustento al Puntaje de Madurez (ej. referencia a un modelo de madurez de datos/IA de 5 niveles). Mantén la lógica simple.

## PARTE 5 — Mapeo a habilidades/roles (ESCO) para profundizar el targeting

Para reforzar el mensaje C-level ("para quién es cada servicio"), etiqueta cada pilar con los **roles decisores** y **competencias** que impacta, alineados a una taxonomía de skills reconocida:
- **Fuente:** **ESCO** (European Skills, Competences, Qualifications and Occupations) — abierta, multilingüe (incluye español), con ocupaciones y skills. Alternativas: **O*NET** (EEUU) o **Lightcast Open Skills**.
- **Alcance de este ciclo (ligero, sin integrar API):** añade a cada pilar en `taxonomyCorpus.json`:
  - `targetRoles: string[]` — los arquetipos de decisor a los que habla ese pilar (ej. Data Engineering → "CTO", "CDO", "Arquitecto de Datos"; Consultoría Estratégica → "CEO", "CDO", "Director de Riesgos"). Usa nomenclatura alineada a ocupaciones ESCO cuando exista equivalente.
  - `skills: string[]` (opcional) — 2–4 competencias clave que el servicio potencia, tomadas del vocabulario ESCO (ej. "gobernanza de datos", "modelado predictivo", "arquitectura cloud").
- **Uso:** estos `targetRoles` permiten personalizar el copy/CTA por persona y, a futuro, que el CopilotDemo o el Chatbot adapten el mensaje según el rol declarado. Refléjalos discretamente en la landing del pilar ("Ideal para: CTO, CDO…") si encaja con el diseño.
- **No integres la API de ESCO ni descargues su dataset** en este ciclo (sería pesado para un sitio estático). Documenta en un comentario que la integración profunda vía ESCO API/dataset (matching automático servicio↔skills↔ocupaciones) queda como iniciativa futura opcional.

---

## Verificación (E2E, con evidencia)
1. `npm run build` sin errores; conteo de páginas estable.
2. **Buscador semántico** (`npm run dev`): el nuevo modelo multilingüe carga (Network: descarga del ONNX del nuevo repo), y el reordenamiento discrimina mejor en español (reporta antes/después con una query).
3. **No-regresión:** chatbot guiado = 0 llamadas; wizard con 10 sectores; copilot responde; páginas de solución renderizan con su SEO/JSON-LD.
4. Confirma que los campos nuevos (`ciiu` en sectores; `standards`, `targetRoles`, `skills` en pilares; keywords ampliados en ambos) están presentes según corresponda.
5. Si algún sector muestra su CIIU o algún pilar su marco en la UI, confírmalo visualmente.

## Cierre documental
- Actualiza `specs/003-taxonomia-servicios/tech_debt.md`: registra el enriquecimiento avanzado (multilingüe, CIIU, keywords, marcos, roles ESCO) y **anota que el schema Zod de la futura migración a Content Collections debe incluir `ciiu`, `standards`, `targetRoles`, `skills` y `keywords` ampliados**.
- Nota en `specs/002-microexperiencias-ia/tech_debt.md`: el cambio de modelo de embeddings a multilingüe cierra la deuda del umbral poco selectivo del buscador (o la mitiga — reporta).

## Forma de respuesta
- Entrega primero el plan (qué modelo elegiste y por qué, mapeo CIIU verificado, ejemplos de keywords y marcos) para mi revisión, luego implementa.
- Reporta la verificación E2E con evidencia (Network del nuevo modelo, antes/después del buscador, 0 llamadas del chatbot).
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el nuevo modelo multilingüe realmente cargue y mejore la discriminación, que los códigos CIIU sean correctos (Rev.4), y que nada se rompa (chatbot 0 llamadas, wizard, copilot).
