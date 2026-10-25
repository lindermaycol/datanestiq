# Índice de prompts y ruta de orquestación — datanestiq

*(Lo mantiene Claude Code. **AntiGravity implementa; Claude Code audita** cada prompt al terminar e itera
—`NNNb`, `NNNc`…— hasta aprobarlo. Se actualiza tras cada ejecución y cada auditoría. Modelo de referencia:
`C:\Users\Lisbeth\notebooklm-hub\prompts\00-indice-orquestacion-y-protocolo.md`. Estándar v1.1.0.)*

## Cómo usarlos
- **Vía Orca (por defecto):** Claude Code lanza cada prompt con `orca orchestration worker-start` en su propio
  worktree (planificar: `gemini-4-argon-high`; ejecutar: `gemini-3.8-flash-high`) y espera el `worker_done`.
- **A mano:** en AntiGravity, una conversación nueva por prompt: `Lee y ejecuta <ruta del repo>\prompts\<archivo>.md`.
- Al terminar, el agente deja `prompts/<NNN>-<slug>.retorno.md`. **No se lanza nada que dependa de un prompt hasta
  que Claude Code lo audite.**

## ▶ RUTA VIGENTE (AAAA-MM-DD, ronda N) — léela primero

| Prompt | Dictamen | Corrección |
|---|---|---|
| — | — | — |

| Orden | Prompt | ¿Paralelo? | Archivos exclusivos | Depende de |
|---|---|---|---|---|
| 1 | — | — | — | — |

**Estado verificado hoy:** <tests / validador / bases intactas, con la evidencia>
**Pendiente del dueño:** <decisiones o acciones que solo puede hacer la persona>

---

## (Histórico) rutas anteriores
<!-- Al abrir una ronda nueva, mueve aquí la RUTA VIGENTE anterior con su fecha; no la borres. -->

## Mapa de olas y paralelismo (referencia)

| Ola | Prompt | Qué hace | Toca (archivos exclusivos) | Depende de |
|---|---|---|---|---|

**Regla de paralelo:** dos prompts corren a la vez solo si no comparten archivos. Los archivos compartidos
(estado de specs, migraciones, configuración) se editan al final de cada prompt, releyéndolos y añadiendo solo lo propio.

## Protocolo común
Ver `.agents/rules/project-standard.md` (orientación barata, SDD, Regla de Oro, bases aisladas y prompt de retorno obligatorio).
