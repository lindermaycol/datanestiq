# Prompt para Antigravity — Spec 013: Conversión Consultiva por Rol × Sector (marco + fases) (SDD) → CREAR spec.md + plan.md, NO implementar aún

> **En una frase:** crea `spec.md` + `plan.md` para un **motor consultivo reutilizable `(rol × sector)`** (bloques A1–A9: objeción→respuesta, CTA dinámico, intents de chatbot, formulario seguro, evidencia honesta, captura de contexto de entrada) e instáncialo en **3 fases** (Público, CFO, CEO). **Consume** la taxonomía enriquecida (Spec 011 Fase 3) y se construye **encima** de la fundación; **no autoríes datos ni implementes** todavía. Éxito = spec describe A1–A9 parametrizado, plan mapea las 3 fases a los insumos, cero duplicación con la fundación/enriquecimiento, cero prueba social inventada.

Sigue **Spec-Driven Development**. Esto NO es un ajuste de UI: es un **motor repetible** que reposiciona el recorrido del comprador (por rol y sector) de "vitrina premium" a "asesor de preventa consultiva". Por su alcance y riesgo (copy del hero, CTAs, formulario de contacto, comportamiento del chatbot, narrativas de contratación/ROI), en esta ronda **solo produces artefactos SDD para MI revisión**: `specs/013-conversion-consultiva-rol-sector/spec.md` + `plan.md` (y, si ayuda, `tasks.md` preliminar por fases). **No implementes todavía.**

---

## 0. Por qué es UNA spec-marco (y no una spec por rol)

El usuario está recibiendo una **serie** de auditorías SDD por rol (ya hay: CDO público, CIO público, CFO finanzas/seguros, CEO estratégico multi-sector; vendrán más: CISO, COO…). Todas piden **la misma máquina** parametrizada por `(rol × sector)`:
- landing con profundidad + **objeciones del rol respondidas**,
- **CTA consultivo** específico (valor-primero, bajo riesgo),
- **intents del chatbot** para ese buyer,
- **evidencia / Casos ROI**,
- **blog por rol/sector**,
- (a veces) herramienta de negocio (p. ej. business case del CFO).

La taxonomía **ya modela** `personas (comité de compra) × sectores × pilares` con `objections/decisionCriteria/triggers`. Es el mismo motor. Crear una spec por documento duplicaría esa maquinaria y derivaría en inconsistencias. Por eso: **una spec-marco** que define el motor UNA vez, y **fases/paquetes por buyer**.

**Relación con el prompt hermano de fundación** ([prompt-antigravity-profundidad-paginas-y-recorridos-por-rol.md](prompt-antigravity-profundidad-paginas-y-recorridos-por-rol.md)): ese entrega la **fundación horizontal** (renderizar los campos enriquecidos de la taxonomía en TODAS las páginas `/soluciones/*` y `/sectores/*`, hubs, nav, legal, blog genérico). **Esa fundación va primero.** La Spec 013 se construye **encima**: añade la capa consultiva por buyer que la fundación no cubre.

**Regla anti-duplicación:** la profundidad genérica de las páginas de sector/solución (subsectores, KPIs `[EST]`, regulaciones, `techStack`, `proofPoints` renderizados desde la taxonomía) la entrega la fundación. Aquí **no la repitas**: aquí añades los elementos consultivos por rol (objeción-respuesta orientada al buyer, CTA por intención, narrativa de contratación/ROI, intents de chatbot, formulario consultivo, business case, FAQ).

**Dependencia de datos (importante):** la **autoría de datos de la taxonomía** (profundizar `techStack`/`competitivePositioning`, KPIs `[EST]`, `objectionResponses`, modelos de despliegue/contratación) la hace el **primer prompt del pipeline** ([prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md](prompt-antigravity-spec011-fase3-enriquecimiento-conversion.md), Spec 011 Fase 3). Esta Spec 013 **consume** esos campos; **no los autoríes aquí**. Si una fase necesita un dato que no existe, **repórtalo** para resolverlo en la capa de datos, no lo hardcodees. Las secciones "Datos" de cada fase abajo describen QUÉ consume esa fase (para trazabilidad), no una instrucción de autoría.

---

## 1. Insumos (fuente de verdad, versionados en el repo)

