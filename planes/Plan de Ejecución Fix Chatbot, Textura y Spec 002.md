# Plan de Ejecución: Fix Chatbot, Textura y Spec 002

Este documento detalla el plan de acción para abordar las correcciones identificadas en la auditoría del Chatbot, el fondo global, el hardening del proxy PHP y la honestidad de la documentación (Spec 002).

## User Review Required

> [!IMPORTANT]
> Revisa este plan cuidadosamente. Los cambios de seguridad y arquitectura (Block 3) limitarán temporal o permanentemente el acceso al chatbot si las peticiones exceden los límites o no provienen del origen correcto. El bloque 5 define el roadmap pero no ejecutará código.

## Proposed Changes

### 1. BLOQUE 1: Chatbot Global
El Chatbot se moverá al layout principal para que esté disponible en todas las páginas y sus CTAs funcionen correctamente.
- **[MODIFY] `src/layouts/BaseLayout.astro`**: Importar y renderizar `<Chatbot client:idle />` antes del cierre del body.
- **[MODIFY] `src/pages/index.astro`**: Eliminar la importación y renderizado de `Chatbot` para evitar duplicación.

### 2. BLOQUE 2: Textura de Fondo Global
Se portará la malla fina y el glow radial del prototipo al Layout, eliminando las texturas locales para evitar inconsistencias visuales.
- **[MODIFY] `src/layouts/BaseLayout.astro`**: Añadir las capas de fondo global (malla fina de 24px y glow radial color brand `#2563EB`) inmediatamente después de abrir el `<body>`.
- **[MODIFY] `src/components/ui/Hero.astro`**: Eliminar la clase `bg-[url('/grid.svg')]`.
- **[MODIFY] `src/components/ui/SolutionHero.astro`**: Eliminar la clase `bg-[url('/grid.svg')]` si existiera (se ha verificado y SolutionHero actualmente usa `/grid.svg`, por lo que se removerá).
- **[DELETE] `public/grid.svg`**: Se eliminará el archivo si no quedan referencias, o simplemente dejaremos de usarlo (revisaremos dependencias).

### 3. BLOQUE 3: Hardening del Proxy (`chat.php`)
Se aplicarán parches de seguridad para cumplir con la Spec 002 y la Constitution.
- **[MODIFY] `public/api/chat.php`**: 
  1. **Enmascaramiento de PII**: Modificar `logInteraction` para reemplazar emails y teléfonos por `[EMAIL_REDACTED]` y `[PHONE_REDACTED]` usando regex.
  2. **CORS Restringido**: Validar el origen de la petición contra una lista blanca (`https://datanestiq.com`, `http://localhost`).
  3. **Límites de Input y Turnos**: Retornar `400 Bad Request` si la cadena sobrepasa 2000 caracteres o si el arreglo de mensajes supera los 20 elementos.
  4. **System Prompt Aislado**: Trasladar el string de `SYSTEM_PROMPT` a PHP, descartar mensajes con `role == 'system'` provenientes del frontend e inyectar el prompt directamente en el servidor.
  5. **Rate Limiting**: Implementar un andamiaje básico de rate limiting usando un archivo temporal (o session/APCu dependiendo de la disponibilidad, priorizando un `// TODO` o control de session temporal) retornando `429 Too Many Requests`.
  6. **Alineación de Modelo**: Usaremos `llama-3.1-8b-instant` en el código (para mayor velocidad) y actualizaremos la Spec 002 para reflejarlo consistentemente.
- **[MODIFY] `src/components/islands/Chatbot.jsx`**: Eliminar el envío de `role: 'system'` en el array inicial de mensajes.

### 4. BLOQUE 4: Honestidad SDD (Spec 002)
Se actualizará la documentación para reflejar que componentes avanzados (Transformers.js, Copiloto) aún no están construidos.
- **[MODIFY] `specs/002-microexperiencias-ia/spec.md`**: Reescribir la sección "Assumptions & Bridges" para indicar que es la arquitectura "objetivo" y dejar claro qué está pendiente. Actualizar mención del modelo a `llama-3.1-8b-instant`.
- **[MODIFY] `specs/002-microexperiencias-ia/tasks.md`**: Reflejar el estado real (`[ ]` para Transformers.js, Copilot Demo, etc.). Añadir los items especificados en el prompt.
- **[MODIFY] `specs/002-microexperiencias-ia/tech_debt.md`**: Registrar la brecha documentada y planificarla para el BLOQUE 5 (o ciclo dedicado posterior).

### 5. BLOQUE 5: Plan de Construcción IA
Se elaborará el desglose para la construcción real de la IA.
- **[NEW] `specs/002-microexperiencias-ia/plan.md`**: Generar el roadmap en tres fases (Fase A: Copilot Demo, Fase B: Buscador Semántico + Transformers.js, Fase C: Wizard Adaptativo), sin escribir el código.

## Verification Plan

### Automated Tests
- `npm run build` para garantizar cero errores de compilación Astro (manteniendo 10 páginas estáticas renderizadas).

### Manual Verification
- Levantar `npm run preview`.
- Navegar a `/nosotros` o `/casos-de-exito` y comprobar que el botón del Navbar abre el Chatbot.
- Confirmar visualmente que el fondo en toda la aplicación muestra la textura global del prototipo.
- Hacer una petición al chatbot con un correo electrónico para verificar que los logs locales de PHP no contengan el texto en claro.
