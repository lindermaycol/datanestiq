# Prompt <NNN> — <título en imperativo>

<!-- Plantilla del estándar común (v1.0.0). Claude Code la llena para AntiGravity. Borra los
comentarios antes de enviarla. Objetivo: que AntiGravity NO tenga que explorar — todo lo que necesita
leer está citado con archivo:línea. Reúne esos rangos con codegraph_context / codegraph_impact y
graphify query, no leyendo archivos completos. -->

**Proyecto:** datanestiq · **Spec/tarea que lo ampara:** `<specs/NNN-…/tasks.md#T-xx | aidlc-docs/…>`
**Worktree / rama:** `<nombre>` · **¿Paralelo con otro prompt?** <Sí/No — archivos que NO debe tocar>
**Presupuesto:** máx. <N> archivos abiertos; lectura solo por los rangos citados abajo.

## 0. Objetivo (1-2 líneas)
<qué debe quedar funcionando y para qué>

## 1. Contexto verificado (hoy, <fecha>)
<!-- Hechos con su fuente: comando + salida corta, o archivo:línea. Nada de memoria. -->
- `<archivo>:<inicio>-<fin>` — <qué hay ahí y por qué importa>
- Decisión previa: `vault/datanestiq/architecture/decisions.md` → «<título de la entrada>» (no reabrirla)

## 2. Herramientas a usar (y qué NO leer)
- `codegraph impact <símbolo>` antes de cambiar `<símbolo>`; `python -m graphify query "<tema>" --budget 1500` si necesitas ubicar algo no citado.
- No leer: `<archivos/carpetas grandes o irrelevantes>`.

## 3. Cambios exactos
1. `<archivo>:<línea-línea>` — <qué cambiar> · regla de negocio a documentar en el código: <…> · verificación: `<comando>`
2. …

## 4. Qué NO hacer
- Regla de Oro: si algo está bloqueado o no se puede verificar, documenta el bloqueo y detente; nunca inventes datos.
- No tocar: <archivos/specs fuera de alcance>. No commitear `.env` ni bases de datos.

## 5. Criterios de aceptación
- [ ] `<comando de test>` pasa (pegar salida resumida en el retorno)
- [ ] <criterio verificable>

## 6. Entregable
Commits en la rama del worktree + `prompts/<NNN>-<slug>.retorno.md` con la plantilla `_PLANTILLA-retorno.md`.
