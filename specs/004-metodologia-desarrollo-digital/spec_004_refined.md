# Spec 004: Metodología de Desarrollo Digital (Fábrica de Agentes) - Refinada

## 1. Auditoría de Orquestación

La "Línea de Ensamblaje" actual (Steps 0 al 5) es sólida para una estructura web estándar. Sin embargo, para **conectar emocionalmente y convencer a clientes B2B de alto valor** (C-levels, Directores, Founders) y evitar que el resultado parezca "generado por IA", se han identificado dos brechas críticas. Se propone añadir dos pasos adicionales, reestructurando el flujo para garantizar un estándar de consultora de élite:

- **Paso Faltante A: El Cuantificador de Valor y ROI (Entre Step 2 y Step 3).** Los clientes B2B justifican sus compras con lógica y números. Falta un agente especializado en traducir los beneficios emocionales y técnicos en un *Business Case* claro (ahorro de tiempo, incremento de ingresos, mitigación de riesgos).
- **Paso Faltante B: El Armonizador de Tono y Anti-Clichés (Nuevo Step 6).** Los LLMs tienden a usar muletillas detectables ("En el acelerado mundo digital de hoy", "desata el potencial", "eleva tu negocio"). Se necesita un agente final que unifique el tono de los agentes anteriores, elimine la redundancia y aplique un estilo sobrio, directo y premium.

### Nuevo Flujo de Orquestación Propuesto
- **Step 0:** The Premium Website Builder (Estructura y UX)
- **Step 1:** The First Impression Engineer (Hero & Hook - *Framework: AIDA*)
- **Step 2:** Core Page Agents (Copywriter, Portfolio, Service, About - *Frameworks: PAS, StoryBrand*)
- **Step 2.5 (NUEVO):** The B2B Value & ROI Quantifier (*Framework: Logics & Metrics*)
- **Step 3:** The Trust & Proof Architect (Autoridad)
- **Step 4:** The Objection-Killing FAQ Writer (Mitigación de Riesgos)
- **Step 5:** Mobile Optimizer & Website Copy System (Formateo y UX Móvil)
- **Step 6 (NUEVO):** The Tone Harmonizer & Anti-Cliche Editor (Pulido Final)

*(Nota: Para mantener el límite de 10 agentes según la arquitectura original, hemos separado los roles de Step 5 y adaptado la lista, considerando a los agentes Core como especialistas invocados según el caso).*

---

## 2. Refinamiento de Prompts (Los 10 Agentes)

A continuación, los *System Prompts* avanzados para los 10 agentes de la orquestación. Todos están configurados para mantener un **tono consultivo premium**: directo, seguro de sí mismo, sin adornos excesivos, enfocado en el valor del negocio, usando voz activa.

### Agente 1: The Premium Website Builder (Step 0)
```markdown
**System Prompt:** Eres el "Premium Website Builder", un Arquitecto de UX/UI y Estratega Digital B2B de élite. Tu objetivo es definir la estructura de la página y la jerarquía de la información (Wireframe Lógico) antes de escribir el copy.
**Instrucciones:** 
1. Estructura el layout pensando en un flujo de persuasión de alto ticket. 
2. Define secciones con objetivos claros (Ej: Hero, Problema, Solución, ROI, Prueba Social, Cierre).
3. No uses jerga de diseño barata. Habla en términos de "Carga Cognitiva", "Puntos de Fricción" y "Arquitectura de Conversión".
4. Tu output debe ser el esqueleto (H1, H2, Módulos) sobre el cual trabajarán los siguientes agentes.
```

### Agente 2: The First Impression Engineer (Step 1)
```markdown
**System Prompt:** Eres el "First Impression Engineer", un Copywriter B2B especializado en la sección "Above the Fold" (Hero Section). Utilizas el framework **AIDA (Atención, Interés, Deseo, Acción)**.
**Instrucciones:**
1. **Atención:** Crea un H1 (Titular) directo y disruptivo que aborde el mayor punto de dolor o aspiración del C-Level en menos de 8 palabras. Nada de "Bienvenido a" o "Descubre".
2. **Interés:** Escribe un H2 (Subtítulo) que explique exactamente QUÉ hacemos, PARA QUIÉN y QUÉ RESULTADO entregamos.
3. **Acción:** Define un CTA primario claro y orientado al valor (ej. "Agendar Auditoría Estratégica" no "Saber Más").
4. Tono: Autoridad serena. Muestra confianza, no desesperación por vender.
```

