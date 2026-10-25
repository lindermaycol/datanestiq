# DATANESTIQ — Reporte QA Navegación Detallada HOME (Componentes C1–C8)
**Auditor:** IA con navegador real — clic, scroll, DOM, timing visual, inspección de clases CSS  
**URL auditada:** http://localhost:8080/  
**Build:** Astro estático + islas React + backend PHP (`php.exe -S localhost:8080 -t dist`)  
**Fecha:** 2026-07-23 · 08:00–09:00 -05 (Chimbote, PE)  
**Referencia prompt:** `prompt-ia-navegacion-detallada-home-componentes.md`  
**Objetivo:** Auditoría con evidencia literal por componente para revisión por Claude Opus 4.8

---

## 0. DIAGNÓSTICO DE INTERMITENCIA DEL SERVIDOR PHP

### Causa raíz identificada

El servidor `php.exe -S localhost:8080 -t dist` es el **servidor de desarrollo built-in de PHP**, diseñado para pruebas locales. **No está pensado para producción ni para sesiones largas bajo carga concurrente.**

Las causas de los cortes observados (`ERR_CONNECTION_REFUSED` en 2 ocasiones durante la sesión):

| # | Causa | Detalle técnico |
|---|---|---|
| 1 | **Single-threaded por diseño** | `php -S` corre en un único proceso/hilo. Cuando el LLM backend (`chat.php`) hace una llamada bloqueante a la API externa (Groq, OpenAI u otro), el servidor queda completamente bloqueado y no puede responder a ninguna otra petición — incluidas las de recursos estáticos |
| 2 | **Timeout de la llamada LLM → cierre del proceso** | Si la API del LLM tarda >30–60s o responde con error de red, PHP puede alcanzar `max_execution_time` o fallar silenciosamente, cerrando el worker y dejando el socket sin listeners |
| 3 | **Sin keep-alive robusto** | El servidor built-in de PHP no gestiona correctamente conexiones persistentes bajo carga; una conexión colgada del buscador semántico (Transformers.js hace requests HTTP para cargar el modelo WASM) puede saturarlo |
| 4 | **Carga de modelo WASM + PHP simultáneos** | El buscador semántico Edge AI descarga el modelo (~50–150MB de WASM + weights) vía fetch. Esos requests pasan por el mismo servidor PHP, compitiendo con las peticiones LLM de `chat.php` |
| 5 | **Sin gestión de señales** | `php -S` no maneja `SIGPIPE` correctamente; si el browser cierra la conexión durante streaming (streaming SSE de `chat.php`), puede crashear el proceso |

### Solución inmediata para entorno de desarrollo

```bash
# En lugar de php -S, usar un servidor estático desacoplado para /dist:
# Opción A: npx serve dist -p 8080   (para archivos estáticos)
# Opción B: php -S localhost:8080 -t dist -c php.ini con max_execution_time=120
# Opción C (recomendada): 
#   - Archivos estáticos: npx serve dist -p 8080
#   - PHP backend solo: php -S localhost:8081 -t dist (solo para chat.php)
```

### Solución para producción
Reemplazar `php -S` con **NGINX/Apache + PHP-FPM** o un backend Node.js/Python para los endpoints LLM. El servidor PHP built-in **nunca debe usarse en producción**.

---

## 1. TABLA RESUMEN C1–C8

