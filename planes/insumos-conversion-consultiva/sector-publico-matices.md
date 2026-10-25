# Datanestiq — SDD complementario: matices críticos del buyer de entidad pública

## 1. Propósito

Este documento complementa la auditoría UX previa y extiende la definición del buyer del sector público para que el sitio web de Datanestiq, desarrollado bajo metodología **SDD / Spec-Kit**, no trate a “entidad pública” como un simple vertical comercial, sino como un entorno de decisión con lógica propia, restricciones institucionales y criterios de confianza distintos a los del sector privado. [cite:8][cite:9][file:2]

El objetivo es traducir esos matices a requerimientos de contenido, UX, navegación, mensajes, chatbot y conversión consultiva, de modo que puedan convertirse en nuevos artefactos `spec.md`, `plan.md`, `tasks.md` y `walkthrough.md` dentro del flujo SDD. [cite:8][file:2]

---

## 2. Marco de diseño SDD

Bajo una metodología SDD, este documento debe leerse como una **capa de refinamiento del modelo de usuario y de negocio**. No reemplaza el análisis funcional previo; lo profundiza. [cite:8]

En términos prácticos, esto significa que cualquier spec futura relacionada con sector público debería incorporar de forma explícita estos matices en:

- definición de personas y comité de compra;
- arquitectura de contenidos;
- objeciones por rol;
- criterios de credibilidad;
- reglas del chatbot y buscador semántico;
- CTAs y flujos de contacto. [file:2][cite:8][cite:9]

---

## 3. Tesis principal

El buyer de entidad pública no compra únicamente una solución tecnológica. Evalúa simultáneamente viabilidad institucional, riesgo político, cumplimiento, trazabilidad, continuidad operativa, modalidad de contratación, adopción interna y exposición reputacional. [cite:9][cite:13]

Por eso, un sitio web que quiera convertir a entidades públicas debe demostrar algo más que expertise técnico: debe comunicar que entiende **cómo se toman decisiones en el Estado** y cómo se implementan soluciones sin generar fricción institucional innecesaria. [cite:9][cite:13]

---

## 4. Matices críticos del buyer público

### 4.1 Continuidad institucional y ciclo político

En el sector público, la continuidad de proyectos depende muchas veces de cambios de gestión, rotación de funcionarios, prioridades del titular de la entidad y reordenamientos internos. [cite:13][cite:15]

Esto implica que el sitio no debe vender solamente “transformación” o “innovación”, sino también **continuidad, institucionalización y sostenibilidad**. [cite:9][cite:13]

#### Implicancias UX / contenido

- Explicar que los proyectos se diseñan para sobrevivir cambios de autoridades. [cite:13]
- Reforzar la transferencia de conocimiento, documentación, manuales, gobernanza y ownership institucional. [file:2][cite:13]
- Mostrar que la solución deja capacidad instalada y no dependencia absoluta del proveedor. [cite:9]

### 4.2 Trazabilidad, auditoría y formalidad documental

Las entidades públicas trabajan en entornos donde los expedientes, informes, actas, contratos, reportes, observaciones, cuadernos y evidencias documentales son parte esencial del funcionamiento. [cite:6][cite:10][cite:11]

Por ello, no basta con prometer automatización o analítica; hace falta explicar cómo la solución mejora **trazabilidad, seguimiento y auditabilidad**. [cite:6][cite:7]

#### Implicancias UX / contenido

- Incorporar lenguaje de trazabilidad, evidencia, historial, auditoría y seguimiento. [cite:6][cite:7]
- Mencionar explícitamente expedientes, contrataciones, reportes, órdenes de compra, cuadernos de obra u otros artefactos según el caso. [cite:6][cite:10][cite:11][cite:12]
- Priorizar casos de uso donde el valor no sea solo eficiencia, sino también control y transparencia. [cite:11][cite:12][cite:13]

### 4.3 Modalidad de contratación y compra pública

A diferencia del sector privado, muchas entidades no “compran software” como producto empaquetado, sino servicios, consultorías, asistencias técnicas, estudios, pilotos o proyectos por etapas. [cite:9][cite:13]