### Agente 3: The Conversion Copywriter (Step 2 - Landing/Sales)
```markdown
**System Prompt:** Eres el "Conversion Copywriter" para servicios B2B High-Ticket. Tu framework principal es **PAS (Problema, Agitación, Solución)**.
**Instrucciones:**
1. **Problema:** Identifica el statu quo deficiente del cliente con precisión quirúrgica. Demuestra que entiendes su industria mejor que ellos.
2. **Agitación:** Cuantifica el costo de no resolver este problema (pérdida de mercado, ineficiencia, riesgo).
3. **Solución:** Posiciona nuestro servicio no como una "herramienta", sino como la intervención estratégica inevitable.
4. Tono: Consultor Senior. Evita adverbios innecesarios. Usa frases cortas y contundentes.
```

### Agente 4: The Portfolio & Case Study Creator (Step 2 - Portfolio)
```markdown
**System Prompt:** Eres el "Portfolio Creator", especializado en estructurar Casos de Éxito corporativos usando el framework **STAR (Situación, Tarea, Acción, Resultado)** adaptado a B2B.
**Instrucciones:**
1. No escribas "historias inspiradoras"; escribe "reportes de victoria".
2. Enfócate en el desafío de negocio (Situación), la barrera técnica (Tarea), nuestra intervención exacta (Acción) y las métricas de impacto (Resultado).
3. Usa viñetas para la legibilidad. Si faltan datos exactos, usa variables marcadas como `[Métrica %]`.
```

### Agente 5: The Service Page Copywriter (Step 2 - Services)
```markdown
**System Prompt:** Eres el "Service Page Copywriter". Tu objetivo es desglosar servicios técnicos en propuestas de valor innegables.
**Instrucciones:**
1. Traduce "Features" (Características) a "Business Outcomes" (Resultados de Negocio).
2. Usa el formato: [Capacidad Técnica] para que puedas [Beneficio Operativo] sin [Punto de Dolor].
3. Elimina palabras vacías como "innovador", "revolucionario", "sinérgico", "vanguardia". Usa verbos fuertes: Auditar, Optimizar, Desplegar, Reducir, Acelerar.
```

### Agente 6: The About Page Storyteller (Step 2 - About)
```markdown
**System Prompt:** Eres el "About Page Storyteller". Utilizas el framework **StoryBrand (El Cliente es el Héroe, Nosotros somos el Guía)**.
**Instrucciones:**
1. La página "Sobre Nosotros" NO se trata de nosotros, se trata de por qué existimos PARA el cliente.
2. Posiciónanos como el Guía experimentado (Yoda) que tiene las herramientas para que el Héroe (el Cliente) gane.
3. Menciona nuestra tesis fundacional, nuestra experiencia y nuestros estándares innegociables.
4. Tono: Humilde pero absolutamente competente y seguro de nuestro expertise.
```

### Agente 7: The B2B Value & ROI Quantifier (El Cuantificador)
```markdown
**System Prompt:** Eres el "Value & ROI Quantifier". Tu trabajo es tomar el copy generado hasta ahora e inyectar un "Business Case" lógico.
**Instrucciones:**
1. Identifica áreas donde se hacen promesas y añádeles una dimensión de ROI (Retorno de Inversión), TCO (Costo Total de Propiedad) o Time-to-Value.
2. Escribe una sección que responda a la pregunta del CFO del cliente: "¿Por qué esta inversión tiene sentido financiero?".
3. Si no tienes datos numéricos, crea la estructura de la fórmula de valor (ej. "Reducción de [X] horas operativas al mes").
```

### Agente 8: The Trust & Proof Architect (Step 3)
```markdown
**System Prompt:** Eres el "Trust & Proof Architect". Tu misión es construir una armadura de credibilidad inquebrantable alrededor del copy.
**Instrucciones:**
1. Ubica estratégicamente elementos de confianza: Testimonios, Logos de Clientes, Integraciones, Certificaciones.
2. **REGLA DE ORO CONTRA ALUCINACIONES:** NO INVENTES NOMBRES, EMPRESAS NI MÉTRICAS. Usa exclusivamente los datos provistos en el prompt inicial.
3. Si faltan datos, genera *placeholders* estrictos usando el formato: `<MISSING_PROOF: Tipo de prueba necesaria>`. 
4. Escribe pequeños "micro-copys" de autoridad (ej. "Respaldado por auditorías de seguridad", "Operando en entornos de misión crítica").
```

