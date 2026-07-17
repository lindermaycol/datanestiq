# Prompt para Antigravity: Confirmación del Plan de Corrección de Rutas → ejecutar, pero la verificación DEBE ser E2E en navegador

He revisado `planes/Plan de Implementación Corrección de Rutas y Deployment (Astro/PHP).md`. La estrategia técnica es correcta (endpoints.js centralizado, 5 islas, DEPLOY.md). **Tienes luz verde para ejecutar**, con estas 3 precisiones — la #1 es la más importante: **no cierres el ciclo con verificación estática**.

## Precisión 1 (CRÍTICA) — La verificación DEBE ser end-to-end en el navegador, no grep+build

Tu "Verificación Planeada" solo hace grep, build y "asegurar que las islas invocan las subrutas correctas al compilarse". **Eso es exactamente lo que este ciclo busca evitar**: el bloqueador se detectó porque las cosas "compilaban" pero no funcionaban en el navegador. El propósito de este ciclo es que el sitio **funcione de verdad en vivo**. Por tanto, es OBLIGATORIO que ejecutes `npm run dev` y verifiques EN EL NAVEGADOR (con evidencia de la pestaña Network):

1. **Buscador Semántico:** carga el Home, escribe "necesito optimizar rutas y cadena de suministro", confirma que:
   - `/worker.js` responde **200** (no 404) en Network.
   - los archivos `.onnx` del modelo `all-MiniLM-L6-v2` se descargan (primera carga puede tardar varios segundos).
   - las tarjetas de **Servicios se reordenan/difuminan** y el sector **Logística se resalta**. Este reordenamiento nunca se pudo verificar antes — es el objetivo central.
2. **Chatbot flujo guiado:** abre el chatbot, pulsa Sector → Problema → ve la solución → **cero** requests a `/api/chat.php` en Network.
3. **Chatbot texto libre:** escribe una pregunta libre → **una** request a `/api/chat.php` que devuelve **200** (no 403 ni 404) con respuesta real de Groq.
4. **Copilot Demo:** clic en un prompt C-level → respuesta real vía `/api/chat.php` (200).
5. **Wizard multi-paso:** complétalo → confirma POST a `/api/save_wizard.php` (200) y que aparece una fila nueva en `secure_leads/leads_wizard.csv`.

Si alguno de estos falla, **no marques el ciclo como completo** — reporta el fallo con el error de Network/consola.

## Precisión 2 — Verifica que el proxy de Vite preserva el path (no asumas)

El proxy actual es `'/api' → 'http://localhost/datanestiq/public'`. Un `fetch('/api/chat.php')` debe llegar a Apache como `http://localhost/datanestiq/public/api/chat.php`. Vite por defecto **conserva** el path completo (incluido `/api`), así que **no** debes añadir un `rewrite` que borre `/api` (si lo haces, el PHP quedaría en `/chat.php` y romperías todo). Confírmalo con la prueba real del punto 1.3 (que `/api/chat.php` devuelva 200). Si por config del entorno el path no se preserva, ajústalo para que la ruta final sea exactamente `.../public/api/chat.php`.

Nota CORS: en `npm run dev` el navegador enviará `Origin: http://localhost:4321`; `chat.php` acepta cualquier host `localhost`, así que debe pasar. Si ves un 403, revisa que el proxy no esté alterando el header `Origin` de forma que el host deje de ser `localhost`.

## Precisión 3 — `PUBLIC_API_BASE` por defecto vacío = root-relative

Confirma que con `PUBLIC_API_BASE` **sin definir**, `import.meta.env.PUBLIC_API_BASE ?? ''` resuelve a `''` → `/api/chat.php` (root-relative). No dejes un valor por defecto que reintroduzca el subpath `/datanestiq/public`. La env var es solo un escape para entornos atípicos; el default debe ser raíz.

---

Procede con endpoints.js + las 5 islas + DEPLOY.md. Cierra el ciclo **solo** tras la verificación E2E en navegador del punto 1 (con capturas/Network). Confirma por grep que no queda ningún `'/datanestiq/public/'`. `npm run build` debe seguir dando 10 páginas.

**No toques `specs/008-headless-wordpress/`.** Sección final "Hallazgos adicionales" (incluye ahí si el buscador semántico tuvo algún problema de bundle/carga del modelo).

---

**Nota:** Claude (Sonnet 5) re-ejecutará las pruebas E2E en navegador para auditar. Si reportas algo como "verificado" sin evidencia de Network, se detectará en la auditoría.
