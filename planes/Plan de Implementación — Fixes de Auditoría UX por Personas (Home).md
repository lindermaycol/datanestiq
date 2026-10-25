# Plan de Implementación — Fixes de Auditoría UX por Personas (Home)

Extensión de **Spec 002 (microexperiencias)** y **Spec 013 (A9 — adaptación por contexto)**.

## Contexto y Diagnóstico

Una auditoría UX por personas (CFO/CIO/CDO/CEO) validó la efectividad de las mejoras recientes del home y detectó 6 ajustes específicos de conversión y claridad de interfaz. Este plan aborda los 6 fixes priorizados manteniendo estrictamente los guardarraíles de la Constitución (0-LLM en flujo guiado, datos reales de taxonomía, cero testimonios/cifras fabricadas).

---

## User Review Required

> [!IMPORTANT]
> **🔴 EXCLUSIÓN DE HONESTIDAD RADICAL (Constitución §2):**
> La auditoría recomendó *"agregar casos ROI anonimizados o ficticios de sector público / CDO"*. Tal como establece el prompt y la Constitución §2, **no se crearán datos, testimonios ni casos de clientes ficticios**. Las métricas de ROI continuarán etiquetadas explícitamente como `[EST] Escenario Ilustrativo`.

> [!NOTE]
> **🔴 Decisión de Diseño — Propuesta para Fix 6 (Chips de Rol):**
> Actualmente los chips del Hero mezclan sectores y roles (`Sector Público`, `Finanzas / CFO`, `Estrategia / CEO`), lo que deja sin entrada a un **CDO de finanzas** o un **CIO**.
> **Propuesta:** Estandarizar los chips por **Roles C-Level** alineados 1:1 con las personas de la taxonomía (`personas.json`):
> 1. 💼 **Finanzas (CFO)** → `handleSelect('finanzas', 'cfo')`
> 2. 🛡️ **Tecnología (CIO)** → `handleSelect('publico', 'cio')`
> 3. 📊 **Datos & IA (CDO)** → `handleSelect('finanzas', 'cdo')`
> 4. 🚀 **Estrategia (CEO)** → `handleSelect('retail', 'ceo')`
> 
> *Si prefieres mantener el texto anterior y solo agregar un 4º chip ("Datos / CDO"), indícalo al revisar este plan.*

---

## Proposed Changes

### Fix 1 — Renderizado de Markdown Seguro en el Copiloto

#### [MODIFY] [CopilotDemo.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/CopilotDemo.jsx)

- **Diagnóstico:** `TypewriterText` solo procesa `**bold**` y saltos de línea. Los enlaces en formato Markdown `[texto](url)` se renderizan como texto plano sin formato.
- **Solución:** Reutilizar y adaptar la lógica `safeText` / `formatText` de `Chatbot.jsx`:
  1. Escapar caracteres HTML `<` y `>` para prevenir XSS.
  2. Parsear hipervínculos `[texto](url)` a enlaces HTML `<a href="url" class="text-brandCyan underline hover:text-white transition-colors" target="_blank" rel="noopener noreferrer">texto</a>`.
  3. Parsear viñetas `-` / `*` y listas numeradas con indentación visual.

---

### Fix 2 — Feedback de Carga y Cold-Start en Buscador Semántico

#### [MODIFY] [SemanticSearch.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/SemanticSearch.jsx)

- **Diagnóstico:** El primer uso del buscador semántico requiere la descarga local del modelo de embeddings Edge AI (~23s en frío).
- **Solución:**
  1. **Skeleton Loader:** Renderizar 3 tarjetas animadas `animate-pulse` con efecto cristal en la zona de resultados durante los estados `loading_model` y `searching`.
  2. **Mensaje Prominente:** Mostrar un badge con el progreso exacto: *"Cargando modelo de IA por primera vez... X% (solo 1 vez por sesión)"*.
  3. **Feedback Inmediato:** Garantizar indicación visual de instantaneidad cuando el modelo ya está en caché (`status === 'ready'`).

---

### Fix 3 — Herencia de Contexto Activo en el Chatbot (0-LLM)

#### [MODIFY] [Chatbot.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)

