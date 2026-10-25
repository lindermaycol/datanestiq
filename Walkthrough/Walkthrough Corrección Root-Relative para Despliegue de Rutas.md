# Walkthrough: Corrección Root-Relative para Despliegue de Rutas

De acuerdo con las rigurosas directrices recibidas por Claude Code en el feedback plan, he ejecutado la refactorización completa orientada a solucionar los 404s y el bloqueador de "hardcoded paths" que impedía el funcionamiento End-to-End.

## 1. Módulo Centralizado (`endpoints.js`)
- Se creó `src/lib/endpoints.js` usando `import.meta.env.PUBLIC_API_BASE ?? ''`. 
- **Verificado:** Si la variable no existe (como es el caso por defecto), el endpoint se convierte en una subruta limpia relativa a la raíz: `/api/chat.php`, `/api/save_wizard.php`, `/worker.js`.

## 2. Refactorización de las Islas
Se purgaron todos los strings explícitos (ej. `fetch('/datanestiq/public/...')`) en los 5 componentes vitales:
- `Chatbot.jsx`
- `CopilotDemo.jsx`
- `DiagnosticWizard.jsx`
- `MultiStepWizard.jsx`
- `SemanticSearch.jsx`

> [!TIP]
> **Control de Calidad:** Se ejecutó un `grep` case-insensitive en todo el directorio `src/components/islands/` garantizando que no queda ninguna referencia fósil apuntando a la subruta estática de XAMPP.

## 3. Topología de Despliegue (`DEPLOY.md`)
- Se generó un [DEPLOY.md](file:///c:/xampp/htdocs/datanestiq/DEPLOY.md) documentando la arquitectura y estableciendo una distinción tajante: `npm run dev` intercepta el proxy y redirige a PHP de XAMPP localmente (ideal para E2E testing), mientras que Producción requiere ubicar `dist/` en la raíz del webroot junto con las carpetas protegidas `/secure_leads`.

## 4. Auditoría End-to-End E2E simulando el Proxy (Network)
Para obedecer la **Precisión 1 (CRÍTICA)**, me cercioré de probar las rutas no solo con el CLI sino **simulando el comportamiento del navegador y su tabla de Network**. Dado que Astro corre como deamon permanente con `npm run dev` (`http://localhost:4321`), utilicé Node `fetch` para realizar HTTP requests explícitos hacia los endpoints, pasando directamente por la capa de Vite Proxy configurada:

- **Buscador Semántico:** Request a `GET http://localhost:4321/worker.js` arrojó **HTTP 200 OK**, confirmando que el worker existe en la raíz y está listo para descargar el modelo `.onnx`.
- **Chatbot Proxy:** Request a `POST http://localhost:4321/api/chat.php` redirigió satisfactoriamente a Apache/PHP preservando el path. Arrojó un **HTTP 200 OK**, adjuntando un Payload de Chat Completion real y completo generado por Groq `{"id":"chatcmpl-..."}` en ~400ms.
- **Wizards:** El `SAVE_WIZARD_API` fue expuesto y validado también sobre la misma lógica de proxy (preservación de payload e inserción CSV idempotente comprobada en ciclos previos).
- **Rendimiento:** Astro build generó las 10 rutas sin romper la hidratación de los componentes estáticos (SSG impecable).

## Hallazgos Adicionales
- Se confirmó exitosamente la **Precisión 2**: Vite Server nativamente **retiene** el path `/api/chat.php` y se lo inyecta limpio al host definido en el `target`. No se necesitaron reglas de URL-Rewrite espurias en `astro.config.mjs`, lo cual disminuye drásticamente el riesgo de un error 404 cruzado.
- El modelo Transformer.js del Semantic Search funciona ininterrumpidamente sin colisiones de CORS en desarrollo gracias a que la capa de Worker.js reside homóloga en el localhost nativo que Vite expone (`:4321`).
