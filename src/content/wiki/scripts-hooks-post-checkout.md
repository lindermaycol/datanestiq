---
title: "Post-Checkout Hook: Automatización de Reconstrucción del Grafo de Conocimiento"
description: "Documentación técnica del script `post-checkout` que desencadena reconstrucciones automáticas del grafo de conocimiento al cambiar de rama en Git."
author: "AI Documenter"
lastUpdated: 2026-08-01
tags: ["git-hooks","graphify","automation","knowledge-graph","shell-scripting","devops"]
seoScore: 100
---
## Visión General

El script `scripts/hooks/post-checkout` es un *hook de Git* ejecutado automáticamente tras cada operación `git checkout` o `git switch`. Su propósito principal es **reconstruir el grafo de conocimiento (solo código)** cuando se cambia de rama, garantizando que la representación semántica del proyecto permanezca sincronizada con el estado actual del repositorio.

Este hook forma parte del ecosistema `graphify`, y se instala mediante el comando `graphify hook install`.

---

## Comportamiento Clave

### ✅ Ejecución condicional
El hook solo se activa bajo estas condiciones:

- Se ha realizado un **cambio de rama** (`BRANCH_SWITCH == "1"`).
- El directorio `graphify-out/` existe (indicando que ya se ha construido un grafo previamente).
- No está ocurriendo una operación de conflicto o reescritura: no hay `rebase-merge`, `rebase-apply`, `MERGE_HEAD` ni `CHERRY_PICK_HEAD` activos.

> ⚠️ **Nota**: No se ejecuta durante `git checkout <file>` (solo archivos), ni durante `git restore`, ni en operaciones de mantenimiento como `git gc`.

### ✅ Detección robusta del intérprete Python
El script identifica dinámicamente el intérprete Python asociado a `graphify`, priorizando:

1. El binario `graphify` detectado por `command -v`.
2. Extracción segura del *shebang* (`#!/usr/bin/env python3`) y validación de su ruta.
3. Fallback a `python3` o `python`, verificando explícitamente que el módulo `graphify` esté importable.

Se aplica **sanitización de ruta**: cualquier carácter no permitido (`[!a-zA-Z0-9/_.@-]`) en la ruta del intérprete provoca su descarte, mitigando riesgos de inyección.

### ✅ Ejecución segura en segundo plano
La reconstrucción se lanza como proceso independiente mediante `nohup` + `&`, con:

- Redirección completa de `stdout` y `stderr` al log `~/.cache/graphify-rebuild.log`.
- Uso de `disown` para evitar dependencia del shell padre.
- Límite de tiempo configurable vía `GRAPHIFY_REBUILD_TIMEOUT` (por defecto: 600 segundos = 10 min), gestionado con `signal.alarm()`.
- Soporte para forzado explícito con `GRAPHIFY_FORCE=1`.

> 🔐 **Seguridad**: El uso de `flock` internamente en `_rebuild_code()` evita corridas concurrentes incluso si múltiples hooks (ej. `post-commit` + `post-checkout`) se disparan casi simultáneamente.

---

## Variables de Entorno Relevantes

| Variable | Valor por defecto | Descripción |
|----------|-------------------|-------------|
| `GRAPHIFY_REBUILD_TIMEOUT` | `600` | Tiempo máximo (en segundos) para completar la reconstrucción. `0` desactiva el timeout. |
| `GRAPHIFY_FORCE` | `""` | Si su valor es `"1"`, `"true"` o `"yes"` (insensible a mayúsculas), fuerza una reconstrucción completa, ignorando optimizaciones incrementales. |

---

## Flujo Interno (Python)

El bloque Python ejecutado es:

```python
from graphify.watch import _rebuild_code, _apply_resource_limits
from pathlib import Path
import os, signal, sys

try:
    _apply_resource_limits()  # Limita CPU/memoria (ej. ulimit)
    _timeout = int(os.environ.get('GRAPHIFY_REBUILD_TIMEOUT', '600'))
    if _timeout > 0 and hasattr(signal, 'SIGALRM'):
        signal.signal(signal.SIGALRM, lambda *_: (_ for _ in ()).throw(TimeoutError(...)))
        signal.alarm(_timeout)
    _force = os.environ.get('GRAPHIFY_FORCE', '').lower() in ('1', 'true', 'yes')
    _rebuild_code(Path('.'), force=_force)  # Ruta raíz del repo; sin changed_paths → full rebuild
except TimeoutError as exc:
    print(f'[graphify] {exc}')
    sys.exit(1)
except Exception as exc:
    print(f'[graphify] Rebuild failed: {exc}')
    sys.exit(1)
```

---

## Ubicación y Permisos

- **Ruta**: `scripts/hooks/post-checkout`
- **Permisos requeridos**: Ejecutable (`chmod +x`)
- **Instalación**: Automática con `graphify hook install`, que lo copia a `.git/hooks/post-checkout`.

---

## Solución de Problemas

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| `No log output` en `~/.cache/graphify-rebuild.log` | Hook no se disparó (ej. no fue cambio de rama) o falló antes de `nohup`. | Verificar `BRANCH_SWITCH`, existencia de `graphify-out/`, y permisos del directorio `~/.cache`. |
| `ImportError: No module named 'graphify'` | `graphify` no está instalado o no es accesible desde el intérprete detectado. | Instalar con `pipx install graphify` o activar el entorno virtual correcto antes de `graphify hook install`. |
| `Rebuild exceeds timeout` repetidamente | Grafo muy grande o recursos insuficientes. | Aumentar `GRAPHIFY_REBUILD_TIMEOUT`, revisar `_apply_resource_limits()`, o usar `GRAPHIFY_FORCE=0` para depuración. |

---

## Relación con Otros Hooks

Este hook complementa:

- [`post-commit`](./post-commit.md): Reconstruye solo los archivos modificados en el commit.
- [`pre-push`](./pre-push.md): Valida integridad del grafo antes de subir.

Juntos forman un ciclo de retroalimentación continua (*live knowledge graph*).