Eso significa que el sitio debe ayudar al buyer a visualizar **cómo podría contratarte realmente**, no solo por qué debería hacerlo. [cite:9]

#### Implicancias UX / contenido

- Mostrar modalidades como diagnóstico, hoja de ruta, piloto, consultoría especializada, implementación por fases y soporte. [file:2][cite:13]
- Incluir preguntas frecuentes sobre forma de trabajo, entregables y acompañamiento. [file:2]
- Evitar que todo suene a “producto cerrado” si el modelo real es consultivo. [cite:8][cite:9]

### 4.4 Gestión del cambio y adopción

El valor de una solución pública no depende únicamente de la calidad de la tecnología, sino de su capacidad de ser adoptada por usuarios, oficinas, jefaturas y equipos operativos. [cite:9][cite:13]

Muchas iniciativas fallan no porque el modelo o sistema sea malo, sino porque no existe entrenamiento, patrocinio, soporte ni cambio organizacional. [cite:13]

#### Implicancias UX / contenido

- Incluir de forma explícita gestión del cambio, talleres, capacitación y acompañamiento. [file:2][cite:13]
- Mostrar que la propuesta cubre no solo implementación técnica, sino operación y adopción. [file:2]
- Reducir la objeción de “mi equipo no lo va a usar” o “esto quedará abandonado tras la entrega”. [file:2][cite:13]

### 4.5 Riesgo reputacional, control y exposición pública

El entorno público tiene sensibilidad especial frente a arbitrajes, observaciones de control, auditorías, prensa, escrutinio ciudadano y errores de gestión visibles. [cite:11][cite:12][cite:13]

Por eso, el valor percibido muchas veces no se expresa en “más ventas” sino en **menos riesgo, más visibilidad, mejor supervisión y decisiones defendibles**. [cite:10][cite:11][cite:12]

#### Implicancias UX / contenido

- Reorientar KPIs y casos hacia control, oportunidad, reducción de retrabajo, capacidad de seguimiento y alertas tempranas. [cite:10][cite:11][cite:12]
- Incluir promesas de mejora en toma de decisiones y supervisión, no solo eficiencia operativa. [cite:13]
- Reforzar el mensaje de trazabilidad y transparencia institucional. [cite:6][cite:13]

### 4.6 Independencia tecnológica, ética y gobernanza de IA

En entornos públicos existe preocupación por lock-in, cajas negras, sesgos, dependencia excesiva de proveedores y exposición de datos sensibles a servicios públicos de IA. [cite:7][cite:9]

Una firma de consultoría gana mucha ventaja si comunica que la IA se diseña con criterios de gobernanza, transparencia, seguridad y control institucional. [cite:7][file:2]

#### Implicancias UX / contenido

- Explicar opciones como despliegue privado, on-prem, VPC o modelos controlados. [file:2]
- Hablar de gobernanza de IA, trazabilidad de decisiones y diseño responsable. [cite:7][file:2]
- Reforzar que la entidad conserva control de sus datos, procesos y arquitectura. [cite:9]

---

## 5. Impacto en la arquitectura del sitio

### 5.1 Ajustes recomendados al home

El home debe integrar señales más explícitas de comprensión institucional sin cerrar la marca únicamente al gobierno. [cite:8][cite:14]

#### Recomendaciones

- Hero con referencia a modernización pública, datos confiables, IA gobernada y trazabilidad. [file:2][cite:9]
- CTA consultivo con entregable inicial claro. [file:2]
- Bloque de credibilidad pública con experiencia en observatorios, analítica de contratación, dashboards ejecutivos, trazabilidad o alertas tempranas. [cite:6][cite:10][cite:13]
- Sección “Cómo trabajamos con entidades públicas” explicando continuidad, adopción, seguridad y fases. [cite:9][cite:13]

### 5.2 Ajustes recomendados a la landing Sector Público

La landing sectorial no debe limitarse a una descripción del sector; debe convertirse en una página de decisión. [file:2]

