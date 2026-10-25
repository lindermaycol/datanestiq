#!/usr/bin/env python3
"""project-standard — pre-commit: bloquea secretos, archivos de entorno, bases de datos y binarios grandes.

Basado en cerebro/scripts/secrets_scan.py (escaneo por VALOR, no por nombre de variable), generalizado
para todos los repos del ecosistema. Lee el contenido STAGED (lo que de verdad entra al commit).

No editar en el repo: se regenera con `python ~/scripts/apply_standard.py --apply <proyecto>`.
Exit 1 bloquea el commit.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

try:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[union-attr]
except (AttributeError, ValueError):
    pass

SECRET_PATTERNS = [
    re.compile(r"sk-ant-[A-Za-z0-9_\-]{8,}"),                       # Anthropic
    re.compile(r"sk-[A-Za-z0-9]{20,}"),                            # OpenAI y compatibles
    re.compile(r"gsk_[A-Za-z0-9]{20,}"),                           # Groq
    re.compile(r"nvapi-[A-Za-z0-9_\-]{20,}"),                      # NVIDIA
    re.compile(r"AIza[0-9A-Za-z_\-]{30,}"),                        # Google
    re.compile(r"AKIA[0-9A-Z]{16}"),                               # AWS
    re.compile(r"gh[pousr]_[A-Za-z0-9]{30,}"),                     # GitHub
    re.compile(r"xox[baprs]-[A-Za-z0-9-]{10,}"),                   # Slack
    re.compile(r"\b[0-9]{8,10}:AA[A-Za-z0-9_\-]{30,}"),            # Telegram bot
    re.compile(r"datanest_live_[A-Za-z0-9]{16,}"),                 # API keys propias
    re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----"),
]
# Línea que declara/lee un secreto sin contenerlo, o lo marca como permitido.
ALLOW_LINE = re.compile(
    r"SecretStr|os\.getenv|os\.environ|getenv\(|Field\(|pragma:\s*allowlist secret|<[^>]+>",
    re.IGNORECASE,
)
# Valor con pinta de ficticio (fixtures de tests, ejemplos de docs).
FAKE_VALUE = re.compile(r"test|fake|dummy|example|ejemplo|placeholder|xxxx|1234|abcd", re.IGNORECASE)

BLOCKED_NAMES = re.compile(
    r"(^|/)(\.env(\.(?!example$)[^/]+)?|credentials[^/]*\.json|token\.json|client_secret[^/]*\.json"
    r"|id_rsa|id_ed25519|[^/]+\.pem|[^/]+\.key)$",
    re.IGNORECASE,
)
DATA_SUFFIXES = {".db", ".sqlite", ".sqlite3", ".duckdb", ".parquet", ".db-wal", ".db-shm"}


def _git(*args: str) -> bytes:
    return subprocess.run(["git", *args], capture_output=True, check=False).stdout


def _config() -> dict:
    p = Path(__file__).with_name("config.json")
    try:
        return json.loads(p.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return {}


def main() -> int:
    cfg = _config()
    max_bytes = int(cfg.get("max_file_mb", 5)) * 1024 * 1024
    staged = [s for s in _git("diff", "--cached", "--name-only", "--diff-filter=ACM", "-z").decode("utf-8", "replace").split("\0") if s]
    problems: list[str] = []

    for path in staged:
        low = path.lower()
        if BLOCKED_NAMES.search(low):
            problems.append(f"{path}: archivo de credenciales/entorno (no se versiona)")
            continue
        if Path(low).suffix in DATA_SUFFIXES:
            problems.append(f"{path}: base de datos/dataset (respáldalo con backup_all.py; el esquema va en migraciones)")
            continue
        size = int(_git("cat-file", "-s", f":{path}").strip() or 0)
        if size > max_bytes:
            problems.append(f"{path}: {size / 1048576:.1f} MB > {cfg.get('max_file_mb', 5)} MB")
            continue
        blob = _git("show", f":{path}")
        if b"\0" in blob[:8000]:
            continue  # binario pequeño (imagen, etc.)
        for n, line in enumerate(blob.decode("utf-8", "replace").splitlines(), 1):
            if ALLOW_LINE.search(line):
                continue
            for pat in SECRET_PATTERNS:
                m = pat.search(line)
                if m and not FAKE_VALUE.search(m.group(0)):
                    problems.append(f"{path}:{n}: posible secreto ({m.group(0)[:8]}…)")
                    break

    if problems:
        print("[project-standard] Commit bloqueado:")
        for p in problems[:30]:
            print(f"  - {p}")
        print("  Si es un falso positivo, marca la línea con `# pragma: allowlist secret`.")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
