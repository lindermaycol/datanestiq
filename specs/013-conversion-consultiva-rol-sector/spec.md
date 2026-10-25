# Spec 013: Conversión Consultiva por Rol × Sector (Marco + Fases)

## 1. Tesis y Secuencia de Conversión

El objetivo de esta especificación es implementar un motor consultivo parametrizado por la tupla `(rol, sector)` que reposicione el recorrido del comprador. En lugar de una "vitrina premium" estática, el sitio actuará como un asesor de preventa consultiva.

### Secuencia de Conversión Objetivo (A1)
1. **Reconocimiento del contexto:** El usuario selecciona o se le infiere su rol y sector.
2. **Identificación del dolor:** El sitio adapta sus textos para espejar los retos inmediatos de ese perfil.
3. **Correspondencia con solución:** Se resaltan las capacidades tecnológicas que resuelven ese dolor específico.
4. **Reducción de riesgo:** Se abordan directamente las objeciones del usuario (ej. lock-in, TCO, seguridad).
5. **Prueba de credibilidad:** Se presentan modelos de ROI o impactos institucionales etiquetados explícitamente como escenarios `[EST]`.
6. **Conversión consultiva:** Llamado a la acción de bajo riesgo ("Diagnóstico estratégico gratuito").

### Matices de decisión por buyer
- **Sector Público (Institucional):** Busca continuidad, gobernanza, trazabilidad y evitar riesgos normativos o de auditoría. Modalidad de proyectos por hitos.
- **CFO (Económico):** Busca ROI demostrable, mitigación de pérdidas financieras (fraude, churn), optimización de TCO y modelos de payback claros.
- **CEO (Estratégico):** Busca ventaja competitiva asimétrica, posicionamiento en el mercado y disrupción prudente, reacio a ver la IA como una simple "moda técnica".

---

## 2. El Motor A1–A9 (Parametrizado por `(rol, sector)`)

### A1 — Secuencia de Conversión
Común a todo buyer. Enmarca la travesía del visitante a través de la reducción progresiva de fricción y el empoderamiento consultivo.

### A2 — Bloque "Resolvemos tus dudas"
Dado un `(rol, sector)`, renderiza las `objections` de la persona mapeadas contra `objectionResponses` desde la taxonomía base (Fase 3). Se trata de un FAQ consultivo, honesto y directo, exento de hardcodeo en `.astro`.

### A3 — CTA Consultivo Dinámico
- **Default:** "Diagnóstico estratégico gratuito" (consultivo-neutro, bajo riesgo).
- **Activado por contexto:** Adapta su propuesta de valor. 
  - *Ejemplo Público:* "Solicita un diagnóstico de madurez de datos e IA".
  - *Ejemplo CFO:* "Auditoría de ROI y pérdidas evitables".
El CTA lee el store local (`userContext`) y obtiene las variantes de la taxonomía.

### A4 — Intents del Chatbot por Buyer
El chatbot mantiene su invariante **0-LLM** para flujos guiados. El árbol pre-renderizado entrega: `problema interpretado → enfoque de solución → riesgo reducido → siguiente paso`. Para texto libre, el motor enruta contra perfiles de `personas.json`.

### A5 — Formulario de Contacto Consultivo
Recopila: tipo de organización, reto principal y situación del stack de datos. 
**Restricciones:** PII redactada en logs, almacenamiento en `secure_leads/` bloqueado públicamente vía .htaccess/nginx.

### A6 — Evidencia / Casos ROI
Estrategia de prueba de credibilidad basada estrictamente en **modelos e hipótesis demostrativas**, señaladas mediante el prefijo `[EST]`. Sin fabricación de testimonios ni entidades falsas.

### A7 — Blog por Rol/Sector
Generación programática usando `docs-generator.mjs` (target `blog`) para nutrir el cluster de artículos asociados al buyer, asegurando fechas estáticas (ISO 8601 sin comillas) para compilación `npm run build` en verde.

