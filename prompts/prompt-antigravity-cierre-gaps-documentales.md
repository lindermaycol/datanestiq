# Prompt para Antigravity: Cerrar 3 gaps documentales del ciclo de Cierre Integral

La auditoría de Claude (Sonnet 5) confirmó que el código del último ciclo (blog + failover LLM + proveedor configurable) está correcto y verificado en caliente. Pero quedaron **3 gaps de documentación** de la Parte B/D que hay que cerrar. **Solo documentación/config, sin tocar código de `src/` ni `chat.php`.**

## Gap 1 — Actualizar los headers "Fase actual" obsoletos en tech_debt.md
Verificado: siguen diciendo estados de prototipo. Corrige el campo `**Fase actual:**` en:
- `specs/001-elevacion-premium/tech_debt.md`: "Prototipo (HTML/Tailwind/Vanilla JS) -> Preparación para Migración a Astro" → **"Implementado en Astro (producción)"**.
- `specs/002-microexperiencias-ia/tech_debt.md`: "Prototipo" → **"Implementado en Astro (producción), verificado E2E"**.
- `specs/007-multi-pagina/tech_debt.md`: "⏳ Pendiente de Implementación" → **"Implementado (6 páginas SSG generadas, verificado)"**.
- Revisa también `003` y `004`: si su header contradice la realidad (003 sigue "Prototipo Estático" pero el corpus ya está en Astro; 004 tenía "Severidad: Resuelta" contradiciendo la deuda ROTO de LangGraph), ajústalos para que sean coherentes con el cuerpo del documento.

## Gap 2 — Crear `planes/ESTADO-SPECS.md`
Entregable faltante. Crea el archivo con una tabla resumen del estado real de las 8 specs, con columnas: **Spec | Fase real | Deuda abierta principal | Próximo hito**. Basa cada fila en el `tech_debt.md` ya reconciliado de cada spec (no inventes; refleja lo documentado). Incluye al pie una nota de los pendientes transversales (scripts stub de verificación, `remote_extract.py` con credenciales en claro, OpenWiki inoperativo).

## Gap 3 — Documentar Alibaba Qwen en `.env.example`
Actualmente `.env.example` solo tiene el ejemplo de DeepSeek para el tier económico. Añade, comentado (sin keys reales), la alternativa Alibaba Qwen (DashScope, OpenAI-compatible), para que el usuario pueda cambiar de proveedor solo editando `.env`. Deja algo como:
```
# Tier económico (OpenAI-compatible). Elige UN proveedor:
# --- Opción A: DeepSeek ---
CHEAP_LLM_URL=https://api.deepseek.com/v1/chat/completions
CHEAP_LLM_KEY=your_cheap_api_key_here
CHEAP_LLM_MODEL=deepseek-chat
# --- Opción B: Alibaba Qwen (DashScope) — descomenta para usar ---
# CHEAP_LLM_URL=https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions
# CHEAP_LLM_KEY=your_dashscope_api_key_here
# CHEAP_LLM_MODEL=qwen-plus
```
Mantén los nombres de variable EXACTOS que lee `chat.php` (`CHEAP_LLM_URL`, `CHEAP_LLM_KEY`, `CHEAP_LLM_MODEL`) — no los renombres.

## Verificación y cierre
- Confirma por grep que ningún `tech_debt.md` de las 8 specs tiene ya un header "Fase actual" que contradiga su contenido.
- Confirma que `planes/ESTADO-SPECS.md` existe con la tabla completa (8 filas).
- Confirma que `.env.example` tiene ambas opciones de proveedor con los nombres de variable correctos.
- Commit: `docs: cierra headers de estado, ESTADO-SPECS.md y ejemplo Qwen en .env.example`.
- No toques código de `src/`, `public/api/`, ni el core WP. Sección final "Hallazgos adicionales".
