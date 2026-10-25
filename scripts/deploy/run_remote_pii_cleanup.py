#!/usr/bin/env python3
"""
run_remote_pii_cleanup.py — Sube y ejecuta scripts/redact_historical_pii.php en el servidor remoto via SSH.
Uso: python scripts/deploy/run_remote_pii_cleanup.py
"""
import os
import sys
import posixpath

try:
    sys.stdout.reconfigure(encoding="utf-8")
    sys.stderr.reconfigure(encoding="utf-8")
except Exception:
    pass

def load_env(path):
    if not os.path.exists(path):
        return
    with open(path, "r", encoding="utf-8") as fh:
        for line in fh:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            os.environ.setdefault(k.strip(), v.strip())

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, "..", ".."))
load_env(os.path.join(ROOT, ".env"))

def fail(msg):
    print(f"❌ {msg}", file=sys.stderr)
    sys.exit(1)

def main():
    try:
        import paramiko
    except ImportError:
        fail("paramiko no instalado. Ejecuta: pip install paramiko")

    host = os.environ.get("IONOS_SSH_HOST", "")
    user = os.environ.get("IONOS_SSH_USER", "")
    password = os.environ.get("IONOS_SSH_PASSWORD", "")
    key_path = os.environ.get("IONOS_SSH_KEY_PATH", "")
    remote_path = os.environ.get("IONOS_REMOTE_PATH", "")

    if not host or not user:
        fail("IONOS_SSH_HOST y IONOS_SSH_USER son requeridos en .env")
    if not remote_path:
        fail("IONOS_REMOTE_PATH es requerido en .env")

    remote_base = remote_path.rstrip("/")
    remote_parent = posixpath.dirname(remote_base)

    print(f"🔗 Conectando a {user}@{host}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        if key_path and os.path.isfile(key_path):
            ssh.connect(host, username=user, key_filename=key_path, timeout=30)
            print("  ✅ Autenticado con clave SSH.")
        elif password:
            ssh.connect(host, username=user, password=password, timeout=30)
            print("  ⚠️  Autenticado con password.")
        else:
            fail("No hay credenciales SSH disponibles.")

        # Subir el script de redacción por SFTP
        sftp = ssh.open_sftp()
        local_script = os.path.join(ROOT, "scripts", "redact_historical_pii.php")
        remote_script_dest = f"{remote_parent}/scripts/redact_historical_pii.php"
        try:
            sftp.put(local_script, remote_script_dest)
            print(f"  📤 Subido: {local_script} → {remote_script_dest}")
        except Exception as e_sftp:
            print(f"  ⚠️  No se pudo subir vía SFTP: {e_sftp}")
        finally:
            sftp.close()

        # Ejecutar redact_historical_pii.php remotamente
        cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli scripts/redact_historical_pii.php"
        print(f"\n🧹 Ejecutando: {cmd}")
        _, out, err = ssh.exec_command(cmd)
        stdout = out.read().decode(errors="replace").strip()
        stderr = err.read().decode(errors="replace").strip()

        if stdout:
            print(stdout)
        if stderr:
            print(f"  stderr: {stderr}")

    finally:
        ssh.close()

if __name__ == "__main__":
    main()
