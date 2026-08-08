# Prompt para Antigravity: Confirmación del Plan (Fix Chatbot, Textura, Spec 002) → ejecutar con 4 precisiones

He revisado `planes/Plan de Ejecución Fix Chatbot, Textura y Spec 002.md`. Es fiel al prompt original. **Tienes luz verde para ejecutar los 5 bloques**, con estas 4 precisiones obligatorias — la #1 evita destruir trabajo existente, la #2 y #3 evitan que el hardening rompa el chatbot en local, la #4 aclara cómo verificar.

## Precisión 1 (CRÍTICA) — `specs/002-microexperiencias-ia/plan.md` YA EXISTE: es `[MODIFY]`, NO `[NEW]`

Tu plan marca `plan.md` del BLOQUE 5 como `[NEW]`. **Ese archivo ya existe** (3.6 KB) y contiene la propuesta técnica original por componentes (Copilot Demo, Wizard, Recomendador) **y una sección "Complexity Tracking" con la deuda de Transformers.js que se agregó en un ciclo anterior.** Si lo creas de cero, borras todo eso.

**Instrucción:** NO sobrescribas el archivo. **Añade** (append) una sección nueva al final titulada `## Roadmap de Implementación IA (Fasado)` con las tres fases (A: Copilot Demo, B: Buscador Semántico + Transformers.js + Context-Aware Chaining, C: Wizard Adaptativo). Conserva intacto todo el contenido actual, incluida la tabla "Complexity Tracking". La nueva sección debe ser coherente con esa tabla (la Fase B es justamente lo que allí se pospuso, así que enláza los conceptos, no los contradigas).

## Precisión 2 — CORS: no bloquees `localhost` con puerto ni las peticiones same-origin

El proxy `chat.php` corre bajo XAMPP/Apache (`http://localhost/...`), y el frontend lo consume desde `/datanestiq/public/api/chat.php`. Al restringir CORS ten en cuenta:
- **Same-origin:** cuando la página se sirve desde XAMPP, la petición a `chat.php` es del mismo origen y el navegador puede **no enviar** el header `Origin`. Si `Origin` está ausente, **trata la petición como válida** (same-origin) — no la bloquees, o romperás el flujo real.
- **localhost con cualquier puerto:** en dev el origen puede ser `http://localhost:4321` (Astro preview) o `http://localhost` (Apache). La allowlist debe aceptar `https://datanestiq.com` y **cualquier** `http://localhost` sin importar el puerto (valida por hostname `localhost`, no por string exacto). Si no, el chatbot deja de funcionar en tu entorno local.

## Precisión 3 — Rate limiting: fail-open, no fail-closed

El [!IMPORTANT] de tu propio plan advierte que los límites "limitarán el acceso al chatbot". Para que el hardening no se convierta en un bug:
- El rate limiter debe **degradar con gracia (fail-open):** si el mecanismo de storage (archivo temporal / APCu / session) falla o no está disponible, la petición debe **permitirse**, no denegarse. Nunca dejes que un error de escritura de un contador bloquee a todos los usuarios (fail-closed).
- Usa un umbral holgado para no molestar un uso normal/demo (los 20 req/10 min por IP que propusiste están bien). El objetivo es frenar bots en bucle, no a un CTO probando el chat.

## Precisión 4 — La verificación de PII enmascarada se hace vía XAMPP, no vía `npm run preview`

Tu "Manual Verification" dice hacer una petición al chatbot y revisar los logs PHP. Ojo: `chat.php` **solo se ejecuta bajo Apache/PHP (XAMPP)**, no bajo `npm run preview` (que es un server estático de Node y devolvería 404 en esa ruta). Para verificar el enmascaramiento de PII:
1. Sirve el sitio construido a través de XAMPP (la ruta donde `/datanestiq/public/api/chat.php` resuelva).
2. Escribe un correo en el chatbot y envíalo.
3. Abre `chat_logs.jsonl` y confirma que aparece `[EMAIL_REDACTED]` en lugar del correo real.

Reporta explícitamente que la prueba se hizo vía XAMPP y el resultado.

---

Procede con la ejecución completa de los 5 bloques aplicando estas 4 precisiones. Recuerda: BLOQUE 5 solo planifica (no construye código de IA), y no toques `specs/008-headless-wordpress/`.

Cuando termines, repórtame igual que siempre: archivos por bloque, resultado de `npm run build` (deben seguir siendo 10 páginas), verificación del chatbot en subpáginas, verificación visual de la textura vs `prototype/`, verificación del enmascaramiento de PII vía XAMPP, y "Hallazgos adicionales". Yo audito al final.