- `planes/insumos-conversion-consultiva/sector-publico-auditoria-ux.md` — backlog UX-PUB-001…015, user stories, spec de chatbot público (CHAT-PUB), fases.
- `planes/insumos-conversion-consultiva/sector-publico-matices.md` — 6 matices del buyer público + backlog SDD-PUB-016…023 + CH-PUB / CT-PUB.
- `planes/insumos-conversion-consultiva/cfo-finanzas-seguros.md` — backlog CFO-UX-001…008, user stories CFO, intents del chatbot CFO.
- `planes/insumos-conversion-consultiva/ceo-estrategico.md` — backlog CEO-UX-001…008, user stories CEO, intents del chatbot CEO (buyer económico-estratégico, multi-sector).

Cada fase debe citar su insumo como anexo de requerimientos.

---

## 2. PARTE A — El MOTOR (defínelo una vez en el spec; es común a todas las fases)

El `spec.md` define, de forma **parametrizada por `(rol, sector)`** y alimentada por la taxonomía:

**A1 — Secuencia de conversión objetivo** (común a todo buyer): reconocimiento del contexto → identificación del dolor → correspondencia con solución → reducción de riesgo → prueba de credibilidad → conversión consultiva.

**A2 — Bloque "Resolvemos tus dudas" (objeción→respuesta).** Componente reutilizable que, dado un `(rol, sector)`, renderiza las `objections` de la persona con respuestas honestas. Data en la taxonomía, no en `.astro`.

**A3 — CTA consultivo DINÁMICO por contexto (DECIDIDO).** El usuario confirmó que **el CTA debe cambiar según `(rol, sector)`**. Implementa un CTA contextual de valor-primero y bajo riesgo que se especializa con el contexto activo (ver A9): p. ej. Público→"Solicita un diagnóstico de madurez de datos e IA"; CFO/Finanzas→"Auditoría de ROI y pérdidas evitables (entregable defendible ante directorio)". **Default sin contexto (RECOMENDACIÓN de Claude — usar salvo que el usuario indique otra):** evoluciona "Auditoría Gratuita" hacia consultivo-neutro que sirva a todos los roles → **"Diagnóstico estratégico gratuito"** (conserva el gancho de gratis/bajo riesgo y suena consultivo, no técnico). Al activarse el contexto, se especializa por `(rol, sector)`. El CTA lee el store de contexto; no hardcodees variantes en `.astro`, derívalas de la taxonomía por `(rol, sector)`.

**A4 — Intents del chatbot por buyer.** Enriquecer `sectorsCorpus`/personas con `problems` e intents por rol. **Invariante intocable:** el flujo guiado sigue **0-LLM** (0 llamadas a `chat.php`, verificable en Network). El mensaje guiado devuelve: **problema interpretado → enfoque de solución → riesgo reducido → siguiente paso**. El modo texto libre (que sí usa `chat.php`, ya grounded en `services.json`) afina tono/criterios por buyer y **entrega valor antes de empujar contacto**.

**A5 — Formulario de contacto consultivo.** Recoge contexto mínimo útil (tipo de organización/entidad, reto principal, situación de sistemas/datos) y **explica qué recibe** tras el contacto. Tono no comercial-agresivo. **Debe reutilizar el pipeline seguro existente** (`chat.php`/lead capture): **PII redactada (Constitución §5)**, `secure_leads/` 403+gitignored. Prohibido crear un canal que loguee PII en claro.

**A6 — Evidencia / Casos ROI (con guardarraíl de honestidad, ver §4).** Estrategia de prueba de credibilidad **sin inventar** casos/testimonios/logos.

**A7 — Blog por rol/sector** vía Spec 012 (`docs-generator.mjs` target `blog`; `pubDate` sin comillas con `new Date().toISOString()`; build verde).

**A8 — Extensibilidad.** El spec debe dejar explícito **cómo se agrega una fase nueva** (un nuevo `(rol, sector)`) reutilizando el motor: qué campos de taxonomía poblar y qué componentes se parametrizan. Así los próximos audits por rol entran como fases, no como specs nuevas.

