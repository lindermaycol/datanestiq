# Plan de Implementación — Spec 021: Panel-First (Diseño SDD)

Este documento describe el plan para redactar y formalizar el diseño de la **Spec 021: Panel-First**, que contempla la lista navegable de sesiones para el Journey Reconstructor, la migración de archivos planos al panel administrativo (`alerts.jsonl`, `usage_metrics.jsonl`, `leads_datanestiq.csv` y reporte del loop learn) y la provisión de datos demo marcados y separables.

## Proposed Changes

### Componente 1: Carpeta de Especificación y Artefactos

#### [NEW] [spec.md](file:///C:/xampp/htdocs/datanestiq/specs/021-panel-first/spec.md)
- Definición de los 5 bloques obligatorios de SDD:
  - **WHY:** Fricciones al usar session_id crudo y fragmentación de archivos planos.
  - **WHAT:** Alcance detallado de la lista navegable de sesiones y del visor de archivos de monitoreo en el panel.
  - **CONSTRAINTS:** Privacidad tras `auth.php`, guards de honestidad de muestra mínima intactos, marcas estrictas de datos demo.
  - **OUT-OF-SCOPE:** No exportación a nuevos formatos, no exposición de APIs de lectura públicas, no remoción inmediata de escritura a archivos.
  - **TASKS:** Tareas secuenciales de implementación para la fase de construcción.

#### [NEW] [data-model.md](file:///C:/xampp/htdocs/datanestiq/specs/021-panel-first/data-model.md)
- Estructura y DDL propuesto para las tablas de base de datos (`alerts`, `usage_metrics`) para optimizar volumen de datos e índices.
- Contrato propuesto de los endpoints admin (`journey_sessions`, `leads_detected`, `alerts_ops`, `usage_ops`, `learn_insights`).
- Diseño del formato y criterio de exclusión de los datos sembrados con el prefijo `demoseed%` y `[DEMO]`.

#### [NEW] [plan.md](file:///C:/xampp/htdocs/datanestiq/specs/021-panel-first/plan.md)
- Plan técnico paso a paso detallando la migración progresiva y la coexistencia de escrituras híbridas (SQLite y archivos plans).
- Arquitectura de la interfaz del panel para incorporar la tabla clickeable en "Demanda & Journey" y las sub-pestañas operativas.

#### [NEW] [tech_debt.md](file:///C:/xampp/htdocs/datanestiq/specs/021-panel-first/tech_debt.md)
- Declaración de la deuda técnica prevista: solapamientos con `chat_metrics`, carga recursiva de logs planos grandes si no son indexados en BD y periodicidad del cron.

---

### Componente 2: Sincronización y Gobernanza

#### [MODIFY] [ESTADO-SPECS.md](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md)
- Agregar la fila correspondiente a la Spec 021 con fase real `DISEÑADA` y su deuda/siguiente hito.

#### [MODIFY] [specsStatus.json](file:///C:/xampp/htdocs/datanestiq/src/data/specsStatus.json)
- Registrar de manera consistente el estado `designed` de la Spec 021 para cumplir con el validador antidrift.

#### [MODIFY] [Fases.md](file:///C:/xampp/htdocs/datanestiq/planes/Fases.md)
- Registrar e incorporar el hito de la Spec 021 dentro del mapa estratégico general.

---

## Verification Plan

### Validación de Coherencia Documental
1. **Sincronización:** Ejecutar `npm run docs:sync` para sincronizar los documentos generados con la wiki.
2. **Build-gate Antidrift:** Ejecutar `node scripts/build-specs-status.mjs` para garantizar que la nueva especificación esté registrada de manera idéntica en SSOT y que no haya drift documental.
3. **Compilación estática:** Ejecutar `npm run build` para asegurar la compilación estática libre de fallos.
