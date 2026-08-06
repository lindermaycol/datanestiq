# Auditoría BUILD Spec 021 — APROBADO con 3 arreglos (1 bug UX + seeder + tokens)

Reauditoría **EN VIVO** (panel autenticado + endpoints + curls anti-fuga). El build está **mayormente bien** y esta vez
**probaste los endpoints de lectura** (todos 200, lección del journey 500 aplicada). La feature central —lista navegable
con clic— **funciona**. Quedan 3 arreglos: 1 bug de UX (bloqueante para la usabilidad), el seeder subdimensionado, y los
tokens en 0.

## ✅ Verificado en vivo (bien)
- **Precisión de fondo cumplida:** `init_crm_db.php` **extiende `chat_metrics`** con `prompt_tokens`/`completion_tokens`
  (idempotente) + `alerts` tabla nueva. **No** hay `usage_metrics` paralela; `usage_ops` agrega desde `chat_metrics`. ✅
- **Sin fuga pública:** `curl` a `/admin/PR-DRAFT-…-SPEC016.md`, `/secure_leads/leads_datanestiq.csv` y
  `/admin/leads_datanestiq.csv` → **404**. El código lee de `planes/` y `secure_leads/` privados. ✅
- **5 endpoints de lectura → 200:** `journey_sessions`/`usage_ops`/`alerts_ops`/`leads_detected`/`learn_insights`. ✅
- **Journey navegable:** la lista muestra resumen legible (**sector/rol + 1ª consulta** "necesito construir un data
  lake con…"), badges **CHAT/CRM**, conteo de eventos; **clic en una fila → carga el journey** a la derecha (timeline con
  badges). El usuario ya **no** pega el session_id. ✅ (Refinamientos 1 y 2 presentes.)
- **Sub-tabs "Formularios CRM / Detectados en Chat (LLM)"**, layout de dos columnas, toggle demo, paginación. ✅
- `learn_insights` con estado honesto ("Sin Reporte Disponible") cuando el `.md` no está. ✅; guard §2 "datos insuficientes" ✅.

## 🔴 Arreglo 1 (bug UX, bloqueante) — la lista "Sesiones Recientes" NO carga al abrir la pestaña
En vivo: al entrar a "Demanda & Journey" la lista aparece **vacía**; solo se pobló **después** de togglear "Incluir
Demo". La red confirma que la UI **no dispara** `journey_sessions` al activar la pestaña (solo se cargó al interactuar).
El usuario abriría la pestaña, vería la lista vacía y concluiría "no hay datos" — cuando sí los hay.
- **Fix:** llama al loader de `journey_sessions` **al activar la pestaña** "Demanda & Journey" (y en el `load` si es la
  pestaña por defecto), con `include_demo` = estado actual del toggle (default 0). No dependas del toggle para la 1ª carga.

## 🟠 Arreglo 2 — el seeder demo está subdimensionado y no puebla los buckets (y no corrió en prod)
`seed_demand_demo.php` genera ~**11** señales (5 gaps + 6 offered). Los guards §2 exigen **≥20** demand_signals
(Bucket 1) y **≥10** `offered=1` en sesiones distintas (Bucket 2) → con 11 el panel sigue en "**Datos insuficientes**".
Además, en vivo **no aparece ninguna fila `demoseed`** (ni con "Incluir Demo" ON) → el seeder **no se ejecutó en
producción**. No cumple el objetivo del usuario (ver Bucket 1/2 poblados).
- **Fix:** sube el seeder a **≥20 demand_signals** con **≥10 `offered=1` en session_ids distintos** (mezcla realista de
  brechas y ofrecidos, todos con `[DEMO]`/`demoseed_`), y **ejecútalo en la DB de producción** (con `/usr/bin/php8.2-cli`).
  Tras sembrar, Bucket 1 (brechas) y Bucket 2 (fuga) deben renderizar; verifica que el toggle DEMO los separe del banner.

## 🟠 Arreglo 3 — `prompt_tokens`/`completion_tokens` quedan en 0 (el desglose no se puebla)
`usage_ops` en vivo devuelve `total_prompt_tokens:0, total_completion_tokens:0` (aunque `total_tokens:30112`). O sea el
desglose —el único valor nuevo que justificó extender `chat_metrics`— **no se está capturando**: `chat.php` no extrae
`usage.prompt_tokens`/`completion_tokens` de la respuesta del LLM.
- **Fix:** en `chat.php`, extrae el desglose de la respuesta del proveedor (Groq/DashScope son OpenAI-compatibles →
  `response.usage.prompt_tokens`/`completion_tokens`; Gemini usa `usageMetadata.promptTokenCount`/`candidatesTokenCount`)
  y guárdalo en las columnas. **Si algún backend no lo da, déjalo en 0 y decláralo** (§2 — no lo inventes); y en la vista
  de Ops, si el desglose es 0, muestra solo `total_tokens` (no barras vacías de prompt/completion).

## 🟡 Menor — el `session_id` se hace visible en el input del reconstructor tras el clic
Al clicar una fila, el `session_id` crudo aparece en el input del "Journey Reconstructor". El objetivo (no tener que
**conocer/pegar** el id) se cumple, pero para ser fiel a "id oculto" puedes **enmascararlo** (mostrar el resumen legible
de la sesión seleccionada en vez del hash). No bloqueante.

## Siguiente paso
Aplica los 3 arreglos (+ el menor si quieres). Deploy gateado dry-run→`--confirm`; corre el seeder en prod. Entrégame el
reporte y **reaudito en vivo**: (1) la lista carga al abrir la pestaña; (2) Bucket 1/2 poblados por el demo, separados por
el toggle; (3) `usage_ops` con desglose real o solo total honesto; (4) sin regresión, sin fuga.

---
**Nota:** Claude (Opus 4.8) reauditó en vivo. Lo estructural está bien (chat_metrics extendido sin duplicar, endpoints
200, sin fuga, lista navegable con clic funcionando). Los arreglos son: **(1)** que la lista cargue al abrir la pestaña
(hoy queda vacía hasta interactuar), **(2)** seeder ≥20/≥10 **y correrlo en prod** para poblar los buckets, **(3)** poblar
o no-mostrar el desglose de tokens (hoy 0).
