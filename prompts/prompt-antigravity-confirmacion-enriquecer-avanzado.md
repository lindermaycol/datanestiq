# Prompt para Antigravity: Confirmación del Plan de Enriquecimiento Avanzado → ejecutar

Revisé `planes/Plan de Implementación Enriquecimiento Avanzado de Taxonomía.md`. Es fiel al prompt. **Luz verde para ejecutar**, con las respuestas a tus 2 preguntas y 3 precisiones (la #1 es importante y no está en tu plan).

## Respuestas a tus Open Questions

**1. ¿GICS además de CIIU?** → **Solo CIIU.** Es el estándar que importa para tu foco (INEI/Perú, sector público). GICS es para clasificar empresas cotizadas/inversión y no mapea limpio a tus 10 verticales; añadiría ruido sin valor para tu audiencia. Si en el futuro apuntas a inversores, se agrega después. Deja el campo `ciiu` únicamente.

**2. Vocabulario de keywords: ¿tecnología o negocio?** → **Balanceado, con ligero énfasis en el lenguaje de dolor/resultado del decisor.** El buscador semántico recibe consultas en lenguaje natural de negocio ("pierdo tiempo en reportes manuales", "no confío en mis datos"), así que incluye AMBOS: términos técnicos (ETL, RAG, forecasting, data lakehouse) **y** términos de dolor/outcome que un CTO/CDO/CFO realmente escribe (reducir costos operativos, cumplimiento normativo, decisiones en tiempo real, prevención de fraude). El segundo grupo es el que más mejora el matching real.

## Precisión 1 (IMPORTANTE) — Verifica el port y el PESO del modelo multilingüe

Tu plan fija `Xenova/paraphrase-multilingual-MiniLM-L12-v2` sin dos verificaciones que pedí:
- **Existencia del port cuantizado:** confirma que el repo `Xenova/paraphrase-multilingual-MiniLM-L12-v2` en Hugging Face tenga realmente `onnx/model_quantized.onnx` (q8). Si no existe cuantizado, usa `Xenova/multilingual-e5-small` (con prefijos `query:`/`passage:`); si ninguno, mantén MiniLM y repórtalo. No asumas.
- **Peso de descarga (trade-off real):** este modelo es **~5× más pesado** que el actual (MiniLM-L6 q8 ≈ 23 MB → multilingual-L12 q8 ≈ ~110–120 MB). Se descarga solo en la primera búsqueda y se cachea en IndexedDB, pero es una diferencia notable en la primera carga. **Confírmalo como aceptable** (para una feature "Edge AI premium" opt-in lo es), y verifica en Network el tamaño real descargado. Si resultara excesivo, reporta el número para decidir. No lo silencies.

## Precisión 2 — Verifica cada código CIIU (Rev.4), no solo los del ejemplo

Varios códigos son ambiguos y debes resolverlos con criterio contra CIIU Rev.4 (INEI):
- Retail y B2B → **46** (comercio al por mayor, B2B) más que 47 (minorista) — elige el que refleje "B2B".
- Minería → **07** (minerales metálicos) u **08** según el caso; puedes usar la sección/división general.
- Manufactura → es la **sección C (divisiones 10–33)**, no una división única; usa un código representativo o el rango.
- Los demás (84, 86, 64, 52, 85, 65, 61) son razonables — confírmalos igual.
Documenta junto a cada código a qué división/actividad corresponde, para trazabilidad.

## Precisión 3 — Verificación E2E con evidencia + reflejar campos en las páginas existentes

- **El buscador debe REORDENAR de verdad con el nuevo modelo** (no solo descargarlo): reporta el antes/después con la query de logística (¿mejora la discriminación vs el 5/6 anterior?). Es el objetivo central.
- **No-regresión con evidencia:** chatbot guiado = 0 llamadas (Network); wizard con 10 sectores; copilot responde.
- **Reflejar los campos nuevos donde ya hay UI:** las páginas de pilar `/soluciones/[id]` YA existen — añade de forma discreta el/los `standards` (marco de industria, señal de autoridad) y `targetRoles` ("Ideal para: CTO, CDO…") en esas páginas. (Los `ciiu` de sector se mostrarán cuando el prompt 003 cree las landing de sector; por ahora solo van en el dato.)

## Precisión 4 (NUEVA — requisito del usuario) — Precarga del modelo en background + cuantizado

Hoy el modelo se carga **perezosamente**: solo se descarga cuando el usuario dispara la primera búsqueda, obligándolo a esperar los ~110 MB en ese momento. El usuario quiere que el modelo se **precargue en segundo plano mientras entra y navega la página**, para que la búsqueda sea instantánea cuando la use.

Implementa un **warmup**:
- En `worker.js`, añade un mensaje de tipo `warmup` que llame a `PipelineSingleton.getInstance()` (carga el modelo) **sin** ejecutar una búsqueda. Mantén `quantized: true, dtype: 'q8'` (obligatorio: el modelo DEBE ser cuantizado).
- En `SemanticSearch.jsx`, al montar el componente, dispara ese warmup en **idle** (`requestIdleCallback`, con fallback a `setTimeout`) para NO competir con el render crítico ni con el LCP. Así el modelo empieza a bajar en background mientras el usuario navega, y cuando escriba su búsqueda ya está listo (o casi).
- El island es `client:visible`; si quieres que la precarga empiece aún antes (sin esperar a que la sección entre en viewport), puedes iniciar el warmup vía un pequeño script `client:idle` o al primer scroll — elige la opción que precargue temprano **sin** bloquear la carga inicial. No arranques la descarga antes de que la página sea interactiva.
- Estado visual opcional: un indicador sutil "IA lista" cuando el modelo terminó de precargar; y si el usuario busca antes de que termine, que muestre el progreso actual (ya existe `loading_model %`).

**Verificación de la precarga:** en Network, confirma que la descarga del `.onnx` **cuantizado** arranca durante la navegación (en idle, tras cargar la página), **antes** de que el usuario haga clic en buscar — y que al momento de buscar el modelo ya está en caché/listo. Confirma que el archivo descargado es el cuantizado (q8), no el fp32.

---

Procede: modelo + warmup en background (Parte 1) → enriquecimiento JSON (CIIU, keywords, standards, targetRoles/skills) → reflejar en páginas de pilar → documentación. Recuerda que TODOS los campos nuevos (`ciiu`, `standards`, `targetRoles`, `skills`, keywords ampliados) deben quedar anotados para el schema Zod de la migración a Content Collections (prompt 003).

`npm run build` estable. No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que el modelo multilingüe cargue y mejore la discriminación (con el peso reportado), que los CIIU sean correctos, y que nada se rompa — re-ejecutando el E2E en el navegador.
