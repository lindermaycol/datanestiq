# Confirmación — Spec 013 (Conversión Consultiva): LUZ VERDE al enfoque, con 2 correcciones 🔴 + 4 precisiones antes de ejecutar

Revisé el plan (`planes/Plan de Implementación Spec 013 (Conversión Consultiva por Rol × Sector).md`) y confirmé que `specs/013-conversion-consultiva-rol-sector/spec.md` existe. Buen diseño del motor A1–A9 y de las 3 fases.

## ✅ Decisión de Casos ROI — APROBADA
Sí al componente `<IllustrativeRoiCase>` con badge permanente **"[EST] Escenario Ilustrativo"** + disclaimer *"proyección basada en promedios de la industria y nuestras capacidades, no un cliente real"*. Es exactamente el mecanismo correcto. Aplícalo a **todos** los casos/métricas de CFO y CEO.

---

## 🔴 Corrección 1 (SEGURIDAD/PII) — el formulario NO crea `contact.php` ni toca `remote_extract`
El plan dice conectar el formulario "a `chat.php` o un endpoint equivalente (ej. `contact.php`)… usando `remote_extract` o similar". **Dos problemas graves:**
1. **`remote_extract.py` NO tiene nada que ver con leads** — es el script de extracción SSH de WordPress/IONOS que **tuvo la fuga de credenciales**. **Prohibido** referenciarlo, usarlo o tocarlo para el formulario.
2. **No crees un `contact.php` nuevo** con manejo de PII ad-hoc — reimplementar la redacción es la vía más fácil a re-filtrar PII.

**Qué hacer:** el formulario **reutiliza el pipeline seguro que YA existe en `public/api/chat.php`** (verificado: `logInteraction` → `secure_leads/` 403+gitignored, con `[EMAIL_REDACTED]`/`[PHONE_REDACTED]`). Envía el lead por ese mismo canal probado. **PII redactada en logs, nunca en claro.** Si necesitas un endpoint distinto, replica **exactamente** el patrón de redacción de `chat.php`, no inventes uno.

## 🔴 Corrección 2 (invariante 0-LLM) — separa los dos "cerebros" del chatbot
El plan mezcla superficies: Fase 1 dice poner intents "en `chat.php` system prompt", pero la tabla de riesgos (correctamente) dice inyectarlos "en el árbol estático de la Isla, sin fetch". Aclara y respeta la separación:
- **Flujo GUIADO (0-LLM, invariante intocable):** los intents/opciones viven en la **taxonomía / árbol estático** (`sector.problems` del corpus, `Chatbot.jsx`). **Cero `fetch`.** Aquí NO se toca `chat.php`.
- **Modo TEXTO LIBRE:** el afinado de tono/criterios por buyer va en el **`chat.php` system prompt** (ya grounded en `services.json`). Eso es correcto **solo** para el modo libre.
- **No enrutes intents del flujo guiado por `chat.php`** — rompería el 0-LLM.

---

## Precisión 1 — Intents = CONSUMIR la taxonomía; los faltantes se reportan (no se hardcodean)
Verifiqué: el sector público **ya tiene** `problems: ["Revisión lenta de expedientes", "Reportes e indicadores manuales"]` → **consúmelos**. Pero "trazabilidad/auditoría" que mencionas **no están en el dato** → eso es un **gap de capa de datos** (Spec 011 Fase 3): **repórtalo** para autorarlo en la fuente, **no lo hardcodees** en la isla. Mismo criterio para CFO/CEO: mapea a `sector.problems` / `persona.objections` existentes; si falta, flag.

## Precisión 2 — REUTILIZA lo que ya construyó la fundación (no reconstruyas)
- El bloque **"Resolvemos tus dudas" (objeción→respuesta) YA existe** en las 16 páginas (fundación, vía `pillarsOfInterest`/`relevantPersonas`). El motor A2 **reutiliza ese render**; no lo dupliques.
- La adaptación de A9 (resaltar/reordenar servicios por contexto) **reutiliza el renderizador de `SemanticSearch.jsx`** (el highlight de `.service-card` que ya existe) y el bus `semanticHighlight`. **No construyas un motor de adaptación paralelo.**

## Precisión 3 — Blog Fase 1 sin duplicar
La fundación ya publicó 2 posts públicos (`interoperabilidad-institucional`, `gobierno-dato-estado`). Los 4 top-up de la Fase 1 deben ser **temas distintos** (p. ej. analítica de contrataciones, IA segura en el Estado, dashboards ejecutivos, calidad de padrones). No repitas los 2 existentes.

## Precisión 4 — Progressive enhancement (chips/CTA/adaptación)
El sitio debe funcionar **100% sin JS y sin contexto**: el CTA default "Diagnóstico estratégico gratuito" se renderiza **SSG** y solo se especializa al hidratar (tu mitigación de FOUC es correcta); los chips son opcionales/descartables/persistidos; la reordenación de la grilla (Fase 3 CEO) no debe romper el orden estático para quien no elige contexto.

---

## Verificación (evidencia real, tras implementar)
1. **0-LLM intacto:** Network del flujo guiado = **cero llamadas a `chat.php`** (solo estáticos). Captura del Network.
2. **PII:** el formulario escribe a `secure_leads/` con `[EMAIL_REDACTED]`/`[PHONE_REDACTED]`; **cero PII en claro**; **cero** referencia a `remote_extract`; **no** hay `contact.php` ad-hoc con logging inseguro.
3. **Casos ROI:** cada `<IllustrativeRoiCase>` muestra el badge `[EST] Escenario Ilustrativo` + disclaimer; **cero** cliente/logo/testimonio real inventado.
4. **Contexto:** elegir un chip `(rol, sector)` cambia CTA + resalta servicios (reusando el highlight existente); persiste en `localStorage`; el sitio funciona sin elegir.
5. **Intents:** consumidos de `sector.problems`/`persona.objections`; gaps reportados, no hardcodeados.
6. `npm run build` **verde**; islas (chatbot 0-LLM, wizard, buscador) intactas; consola limpia.
7. Sección final "Hallazgos adicionales".

## Orden de fases
Implementa **Motor (A1–A9) → Fase 1 (Público) → Fase 2 (CFO) → Fase 3 (CEO)**. Puedes entregar por fases para que audite incrementalmente. Reporta con evidencia real por fase; no des por bueno lo no probado.

---
**Nota:** Claude (Opus 4.8) auditará en el navegador (servido por PHP): el 0-LLM en Network, la redacción de PII del formulario, los badges `[EST]` de honestidad, el cambio de CTA/adaptación por contexto, y que las islas y el build sigan verdes.
