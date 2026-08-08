---
title: "Build Gate Antidrift para el Estado de Specs (Spec 017)"
description: "El script `build-specs-status.mjs` es un build-gate antidrift que valida en tiempo de compilación que `src/data/specsStatus.json` (SSOT) no diverja de la tab"
author: "AI Documenter"
lastUpdated: 2026-08-04
tags: ["build-gate","antidrift","specs","status","validation","security","SSOT"]
seoScore: 100
---
# Build Gate Antidrift para el Estado de Specs (Spec 017)
## Introducción
El script `build-specs-status.mjs` es un "Build Gate Antidrift" diseñado para asegurar la coherencia de la "Documentación Viva" de Datanestiq. Su función principal es validar en tiempo de compilación que `src/data/specsStatus.json` (considerado la Fuente Única de Verdad o SSOT) no diverja de la tabla de seguimiento humana mantenida en `planes/ESTADO-SPECS.md`. En caso de detectar cualquier desincronización (drift), el script interrumpe la compilación con un código de salida 1, garantizando la integridad de la información.
## Regla Estricta de Seguridad (Constitución §6)
- **PROHIBIDO** emitir `specs-status.json` en la carpeta `public/` para evitar que se expongan públicamente detalles de deuda técnica o arquitectura interna del sistema. Esta es una medida crítica para la protección de la información sensible.
## Uso
Para ejecutar el script y realizar la validación, utilice el siguiente comando:
```bash
node scripts/build-specs-status.mjs
```
## Funcionamiento
El script sigue una serie de pasos lógicos para detectar y prevenir el drift:
1.  **Verificación de Archivos Críticos**: Inicialmente, comprueba la existencia de `src/data/specsStatus.json` y `planes/ESTADO-SPECS.md`. Si alguno de estos archivos esenciales no se encuentra, el script emite un `[ERROR CRÍTICO]` y termina la ejecución con código de salida 1.
2.  **Carga de Datos**: Lee y parsea el contenido de `src/data/specsStatus.json` y carga el texto de `planes/ESTADO-SPECS.md`.
3.  **Iteración y Búsqueda**: Itera sobre cada especificación (`spec`) definida en `specsStatus.json`. Para cada `spec.id`, busca la fila correspondiente en `ESTADO-SPECS.md` utilizando una expresión regular que identifica el formato `**SPEC_ID (Nombre)** | Estado | ...`.
4.  **Detección de Drift por Ausencia**: Si una `spec.id` declarada en `specsStatus.json` no se encuentra en `ESTADO-SPECS.md`, se registra un `[DRIFT DETECTADO]` indicando una inconsistencia.
5.  **Mapeo de Estados**: Si se encuentra la fila correspondiente en el Markdown, extrae el texto del estado y lo mapea a un estado equivalente estandarizado (`LIVE`, `DESIGNED`, `IN_PROGRESS`, `SUPERSEDED`). Este mapeo se basa en la presencia de emojis específicos (e.g., `✅` o `🟢` para `LIVE`, `🟠` para `DESIGNED`, `🔴` para `SUPERSEDED`, `⏸️` para `IN_PROGRESS`).
6.  **Verificación de Congruencia**: Compara el estado de la especificación en `specsStatus.json` (`jsonStatus`) con el estado mapeado del Markdown (`mdStatusEquivalent`). Se considera una coincidencia si los estados son idénticos, o si ambos estados son `DESIGNED` o `IN_PROGRESS` (permitiendo una flexibilidad controlada entre estos dos en el seguimiento manual). Cualquier otra discrepancia se registra como un `[DRIFT ERROR]` detallado.
7.  **Fallo de Compilación por Drift**: Si se detecta cualquier tipo de drift durante la verificación, el script muestra un mensaje `🔴 [BUILD FAILED]` y termina la ejecución con código de salida 1, exigiendo la corrección de las diferencias.
8.  **Aplicación de Seguridad (§6)**: Si no se detecta drift, el script procede a eliminar el archivo `public/api/specs-status.json` si existe. Esta acción refuerza la Regla Estricta de Seguridad (§6) al prevenir activamente la exposición pública de datos internos del sistema.
9.  **Confirmación de Éxito**: Finalmente, si todas las verificaciones pasan y no se detecta drift, el script muestra un mensaje `[SUCCESS]`, confirmando que el Build-gate Antidrift ha pasado al 100% y que las especificaciones están sincronizadas y seguras.
## Seguridad §6
El script implementa activamente la Constitución §6 al eliminar de forma preventiva cualquier instancia de `public/api/specs-status.json`. Esto asegura que el SSOT (`src/data/specsStatus.json`) se mantenga estrictamente privado y no se sirva accidentalmente a través de la interfaz pública, protegiendo la arquitectura interna y la deuda técnica de ser expuestas.