**A9 — Captura de contexto de entrada + personalización adaptativa (EXTIENDE Spec 002, NO es sistema nuevo).**
Petición del usuario: que al entrar se capture contexto y el sitio se adapte. **Reutiliza la maquinaria ya existente** de "Context-Aware Chaining and Semantic Highlighting" (Spec 002) — no construyas un motor de adaptación paralelo:
- **Bus de contexto (ya existe):** `src/store/index.ts` tiene `userChallenge` y `semanticHighlight` (atoms nanostores). Añade un atom `userContext = { rol, sector }` (persistido en `localStorage`). Varias islas ya se suscriben a este bus (`SemanticSearch`, `DiagnosticWizard`, `SolutionsByRoleAndIndustry`, `Chatbot`).
- **Renderizador de adaptación (ya existe):** `SemanticSearch.jsx` ya **resalta/atenúa** las `.service-card` y detecta sector para recomendar soluciones. **Reutiliza ese mismo mecanismo** para que, ante un `userContext`, se resalten servicios/sectores relevantes y se ordene el contenido — sin duplicar lógica de highlight.
- **Captura ligera (lo único nuevo). RECOMENDACIÓN de Claude (default — usar salvo que el usuario indique otra):** una fila de **chips descartables en el hero** ("Soy [rol] · en [sector]") — es la de **menor fricción, más visible y un solo tap**, escribe `userContext` directo y no tapa el sitio. Como **camino secundario**, reutiliza el **flujo guiado 0-LLM del chatbot** (que ya captura `sector→rol`) para pre-cargar el mismo `userContext`. **Prohibido** un interstitial que tape el sitio o gatee el contenido (daña UX/SEO y contradice los insumos, que advierten contra fricción). El chip debe poder cerrarse y **recordar** que se cerró (no re-mostrar).
- **Consumidores de `userContext`:** el **CTA dinámico (A3)**, el resaltado de servicios/sectores (renderizador existente), el **pre-contexto del chatbot** (saltar pasos ya conocidos), y el orden/objeciones que muestran las landings (motor A2). 
- **Reglas:** el sitio funciona 100% sin responder (progressive enhancement); responder solo **enriquece**; persistir la elección para **no volver a preguntar**; la adaptación **reordena/resalta contenido existente**, nunca fabrica. Todo client-side (SSG): las variantes salen del corpus de taxonomía ya embebido en el cliente.

---

## 3. PARTES B/C — Las FASES (paquetes por buyer)

### FASE 1 — Sector Público (buyer institucional) — insumos `sector-publico-*`
Capa vertical pública sobre el motor:
- **Datos consumidos (autoría en Spec 011 Fase 3):** los **6 matices** del público codificados en la taxonomía (continuidad institucional; trazabilidad/auditoría; modalidad de contratación —diagnóstico/piloto/fases, no producto—; gestión del cambio/adopción; riesgo reputacional →KPIs de control/supervisión/alertas; gobernanza de IA/independencia →on-prem/VPC/anti-lock-in). Si falta alguno, repórtalo.
- **Home:** línea secundaria del hero con comprensión institucional (sin exclusivizar la marca al gobierno) + sección "Cómo trabajamos con entidades públicas" (continuidad/adopción/seguridad/fases) + bloques "Si hoy enfrentas…".
- **`/sectores/publico` como página de decisión:** subsectores (ministerios, GORE, municipalidades, reguladores), problemas por entidad, criterios por rol, **modalidades de abordaje**, CTA de diagnóstico. Casos de dominio: interoperabilidad institucional, analítica de contratación (OECE), calidad de padrones, trazabilidad documental.
- **Bloque "Seguridad y despliegue":** on-prem / VPC / nube privada / **LLM privado** / segmentación / trazabilidad.
- **Chatbot público (CH-PUB-001…005):** vocabulario institucional (expedientes, contrataciones, observaciones, trazabilidad, tableros, automatización documental), roles correctos (CIO/CDO/CISO/Gerencia/Operaciones).
- **FAQ institucional** (tiempos, seguridad, integración, soporte, modalidad de trabajo, transferencia de conocimiento).
- **Blog público:** 4–6 artículos (gobierno del dato, analítica de contrataciones, IA segura, interoperabilidad, trazabilidad, dashboards ejecutivos).

