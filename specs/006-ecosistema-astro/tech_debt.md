# Deuda Técnica: Spec 006 (Ecosistema Astro)

## Estado
- **Fase actual:** ✅ Implementado en Astro (producción), E2E verificado
- **Impacto:** Alto
- **Severidad:** Baja

## Lista de Deuda Técnica (Technical Debt)

### 1. Inconsistencia de Rutas y Proxy Local [RESUELTO]
- **Descripción:** Las peticiones `fetch` apuntaban hardcoded a la carpeta estática de PHP en XAMPP (`/datanestiq/public/`), rompiendo la compatibilidad entre `npm run dev` y el build `dist/`.
- **Resolución:** Implementado `src/lib/endpoints.js` con esquema root-relative. Se documentó `DEPLOY.md` validando que Vite Proxy preserva rutas sin 404.

### 2. Eliminación de la Carpeta Prototype (T019) [DECISIÓN CERRADA]
- **Descripción:** Originalmente se iba a borrar el prototipo estático Vanilla JS.
- **Resolución:** Tarea cancelada. La carpeta se mantiene históricamente para consulta.

### 3. Scripts de Verificación en `package.json` [DEUDA DE CI/CD]
- **Descripción:** Los scripts `lint:strict`, `security:scan`, `openwiki:audit` y `audit:lighthouse` son `echo` stubs. Los Success Criteria (Lighthouse 100/100, Linting, etc.) no tienen validación automática real.

### 4. Optimización de Assets [BACKLOG]
- **Descripción:** Migrar las etiquetas de imágenes genéricas y el procesamiento de media al motor nativo `astro:assets` para escalar performance.
