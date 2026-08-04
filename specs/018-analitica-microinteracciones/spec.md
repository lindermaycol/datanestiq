# Spec 018: Analítica de Micro-Interacciones (Behavioral & Engagement)

**Estado:** 🟠 DISEÑADA, PENDIENTE DE AUDITORÍA (2026-08-04)

---

## 1. WHY (Motivación y Contexto)

La **Spec 016** (Analítica de Negocio) captura el embudo de conversión y el recorrido de los usuarios que completan con éxito el formulario y se convierten en **leads**. Sin embargo, la gran mayoría de los visitantes del sitio son usuarios anónimos que interactúan con las micro-experiencias (Buscador Semántico, Diagnóstico GUI, Demo de Copiloto) pero **abandonan el flujo sin dejar sus datos de contacto**.

Actualmente existe un punto ciego sobre el comportamiento de estos usuarios:
1. No sabemos qué sectores, roles o temas son los más buscados o seleccionados en las chips de contexto.
2. Desconocemos en qué paso exacto del chatbot guiado o del asistente diagnóstico deciden abandonar la experiencia.
3. No hay visibilidad del enganche relativo entre las distintas islas interactivas (ej. si el usuario busca algo, luego prueba el copiloto, y finalmente abre el chatbot).

La **Spec 018** resuelve este problema mediante una instrumentación técnica ligera y de primera parte (first-party) de eventos de interacción, permitiendo optimizar el contenido y flujo de conversión basándonos en datos reales de comportamiento de todos los visitantes.

---

## 2. WHAT (Alcance Funcional)

### 1. Instrumentación de Eventos de Interacción (Capa de Cliente)
- **Beacons de Telemetría:** Implementación de capturadores de eventos no bloqueantes en las islas de Astro:
  - Clic en chips de contexto (`ContextChips`).
  - Consultas textuales en el buscador semántico (`SemanticSearch`).
  - Clics en tarjetas de demostración (`CopilotDemo`).
  - Avances y abandonos en las fases del asistente de diagnóstico (`DiagnosticWizard`).
  - Progresión de pasos en el chatbot guiado (`Chatbot`).
- **fire-and-forget:** Emisión de eventos mediante `navigator.sendBeacon()` o peticiones HTTP fetch asíncronas no-bloqueantes, garantizando cero degradación de velocidad y manejo silencioso de errores.

### 2. Endpoint de Escritura y Redacción PII (Capa de Servidor)
- **Endpoint Gated para Escritura:** `public/api/track_event.php`, expuesto al cliente de forma pública pero con permisos de **solo escritura** (ningún cliente anónimo puede listar o consultar datos).
- **Sanitización de Datos (§6):** Redacción estricta en el servidor de cualquier patrón que simule PII (correos electrónicos o números telefónicos) en campos de texto libre (como búsquedas o mensajes) antes de su persistencia.
- **Unificación del Modelo:** Persistencia de eventos en la tabla `interaction_events` de `crm.sqlite`. El recorrido de leads de la Spec 016 se deducirá consultando esta tabla por `session_id`, eliminando la duplicidad en el modelo de datos.

### 3. Dashboard de Comportamiento (Panel `/admin/`)
- **Sección de Engagement:** Nueva pestaña "Comportamiento" en el panel `/admin/` (detrás de `auth.php` y con Vanilla CSS).
- **Métricas Clave:**
  - Desglose de popularidad (top sectores, top roles, top consultas y escenarios).
  - Tasa de drop-off por etapa en el asistente de diagnóstico y chatbot.
  - Recorrido agregado (pathing) entre componentes.

---

## 3. CONSTRAINTS (Guardarraíles Duros)

1. **Constitución §5 (0-LLM):** La instrumentación es puramente de registro técnico. Ningún beacon o evento de interacción realiza llamadas a APIs de LLM.
2. **Constitución §6 (Seguridad y Privacidad):** Cero trackers de terceros (no Google Analytics, no cookies comerciales). Toda la telemetría es anónima utilizando `session_id` efímero del navegador. Se prohíbe el *fingerprinting* del dispositivo.
3. **Cero Impacto en UX y Desempeño:** Los beacons se envían sin `await`, con fallos silenciosos encapsulados en `try/catch`, asegurando que si la base de datos o el endpoint fallan, el usuario continúe su navegación con latencia cero.
4. **Constitución §2 (Honestidad de Datos):** Si la muestra acumulada de eventos es insuficiente (menos de 20 eventos globales), el panel mostrará "Datos de comportamiento insuficientes", prohibiendo la simulación de métricas ficticias.
5. **Acceso Restringido:** Las lecturas y consultas de analítica de microinteracciones quedan confinadas estrictamente detrás de la autenticación `auth.php`.

---

## 4. OUT-OF-SCOPE (Fuera de Alcance)

- Personalización dinámica de la UI en tiempo real según el historial del usuario.
- Pruebas A/B en caliente controladas por servidor.
- Notificaciones push o disparadores automáticos de campañas de email.
- Integración con píxeles publicitarios de redes sociales.

---

## 5. TASKS (Plan de Trabajo de Diseño)
- [x] Crear manifiesto funcional `specs/018-analitica-microinteracciones/spec.md`.
- [x] Diseñar el esquema de tabla `interaction_events` y la integración del journey 016 en `data-model.md`.
- [x] Estructurar fases de desarrollo y gates de deploy en `plan.md`.
- [x] Declarar implicaciones de volumen y mitigaciones de latencia en `tech_debt.md`.
- [x] Agregar la Spec 018 a `planes/ESTADO-SPECS.md` y `src/data/specsStatus.json` respetando el build-gate.
