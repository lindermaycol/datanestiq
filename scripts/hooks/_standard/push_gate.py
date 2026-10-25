#!/usr/bin/env python3
"""project-standard — pre-push: protege las ramas principales.

1. Nunca reescribir historia (push no fast-forward / force) en una rama protegida.
2. Desde un worktree enlazado (los que crea Orca para cada agente) no se empuja directo a una rama
   protegida: el trabajo del agente llega por merge revisado.
3. Ramas `pr_only_branches` (opcional, por proyecto en standard.json): ningún push directo, desde
   ningún checkout; los cambios entran solo por Pull Request.
Excepción explícita para 2 y 3: STANDARD_ALLOW_PROTECTED_PUSH=1 (documentar el motivo).

No editar en el repo: se regenera con `python ~/scripts/apply_standard.py --apply <proyecto>`.
Git pasa por stdin: <local_ref> <local_sha> <remote_ref> <remote_sha>
"""
from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path

ZERO = "0" * 40


def _git(*args: str) -> str:
    return subprocess.run(["git", *args], capture_output=True, text=True, check=False).stdout.strip()


def main() -> int:
    try:
        cfg = json.loads(Path(__file__).with_name("config.json").read_text(encoding="utf-8"))
    except (OSError, ValueError):
        cfg = {}
    pr_only = set(cfg.get("pr_only_branches", []))
    protected = set(cfg.get("protected_branches", ["main", "master", "develop"])) | pr_only
    linked_worktree = os.path.abspath(_git("rev-parse", "--git-dir")) != os.path.abspath(_git("rev-parse", "--git-common-dir"))
    allow = os.environ.get("STANDARD_ALLOW_PROTECTED_PUSH") == "1"

    for line in sys.stdin:
        parts = line.split()
        if len(parts) != 4:
            continue
        _local_ref, local_sha, remote_ref, remote_sha = parts
        branch = remote_ref.removeprefix("refs/heads/")
        if branch not in protected or local_sha == ZERO:
            continue
        if remote_sha != ZERO and subprocess.run(
            ["git", "merge-base", "--is-ancestor", remote_sha, local_sha], capture_output=True
        ).returncode != 0:
            print(f"[project-standard] Push bloqueado: reescribiría la historia de '{branch}' (rama protegida).")
            return 1
        if branch in pr_only and not allow:
            print(f"[project-standard] Push bloqueado: '{branch}' solo recibe cambios por Pull Request.")
            print("  Sube tu rama (p. ej. develop o feature/<nombre>), abre el PR y haz merge cuando pase la CI.")
            print("  Hotfix urgente documentado: STANDARD_ALLOW_PROTECTED_PUSH=1 git push ...")
            return 1
        if linked_worktree and not allow:
            print(f"[project-standard] Push bloqueado: un worktree de agente no empuja directo a '{branch}'.")
            print("  Empuja tu rama y haz el merge revisado (o STANDARD_ALLOW_PROTECTED_PUSH=1 si es intencional).")
            return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