### FASE 2 — CFO Económico (Finanzas / Seguros / Minería) — insumo `cfo-finanzas-seguros.md`
Capa vertical del comprador **económico** sobre el motor:
- **Datos consumidos (autoría en Spec 011 Fase 3):** objeciones económicas del CFO con respuesta ("ROI abstracto/largo plazo", "cambiar el ERP cuesta demasiado", "no defendible ante directorio/auditoría") y KPIs `[EST]` financieros (reducción de pérdidas por fraude, recuperación/scoring, eficiencia de capital, NPL, churn, reservas, provisiones, siniestralidad, payback). Si falta alguno, repórtalo.
- **Landing finanzas y seguros:** 4–6 KPIs `[EST]` por sector, marcos regulatorios (SBS/Basilea/IFRS-provisiones/etc.), problemas típicos (fraude, morosidad, scoring, riesgo, siniestralidad, pricing, reservas).
- **Lecturas de solución para CFO:** BI (reporting a directorio, monitoreo de KPIs), AI & Data Science (modelos de riesgo/scoring/fraude/churn/pérdidas), Data Engineering (calidad/integración/pipelines) — **enfatizando impacto en indicadores económicos**, no tecnología.
- **Sección "Modernización incremental y coexistencia con sistemas core":** integración sin reescribir ERP/core bancario/sistemas de póliza; proyectos por capas; control de TCO. (Rebate CFO-UX-005.)
- **Casos ROI para CFO:** contextos comparables, antes/después, payback, carga de inversión, notas de TCO — **al menos uno de finanzas y uno de seguros** — **respetando el guardarraíl de honestidad (§4): nada fabricado; usar proyecciones `[EST]` claramente marcadas como modelo, no como cliente real.**
- **Business case / herramienta de ROI:** recurso (post/página/descargable) sobre cómo construir el business case de datos e IA (componentes de ROI, costos, tiempos, riesgos). (CFO-UX-006.)
- **Chatbot CFO:** intents "ROI de IA", "impacto en EBITDA", "pérdidas evitables", "ERP", "TCO"; reconoce finanzas/seguros, pregunta por el dolor económico y sugiere evaluación cuantitativa (diagnóstico/business case).
- **CTA CFO:** "Auditoría de potencial de ROI / pérdidas evitables", con entregable **defendible ante directorio/auditoría**.
- **Blog CFO:** "Cómo evaluar ROI en proyectos de IA", "Datos para reducir pérdidas", "Data & IA como palanca de EBITDA".

### FASE 3 — CEO Estratégico (multi-sector: Retail / Finanzas / Educación / Logística) — insumo `ceo-estrategico.md`
Capa vertical del comprador **económico-estratégico** (visión/crecimiento/diferenciación) sobre el motor:
- **Datos consumidos (autoría en Spec 011 Fase 3):** objeciones estratégicas del CEO con respuesta ("la IA es una moda", "no somos una empresa tecnológica", "mi organización no tiene cultura/capacidades") y metas de decisión (ventaja competitiva, crecimiento de ingresos, nuevos productos digitales, experiencia del cliente, posicionamiento). Si falta alguno, repórtalo.
- **Home — narrativa estratégica:** bloque que enmarque datos e IA como **palancas estratégicas** (no solo TI), rebatiendo "moda"/"no somos tech" con enfoque **serio, gradual y gobernado**. Aprovecha el messaging existente de "ventaja asimétrica" como ancla — extiéndelo, no lo reemplaces.
- **Lecturas de solución para CEO:** estrategia digital / analítica avanzada / IA orientadas a **cómo se construye ventaja competitiva** (crecimiento, diferenciación, nuevos productos, mejor CX) — no lenguaje técnico.
- **Rutas "Para CEO en [industria]"** (itinerarios reto → oportunidad → propuesta → caso → CTA): este es exactamente el recorrido `(rol × sector)` que produce el motor (A1/A2); instáncialo para Retail/Finanzas/Educación/Logística. **No dupliques** la maquinaria del motor.
- **Casos / historias:** enfatizar **impacto estratégico** (trayectoria competitiva), no solo KPIs tácticos — **con el guardarraíl de honestidad (§4): modelos/escenarios `[EST]` etiquetados, nunca clientes reales inventados.**
- **Chatbot CEO:** rol de "sparring estratégico"; intents "ventaja competitiva", "crecimiento/retención/nuevos mercados", "no somos empresa tecnológica"; pregunta industria/tamaño/retos/horizonte y sugiere siguiente paso consultivo (evaluación estratégica/workshop).
- **CTA CEO:** "Evalúa el potencial estratégico de datos e IA en tu organización" (diálogo estratégico, no venta directa).
- **FAQ estratégica:** cómo se empieza, cuánto tarda, qué riesgos se gestionan, cómo se trabaja con equipos internos, cómo se mide el éxito.
- **Blog CEO:** cómo datos e IA cambian estrategia/modelos de negocio/productos/CX.

