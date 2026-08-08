#!/usr/bin/env python3
"""
run_remote_seed.py — Ejecuta seed_demand_demo.php en el servidor remoto via SSH.
Requiere las mismas variables .env que deploy_ionos.py.
Uso: python scripts/deploy/run_remote_seed.py
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

    # remote_path = "app/public" → remote_parent = "app"
    remote_base = remote_path.rstrip("/")
    remote_parent = posixpath.dirname(remote_base)  # = "app"
    # El seeder está en app/scripts/seed_demand_demo.php (relativo al home SSH)
    seed_remote = posixpath.join(remote_parent, "scripts", "seed_demand_demo.php")  # app/scripts/seed_demand_demo.php

    print(f"🔗 Conectando a {user}@{host}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        if key_path and os.path.isfile(key_path):
            ssh.connect(host, username=user, key_filename=key_path, timeout=30)
            print("  ✅ Autenticado con clave SSH.")
        elif password:
            ssh.connect(host, username=user, password=password, timeout=30)
            print("  ⚠️  Autenticado con password (migra a clave SSH).")
        else:
            fail("No hay credenciales SSH disponibles (IONOS_SSH_KEY_PATH o IONOS_SSH_PASSWORD)")

        # Primero subir el seeder por SFTP (el deploy_ionos no lo incluye por defecto)
        sftp = ssh.open_sftp()
        local_seed = os.path.join(ROOT, "scripts", "seed_demand_demo.php")
        remote_seed_dest = f"{remote_parent}/scripts/seed_demand_demo.php"
        try:
            sftp.put(local_seed, remote_seed_dest)
            print(f"  📤 Subido: {local_seed} → {remote_seed_dest}")
        except Exception as e_sftp:
            print(f"  ⚠️  No se pudo subir vía SFTP: {e_sftp}")
        finally:
            sftp.close()

        # Ejecutar seed_demand_demo.php remotamente (igual que deploy_ionos --init-crm)
        cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli scripts/seed_demand_demo.php"
        print(f"\n🌱 Ejecutando: {cmd}")
        _, out, err = ssh.exec_command(cmd)
        stdout = out.read().decode(errors="replace").strip()
        stderr = err.read().decode(errors="replace").strip()

        if stdout:
            print(stdout)
        if stderr:
            print(f"  stderr: {stderr}")

        # Verificar conteo de señales sembradas
        count_cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli -r \"$db=new PDO('sqlite:secure_leads/crm.sqlite'); echo $db->query(\\\"SELECT COUNT(*) FROM demand_signals WHERE session_id LIKE 'demoseed%'\\\")->fetchColumn();\""
        _, out2, _ = ssh.exec_command(count_cmd)
        count = out2.read().decode(errors="replace").strip()
        print(f"\n✅ Verificación: {count or '?'} señales demoseed en BD remota.")

    finally:
        ssh.close()

if __name__ == "__main__":
    main()
