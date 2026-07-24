# Prompt para Antigravity — Fix: el chatbot repite el nombre del servicio (subtítulos duplicados)

Corrige un defecto de **calidad de respuesta del AI Concierge** (Spec 002, texto libre). Es un ajuste del **`SYSTEM_PROMPT` en `public/api/chat.php`**. No es spec nueva.

## Diagnóstico (verificado)
En respuestas de texto libre, el modelo lista cada servicio **dos veces**: una como bullet en negrita con descripción, y otra como **nombre suelto** (que se renderiza como enlace subrayado). Ejemplo real:
```
• Inteligencia Artificial & Data Science: Desplegamos arquitecturas... optimizar recursos y servicios.
Inteligencia Artificial & Data Science          ← repetido (enlace suelto)
• Hiperautomatización Inteligente: Orquestamos RPA...
Hiperautomatización Inteligente                 ← repetido
```
**Causa raíz:** el `SYSTEM_PROMPT` (define en `chat.php`, ~línea 109) tiene reglas que se pisan:
- Regla 1: "presenta opciones en formato de **lista**".
- Regla 4: "**ENLAZA** el servicio usando Markdown con el **NOMBRE REAL** como texto del enlace".
- Regla 5: "**EXPLICA** brevemente el servicio".
No le dice que el **nombre del ítem de la lista SEA el enlace**, así que el modelo separa: nombre en el bullet + enlace con el nombre aparte → **duplicación**.
`formatText` de `Chatbot.jsx` (líneas 282-307) renderiza cada línea **una sola vez** — **no** es un bug de render; no lo toques.

## Fix (solo el `SYSTEM_PROMPT` de `chat.php`)
Reescribe/afina las reglas para que **cada servicio se mencione UNA sola vez**, como un ítem de lista cuyo **nombre es el enlace Markdown**, con la explicación en la misma línea. Deja explícito el formato deseado y **prohíbe** repetir el nombre fuera del enlace o como título aparte. Ejemplo del formato a exigir en el prompt:
```
- [Inteligencia Artificial & Data Science](/soluciones/ai-data-science): breve explicación de valor.
- [Hiperautomatización Inteligente](/soluciones/hiperautomatizacion): breve explicación de valor.
```
Puntos a incorporar en las reglas:
- El **nombre del servicio dentro de la lista es el texto del enlace** (`[Nombre](/soluciones/slug)`), y la explicación va **inline tras los dos puntos**, en la misma viñeta.
- **Nunca** repitas el nombre del servicio fuera del enlace, ni como encabezado/subtítulo suelto encima o debajo.
- Mantén el resto intacto: value-first antes de pedir contacto (regla 2), catálogo real (nada inventado), adaptación por sector (regla 6), y "puede escribir otra opción si ninguna aplica".

## Guardarraíles
- Es un string **PHP con comillas dobles** (`define('SYSTEM_PROMPT', "...")`): **escapa correctamente** cualquier `"` interna (como ya se hace con `\"Nombre del Servicio\"`). **Debe pasar `php -l public/api/chat.php`** (ojo: una comilla sin escapar rompe TODO el endpoint y devuelve HTML en vez de JSON — ya nos pasó).
- No toques el **failover** (Groq→DashScope→Gemini) ni el flujo guiado **0-LLM** (esto es solo texto libre).
- No modifiques `formatText` (render correcto).

## Verificación (evidencia real)
1. `php -l public/api/chat.php` → sin errores de sintaxis.
2. En el chatbot (texto libre), pide "necesito una solución de IA" tras elegir sector/rol → la respuesta lista los servicios como **ítems enlazados únicos** (`[Nombre](/soluciones/…): explicación`), **sin** el nombre repetido como subtítulo suelto.
3. El nombre de cada servicio aparece **una sola vez** por ítem; los enlaces apuntan a `/soluciones/<slug>` correctos.
4. Se mantiene value-first (no pide contacto en la primera respuesta a una pregunta concreta).

---
**Nota:** Claude (Opus 4.8) reauditará en el chatbot: respuesta de texto libre con servicios listados **sin duplicar** el nombre, enlaces correctos, `php -l` limpio y 0-LLM/failover intactos.
