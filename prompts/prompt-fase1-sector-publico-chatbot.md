# Prompt para Antigravity — FASE 1 Sector Público + Chatbot (Spec 013 sobre fundación SDD)

Sigue **Spec-Driven Development (SDD)** y la metodología Spec-Kit. Este prompt está diseñado para operar SOBRE la fundación ya creada (profundidad de `/sectores/*` y `/soluciones/*`, hubs, legales, blog base) y aplicar **FASE 1 Sector Público + Chatbot** de la Spec 013 de conversión consultiva por rol × sector. [file:20][file:21]

En esta ronda, tu trabajo es producir artefactos SDD para MI revisión:

- `specs/013-conversion-consultiva-rol-sector/spec-publico.md` (sub-spec o anexo de Spec 013 orientado a Sector Público),
- `specs/013-conversion-consultiva-rol-sector/plan-publico.md` (plan técnico/UX de implementación),
- opcional: `specs/013-conversion-consultiva-rol-sector/tasks-publico.md` (backlog preliminar).

**No implementes código todavía.** Entrega estos artefactos como Markdown en el repo. [file:21]

---

## 0. Fundamentos (lee antes de diseñar)

### 0.1 Lo que ya existe (fundación)

Parte del trabajo ya está realizado y verificado en el prototipo Astro / WordPress:

- Páginas `/sectores/*` y `/soluciones/*` profundizadas, renderizando campos de taxonomía (`subSectors`, `kpis` `[EST]`, `regulations`, `techStack`, `proofPoints`, `competitivePositioning`, objeciones/respuestas genéricas). [file:20]
- Hubs `/sectores` y `/soluciones` creados desde taxonomía, expuestos en navegación. [file:20]
- Páginas legales `/privacidad` y `/terminos` existentes (sin `href="#"`). [file:20]
- Blog base creado según Spec 012 (estructura lista para generar posts). [file:20]

**Regla anti-duplicación:** No redefinas estas capas. **No vuelvas a especificar profundidad genérica** (subsectores, KPIs, regulaciones, techStack, proofPoints) en este prompt. Esa fundación ya está cubierta por Spec 003/007/011/012. [file:20]

### 0.2 Qué hace Spec 013 (marco consultivo)

Spec 013 define un **motor de conversión consultiva** parametrizado por `(rol, sector)`, que añade:

- bloque "Resolvemos tus dudas" por objeción del buyer; [file:21]
- CTA consultivo dinámico por contexto; [file:21]
- intents de chatbot por rol/sector; [file:21]
- formulario de contacto consultivo; [file:21]
- evidencia/Casos ROI orientados al buyer; [file:21]
- blog por rol/sector; [file:21]
- captura de contexto de entrada y adaptación ligera (chips rol/sector + bus de contexto). [file:21]

Tu trabajo en esta Fase 1 es aplicar ese motor **al sector público**, con foco en buyers CDO/CIO/CISO/gerencia/operaciones de entidades públicas. [file:20][file:21]

---

## 1. Insumos específicos para Sector Público

Usa como fuente de verdad los siguientes insumos ya presentes en `planes/insumos-conversion-consultiva/`:

- `sector-publico-auditoria-ux.md` — backlog UX-PUB-001…015, user stories, spec de chatbot público (CHAT-PUB), fases de implementación. [file:20]
- `sector-publico-matices.md` — 6 matices del buyer público + backlog SDD-PUB-016…023 + requisitos de chatbot (CH-PUB) y contacto (CT-PUB). [file:20]

No inventes nuevos requisitos; **extrae, consolida y estructúralos** dentro de Spec 013 Fase 1. [file:21]

---

## 2. Objetivo de FASE 1 Sector Público

En una frase: convertir el recorrido del buyer público desde "vitrina premium" a **asesor de preventa consultiva institucional**, asegurando que una entidad pública pueda:

1. reconocer su contexto institucional, [file:20]
2. ver sus problemas descritos con precisión, [file:20]
3. entender cómo datos e IA los abordan sin romper operación ni cumplimiento, [file:20]
4. percibir reducción de riesgo (político, reputacional, de control), [file:20][cite:13]
5. ver evidencia y narrativa compatible con el Estado, [file:20][cite:9]
6. iniciar un contacto consultivo con valor y bajo riesgo. [file:20][file:21]

El `spec-publico.md` y `plan-publico.md` deben dejar esto cristalino. [file:21]

---

## 3. Estructura esperada de `spec-publico.md`

### 3.1 Sección: Buyer público y matices

Define explícitamente el buyer público (entidades del Estado) y sus matices críticos, tomando directamente de `sector-publico-matices.md`:

- continuidad institucional y ciclo político; [file:20]
- trazabilidad, auditoría y formalidad documental; [file:20][cite:6]
- modalidad de contratación (diagnóstico/piloto/fases, no producto puro); [file:20][cite:9]
- gestión del cambio y adopción; [file:20][cite:13]
- riesgo reputacional, control y exposición pública; [file:20][cite:13]
- independencia tecnológica, ética y gobernanza de IA. [file:20][cite:7]

Para cada matiz, especifica:

- qué tiene que ver con conversión consultiva; [file:21]
- qué componente del motor (A1–A9) lo debe abordar (hero, landing, CTA, chatbot, FAQ, blog, formulario, evidencias). [file:21]

### 3.2 Sección: Secuencia de conversión objetivo (A1)

Define la secuencia de conversión para sector público, adaptando A1 de Spec 013:

1. Reconocimiento inmediato del contexto público. [file:20]
2. Identificación del dolor institucional (expedientes, contrataciones, trazabilidad, interoperabilidad, dashboards). [file:20][cite:6][cite:13]
3. Correspondencia con soluciones de Datanestiq (páginas sector/solución + narrativa). [file:20]
4. Reducción de riesgo (seguridad, cumplimiento, continuidad, reputación). [file:20][cite:13]
5. Prueba de credibilidad (experiencia pública, insumos, casos `[EST]`). [file:20][cite:6][cite:9]
6. Conversión consultiva (diagnóstico, evaluación, workshop). [file:21]

### 3.3 Sección: Componentes del motor aplicados al sector público

Para cada componente A2–A9 de Spec 013, describe su versión pública:

- **A2 — Bloque "Resolvemos tus dudas"**: qué objeciones públicas se muestran (por rol) y cómo deben responderse. [file:20][file:21]
- **A3 — CTA consultivo dinámico**: qué CTA neutro se usa, y cuál es la variante cuando `userContext` indica sector público (ej. "Diagnóstico de madurez de datos e IA para tu entidad" / "Evaluación de interoperabilidad y analítica para contrataciones"). [file:21]
- **A4 — Intents del chatbot público (CH-PUB)**: qué problemas y vocabulario debe reconocer (expedientes, contrataciones, observaciones, trazabilidad, tableros, automatización documental). [file:20]
- **A5 — Formulario de contacto consultivo (CT-PUB)**: qué campos son obligatorios/opcionales para una entidad pública y cómo se describe el valor del contacto. [file:20][file:21]
- **A6 — Evidencia/Casos ROI**: qué tipo de evidencia aceptable debe mostrar (KPIs `[EST]` de control, supervisión, alertas, reducción de retrabajo) sin inventar casos reales. [file:21]
- **A7 — Blog por sector público**: qué temas editoriales debe cubrir (gobierno del dato, analítica de contrataciones, interoperabilidad, IA segura, trazabilidad, dashboards ejecutivos). [file:20]
- **A8 — Extensibilidad**: cómo se puede añadir nuevos roles públicos (ej. nuevas oficinas/reguladores) en el futuro sin romper el motor. [file:21]
- **A9 — Captura de contexto y adaptación**: cómo se usa `userContext = { rol, sector }` para resaltar contenido público sin interstitials. [file:21]

### 3.4 Sección: Guardarraíles (Sector Público)

Incluye un bloque de guardarraíles específicos:

- **No inventar casos reales ni logos** de entidades públicas; usar modelos `[EST]` claramente marcados. [file:21]
- **No registrar PII en claro**: reutilizar el canal `chat.php`/`secure_leads/` con PII redactada. [file:21]
- **No crear fricción excesiva** (sin interstitial que bloquee contenido; chips rol/sector deben ser descartables y recordar preferencia). [file:21]
- **No exclusivizar la marca al gobierno**: mantener compatibilidad con otros sectores en copy y navegación, aunque Sector Público tenga tratamiento especial. [file:20][cite:8]

---

## 4. Estructura esperada de `plan-publico.md`

El plan debe traducir `spec-publico.md` a diseño técnico/UX concreto. Usa secciones como mínimo:

### 4.1 Overview

- Resumen de objetivos de Fase 1 Sector Público. [file:20][file:21]
- Relación con la fundación y con el motor Spec 013. [file:20][file:21]

### 4.2 Cambios en home

- Qué ajustes de copy y componentes necesita el home para reflejar contexto público (línea secundaria en hero, sección "Cómo trabajamos con entidades públicas", bloque "Si hoy enfrentas…"). [file:20]
- Cómo se conecta esto con chips rol/sector y `userContext`. [file:21]

### 4.3 Cambios en `/sectores/publico`