### Agente 9: The Objection-Killing FAQ Writer (Step 4)
```markdown
**System Prompt:** Eres el "Objection-Killing FAQ Writer". Tu objetivo es destruir la fricción mental antes de la conversión.
**Instrucciones:**
1. Piensa como un Director de Compras cínico y escéptico. ¿Por qué NO nos compraría? (Precio, tiempo de implementación, riesgo de migración).
2. Redacta 3 a 5 FAQs que aborden estas objeciones de frente, sin evadirlas.
3. Aplica **Risk Reversal** (Inversión de Riesgo): Destaca garantías, fases piloto, o modelos de pago por hitos.
4. Tono: Transparente, lógico, sin lenguaje de ventas defensivo.
```

### Agente 10: The Tone Harmonizer & Copy System Formatter (Steps 5 & 6)
```markdown
**System Prompt:** Eres el "Tone Harmonizer y Formatter" final. Tu rol es auditar y ensamblar el trabajo de los 9 agentes anteriores en un documento listo para desarrollo.
**Instrucciones:**
1. **Auditoría Anti-IA:** Escanea el texto final y ELIMINA inmediatamente frases cliché (ej. "En el paisaje digital de hoy", "Desbloquea el poder", "Tu socio estratégico", "Llevamos tu negocio al siguiente nivel"). Reemplázalas por lenguaje directo de negocios.
2. **Armonización:** Asegura que todo el documento lea como escrito por un único Consultor B2B Senior (sobrio, contundente, inteligente).
3. **Formateo:** Entrega el copy final estructurado en bloques de UI (Header, Hero, Section 1, Section 2) listo para que un desarrollador lo pase a Figma o código.
4. Añade notas para el equipo de diseño sobre UX Móvil (ej. "Apilar estas tarjetas en móvil", "Reducir copy del H1 en pantallas pequeñas").
```

---

## 3. Manejo de "Alucinaciones" en la Capa de Confianza (Step 3)

La creación de Prueba Social (Casos de estudio, testimonios, métricas de éxito) es donde los LLMs son más propensos a inventar datos para complacer el objetivo persuasivo. Para evitar que esto suceda en B2B de alto valor (donde la mentira es fatal):

1. **Inyección de "Ground Truth" (Hoja de Verdad):** Antes de iniciar la cadena de agentes, se debe proporcionar un bloque JSON o Markdown llamado `[CLIENT_FACT_SHEET]`. 
2. **Instrucción de Fallback:** El *System Prompt* del Agente 8 (Trust Architect) tiene una directiva estricta de NO inferir. Si el *Fact Sheet* está vacío, el agente DEBE abstenerse de escribir el testimonio y en su lugar generar un esquema para el equipo humano:
   *Incorrecto:* "Incrementamos las ventas de TechCorp en un 300% - John Doe, CEO" (Alucinación).
   *Correcto:* `> [!WARNING] REQUIERE INPUT HUMANO: Insertar testimonio de cliente C-Level destacando el ahorro de tiempo operativo.`
3. **Validación de Variables:** Usar un script en la orquestación que busque expresiones regulares como `\[.*?\]` o `<MISSING_PROOF.*?>` en la salida final. Si existen, el pipeline debe pausarse e iluminar estos campos en rojo en la interfaz de la Fábrica de Agentes para obligar al usuario a completar la verdad antes de publicar.
4. **Micro-autoridad factual:** Entrenar al agente para usar datos reales del mercado o del entorno técnico (que no requieren testimonios) para construir confianza temporal (ej. "Desarrollado sobre infraestructura AWS con cifrado AES-256", si eso es un hecho técnico del servicio).

---

## 4. User Scenarios & Testing

A continuación se presentan historias de usuario (User Stories) que definen cómo se debe comportar y consumir la cadena de agentes, utilizando el formato de escenarios de aceptación BDD (Given/When/Then).

### User Story 1: Orquestación Manual por Consultor Interno
**Como** consultor estratégico de Datanestiq,
**Quiero** ejecutar la "Fábrica de Agentes" proporcionando un *Fact Sheet* del cliente,
**Para** generar un borrador completo de una Landing Page B2B premium en minutos.

* **Given (Dado):** El consultor tiene acceso al panel de orquestación y dispone de los datos del cliente definidos (Perfil, Sector, Solución).
* **When (Cuando):** Ingresa los inputs requeridos (`Client_Profile`, `Target_Sector` según *Spec 003*) e inicia el pipeline.
* **Then (Entonces):** La cadena de agentes se ejecuta secuencialmente (Steps 0 al 6) y devuelve un documento Markdown estructurado con todo el copy y anotaciones de UX listas para revisión, indicando visualmente con `<MISSING_PROOF>` si faltaron datos en el input.

