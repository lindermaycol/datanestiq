# Plan de Implementación: Corrección de Rutas y Deployment (Astro/PHP)

Este plan aborda el bloqueador de rutas detectado en las pruebas E2E, donde el build compilado y la API no resolvían bajo la misma raíz, unificando el ecosistema hacia un esquema **root-relative** centrado en la carpeta `dist/`.

## Propuesta Técnica

### [NEW] [src/lib/endpoints.js](file:///c:/xampp/htdocs/datanestiq/src/lib/endpoints.js)
Creación de un módulo centralizado que exportará las rutas de la API, utilizando `import.meta.env.PUBLIC_API_BASE` para permitir reconfiguraciones en el futuro sin modificar el código fuente de los componentes.
```javascript
const BASE = import.meta.env.PUBLIC_API_BASE ?? '';
export const CHAT_API = `${BASE}/api/chat.php`;
export const SAVE_WIZARD_API = `${BASE}/api/save_wizard.php`;
export const WORKER_URL = `${BASE}/worker.js`;
```

### [MODIFY] Componentes de Astro Islands
Se sustituirán todos los fetch y workers hardcodeados (`/datanestiq/public/...`) por las constantes importadas de `endpoints.js` en los siguientes 5 archivos:
- [src/components/islands/CopilotDemo.jsx](file:///c:/xampp/htdocs/datanestiq/src/components/islands/CopilotDemo.jsx)
- [src/components/islands/Chatbot.jsx](file:///c:/xampp/htdocs/datanestiq/src/components/islands/Chatbot.jsx)
- [src/components/islands/SemanticSearch.jsx](file:///c:/xampp/htdocs/datanestiq/src/components/islands/SemanticSearch.jsx)
- [src/components/islands/MultiStepWizard.jsx](file:///c:/xampp/htdocs/datanestiq/src/components/islands/MultiStepWizard.jsx)
- [src/components/islands/DiagnosticWizard.jsx](file:///c:/xampp/htdocs/datanestiq/src/components/islands/DiagnosticWizard.jsx)

### [NEW] [DEPLOY.md](file:///c:/xampp/htdocs/datanestiq/DEPLOY.md)
Documentación técnica estandarizada sobre las estrategias de despliegue para los ingenieros.
- **Producción:** Desplegar `dist/` en la raíz del webroot (`datanestiq.com`), colocar los scripts PHP y Worker junto a esta raíz, dejando la carpeta `secure_leads/` bloqueada por `.htaccess`.
- **Desarrollo (E2E Local):** Utilizar siempre `npm run dev` aprovechando el proxy de Vite configurado en `astro.config.mjs` (`/api` -> `http://localhost/datanestiq/public`), asegurando resolución simultánea de UI estática y backend PHP dinámico.

## Verificación Planeada
1. Verificar vía grep que no quede ningún string hardcodeado a `/datanestiq/public/`.
2. Validar que la compilación (`npm run build`) no se rompa (10 páginas generadas).
3. Asegurar que las islas invocan sus API a las subrutas correctas (`/api/...`) al compilarse.
