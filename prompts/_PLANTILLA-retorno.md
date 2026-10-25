# Retorno <NNN> — <título del prompt>

<!-- Plantilla del estándar común (v1.0.0). AntiGravity la llena al terminar, para que Claude Code
audite SIN releer el código completo. Solo hechos verificables; sin prosa ni volcado de código. -->

**Prompt:** `prompts/<NNN>-<slug>.md` · **Rama/worktree:** `<nombre>` · **Estado:** <hecho | parcial | bloqueado>

## 1. Commits
| Hash | Mensaje |
|---|---|
| `<hash>` | <mensaje> |

## 2. Cambios (archivo:línea → intención, una línea cada uno)
- `<archivo>:<inicio>-<fin>` — <qué cambió y por qué>

## 3. Comandos ejecutados (salida resumida, ≤ 5 líneas cada uno)
```
$ <comando>
<salida relevante>
```

## 4. Bases de datos / migraciones
- <migración creada `db/migrations/NNN_*.sql` | ninguna> · <tablas tocadas>

## 5. Afirmaciones a verificar (para la auditoría de Claude Code)
| Afirmación | Cómo se verifica |
|---|---|
| <«la tarea T-xx quedó hecha»> | `grep -n "T-xx" specs/…/tasks.md` |
| <«el test X pasa»> | `<comando de test>` |

## 6. Bloqueos y preguntas
- <bloqueo documentado (Regla de Oro) | ninguno>
- <pregunta para el dueño o para Claude Code>