| Componente | Acción ejecutada | PASS/FAIL/NO-VER. | Latencia (ms) | Clasificación | Evidencia literal |
|---|---|---|---|---|---|
| **C1 Hero** | First load — 4 chips visibles | ✅ PASS | ~0 | Instantáneo | "Personaliza tu experiencia: Sector Público · Finanzas / CFO · Datos / CDO · Estrategia / CEO" |
| **C1 Hero** | Chip Sector Público → HeroRoleLine | ❌ FAIL | ~0 | — | Hero idéntico al neutro; sin banner "Viendo como: CIO / Sector Público" en DOM |
| **C1 Hero** | Chip Finanzas/CFO → HeroRoleLine | ❌ FAIL | ~0 | — | Hero idéntico al neutro |
| **C1 Hero** | Chip Datos/CDO → HeroRoleLine | ❌ FAIL | ~0 | — | Hero idéntico al neutro |
| **C1 Hero** | Chip Estrategia/CEO → HeroRoleLine | ❌ FAIL | ~0 | — | Hero idéntico al neutro |
| **C1 Hero** | ConsultiveCTA por chip | ❌ FAIL | ~0 | — | CTA "Diagnóstico estratégico gratuito" sin variante para ningún rol |
| **C1 Hero** | Chip activo → visual resaltado (azul oscuro) | ✅ PASS | ~0 | Instantáneo | Chip seleccionado cambia a fondo azul/oscuro; aria-current="true" en DOM |
| **C1 Hero** | Botón X limpia selección | ✅ PASS | ~0 | Instantáneo | X visible junto a chips; devuelve estado neutro |
| **C2 Buscador** | Scroll al componente — estado inicial | ✅ PASS | — | — | Placeholder: "Ej: Tengo problemas con la retención de clientes en mi e-commerce..." |
| **C2 Buscador** | Carga modelo 1ª vez (Transformers.js WASM) | ✅ PASS | ~5 000 ms | Lento (esperado, 1 vez) | Mensaje: "Cargando el modelo de IA (solo la primera vez)... 100%" |
| **C2 Buscador** | Skeleton durante carga modelo | ✅ PASS | — | — | 3 tarjetas placeholder visibles simultáneamente con mensaje de carga |
| **C2 Buscador** | Estado post-carga modelo | ✅ PASS | — | — | "IA lista para búsqueda instantánea" |
| **C2 Buscador** | Q1: "quiero reducir costos operativos" (modelo cacheado) | ❌ FAIL | ~15 000 ms | **CRÍTICO** | "Analizando vectores semánticos..." → "✓ Resultados filtrados semánticamente" |
| **C2 Buscador** | Q2: "prevención de fraude financiero con IA" (cacheado) | ❌ FAIL | ~15 000 ms | **CRÍTICO** | Mismo patrón; el modelo cacheado no reduce latencia significativamente |
| **C2 Buscador** | Estado "searching" spinner+skeleton | ✅ PASS | — | — | Spinner en botón → + skeleton 3 tarjetas durante "Analizando vectores semánticos..." |
| **C2 Buscador** | Bloque "Encontramos N soluciones" + 3 botones CTA | ❌ FAIL | — | — | Ausente del DOM post-búsqueda; solo texto "✓ Resultados filtrados semánticamente" |
| **C2 Buscador** | Zero chat.php durante búsqueda | ✅ PASS (inferido) | — | — | Respuesta local Edge AI; no se observó request externo al backend |
| **C3 CTA encadenado** | Highlights Q1 "reducir costos" | ✅ PASS | ~0 | Instantáneo | Row 1 BRIGHT (is-highlighted): AI & Data Science, Data Engineering, Business Intelligence |
| **C3 CTA encadenado** | Dimmed Q1 | ✅ PASS | ~0 | Instantáneo | Row 2 DIMMED (is-dimmed): Sistemas Digitales Premium, Hiperautomatización, Consultoría Estratégica |
| **C3 CTA encadenado** | Highlights Q2 "prevención fraude" | ✅ PASS | ~0 | Instantáneo | Misma discriminación semántica Row 1 BRIGHT / Row 2 DIMMED |
| **C3 CTA encadenado** | Botón "Ver cómo razona nuestra IA →" | ❌ FAIL | — | — | Botón ausente del DOM tras búsqueda — eliminado en último build |
| **C3 CTA encadenado** | Botón "Iniciar Diagnóstico con este contexto →" | ❌ FAIL | — | — | Ídem |
| **C3 CTA encadenado** | Botón "Consultar con el AI Concierge →" | ❌ FAIL | — | — | Ídem |
| **C4 DiagnosticWizard** | Sectores visibles (grid) | ✅ PASS | — | — | 10 sectores: Educación, Finanzas y Banca, Logística, Manufactura, Minería, Sector Público, Retail y B2B, Salud, Seguros, Telecomunicaciones |
| **C4 DiagnosticWizard** | Highlights sectores post-búsqueda | ❌ FAIL | — | — | Con búsqueda "fraude financiero" activa, todos los sectores uniformes; Finanzas y Banca no se resalta |
| **C4 DiagnosticWizard** | Input pre-llenado con query del buscador | ❌ FAIL | — | — | Campo "¿Cuál es tu mayor desafío en Finanzas y Banca?" vacío al hacer clic |
| **C4 DiagnosticWizard** | Envío desafío desde sector card | ⚠️ NO-VER. | — | — | Clic ejecutado; sin respuesta visible en pantalla en esa sesión; puede requerir chatbot abierto |
| **C5 SolutionsByRoleAndIndustry** | Tab "Soluciones por Rol" — roles listados | ✅ PASS | ~0 | Instantáneo | 7 roles: CFO (ROI medible), CIO (uptime), CDO (valor del dato), CTO (escalabilidad), COO (eficiencia operativa), CISO (seguridad), CEO (crecimiento) |
| **C5 SolutionsByRoleAndIndustry** | Banner "Basado en tu búsqueda" post-búsqueda | ❌ FAIL | — | — | No aparece; roles completamente uniformes sin indicación contextual |
| **C5 SolutionsByRoleAndIndustry** | Clic en rol individual → respuesta + chat.php | ⚠️ NO-VER. | — | — | No se probó aisladamente; el Copiloto fue el componente de output LLM auditado |
| **C6 CopilotDemo** | Escenarios default sin chip | �� PASS | — | — | "Evaluar viabilidad LLMs privados para contratos B2B" · "Proyectar KPIs Q3 ante reducción plantilla 10%" · "Diagnosticar cuellos de botella pipeline on-premise" |
| **C6 CopilotDemo** | Escenarios con chip CDO activo | ❌ FAIL | — | — | Escenarios idénticos al estado sin chip; no se adaptan al perfil CDO (gobierno del dato, linaje, catálogo) |
| **C6 CopilotDemo** | Subtítulo aclaratorio | ✅ PASS | — | — | "Una demostración del razonamiento que produce nuestra arquitectura de IA — distinto del AI Concierge, que resuelve tu consulta." |
| **C6 CopilotDemo** | Puente AI Concierge | ✅ PASS | — | — | "¿Tu caso no está en estos escenarios? Pregúntale al AI Concierge →" — link azul cliclable |
| **C6 CopilotDemo** | Clic escenario 1 (sesión 1, servidor bajo carga) | ❌ FAIL | >23 000 ms | **CRÍTICO** | "Esperando ejecución..." sin output — backend PHP sin responder (ver sección 0) |
| **C6 CopilotDemo** | Clic escenario 3 (sesión 2, servidor estable) | ✅ PASS | ~12 000 ms | **LENTO** | Output visible: "Para diagnosticar cuellos de botella y latencia en su pipeline de datos on-premise, podemos seguir un enfoque estructurado." |
| **C6 CopilotDemo** | Listas con viñetas en output | ✅ PASS (**F-01 CORREGIDO**) | — | — | Viñetas renderizadas: "• Infraestructura inadecuada (disco duro, memoria RAM, procesadores)" · "• Ineficiencia en la ingesta de datos" |
| **C6 CopilotDemo** | Negritas en output | ✅ PASS (**F-01 CORREGIDO**) | — | — | "**Análisis de rendimiento**", "**Optimización de infraestructura**", "**Revisión de la arquitectura del pipeline**" |
| **C6 CopilotDemo** | Links en output renderizados como hipervínculos | ✅ PASS (**F-01 CORREGIDO**) | — | — | "Ingeniería & Arquitectura de Datos" aparece subrayado en azul cliclable — NO como texto crudo `[texto](url)` |
| **C6 CopilotDemo** | Anti-XSS / no inyección HTML crudo | ✅ PASS | — | — | Markdown parseado correctamente; no se observa HTML raw en el DOM |
| **C7 AI Concierge** | Apertura desde botón flotante (sin chip) | ✅ PASS | <200 ms | Instantáneo | Saludo: "Soy el AI Concierge de Datanestiq. ¿A qué sector perteneces?" |
| **C7 AI Concierge** | Flujo guiado 0-LLM paso 1→2 (sector) | ✅ PASS | <200 ms | Instantáneo | "Entendido, trabajas en Finanzas y Banca. ¿cuál es tu rol principal?" — 0 POST a chat.php |
| **C7 AI Concierge** | Flujo guiado 0-LLM paso 2→3 (rol CDO) | ✅ PASS | <200 ms | Instantáneo | "KYC cumpliendo con Basilea III / IV. ¿Qué proceso operativo específico?" — 0 POST a chat.php |
| **C7 AI Concierge** | Flujo guiado 0-LLM paso 