### A8 — Extensibilidad (Agregar nuevas fases)
Para agregar un nuevo rol (ej. CISO):
1. Poblar `personas.json` con `pains`, `goals`, `objectionResponses` del CISO.
2. Definir una nueva Fase en esta Spec o heredarla directamente, ya que los bloques de A1 a A7 responderán dinámicamente si el `userContext` es activado.

### A9 — Captura de Contexto de Entrada
- **Mecanismo Primario:** Fila de **chips descartables** en el hero ("Soy [rol] · en [sector]"). Interfaz limpia de 1 solo clic. Persistido en localStorage (no vuelve a salir si se descarta).
- **Mecanismo Secundario:** El chatbot guiado alimenta silenciosamente este mismo `userContext`.
- **Integración:** Extiende los nanostores de `src/store/index.ts` (`userChallenge`, `semanticHighlight`).

---

## 3. Fases de Implementación (Paquetes por Buyer)

### Fase 1 — Sector Público
*Insumos:* `planes/insumos-conversion-consultiva/sector-publico-auditoria-ux.md` y `sector-publico-matices.md`
- **Home:** Adaptación institucional.
- **Página de Decisión:** `/sectores/publico` enriquece modalidades de abordaje (pilotos) e interoperabilidad.
- **Seguridad:** Énfasis en VPC / On-Prem.
- **Chatbot:** Intents institucionales (expedientes, auditorías).
- **Blog:** 4-6 artículos enfocados al gobierno público (top-up de los 2 ya creados).

### Fase 2 — CFO Económico (Finanzas / Seguros / Minería)
*Insumo:* `planes/insumos-conversion-consultiva/cfo-finanzas-seguros.md`
- **Landing (Finanzas/Seguros):** Lecturas económicas sobre KPIs (`[EST]`).
- **Coexistencia:** Interoperabilidad sin reemplazar el ERP / Core bancario.
- **Casos ROI:** ≥1 de finanzas y ≥1 de seguros como **escenarios modelo ilustrativos**.
- **Business Case:** Herramienta descargable o página de cálculo para construcción de ROI.

### Fase 3 — CEO Estratégico (Multi-sector)
*Insumo:* `planes/insumos-conversion-consultiva/ceo-estrategico.md`
- **Home:** Narrativa estratégica sobre la ventaja competitiva asimétrica, combatiendo el dolor "es una moda".
- **Rutas "Para CEO":** Mismo motor, aplicado a la visión de diferenciación a largo plazo.
- **Chatbot:** Sparring estratégico evaluando horizonte y riesgos.
- **CTA:** Evaluación del potencial estratégico.

---

## 4. Guardarraíles (NO negociables)
1. **HONESTIDAD ABSOLUTA (BLOQUEANTE):** Ningún testimonio, logo, métrica pasada o certificación será inventado. Todo caso ROI es un modelo ilustrativo, obligatoriamente etiquetado bajo `[EST]`.
2. **Taxonomía Fuente:** Todo contenido de profundización sale de la taxonomía. Nada de textos estáticos extensos inventados en `.astro`.
3. **Privacidad y Seguridad:** PII redactada en la captura del lead. Chatbot mantiene el flujo 0-LLM.
4. **Integridad Build:** `npm run build` en verde, sin duplicar rutas.

---

## 5. Criterios de Aceptación y Exclusiones
- [ ] Flujo guiado del chatbot 0-LLM confirmado para cada fase, sin propiedades `undefined`.
- [ ] Motor `userContext` integrado vía `nanostores` (extiende, no duplica). Chips en hero persistidos (LocalStorage).
- [ ] Renderización asíncrona de CTA basado en contexto activo.
- [ ] Formulario consultivo integrado que intercepta contexto y ofusca PII.
- [ ] Casos de uso `[EST]` explícitamente clarificados como "modelo ilustrativo".
- **EXCLUSIONES:** Esta especificación **NO** duplica el renderizado fundamental cubierto en Spec 011 Fase 4 (ya implementado). Asume que `techStack`, `deploymentModels` y `objectionResponses` elementales ya están visibles, y aquí se dedican a su **re-priorización** visual basada en contexto.
