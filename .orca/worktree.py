#!/usr/bin/env python3
"""project-standard — ciclo de vida de un worktree de Orca (scripts.setup / scripts.archive de orca.yaml).

setup   (al crear el worktree):
  1. Respaldo previo de las bases que se van a copiar (`db_copy`) en
     ~/backups/<proyecto>/pre-worktree/<nombre>-<fecha>/. Solo esas: el respaldo completo (lake,
     raw, etc.) lo hace ~/scripts/backup_all.py programado, no cada worktree.
  2. Copia aislada de las bases locales (`db_copy` en .orca/project.json) desde el checkout principal,
     para que cada agente trabaje sobre la suya y no choque con otros worktrees.
  3. Copia `.env` si falta (respaldo de .worktreeinclude).
  4. Índice CodeGraph propio del worktree.
archive (antes de borrar el worktree):
  Guarda una copia de las bases del worktree en ~/backups/<proyecto>/worktrees/<nombre>-<fecha>/.
  Los datos NO se fusionan automáticamente: los cambios de esquema viajan como migraciones en git y
  se aplican a la base principal tras el merge.

Si se ejecuta en el checkout principal no hace nada. No editar: se regenera con apply_standard.py.
"""
from __future__ import annotations

import json
import shutil
import subprocess
import sys
from datetime import datetime
from pathlib import Path


def _git(*args: str) -> str:
    return subprocess.run(["git", *args], capture_output=True, text=True, check=False).stdout.strip()


def _copiar(origen: Path, destino: Path) -> None:
    if origen.is_dir():
        shutil.copytree(origen, destino, dirs_exist_ok=True)
    else:
        destino.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(origen, destino)


def main(accion: str) -> int:
    raiz = Path(_git("rev-parse", "--show-toplevel"))
    principal = Path(_git("rev-parse", "--path-format=absolute", "--git-common-dir")).parent
    if raiz.resolve() == principal.resolve():
        print("[orca-worktree] checkout principal: nada que hacer")
        return 0
    cfg = json.loads((raiz / ".orca" / "project.json").read_text(encoding="utf-8"))
    proyecto, bases = cfg["project"], cfg.get("db_copy", [])

    sello = f"{raiz.name}-{datetime.now():%Y%m%d-%H%M%S}"
    if accion == "setup":
        respaldo = Path.home() / "backups" / proyecto / "pre-worktree" / sello
        for rel in bases:
            if (principal / rel).exists():
                _copiar(principal / rel, respaldo / rel)
        if respaldo.exists():
            print(f"[orca-worktree] respaldo previo en {respaldo}")
        for rel in bases + [".env"]:
            origen, destino = principal / rel, raiz / rel
            if origen.exists() and not destino.exists():
                _copiar(origen, destino)
                print(f"[orca-worktree] copia aislada: {rel}")
        # En Windows npm instala codegraph.cmd junto a un script sin extensión que CreateProcess no ejecuta.
        codegraph = shutil.which("codegraph.cmd") or shutil.which("codegraph")
        if cfg.get("codegraph_in_worktree") and codegraph:
            try:
                r = subprocess.run([codegraph, "init", "-i"], cwd=raiz, capture_output=True, check=False)
                ok = r.returncode == 0
            except OSError:
                ok = False
            print(f"[orca-worktree] codegraph: {'ok' if ok else 'falló (no bloquea)'}")
        return 0

    if accion == "archive":
        destino = Path.home() / "backups" / proyecto / "worktrees" / sello
        for rel in bases:
            if (raiz / rel).exists():
                _copiar(raiz / rel, destino / rel)
        if destino.exists():
            print(f"[orca-worktree] bases del worktree guardadas en {destino}")
        print("[orca-worktree] recuerda: los cambios de esquema se aplican a la base principal vía migraciones tras el merge")
        return 0

    print("uso: python .orca/worktree.py setup|archive")
    return 2


if __name__ == "__main__":
    sys.exit(main(sys.argv[1] if len(sys.argv) > 1 else ""))