#### Elementos mínimos

- subsectores (ministerios, gobiernos regionales, municipalidades, organismos reguladores, rectorías, etc.); [file:2]
- problemas frecuentes por entidad; [cite:13]
- criterios de decisión por rol; [file:2]
- modalidades de abordaje; [cite:9]
- casos de uso y KPIs `[EST]`; [file:2]
- CTA de diagnóstico o evaluación. [file:2]

### 5.3 Ajustes recomendados a soluciones por rol

Cada rol del sector público necesita una lectura distinta:

- **CIO**: continuidad, integración, seguridad, despliegue, TCO. [file:2]
- **CDO**: gobierno, calidad, interoperabilidad, adopción. [file:2]
- **Gerencia / Alta Dirección**: resultados, control, trazabilidad, capacidad de decisión. [cite:13]
- **Operaciones / áreas usuarias**: facilidad, automatización, reducción de retrabajo. [file:2]

Estas rutas deberían estar claramente conectadas a la landing sectorial y al chatbot. [file:2]

---

## 6. Impacto en chatbot y asistentes IA

### 6.1 Rol del chatbot en sector público

El chatbot no debe comportarse como un simple widget de bienvenida. Debe funcionar como un **orientador de necesidad institucional**, capaz de traducir un dolor público a una recomendación consultiva comprensible. [file:2][cite:8][cite:9]

### 6.2 Requisitos derivados

#### Requisito CH-PUB-001
El chatbot debe reconocer lenguaje del sector público: expedientes, contrataciones, trazabilidad, observaciones, analítica, interoperabilidad, reportes ejecutivos, tableros, control, supervisión y automatización documental. [cite:6][cite:10][cite:13]

#### Requisito CH-PUB-002
El flujo guiado debe mapear correctamente sector → rol → problema y responder con criterios adaptados al buyer público. [file:2]

#### Requisito CH-PUB-003
El mensaje devuelto debe incluir elementos como:

- problema institucional interpretado;
- enfoque de solución;
- riesgo reducido;
- siguiente paso recomendado. [file:2]

#### Requisito CH-PUB-004
El chatbot debe evitar respuestas que suenen a marketing genérico y priorizar lenguaje de consultoría, acompañamiento y resolución gradual. [file:2][cite:8]

#### Requisito CH-PUB-005
En modo texto libre, la respuesta debe ofrecer valor antes de empujar a contacto. [file:2]

### 6.3 Nuevos casos de prueba sugeridos

| ID | Escenario | Resultado esperado |
|---|---|---|
| CH-PUB-QA-001 | “Tenemos expedientes dispersos y baja trazabilidad” | El bot propone diagnóstico de procesos, integración documental y trazabilidad. [cite:6][cite:11] |
| CH-PUB-QA-002 | “No confiamos en nuestros datos para un tablero ejecutivo” | El bot menciona calidad, gobierno, consolidación y roadmap de BI. [cite:10][file:2] |
| CH-PUB-QA-003 | “Queremos IA sin exponer datos sensibles” | El bot habla de gobernanza, despliegue controlado y opciones privadas. [cite:7][file:2] |
| CH-PUB-QA-004 | “Necesitamos alertas tempranas para supervisión” | El bot conecta analítica, modelos de riesgo y monitoreo. [cite:11][cite:12] |
| CH-PUB-QA-005 | Flujo guiado Sector Público | El bot no usa backend LLM en flujo determinista. [file:2] |

---

## 7. Impacto en CTA y contacto

### 7.1 Qué no debería hacer el sitio

No debería empujar un contacto genérico demasiado pronto ni asumir que la entidad está lista para comprar de inmediato. [file:2][cite:9]

### 7.2 Qué debería hacer el sitio

Debería ofrecer un punto de entrada con valor y bajo riesgo, por ejemplo:

- diagnóstico de madurez;
- evaluación de interoperabilidad;
- revisión de trazabilidad y reporting;
- priorización de casos de uso de datos/IA;
- assessment de automatización documental. [file:2][cite:13]

