# Tareas: Spec 010 (Generador Multi-Destino)

## Fase 1: Enrutamiento y Base
- `[x]` Crear script base `docs-generator.mjs`.
- `[x]` Manejar argumentos CLI (`--target`, `--dry-run`, `--seed`, `--brief`).
- `[x]` Carga de `.env` manual integrada sin dependencias extra.

## Fase 2: Balanceo y LLMs
- `[x]` Integrar llamadas `fetch` OpenAI-compatibles.
- `[x]` Manejar Pool de Proveedores: Groq, DashScope, Gemini.
- `[x]` Construir arreglo `getProviderOrder` con selección aleatoria por `poolIndex`.
- `[x]` Implementar try-catch de fallback atómico (Failover).
- `[x]` Backoff exponencial ante respuestas 429 conjuntas.

## Fase 3: Seguridad y Parser
- `[x]` Lógica de protección vía lista blanca `git ls-files` y `SECURITY_EXCLUDES`.
- `[x]` Extraer y restaurar bloques `<!-- OPENWIKI:IGNORE:START -->`.
- `[x]` Manejo estricto de comillas y carácteres en Frontmatter.
- `[x]` Extraer `pubDate` dinámico y sin comillas explícitas para Zod en el target Blog.

## Fase 4: Targets y Contenidos
- `[x]` Target `wiki`: procesar diffs y respetar schema de wiki.
- `[x]` Target `agents`: escanear `specs/` y generar `AGENTS.md` fundacional.
- `[x]` Target `skills`: procesar `.agents/skills/SKILL.md` (skills tracked).
- `[x]` Target `blog`: redactar basándose en el argumento `--brief` y leer contexto taxonómico.
- `[x]` Habilitar flag `--seed` / `--all` con lógica que da sentido al primer commit.
