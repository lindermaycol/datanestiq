# Especificación Funcional — Spec 027: Experience Bank (Banco de Experiencia Curada 0-LLM)

## 1. Resumen Ejecutivo & Contexto
La Spec 016 definió el bucle de aprendizaje continuo `chat -> lead -> learn`. Actualmente, cuando una consulta compleja del usuario requiere la síntesis de respuestas mediante modelos de lenguaje (LLM free-tier / GraphRAG Spec 025), la respuesta generada se entrega en tiempo real pero no se capitaliza de forma reutilizable.

La **Spec 027** implementa un **Banco de Experiencias Curadas (Experience Bank)**. Este módulo captura automáticamente aquellas consultas complejas que generaron un resultado de negocio positivo (captura de lead o agendamiento de cita), las somete a un **gate obligatorio de curación humana** en el panel `/admin/`, y expone las respuestas aprobadas para ser reutilizadas **100% 0-LLM en el cliente** mediante el Web Worker `public/worker.js` antes de invocar la ruta de pago/free-tier LLM.

---

## 2. Historias de Usuario & Escenarios

### US-027.1: Reuso 0-LLM de Respuestas Probadas en el Chatbot
**Como** Visitante del Sitio Web,  
**Quiero** recibir respuestas inmediatas, precisas y probadas para mis consultas de negocio compuestas,  
**Para** resolver mis dudas sin latencia de red ni variaciones estocásticas en la respuesta.

- **Criterios de Aceptación**:
  - El chatbot consulta el corpus de experiencias **aprobadas** antes de invocar la ruta compleja `_callLLM`.
  - La coincidencia semántica utiliza el Web Worker Xenova (`public/worker.js`) mediante el contrato de indexado `type: 'index'` y búsqueda por defecto pasando `corpusTexts` de experiencias con un umbral estricto de similitud coseno $\ge 0.75$.
  - Si la consulta supera el umbral, la respuesta se entrega en el cliente **0-LLM (0 tokens consumidos)**, incluyendo un distintivo transparente: *"Respuesta de Experiencia Curada Datanestiq"*.
  - Si la coincidencia es menor a $0.75$, el flujo cae limpiamente a la ruta compleja de GraphRAG + `chat.php`.

### US-027.2: Ingesta Candidata Passthrough Basada en Resultados (Outcome-Gated)
**Como** Sistema de Captura de Inteligencia,  
**Quiero** identificar únicamente aquellas interacciones del chatbot que contengan una consulta compleja previa y resultaron en una conversión efectiva (lead capturado o cita agendada),  
**Para** proponerlas automáticamente como candidatos de experiencia sin saturar el banco con conversaciones inconclusas o irrelevantes.

- **Criterios de Aceptación**:
  - `Chatbot.jsx` mantiene en memoria de sesión la última consulta compleja y su respuesta sintetizada por LLM/GraphRAG (`last_complex_query`, `last_answer`, `source_route`).
  - Al completar la captura de un lead (`save_wizard.php`) o agendamiento de una cita (`book_appointment.php`), el cliente envía opcionalmente estos campos.
  - **Guard Outcome-Gated**: Si la sesión no registra una consulta compleja previa (`last_complex_query` está vacío), **NO se crea registro candidato** en `experience_bank`.
  - Toda consulta almacenada se sanitiza mediante `redactPii()` compartida (`question_redacted`) y nace con `status = 'pendiente'`. NINGÚN candidato `pendiente` es expuesto al público o servido por el Web Worker.

### US-027.3: Panel de Curación y Edición Humana (§2 Honestidad Radical)
**Como** Administrador / Operador de Consultoría,  
**Quiero** revisar, editar y aprobar o rechazar los candidatos de experiencia en el panel `/admin/`,  
**Para** garantizar la veracidad, ausencia de alucinaciones y vigencia comercial antes de habilitar su reuso.

- **Criterios de Aceptación**:
  - El panel `/admin/` (en `view-behavior`) incluye un módulo de gestión de candidatos pendientes.
  - El operador puede corregir el texto de la respuesta, asignar opcionalmente la URL de cita fuente (`cite_url`), ajustar la fecha de vencimiento (`expires_at`, por defecto 180 días) y marcar el registro como `aprobado` o `rechazado`.
  - La auto-aprobación está estrictamente **prohibida** (0 auto-aprobaciones).

---

## 3. Garantías de la Constitución Operativa

### 📜 §2 Honestidad Radical (Curación Obligatoria & Provenance)
- **Gate Humano Inquebrantable**: Ninguna respuesta generada por un modelo de lenguaje se re-sirve de forma autónoma. Todo elemento del `experience_corpus` requiere aprobación explícita humana (`reviewed_by`).
- **Frescura / TTL (180 Días)**: Toda experiencia aprobada cuenta con una fecha de caducidad (`expires_at`). Al expirar, deja de ser servida automáticamente hasta su re-revisión.

### 🛡️ §6 Privacidad y Sanitización de PII
- Toda pregunta guardada en `experience_bank` pasa por la función compartida `redactPii()` (`question_redacted`).
- El operador humano verifica en la interfaz de curación que la respuesta aprobada esté libre de nombres o datos privados de clientes previos.

### ⚡ 0-LLM y Eficiencia
- Las experiencias aprobadas se recuperan en el navegador del usuario utilizando el Web Worker `public/worker.js` (Transformers.js / Xenova MiniLM), reduciendo la latencia de respuesta a $<50\text{ ms}$ y eliminando consumo de tokens LLM.