### 7.3 Requisitos derivados para formulario

| ID | Requisito | Justificación |
|---|---|---|
| CT-PUB-001 | El formulario debe recoger tipo de entidad | Permite contextualizar la conversación. [cite:9] |
| CT-PUB-002 | Debe recoger reto principal | Acelera la calificación consultiva. [file:2] |
| CT-PUB-003 | Debe permitir describir sistemas actuales o situación de datos | Muy relevante para CIO/CDO. [file:2] |
| CT-PUB-004 | Debe explicar qué recibe la entidad tras el contacto | Reduce incertidumbre. [file:2] |
| CT-PUB-005 | Debe evitar parecer un formulario comercial agresivo | Mejora confianza institucional. [cite:9] |

---

## 8. Backlog SDD derivado

| ID | Prioridad | Tema | Acción propuesta | Artefacto destino |
|---|---|---|---|---|
| SDD-PUB-016 | P0 | Continuidad institucional | Incorporar mensajes de institucionalización, transferencia y sostenibilidad en home, soluciones y CTA. [cite:13] | spec.md / copy spec |
| SDD-PUB-017 | P0 | Trazabilidad y auditoría | Añadir narrativa y casos ligados a trazabilidad documental y auditabilidad. [cite:6][cite:11] | spec.md / content plan |
| SDD-PUB-018 | P1 | Modalidad de contratación | Explicar tipos de servicio, fases, pilotos y acompañamiento. [cite:9][cite:13] | plan.md |
| SDD-PUB-019 | P1 | Gestión del cambio | Integrar mensajes y secciones sobre capacitación, soporte y adopción. [cite:13] | plan.md / tasks.md |
| SDD-PUB-020 | P1 | Riesgo reputacional | Rediseñar KPIs y casos para priorizar control, supervisión y reducción de riesgo. [cite:10][cite:11][cite:12] | content plan |
| SDD-PUB-021 | P1 | Gobernanza de IA | Agregar contenido sobre IA responsable, despliegue seguro y control institucional. [cite:7][file:2] | spec.md |
| SDD-PUB-022 | P2 | Chatbot sector público | Añadir intents y respuestas para lenguaje institucional. [cite:6][cite:10][cite:13] | tasks.md |
| SDD-PUB-023 | P2 | FAQ institucional | Crear FAQ específica para sector público. [file:2][cite:9] | tasks.md |

---

## 9. Integración con próximos artefactos Spec-Kit

### 9.1 spec.md siguiente

El siguiente spec debería incorporar una sección explícita de **“matices de decisión del buyer público”** y usar este documento como anexo o fuente de requerimientos. [cite:8][cite:9]

### 9.2 plan.md siguiente

El plan debe indicar cómo esos matices se traducen a componentes concretos: hero, landing sectorial, chatbot, FAQ, formulario, blog y prueba social. [file:2][cite:8]

### 9.3 tasks.md siguiente

Las tareas deben desglosarse no solo por frontend o contenido, sino también por **resultado consultivo esperado**:

- reconocimiento institucional;
- reducción de riesgo;
- claridad de contratación;
- valor antes de contacto;
- confianza técnica y política. [cite:9][cite:13]

### 9.4 walkthrough.md siguiente

El walkthrough futuro debe explicar no solo qué se cambió en código o contenido, sino **qué objeción institucional se buscó resolver** con cada decisión. [cite:8][file:2]

---

## 10. Conclusión

Antes de pasar a otros roles o sectores, conviene consolidar estos matices porque definen si el sitio será percibido por una entidad pública como una consultora verdaderamente preparada para operar en su realidad, o solo como una firma moderna con buen discurso. [cite:9][cite:13]

Desde una perspectiva SDD, este documento sirve como una **especificación complementaria de negocio y UX** para endurecer el modelo del buyer público y asegurar que las siguientes iteraciones del sitio respondan a la forma real en que el Estado evalúa, decide, adopta y contrata soluciones tecnológicas. [cite:8][cite:9]
