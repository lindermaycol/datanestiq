#!/usr/bin/env python3
"""project-standard — hook Stop de Claude Code: recordatorio de ADR / log al cerrar trabajo.

Solo avisa cuando el estado del repo cambió desde el último aviso (nuevo commit o cambios sin
commitear distintos), para no repetir el mismo recordatorio en cada turno. Nunca bloquea.
No editar en el repo: se regenera con apply_standard.py.
"""
from __future__ import annotations

import hashlib
import json
import subprocess
import sys
from pathlib import Path


def _git(*args: str) -> str:
    return subprocess.run(["git", *args], capture_output=True, text=True, check=False).stdout.strip()


def main() -> int:
    cfg_path = Path(__file__).with_name("standard.json")
    try:
        cfg = json.loads(cfg_path.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return 0
    status = _git("status", "--porcelain")
    head = _git("rev-parse", "HEAD")
    if not status and not head:
        return 0
    huella = hashlib.sha1(f"{head}\n{status}".encode()).hexdigest()
    estado = Path(_git("rev-parse", "--git-dir") or ".git") / "project-standard-stop"
    try:
        if estado.read_text().strip() == huella:
            return 0
    except OSError:
        pass
    try:
        estado.write_text(huella)
    except OSError:
        pass
    proyecto, vault = cfg.get("project", "?"), cfg.get("vault_folder", "?")
    print(json.dumps({
        "systemMessage": (
            f"[project-standard] ¿Hubo una decisión de arquitectura? Regístrala en "
            f"vault/{vault}/architecture/decisions.md (plantilla ADR) y cierra la sesión con /guardar {proyecto}."
        )
    }))  # escapes \uXXXX: la consola de Windows no es UTF-8
    return 0


if __name__ == "__main__":
    sys.exit(main())