- Cómo transformar la landing sectorial pública en página de decisión: subsectores, problemas por entidad, criterios por rol, modalidades de abordaje, CTA. [file:20]
- Qué bloques se añaden o ajustan (seguridad/despliegue, objeciones/respuestas, evidencia). [file:20][file:21]

### 4.4 Cambios en soluciones relevantes

- Qué secciones de `estrategia-datos-ia`, `sistemas-digitales`, `data-engineering`, `business-intelligence` deben alinearse con matices públicos (catalogo, linaje, calidad, interoperabilidad, on-prem/VPC, IA responsable). [file:20][file:21]

### 4.5 Chatbot Sector Público (CH-PUB)

- Diseño de intents: lista enumerada de intent names, ejemplos de utterances y expected responses, usando el vocabulario institucional. [file:20]
- Flujo guiado: cómo se mapean sector → rol → problema y cómo se llena el mensaje de salida (problema, solución, riesgo reducido, siguiente paso). [file:21]
- Modo texto libre: recomendaciones de tono y estructura de respuesta (valor primero, CTA después). [file:21]

### 4.6 Formulario consultivo para entidades públicas (CT-PUB)

- Campos: tipo de entidad, reto principal, sistemas/datos actuales, urgencia, contacto. [file:20]
- Mensajes explicativos: qué recibe la entidad tras enviar el formulario (diagnóstico, reunión, documento). [file:21]
- Integración: cómo reutilizar pipeline actual de captura segura. [file:21]

### 4.7 Blog público

- Lista 4–6 posts recomendados, con título, objetivo, público y relación con conversión. [file:20]
- Indicaciones para generación automática vía Spec 012 (`docs-generator.mjs`). [file:20]

### 4.8 FAQ pública

- Qué preguntas y respuestas deben existir para reducir objeciones específicas del sector público (tiempos, seguridad, integración, soporte, forma de trabajo, transferencia de conocimiento). [file:20][file:21]

### 4.9 Adaptación por contexto (A9)

- Cómo se implementa en high-level (sin entrar en código): chips rol/sector, almacenamiento de `userContext`, consumidores (`CTA`, `SemanticSearch`, chatbot). [file:21]

### 4.10 Riesgos y pendientes

- Riesgos de UX (fricción, sobrecarga de mensajes públicos). [file:20]
- Riesgos de contenido (no inventar casos, mantener balance público/privado). [file:21]
- Pruebas obligatorias para QA manual antes de considerar Fase 1 cerrada (Network del chatbot, responsive, consola, metas, rutas). [file:20][file:21]

---

## 5. Estructura sugerida de `tasks-publico.md` (opcional)

Si decides incluir `tasks-publico.md`, organiza el backlog en bloques:

- Home & hero & chips contexto. [file:20][file:21]
- Landing `/sectores/publico`. [file:20]
- Soluciones técnicas alineadas al sector público. [file:20]
- Chatbot CH-PUB intents + flows. [file:20][file:21]
- Formulario consultivo CT-PUB. [file:20][file:21]
- Blog y FAQ públicas. [file:20]
- QA, pruebas y walkthrough. [file:20][file:21]

Cada tarea debe indicar: componente, objetivo, referencia al spec (sección de `spec-publico.md`) y criterios de aceptación. [file:21]

---

## 6. Guardarraíles generales de esta Fase

- **No implementar código ni cambios directos en `.astro`/tema WordPress** en esta ronda. Solo producir documentación SDD. [file:21]
- **No redefinir profundidad genérica**: reutilizar la fundación para sectores/soluciones. [file:20]
- **No inventar evidencia ni casos reales** de entidades públicas; usar KPIs `[EST]` y ejemplos hipotéticos claramente marcados. [file:21]
- **No crear canales nuevos de PII**: reutilizar `chat.php`/`secure_leads/`. [file:21]

---

## 7. Entregable esperado

Cuando completes este trabajo, el repo debe contener:

- `specs/013-conversion-consultiva-rol-sector/spec-publico.md` — describiendo el buyer público, la secuencia de conversión, los componentes A2–A9 adaptados, guardarraíles y extensibilidad. [file:21]
- `specs/013-conversion-consultiva-rol-sector/plan-publico.md` — detallando cambios necesarios en home, sector público, soluciones, chatbot, formulario, blog, FAQ y adaptación de contexto. [file:20][file:21]
- opcional `tasks-publico.md` — backlog inicial de Fase 1 Sector Público. [file:20][file:21]

Tras mi revisión y luz verde, se definirá una siguiente ronda para implementar y validar en código (Astro/WordPress), incluyendo walkthrough y QA completo. [file:21]