> **Nota de solapamiento CFO↔CEO:** ambos son compradores económicos y comparten sectores (finanzas). Trátalos como **dos lentes del mismo motor** (CFO = ROI/TCO/defendible; CEO = crecimiento/diferenciación/estrategia), no como contenido duplicado: la misma landing de finanzas debe poder **enfatizar distinto** según el `rol` activo (ver A9).

---

## 4. Guardarraíles (críticos — NO negociables)

- **🔴 HONESTIDAD — el más importante y el de mayor riesgo aquí.** AMBAS fases piden repetidamente "prueba social / microcasos / testimonios / casos con métricas antes-después / al menos un caso de finanzas y uno de seguros". Datanestiq es firma nueva: **NO puede inventar casos de éxito, testimonios, logos de clientes ni certificaciones.** La credibilidad se construye **honestamente** con: fluidez de dominio (vocabulario/procesos/regulaciones reales), metodología, **proyecciones y modelos claramente marcados `[EST]`** (nunca presentados como resultados de un cliente real), y capacidades reales. Los "Casos ROI" del CFO deben plantearse como **escenarios/modelos ilustrativos etiquetados**, no como clientes reales inexistentes. El `spec.md` debe declararlo como **criterio de aceptación explícito y bloqueante**.
  - ✅ **Aceptable:** *"Modelo `[EST]`: en una aseguradora media, la detección de fraude con ML puede reducir la pérdida esperada ~X% (escenario ilustrativo, no cliente real)"*; *"Basado en benchmarks públicos del sector…"*; una metodología o un proceso real descrito.
  - ❌ **No aceptable:** *"Ayudamos al Banco X a reducir…"* (cliente/logo inexistente); un testimonio con nombre y cargo inventados; una certificación/sello que no se posee; una métrica presentada como resultado real cuando es una proyección.
- **Taxonomía = fuente única de verdad.** Enriquecer en YAML/`personas.json` + `build-taxonomy.mjs` (valida Zod + integridad de aristas); nada hardcodeado en `.astro`. `contentAngles` sigue **fuera** del corpus cliente.
- **No romper.** Flujo guiado del chatbot **0-LLM**; islas (wizard, buscador) intactas; `npm run build` **verde**; sin `undefined`/placeholders/textos vacíos; markers `[EST]` conservados.
- **Seguridad.** No tocar core WordPress (`wp-admin/`, `wp-includes/`, `wp-content/`). PII redactada; `secure_leads/` 403+gitignored; no exponer `.env`/claves.
- **Marca no exclusiva a un vertical.** El refuerzo por buyer no debe romper la propuesta para otros sectores/roles.
- **Sin rutas duplicadas** ni drafts expuestos.

---

## 5. Criterios de aceptación / QA (inclúyelos en el spec)
1. Flujo guiado del chatbot **0-LLM** confirmado en Network para cada fase; roles correctos por sector; sin `undefined`/campos vacíos.
2. Motor (§2) definido de forma reutilizable; una fase nueva se agrega poblando taxonomía + parametrizando componentes (documentado en A8).
2b. **Personalización de entrada (A9)** extiende el store `userChallenge`/`semanticHighlight` (Spec 002) con `userContext`, reutiliza el renderizador de highlight existente, la captura es **no-bloqueante/opcional/persistida**, y el CTA dinámico (A3) reacciona al contexto. Sin motor de adaptación duplicado.
3. **Fase 1:** `/sectores/publico` como página de decisión (sin duplicar la profundidad de la fundación) + seguridad/despliegue + "cómo trabajamos con entidades públicas" + chatbot público + FAQ institucional + 4–6 blogs.
4. **Fase 2 (CFO):** landing finanzas y seguros con KPIs `[EST]` + regulaciones; sección de coexistencia con core/ERP; casos ROI **etiquetados como modelo `[EST]`** (≥1 finanzas, ≥1 seguros); business case; chatbot CFO; CTA de ROI; blogs CFO.
4b. **Fase 3 (CEO):** narrativa estratégica en home (rebate "IA es moda"/"no somos tech"); lecturas de solución orientadas a ventaja competitiva; rutas "Para CEO en [industria]" (Retail/Finanzas/Educación/Logística) instanciadas desde el motor; chatbot CEO (sparring estratégico); CTA de evaluación estratégica; FAQ estratégica; blogs CEO. Sin duplicar la landing de finanzas con la Fase 2 (misma landing, énfasis por `rol` activo vía A9).
5. Formulario consultivo recoge contexto y explica entregable; **PII redactada**.
6. **Cero** prueba social fabricada; toda métrica/caso estimado marcado `[EST]` y presentado como modelo.
7. Responsive desktop/móvil; consola limpia; títulos/meta/breadcrumbs JSON-LD; sin rutas duplicadas ni drafts.