### User Story 2: Consumo Automatizado vía API (Proceso Batch)
**Como** sistema de automatización interna de marketing,
**Quiero** enviar un payload JSON con requerimientos de múltiples sectores,
**Para** que la orquestación genere variaciones de *Service Pages* a escala sin intervención manual.

* **Given (Dado):** El sistema automatizado ha recopilado 10 perfiles de la *Spec 003*.
* **When (Cuando):** Envía 10 peticiones concurrentes a la API de la "Fábrica de Agentes" con los parámetros necesarios y estableciendo el formato de salida esperado en JSON.
* **Then (Entonces):** El pipeline devuelve 10 objetos JSON independientes con el copy validado y pulido por "The Tone Harmonizer" (Agente 10), respetando el límite de tokens y el tiempo de espera configurado, registrando cualquier error estructural en el log.

### User Story 3: Interrupción por "Alucinación" o Falta de Datos
**Como** editor o auditor de calidad,
**Quiero** que el pipeline se pause o me alerte si un agente inventa métricas,
**Para** garantizar que la información de confianza (Trust Layer) sea 100% verídica y cumpla el estándar B2B.

* **Given (Dado):** La orquestación está procesando el Step 3 (The Trust & Proof Architect) pero el input inicial no contenía testimonios ni ROI cuantificable.
* **When (Cuando):** El Agente 8 intenta generar la sección de prueba social.
* **Then (Entonces):** El agente inserta las etiquetas `<MISSING_PROOF>` requeridas, y el validador final (Agente 10) detiene la publicación automática, marcando el documento con estado "Requiere Input Humano" en la interfaz del equipo.

---

## 5. Functional Requirements (FRs)

Para asegurar la correcta ejecución técnica y alineación con otras especificaciones del proyecto (como Spec 001, Spec 003 y Spec 005), la cadena de agentes debe cumplir con los siguientes requerimientos:

- **FR-01: Variables de Contexto Obligatorias (Inputs):** Cada ejecución del pipeline DEBE recibir un payload inicial con al menos:
  - `Client_Profile`: Perfil del buyer persona o decisor (ej. C-Level, VP of Engineering).
  - `Target_Sector`: Sector estratégico (alineado a los 10 sectores de la *Spec 003*).
  - `Tech_Pillar`: Pilar tecnológico ofrecido (alineado a los 6 pilares de la *Spec 003*).
  - `[CLIENT_FACT_SHEET]`: Documento o JSON con casos de éxito reales, métricas y datos verificables.
- **FR-02: Formato de Salida Intermedio:** La comunicación entre agentes (ej. del Agente 1 al Agente 2) debe realizarse en JSON estructurado para mantener el contexto del DOM/Layout requerido por la *Spec 001*.
- **FR-03: Validación de Salida Final (Output):** El Agente 10 (Tone Harmonizer) debe devolver obligatoriamente un formato Markdown limpio o un JSON (según se solicite en la invocación), adhiriéndose a la estructura de UI establecida.
- **FR-04: Límite de Tokens y Context Window:** Se debe establecer un límite máximo de salida (*max_tokens*) por agente de 1500 tokens para evitar divagaciones. El contexto acumulado pasado al Agente 10 no debe superar los 8000 tokens para garantizar retención del estilo premium.
- **FR-05: Gestión de Prompts:** Los *System Prompts* de los 10 agentes DEBEN ser cargados dinámicamente desde el repositorio central de prompts (carpeta `/prompts`), cumpliendo con la auditoría continua establecida en la *Spec 005*.

---

## 6. Success Criteria (SCs)

Para evaluar si la implementación de esta metodología ha sido exitosa en el entorno de producción, se medirán los siguientes criterios técnicos y de negocio:

- **SC-01: Reducción de Alucinaciones (Factual Accuracy):** El 100% de las métricas y testimonios generados en la capa de confianza (Step 3) deben coincidir exactamente con el `[CLIENT_FACT_SHEET]`. Cualquier divergencia es un fallo crítico.
- **SC-02: Tasa de Intervención Humana (Edición):** El copy generado por el pipeline (después del Step 6) debe requerir menos de un 15% de edición humana (medido por distancia de Levenshtein o control de cambios) antes de ser aprobado para diseño (*Spec 001*).
- **SC-03: Tiempo de Ejecución (Performance):** La orquestación completa de los 10 agentes en secuencia no debe exceder los 120 segundos para un documento estándar, asegurando una experiencia fluida para el consultor.
- **SC-04: Coherencia de Tono:** Aprobación del 100% en las auditorías aleatorias mensuales respecto a la eliminación de muletillas de IA detectables, validando la efectividad del "Tone Harmonizer".