- **Diagnóstico:** Si el usuario selecciona un chip de rol/sector en el Hero (ej. CEO) y abre el Chatbot, la conversación inicia desde cero preguntando el sector.
- **Solución:**
  1. Importar `userContext` de `@nanostores/react` en `Chatbot.jsx`.
  2. Al inicializar el bot, leer `userContext`:
     - Si hay **sector + rol**: saltar directo al paso `problem` pre-cargando la selección.
     - Si hay solo **sector**: saltar al paso `role`.
     - Si hay solo **rol**: sugerir sectores afines del rol (usando `persona.relevantSectors`).
  3. Actualizar la frase inicial del asistente para reconocer la persona heredada (ej. *"¡Hola! Veo que estás explorando como CFO. ¿Cuál de estos procesos financieros te genera más cuellos de botella hoy?"*).
  4. **Guardarraíl 0-LLM:** Lectura de Nanostores client-side y mutación de estado local React; **0 llamadas HTTP extra**.

---

### Fix 4 — Pre-llenado de Formulario de Captura de Lead

#### [MODIFY] [Chatbot.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx) / [MultiStepWizard.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/MultiStepWizard.jsx)

- **Diagnóstico:** Al llegar al formulario de captura de lead, los campos `organizacion`, `reto` y `stack` inician vacíos.
- **Solución:**
  1. Pre-llenar `reto` con la consulta o problema seleccionado si el campo está vacío.
  2. Pre-llenar `organizacion` con el sector heredado (ej. *"Organización del sector Finanzas"*).
  3. Usar `useRef` para pre-llenar **una sola vez** al abrir la tarjeta de lead, permitiendo al usuario editar o borrar libremente cualquier campo.
  4. Reutilizar la canalización segura de `save_wizard.php` (SQLite + CSV fallback fuera del webroot).

---

### Fix 5 — Microcopy Reforzado por Rol en Hero (4 Roles)

#### [MODIFY] [HeroRoleLine.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/islands/HeroRoleLine.jsx)

- **Diagnóstico:** La micro-línea actual muestra solo el primer objetivo (`goals[0]`), siendo demasiado sutil para captar la atención del comprador.
- **Solución:**
  1. Reforzar el diseño del banner con un contenedor de tarjeta de vidrio (`glass-card` con borde cyan tenue).
  2. Construir la propuesta de valor para los 4 roles principales (`cfo`, `cio`, `cdo`, `ceo`) desde `personas.json`:
     - **CFO:** *"ROI medible y TCO defendible ante directorio · Criterio: Payback Period e impacto en EBITDA"*
     - **CIO:** *"Modernización de sistemas legacy con alta disponibilidad · Criterio: Seguridad certificada y reducción de TCO"*
     - **CDO:** *"Gobernanza de datos y eliminación de silos · Criterio: Calidad de datos y escalabilidad corporativa"*
     - **CEO:** *"Transformación digital y ventaja competitiva asimétrica · Criterio: Crecimiento de ingresos y cuota de mercado"*
  3. **Constitución §2:** Salida 100% derivada del esquema de la taxonomía.

---

### Fix 6 — Entrada para CDO y Estandarización de Chips

#### [MODIFY] [ContextChips.jsx](file:///C:/xampp/htdocs/datanestiq/src/components/ui/ContextChips.jsx)

- **Solución:** Actualizar la lista de chips en el Hero para incluir los 4 buyer roles principales de forma homogénea (CFO, CIO, CDO, CEO), asignando los sectores afines especificados en `personas.json`.

---

## Verification Plan

### Automated Verification
1. **Taxonomía:** Ejecutar `node scripts/build-taxonomy.mjs` para asegurar cero regresiones en los JSONs.
2. **Build de Producción:** Ejecutar `npm run build` para validar que las 38+ páginas compilan correctamente sin errores de sintaxis o hidratación.

### Manual Verification (Navegador)
1. **Copiloto (Fix 1):** Probar respuesta con enlace y verificar que los tags `[texto](url)` se renderizan como hipervínculos azules/cyan clicables.
2. **Cold-start Buscador (Fix 2):** Simular primera búsqueda y confirmar animación de 3 tarjetas skeleton y badge con porcentaje de carga del modelo Edge AI.
3. **Chatbot con Contexto (Fix 3):** Hacer clic en chip "CFO", abrir Chatbot y confirmar que inicia en la etapa de rol/problema con mensaje personalizado sin llamadas a `chat.php`.
4. **Formulario (Fix 4):** Confirmar que el formulario de contacto aparece con el reto/sector pre-llenado y que se puede borrar/editar.
5. **Hero por Rol (Fix 5):** Alternar entre los 4 chips (CFO, CIO, CDO, CEO) y verificar la tarjeta de propuesta de valor en el Hero.
6. **Hallazgos Adicionales:** Documentar observaciones finales de auditoría en la respuesta.
