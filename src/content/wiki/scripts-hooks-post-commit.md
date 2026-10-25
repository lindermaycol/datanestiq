---
title: "Git Post-Commit Hook para Graphify"
description: "Documentación del script `post-commit` que automatiza la reconstrucción del grafo de conocimiento de Graphify después de cada commit, sin bloquear el flujo d"
author: "AI Documenter"
lastUpdated: 2026-08-01
tags: ["git","hook","graphify","automation","post-commit","python"]
seoScore: 95
---
Este documento describe el script `post-commit` ubicado en `scripts/hooks/post-commit`, que es un hook de Git diseñado para integrarse con la herramienta Graphify. Su función principal es asegurar que el grafo de conocimiento de Graphify se mantenga actualizado automáticamente después de cada `git commit` que involucre cambios en archivos de código.

## Propósito

El hook `post-commit` de Graphify tiene como objetivo principal automatizar la reconstrucción del grafo de conocimiento de un proyecto. Esto garantiza que el grafo siempre refleje el estado más reciente del código base, sin requerir intervención manual. Está diseñado para ser eficiente y no bloquear el proceso de commit de Git.

## Instalación

Este hook es instalado automáticamente por el comando `graphify hook install`.

## Funcionamiento

El script se ejecuta automáticamente después de que un `git commit` se ha completado con éxito. Su lógica se puede dividir en varias etapas:

### 1. Pre-verificaciones y Salida Temprana

Para evitar interrupciones en operaciones complejas de Git, el script verifica si se está realizando una operación de Git que podría ser bloqueada por el hook. Si detecta que Git está en medio de un `rebase`, `merge` o `cherry-pick`, el script sale inmediatamente para no interferir con el flujo de trabajo.

```bash
GIT_DIR=$(git rev-parse --git-dir 2>/dev/null)
[ -d "$GIT_DIR/rebase-merge" ] && exit 0
[ -d "$GIT_DIR/rebase-apply" ] && exit 0
[ -f "$GIT_DIR/MERGE_HEAD" ] && exit 0
[ -f "$GIT_DIR/CHERRY_PICK_HEAD" ] && exit 0
```

También verifica si hubo cambios en los archivos entre el commit actual y el anterior. Si no hay cambios, no hay necesidad de reconstruir el grafo, y el script sale.

```bash
CHANGED=$(git diff --name-only HEAD~1 HEAD 2>/dev/null || git diff --name-only HEAD 2>/dev/null)
if [ -z "$CHANGED" ]; then
    exit 0
fi
```

### 2. Detección del Intérprete de Python

El script intenta encontrar el intérprete de Python correcto que pueda ejecutar `graphify`. Sigue un proceso robusto:

1.  **`command -v graphify`**: Primero, intenta localizar el ejecutable `graphify` en el `PATH`.
2.  **Shebang Parsing**: Si encuentra `graphify`, intenta extraer el intérprete de Python de su línea shebang (por ejemplo, `#!/usr/bin/env python3`). Se realiza una validación para evitar inyecciones de comandos.
3.  **Verificación de Importación**: Confirma que el intérprete detectado puede importar el módulo `graphify`.
4.  **Fallback**: Si la detección falla, intenta `python3` y luego `python` como opciones de respaldo.
5.  **Salida**: Si no se puede encontrar un intérprete de Python válido que pueda ejecutar `graphify`, el script sale.

### 3. Reconstrucción en Segundo Plano

Una de las características clave de este hook es que la reconstrucción del grafo se ejecuta en segundo plano. Esto es crucial porque una reconstrucción completa del repositorio puede llevar tiempo, y bloquear el hook `post-commit` detendría la shell del usuario, impidiendo que el comando `git commit` retorne inmediatamente.

El script utiliza `nohup` y `disown` para lanzar el proceso de Python de forma desatendida:

```bash
nohup $GRAPHIFY_PYTHON -c "..." > "$_GRAPHIFY_LOG" 2>&1 < /dev/null &
disown 2>/dev/null || true
```

*   `GRAPHIFY_CHANGED`: La lista de archivos modificados se exporta como una variable de entorno para que el script de Python pueda acceder a ella.
*   `_GRAPHIFY_LOG`: La salida de la reconstrucción se redirige a un archivo de log (`~/.cache/graphify-rebuild.log`) para depuración y seguimiento.

### 4. Lógica de Reconstrucción de Graphify (Python)

El fragmento de código Python incrustado dentro del script bash realiza la lógica central de reconstrucción:

1.  **Obtener Archivos Cambiados**: Lee la variable de entorno `GRAPHIFY_CHANGED` para obtener la lista de archivos que fueron modificados en el commit.
2.  **Llamada a `_rebuild_code`**: Importa y llama a la función `_rebuild_code` del módulo `graphify.watch`. Esta función es la encargada de analizar los archivos de código modificados y actualizar el grafo de conocimiento.
3.  **Límites de Recursos y Tiempo de Espera**: Aplica límites de recursos (`_apply_resource_limits`) y establece un tiempo de espera configurable (`GRAPHIFY_REBUILD_TIMEOUT`, por defecto 600 segundos) para evitar que la reconstrucción se ejecute indefinidamente.
4.  **Manejo de Errores**: Captura `TimeoutError` y otras excepciones, imprimiendo mensajes de error en el log y saliendo con un código de error apropiado.

## Beneficios

*   **Automatización**: Mantiene el grafo de conocimiento siempre actualizado sin intervención manual.
*   **No Bloqueante**: Permite que el comando `git commit` retorne inmediatamente, mejorando la experiencia del desarrollador.
*   **Eficiencia**: Solo reconstruye el grafo si hay cambios en los archivos de código.
*   **Robustez**: Maneja diferentes configuraciones de Python y evita interferir con operaciones complejas de Git.

Este hook es una pieza fundamental para mantener la "Documentación Viva" de Datanestiq, asegurando que el grafo de conocimiento refleje con precisión el estado actual del código base.