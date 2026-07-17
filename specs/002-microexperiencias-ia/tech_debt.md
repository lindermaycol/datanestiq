# Deuda Técnica: Spec 002 (Microexperiencias IA)

## Estado
- **Fase actual:** Implementado en Astro (producción), verificado E2E
- **Impacto:** Alto
- **Severidad:** Bloqueante para operaciones reales de captación de clientes.

## Lista de Deuda Técnica (Technical Debt)

### 1. Simulación de Base de Datos (LocalStorage) - [RESUELTO]
- **Descripción:** Actualmente, los Leads (contactos) capturados a través del Wizard de Diagnóstico se guardan en el `localStorage` del navegador del usuario bajo la clave `datanestiq_leads`.
- **Riesgo:** Si un CTO llena el formulario, los datos se quedan en su computadora. Datanestiq no los recibe.
- **Resolución Aplicada:** Se ha migrado el almacenamiento a un endpoint PHP (`save_wizard.php`) y un script batch extractivo que guarda los CSVs en un directorio `/secure_leads/` protegido con `.htaccess` (Require all denied), eliminando la fuga de datos por localStorage.

### 2. Interfaz de Chatbot no Acoplada a Backend Real - [RESUELTO (Híbrido React/PHP)]
- **Descripción:** El chatbot simula respuestas en el frontend o hace peticiones básicas a un script temporal de PHP.
- **Riesgo:** Lógica de negocio frágil.
- **Resolución Aplicada:** Se ha migrado `Chatbot.jsx` a una Máquina de Estados Híbrida (FR-014/015), proporcionando un árbol determinista con latencia cero mediante botones HTML accesibles. Las opciones de escape de texto libre apuntan al LLM con un payload que incluye el contexto de la máquina. `chat.php` se actualizó a un diseño "Tool-Centric" inyectando el contexto de forma modular. 
- **Nota de Arquitectura LangGraph:** El plan original sugería reescribir todo a Python/FastAPI+LangGraph+Redis. Se ha determinado que para el chatbot en vivo, la solución actual (React State Machine + PHP proxy con Prompt Caching) es óptima, ahorrando llamadas innecesarias al LLM. El backend en Python queda diferido exclusivamente para flujos asíncronos de copy (Spec 004), por lo que esta deuda se considera saldada para el ámbito conversacional en vivo.

### 3. Variables Globales Contaminando Scope - [RESUELTO]
- **Descripción:** El "Context-Aware Chaining" utiliza `window.DatanestiqContext` en el scope global del navegador.
- **Riesgo:** Posibles colisiones de nombres o manipulaciones indeseadas.
- **Resolución Aplicada:** Refactorizado completamente mediante `nanostores`. Se aislaron `userChallenge` y `semanticHighlight`, habilitando reactividad pura entre los componentes Astro/React.

### 4. Brecha de Implementación IA (Transformers.js, Copiloto) - [RESUELTO]
- **Descripción:** La Spec 002 documentaba componentes avanzados de IA en cliente y servidor (Buscador Semántico, Copiloto C-Level, Wizard Adaptativo) como si ya estuviesen implementados, pero en la realidad no existen en el código actual.
- **Impacto:** Deuda de documentación y falta de cumplimiento de las historias de usuario 1, 2, 3 y 4 completas.
- **Resolución Aplicada:** Se implementó exitosamente el Roadmap de Fases (A a F), incorporando Transformers.js vía CDN (Web Worker), Semantic Search, Copilot Demo C-Level, y Context-Aware Chaining con puntaje de madurez.

### 5. Riesgo Financiero por Ausencia de Gobernanza IA - [RESUELTO]
- **Descripción:** El proxy `chat.php` estaba expuesto a loops infinitos de agentes y ataques de denegación de billetera al no contar con límites diarios (kill-switches) ni monitoreo del costo por invocación, contraviniendo políticas básicas de FinOps.
- **Resolución Aplicada:** Se implementaron los FR-020, FR-021 y FR-022. `chat.php` ahora registra latencia y uso de tokens a `usage_metrics.jsonl`, posee un límite diario de gasto (fail-open) que detiene automáticamente el flujo devolviendo un JSON con un mensaje cortés si se vulnera el threshold, e incorpora un enrutador por intención (FR-009/FR-013) que direcciona intenciones sencillas hacia modelos baratos y consultas complejas de C-Level hacia Llama-70B.

### 6. Fragmentación de Datos (Hardcoding en Islas) - [RESUELTO]
- **Descripción:** Las islas interactivas (`DiagnosticWizard.jsx`, `CopilotDemo.jsx`, `Chatbot.jsx`) tenían sectores y prompts hardcodeados en lugar de consumir la taxonomía oficial de la empresa.
- **Riesgo:** Inconsistencia comercial, doble esfuerzo de mantenimiento y desalineación con la estrategia.
- **Resolución Aplicada:** Se enriqueció `taxonomyCorpus.json`, `sectorsCorpus.json` y `personas.json` con atributos avanzados (`kpis`, `regulations`, `objections`, `proofPoints`). Se refactorizaron las islas para consumir estos campos dinámicos (ej. el Chatbot rutea por buyerRole, DiagnosticWizard usa KPIs reales del sector), cerrando por completo la deuda UX y apalancando la taxonomía enriquecida (Spec 011).

### 7. Umbral Poco Selectivo en Buscador Semántico (Precisión y Rendimiento) - [RESUELTO]
- **Descripción:** El modelo de embeddings original (`all-MiniLM-L6-v2`) estaba optimizado para inglés, lo que degradaba la precisión de búsqueda para queries en español, generando falsos positivos. Además, el modelo descargaba sus pesos de manera síncrona en el primer uso, provocando latencia percibida.
- **Resolución Aplicada:** Se reemplazó el motor por `paraphrase-multilingual-MiniLM-L12-v2` cuantizado, el cual mejora drásticamente la discriminación semántica en español. Se mitigó el impacto de su mayor peso mediante la implementación de un mecanismo de **Warmup en background** (usando `requestIdleCallback`) que precarga el modelo mientras el usuario navega, logrando una experiencia de búsqueda instantánea.