---

## 5b. Formato esperado de los artefactos (estructura obligatoria)

**`spec.md` debe contener, como bloques explícitos:**
1. **Tesis y secuencia de conversión** (§A1) + sección "Matices de decisión por buyer".
2. **El motor A1–A9**, cada bloque como una sección con sus requisitos parametrizados por `(rol, sector)`.
3. **Fase 1 / Fase 2 / Fase 3**, cada una citando su insumo (`planes/insumos-conversion-consultiva/*`) como anexo.
4. **Guardarraíles** (honestidad bloqueante + PII + 0-LLM + taxonomía-fuente).
5. **Criterios de aceptación** (los de §5) + **exclusiones** (lo que NO cubre: la profundidad genérica ya la da la fundación).

**`plan.md` debe estructurarse así:**
1. **Overview** + deliverables.
2. **Motor** — cómo se implementa cada bloque A1–A9 (componentes, store, campos de taxonomía).
3. **Un anexo por fase** (Público / CFO / CEO), cada uno con: *objetivos · cambios en taxonomía · cambios en páginas · cambios en chatbot · cambios en CTA/formulario · cambios en blog/FAQ*.
4. **Riesgos y mitigaciones.**
5. **Tareas** (`tasks.md` preliminar) agrupadas por fase.

## 5c. Qué consideramos "éxito" en esta ronda (autochequeo antes de entregar)
- [ ] `spec.md` describe el motor **A1–A9** parametrizado por `(rol, sector)` como bloques explícitos.
- [ ] `plan.md` mapea el motor a **Fase 1 (Público), Fase 2 (CFO), Fase 3 (CEO)** con anexo por fase y referencia explícita a cada insumo.
- [ ] **No se duplican** campos/profundidad de taxonomía ya cubiertos por la fundación.
- [ ] Se respetan **todos** los guardarraíles (honestidad, PII, 0-LLM, taxonomía-fuente); los "Casos ROI" quedan como modelos `[EST]` etiquetados.
- [ ] A9 **extiende** el store existente (Spec 002), no crea un motor de adaptación paralelo.
- [ ] Marcadas las decisiones (defaults aplicados + la única que requiere confirmación: etiquetado de Casos ROI).

## 6. Forma de respuesta
- **Entrega `specs/013-conversion-consultiva-rol-sector/spec.md` + `plan.md`** (con la estructura de §5b) con: (a) el **motor** (Parte A) definido una vez; (b) **Fase 1 (Público)**, **Fase 2 (CFO)** y **Fase 3 (CEO)** como paquetes; (c) `tasks.md` preliminar por fases si ayuda. **NO implementes.**
- **Decisiones ya resueltas con default recomendado** (impleméntalas con el default salvo que el usuario diga lo contrario): CTA dinámico + default "Diagnóstico estratégico gratuito" (§A3); captura de contexto = chips descartables en el hero, chatbot guiado como camino secundario (§A9). Menciónalas en el plan por transparencia, pero **no las dejes bloqueadas**.
- **Decisión que SÍ requiere mi confirmación:** **cómo etiquetar los "Casos ROI"** (CFO/CEO) para no fingir clientes reales (propón el formato de etiqueta `[EST]`/"modelo ilustrativo").
- Sección final "Hallazgos adicionales".

---

**Orden recomendado de ejecución:** (1) **enriquecimiento** de taxonomía (Spec 011 Fase 3) → (2) prompt de **fundación** (profundidad/hubs/nav/legal) → (3) esta **Spec 013** (Fase 1 Público → Fase 2 CFO → Fase 3 CEO). **Nota:** Claude (Opus 4.8) auditará en el navegador (servido por PHP/XAMPP) que el flujo guiado siga 0-LLM, que el contenido sea **honesto** (sin prueba social/casos inventados), que el formulario no filtre PII y que el build quede verde. **Pendientes del usuario (sin cambios):** desplegar el `chat.php` corregido a IONOS + claves `DASHSCOPE`/`GEMINI` en `.env` de producción; rotar la SSH de IONOS.
