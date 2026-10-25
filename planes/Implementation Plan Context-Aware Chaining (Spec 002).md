# Implementation Plan: Context-Aware Chaining (Spec 002)

Basado en la nueva historia de usuario (User Story 4) y los requerimientos funcionales (FR-017, FR-018, FR-019), el objetivo es conectar las interacciones aisladas de la página en un "Journey" continuo. Si el usuario ya expresó un dolor en el buscador superior o en los sectores, el Wizard final debe recordarlo automáticamente y saltarse el Paso 1 genérico.

## 1. Contexto Técnico
- **Archivo Objetivo:** `c:/xampp/htdocs/datanestiq/prototype/app.js` y `index.html`.
- **Lógica:** Implementaremos un Singleton/Gestor de Estado Global (`window.DatanestiqContext`). Se utilizará el IntersectionObserver para detectar cuándo el usuario hace scroll hacia el Wizard y aplicar el bypass visual si hay contexto guardado.

## 2. Proposed Changes

### 2.1. Gestor de Estado Global (FR-017)
- **[MODIFY]** `app.js`: Inyectaremos al inicio del archivo la inicialización del objeto:
  ```javascript
  window.DatanestiqContext = {
      userChallenge: null,
      detectedSector: null
  };
  ```

### 2.2. Captura de Contexto (FR-018)
- **[MODIFY]** `app.js`: En la función del buscador semántico (al dar Enter) y en la función `generateSectorPitch` (inputs de los sectores), interceptaremos el texto escrito por el usuario y lo guardaremos en `window.DatanestiqContext.userChallenge`.

### 2.3. Bypass Inteligente del Wizard (FR-019)
- **[MODIFY]** `app.js`: Crearemos una función `checkContextAndBypassWizard()`.
- Usaremos un `IntersectionObserver` que vigile el div `#diagnostic-wizard`. Cuando el usuario haga scroll y el Wizard entre en pantalla:
  - Si `DatanestiqContext.userChallenge` NO es null:
    - Ocultar `wizard-step-1`.
    - Mostrar `wizard-step-2`.
    - Modificar dinámicamente el HTML del `wizard-step-2` inyectando el texto: *"Basado en tu reto con [userChallenge], pasemos al diseño de solución. ¿Dónde alojas estos datos actualmente?"*
    - Añadir un badge visual indicando "✨ Contexto Heredado de IA".
    - Avanzar la barra de progreso automáticamente al 66%.

## 3. Verification Plan
- **Prueba 1:** Recargar la página, hacer scroll directo al Wizard sin escribir nada. Debe mostrar el Paso 1 normalmente.
- **Prueba 2:** Escribir "Reportes lentos" en el buscador superior, hacer scroll hacia abajo. Al asomarse el Wizard, debe saltar al Paso 2 automáticamente y mostrar el badge.

---

> [!IMPORTANT]
> **User Review Required:**
> ¿Estás de acuerdo con utilizar el `IntersectionObserver` para gatillar este cambio cuando el usuario hace scroll hacia el formulario, creando ese efecto "Mágico" frente a sus ojos, o prefieres que el bypass suceda en *background* antes de que llegue a la sección?
