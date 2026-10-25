---
trigger: always_on
description: Estándar común del ecosistema (datanestiq) — orientación barata, SDD, Regla de Oro y prompt de retorno para Claude Code.
---

## Estándar común del ecosistema — datanestiq

> Regla permanente generada por `apply_standard.py` (estándar v1.1.0). No la edites aquí: cambia
> `vault/setup/project-standard/` y vuelve a aplicar.

**1. Orientación (en este orden, antes de grep o de leer archivos completos):**
- Grafo: `python -m graphify query "<pregunta>" --budget 1500` (o el MCP `graphify`). Tras cambiar código: `python -m graphify update .`.
- Símbolos: `codegraph context "<tarea>"`, `codegraph callers|callees|impact <símbolo>` (o el MCP `codegraph`). `impact` lista los tests afectados.
- Memoria (solo lectura, fuera del repo): `C:\Users\Lisbeth\vault\wiki\Root.md`, luego grep del tema en `C:\Users\Lisbeth\vault\datanestiq\architecture\decisions.md` y los 2-3 logs más recientes de `...\datanestiq\logs\`. **No edites el vault**: las decisiones las registra Claude Code con `/guardar datanestiq`.
- Lee por rangos `archivo:inicio-fin` (los que te da el prompt), nunca archivos completos "por si acaso".

**2. Metodología:** SDD (spec-kit). Ningún cambio de comportamiento sin spec/tarea que lo ampare; si no existe, el primer entregable es la spec.

**3. Regla de Oro:** si una fuente o dependencia está bloqueada o algo no se puede verificar, **documenta el bloqueo y detente**. Nunca inventes datos ni resultados para simular éxito.

**4. Bases de datos:** trabajas sobre la copia aislada de tu worktree (`.orca/worktree.py`). Cambios de esquema = archivo de migración versionado; nunca commitees `.db/.sqlite/.duckdb/.parquet` ni `.env` (el pre-commit los bloquea).

**5. Al terminar — prompt de retorno OBLIGATORIO** para que Claude Code audite sin releer todo:
crea `prompts/<NNN>-<slug>.retorno.md` copiando `prompts/_PLANTILLA-retorno.md`. Claude Code lo audita y, si algo
no cuadra, te enviará un prompt de corrección (`NNNb`); la ruta de prompts (paralelo/secuencial) está en
`prompts/00-indice-orquestacion*.md`: respétala y no edites ese índice. Debe listar commits (hash), cambios `archivo:línea-línea` con una línea de intención cada uno, comandos ejecutados con su salida resumida, migraciones/BD tocadas, cada afirmación con el comando que la verifica, bloqueos y preguntas. Sin prosa ni volcado de código.
