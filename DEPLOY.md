# Guía de Despliegue y Arquitectura de Rutas (Datanestiq)

Este documento centraliza las directrices para desplegar el proyecto Astro (SSG/Islas) junto a su backend PHP y el ecosistema de IA.

## 1. Topología de Directorios y Assets
El ecosistema completo se conforma por:
- **Frontend Estático (Astro):** HTML, CSS e Islas hidratables.
- **Backend (PHP):** Endpoints de API (`chat.php`, `save_wizard.php`).
- **Web Workers:** Lógica cliente pesada (`worker.js` para Semantic Search).
- **Recursos Protegidos:** Carpeta `/secure_leads/` con configuración de denegación vía `.htaccess`.

### Esquema de Rutas Root-Relative
Todos los fetch requests de las Islas usan **rutas relativas a la raíz (Root-Relative)**, ej: `/api/chat.php`, `/worker.js`. 
Esto se administra centralizadamente en `src/lib/endpoints.js` usando `import.meta.env.PUBLIC_API_BASE ?? ''`.

## 2. Entorno de Producción (`datanestiq.com`)

En producción, el sitio debe vivir en la **raíz absoluta** del dominio.

### Procedimiento:
1. Ejecutar `npm run build`.
2. Astro depositará todos los estáticos compilados en la carpeta `dist/`.
3. Notar que la carpeta `public/` (incluyendo `api/` y `worker.js`) se copia automáticamente a la raíz de `dist/`.
4. El contenido de `dist/` debe ser transferido a la raíz pública del servidor web (ej. `public_html/` o `www/`).
5. La carpeta `secure_leads/` DEBE situarse donde los scripts PHP esperan encontrarla (usualmente `../../secure_leads/` relativo al endpoint en `api/`). En una arquitectura estándar, se sitúa fuera del webroot público, o al mismo nivel de la raíz pero protegida por su archivo `.htaccess` (que ya contiene `Require all denied`).

**Validación:** `datanestiq.com/api/chat.php` debe ser accesible y devolver JSON.

## 3. Entorno de Desarrollo y Pruebas E2E (Local)

Para probar end-to-end (E2E) con el backend de PHP encendido localmente (ej. XAMPP), **NUNCA utilices `npm run preview` ni abras los archivos estáticos desde una ruta anidada**.

### Procedimiento:
1. Asegurarse que el servidor Apache (XAMPP/WAMP/LAMP) está corriendo y el proyecto está situado en `htdocs/datanestiq/` (o su equivalente según tu virtualhost).
2. Levantar el entorno de Vite usando:
   ```bash
   npm run dev
   ```
3. El frontend levantará en `http://localhost:4321`.
4. Vite incluye un **Proxy** integrado (`astro.config.mjs`) configurado para interceptar `/api` y enviarlo a `http://localhost/datanestiq/public/api`. Vite preserva la ruta completa.
5. El `worker.js` se sirve de forma nativa desde la raíz local de Vite (`http://localhost:4321/worker.js`) permitiendo al Buscador Semántico instanciar el modelo Transformers.js sin fallas de CORS ni 404s.

**Validación:** Abre `http://localhost:4321` en tu navegador para ver un entorno clon idéntico a producción donde Frontend, LLMs y Backend PHP colaboran en tiempo real.
