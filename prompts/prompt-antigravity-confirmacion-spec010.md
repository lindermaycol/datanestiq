# Prompt para Antigravity: Confirmación del Plan Spec 010 (Generador Multi-Destino) → ejecutar con precisiones

Revisé `specs/010-generador-multidestino/spec.md` y el plan. Están **fieles y bien estructurados** (4 targets, balanceo + backoff 429 + failover, `providerStats`, exclusiones de seguridad, CLI, verificación). **Luz verde para ejecutar**, con 6 precisiones.

## Precisión 1 (SEGURIDAD, la más importante) — usa `.gitignore` como fuente de verdad, no solo una lista
Ya endurecimos `.gitignore` para excluir exactamente lo sensible (`.env`, `secure_leads/`, `*.jsonl`, `wp-config.php`, core WP). En vez de mantener una lista negra paralela que puede quedar desincronizada:
- **Procesa solo archivos git-tracked / no-ignorados.** Para `wiki` con `git diff`, los archivos ya son tracked (los gitignored no aparecen). Para cualquier recolección de archivos (target `agents`, etc.), filtra con **`git check-ignore`** (o `git ls-files`) para descartar lo que `.gitignore` ya excluye. Así, si mañana se añade un secreto al `.gitignore`, queda excluido automáticamente.
- **Además**, mantén la lista negra explícita como respaldo, pero **complétala**: agrega `remote_extract.py` y `wp-content`, y usa match por **ruta/prefijo o nombre exacto** (no substring naïve). Nunca enviar esos contenidos al LLM.

## Precisión 2 — Rename: actualiza TODAS las referencias funcionales (o algo se rompe)
Al renombrar `openwiki-sync.mjs` → `docs-generator.mjs`, hay que actualizar quien lo invoca:
- **`.github/workflows/openwiki-audit.yml`** (workflow de la Spec 005): cámbialo a `node scripts/docs-generator.mjs --target=wiki` (o deshabilítalo si ya no aplica; decide en el plan).
- **`scripts/openwiki-local-sync.sh`**: actualiza la invocación.
- Referencias en docs de la Spec 005 (`tech_debt.md`, etc.) y en `USO-MANUAL.md`/`GUIA-GATEWAY-Y-BALANCEO.md` si mencionan el script — actualízalas para que no queden colgadas.
- Verifica que nada más quede apuntando al nombre viejo.

## Precisión 3 — Target `skills`: fuente REAL, no inventada
Las skills del proyecto viven en **`.agents/skills/<nombre>/SKILL.md`** (las speckit: `speckit-specify`, `speckit-plan`, `speckit-tasks`, `speckit-implement`, `speckit-analyze`, `speckit-clarify`, `speckit-constitution`, `speckit-checklist`, `speckit-converge`, `speckit-taskstoissues`). El target `skills` debe **leer esos `SKILL.md` reales** y resumirlos — **no inventar** habilidades. (Ojo: `.agents/` está gitignored salvo la excepción `!.agents/skills/`, así que son tracked y legibles.)

## Precisión 4 — Target `agents`: resumen estructurado, no volcado crudo (1 llamada, fundamentado)
`AGENTS.md` es **un solo doc** = **1 llamada**. No metas el repo entero en el prompt (revienta tokens y no es fundamentado). Arma un **contexto curado**: el **árbol de archivos** (rutas, sin contenidos masivos), los **títulos de las specs** (`specs/*/spec.md`), y los **archivos de configuración clave** (`astro.config.mjs`, `package.json` scripts, `src/content.config.ts`). Con eso el LLM redacta un overview real para agentes (arquitectura, convenciones, cómo navegar). Como `AGENTS.md` influye en Claude Code/Antigravity, que sea **preciso** y sin instrucciones inventadas.

## Precisión 5 — `--dry-run` debe simular SIN claves
Reafirma la lección ya aplicada en `openwiki-sync.mjs`: el corto-circuito `DRY_RUN` va **antes** de cualquier verificación de claves, para que `--dry-run` funcione en un clon sin `.env` y **nunca** consuma API.

## Precisión 6 — Verificación: muestra el REPARTO real (lote) y el failover ante 429
- **Balanceo:** para evidenciarlo, genera un **lote de varios docs** (ej. 6) y muestra el `providerStats` con el **reparto entre los 3** (no basta 1 doc). Confirma que un lote típico hace **pocas llamadas** (1/doc) → cabe en free tier.
- **Failover:** además del test de token inválido (401), verifica el camino **429**: si un proveedor da 429 salta al siguiente; si los 3 dan 429, backoff + reintento. Reporta lo real.
- Modelos: usa IDs vigentes verificados (Groq `qwen/qwen3-32b` o `llama-3.3-70b-versatile`; DashScope `qwen-plus`; Gemini `gemini-2.5-flash` o `gemini-3.1-flash-lite`).

## Cierre de estado
- `planes/ESTADO-SPECS.md`: fila 010 con estado real; 009 archivada ("requiere tier de pago"); nota en 005 sobre su evolución a la 010.
- `.env.example`: `GROQ_MODEL`/`DASHSCOPE_MODEL`/`GEMINI_MODEL` + pesos.

## Forma de respuesta
- Reporta la verificación E2E con la **distribución real de proveedores** (tabla) y una muestra por destino. No des por bueno lo no probado.
- No toques el core WP. Sección final "Hallazgos adicionales".

---

**Nota:** Claude (Sonnet 5) auditará que las exclusiones de seguridad realmente impidan enviar `.env`/`secure_leads`/`wp-config`/core WP al LLM (idealmente vía `.gitignore`), que el rename no deje referencias rotas, que `skills` lea los `SKILL.md` reales, que el balanceo reparta de verdad (evidencia de lote), y que cada destino cumpla su esquema Zod (build en verde).
