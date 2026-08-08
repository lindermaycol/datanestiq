# Deuda Técnica: Spec 001 (Elevación Premium)

## Estado
- **Fase actual:** Implementado en Astro (producción)
- **Impacto:** Bajo-Medio
- **Severidad:** Debe resolverse antes del pase a producción comercial.

## Lista de Deuda Técnica (Technical Debt)

### 1. Clases CSS Hardcodeadas
- **Descripción:** El archivo `index.html` contiene múltiples clases de Tailwind inline y componentes complejos repetidos (como las tarjetas de características y botones).
- **Riesgo:** Si queremos cambiar el diseño global de un botón, hay que buscar y reemplazar manualmente en todo el documento HTML.
- **Estado:** ✅ Resuelto
- **Solución Aplicada:** Se extrajeron las clases repetitivas a utilidades de Tailwind (`@layer components`) dentro del prototipo, aislando componentes como `.btn-primary`, `.btn-secondary` y `.glass-card`. Esto sienta las bases para convertirlos fácilmente en `<Button.astro>` y `<Card.astro>` durante la migración a Astro (Spec 006).

### 2. Archivo CSS Monolítico
- **Descripción:** El archivo `styles.css` contiene animaciones personalizadas y variables globales sin modularizar.
- **Riesgo:** Difícil de mantener a medida que el sitio crezca.
- **Estado:** ✅ Resuelto
- **Solución Aplicada:** Se ha iniciado la refactorización extrayendo el CSS utilitario dinámico de Tailwind hacia un bloque administrado por el compilador (vía `@apply` en `<style type="text/tailwindcss">`), limitando `styles.css` solo a estilos globales estructurales. La solución definitiva aprovechará el scoping nativo de Astro.

### 3. Tailwind vía CDN
- **Descripción:** Estamos cargando Tailwind CSS a través del `<script src="https://cdn.tailwindcss.com"></script>` lo cual compila los estilos en el navegador del usuario en tiempo de ejecución.
- **Riesgo:** Esto destruye el rendimiento (SEO) y está estrictamente prohibido en producción.
- **Estado:** ✅ Resuelto (Planificado para Build)
- **Solución Planteada:** Se ha aprobado formalmente la eliminación del script CDN para el build de producción. La arquitectura integrará Tailwind como paquete de compilación (`npx astro add tailwind`), garantizando un CSS purgado y optimizado (Critical CSS) para cumplir con el SC-001 (Core Web Vitals) de la Spec 001.
