# Prompt para Antigravity: Corregir la inconsistencia de rutas (assets root vs API `/datanestiq/public/`) — bloqueador de E2E

Actúa como **Ingeniero de build/deploy de Astro + configuración de servidores (Apache/Vite)**.

## Contexto: bloqueador detectado en pruebas en caliente bajo XAMPP

Claude (Sonnet 5) ejecutó pruebas en caliente reales bajo XAMPP. **El backend PHP funciona al 100%** (chat.php con Groq, PII redactada, kill-switch, metering, ruteo de modelo, save_wizard→CSV, extraer_leads batch idempotente). Pero encontró un **bloqueador de rutas** que impide que el sitio funcione end-to-end en un navegador:

- El HTML compilado referencia **assets en la raíz**: `href="/_astro/..."`, `href="/favicon.svg"` (correcto para deploy en raíz de dominio, porque se quitó `base` en un ciclo previo por SEO).
- Pero los 6 `fetch`/`Worker` de las islas están **hardcodeados a un subpath**: `/datanestiq/public/...`.

Verificado por `curl`:
- Sirviendo el build en `http://localhost/datanestiq/dist/`, los assets `/_astro/...` dan **404** (buscan en la raíz de Apache, no en el subpath). → la página **no renderiza bien**.
- Las rutas `/datanestiq/public/api/chat.php` sí resuelven bajo XAMPP, pero en producción (`datanestiq.com` en la raíz) estarían **equivocadas** (no hay `/datanestiq/` en el dominio real).

**Conclusión: no existe hoy un entorno donde la página renderice Y la API/worker resuelvan a la vez.** Hay que unificar el esquema de rutas.

### Rutas hardcodeadas a corregir (6, en 5 archivos)
```
src/components/islands/CopilotDemo.jsx:54      fetch('/datanestiq/public/api/chat.php'...)
src/components/islands/Chatbot.jsx:45          fetch('/datanestiq/public/api/save_wizard.php'...)
src/components/islands/Chatbot.jsx:121         fetch('/datanestiq/public/api/chat.php'...)
src/components/islands/SemanticSearch.jsx:35   new Worker('/datanestiq/public/worker.js'...)
src/components/islands/MultiStepWizard.jsx:46  fetch('/datanestiq/public/api/save_wizard.php'...)
src/components/islands/DiagnosticWizard.jsx:127 fetch('/datanestiq/public/api/chat.php'...)
```

## Estrategia de solución: rutas root-relative + `dist/` como raíz web

El sitio de producción vive en la **raíz** de `datanestiq.com`, y Astro copia `public/` a la raíz de `dist/` (así `public/worker.js`→`/worker.js`, `public/api/chat.php`→`/api/chat.php`). Por tanto, el esquema correcto y único es **root-relative**, con `dist/` como raíz web desplegada.

### Cambio 1 — Unifica los fetch/worker a rutas root-relative
Reemplaza en los 6 sitios:
- `'/datanestiq/public/api/chat.php'` → `'/api/chat.php'`
- `'/datanestiq/public/api/save_wizard.php'` → `'/api/save_wizard.php'`
- `'/datanestiq/public/worker.js'` → `'/worker.js'`

**Centralízalo** en un único módulo para no volver a hardcodear: crea `src/lib/endpoints.js` (o `.ts`) que exporte:
```js
// Base configurable; por defecto root-relative (producción y dev con proxy)
const BASE = import.meta.env.PUBLIC_API_BASE ?? '';
export const CHAT_API = `${BASE}/api/chat.php`;
export const SAVE_WIZARD_API = `${BASE}/api/save_wizard.php`;
export const WORKER_URL = `${BASE}/worker.js`;
```
y usa esas constantes en las 5 islas. Así, si algún entorno necesitara un subpath, se ajusta con la env var `PUBLIC_API_BASE` sin tocar código.

### Cambio 2 — Verifica/ajusta el proxy de Vite para desarrollo (`npm run dev`)
`astro.config.mjs` ya tiene un proxy `'/api' → 'http://localhost/datanestiq/public'`. Con el nuevo esquema, un `fetch('/api/chat.php')` en `npm run dev` se proxeará a `http://localhost/datanestiq/public/api/chat.php` (Apache ejecuta el PHP). Confirma que el proxy preserva el path (`/api/chat.php` → `.../public/api/chat.php`). Si Vite no reenvía bien la subruta, ajusta el `rewrite`/target para que quede exacto. Con esto, **`npm run dev` sirve los assets en la raíz (correcto) Y proxea la API a Apache (correcto)** → entorno de prueba E2E funcional en el navegador.

### Cambio 3 — El Worker en dev
`public/worker.js` se sirve en `/worker.js` tanto en `npm run dev` como en `dist/`. Con `WORKER_URL='/worker.js'` funciona en ambos. (En dev no pasa por el proxy `/api`, va directo al dev server, que sí sirve `public/` en la raíz.) Verifica que el Worker cargue el modelo de Transformers.js sin 404.

### Cambio 4 — Documenta el deployment
En `specs/006-ecosistema-astro/` (o `007`) o un `DEPLOY.md`, documenta claramente:
- **Producción:** se despliega el contenido de `dist/` como **raíz** del webroot de `datanestiq.com`. El backend PHP (`public/api/*.php`) debe quedar accesible en `/api/*` de ese mismo dominio (copiar `public/api` y `public/worker.js` junto al `dist`, o servirlos desde la misma raíz). El directorio `secure_leads/` debe quedar **fuera** del webroot o con su `.htaccess` deny (ya existe).
- **Desarrollo/E2E local:** usar `npm run dev` (assets en raíz + proxy `/api` a Apache), NO `npm run preview` (server estático, no ejecuta PHP) ni abrir `dist/` directo por subpath.

## Verificación (debe hacerse en el navegador, no solo build)
1. `npm run build` → 10 páginas, sin errores.
2. `npm run dev` y en el navegador, sobre el Home:
   - **Buscador Semántico:** escribe "optimizar rutas y cadena de suministro", confirma en Network que `/worker.js` carga (200) y los `.onnx` del modelo se descargan; verifica que las tarjetas de Servicios se reordenan/difuminan y el sector Logística se resalta.
   - **Chatbot flujo guiado:** sector→problema→solución = 0 llamadas; texto libre = 1 llamada a `/api/chat.php` con 200 real.
   - **Copilot Demo:** clic en un prompt C-level → respuesta real vía `/api/chat.php`.
   - **Wizard multi-paso:** completa y confirma que postea a `/api/save_wizard.php` (200) y aparece fila en `secure_leads/leads_wizard.csv`.
3. Reporta capturas/Network de cada uno.

## Forma de respuesta
- Confirma que ya no queda ningún `'/datanestiq/public/'` hardcodeado (grep).
- Reporta la verificación E2E en navegador vía `npm run dev` (es el punto del ciclo: que funcione de verdad, no solo compile).
- **No toques `specs/008-headless-wordpress/`** ni el core WP.
- Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará re-ejecutando las pruebas E2E en el navegador.
