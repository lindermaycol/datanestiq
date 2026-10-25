#!/usr/bin/env python3
"""
verify_remote_endpoints.py — Se conecta a IONOS vía SSH y ejecuta api.php mediante CLI
para validar el estado en vivo de los endpoints y asegurar que el fix de exclusión demo
está 100% operativo en producción.
Uso: python scripts/deploy/verify_remote_endpoints.py
"""
import os
import sys
import posixpath
import json

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

def run_remote_api_query(ssh, remote_parent, action, include_demo=0):
    queryString = f"action={action}&include_demo={include_demo}"
    code = f'$_SESSION = ["crm_authenticated" => true]; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_GET = []; parse_str("{queryString}", $_GET); error_reporting(0); ini_set("display_errors", 0); include "public/admin/api.php";'
    
    # Ejecutar en el servidor usando php8.2-cli
    cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli -r '{code}'"
    _, out, err = ssh.exec_command(cmd)
    stdout = out.read().decode('utf-8', errors='replace').strip()
    stderr = err.read().decode('utf-8', errors='replace').strip()
    
    if stderr:
        print(f"  [SSH STDERR]: {stderr}")
        
    try:
        return json.loads(stdout)
    except Exception:
        return {"error": "Not JSON", "raw": stdout}

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

    if not host or not user or not remote_path:
        fail("Configuración SSH incompleta en .env")

    remote_base = remote_path.rstrip("/")
    remote_parent = posixpath.dirname(remote_base)

    print(f"🔗 Conectando SSH a {user}@{host} para verificación en vivo...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        if key_path and os.path.isfile(key_path):
            ssh.connect(host, username=user, key_filename=key_path, timeout=30)
        elif password:
            ssh.connect(host, username=user, password=password, timeout=30)
        else:
            fail("No hay credenciales SSH.")

        print("✅ Autenticado en servidor de producción.")
        
        # 1. Verificar endpoint demand_signals sin demo
        res_ds_real = run_remote_api_query(ssh, remote_parent, 'demand_signals', 0)
        print("\n🔍 1. demand_signals (include_demo=0):")
        print(f"   - Response Keys: {list(res_ds_real.keys()) if isinstance(res_ds_real, dict) else 'Error'}")
        if isinstance(res_ds_real, dict):
            print(f"   - Is Insufficient: {res_ds_real.get('insufficient_data')}")
            print(f"   - Total Signals: {res_ds_real.get('total')}")
            print(f"   - Contains include_demo key: {'include_demo' in res_ds_real}")
            print(f"   - include_demo value: {res_ds_real.get('include_demo')}")
        else:
            print(f"   - Raw: {res_ds_real}")

        # 2. Verificar endpoint demand_signals con demo
        res_ds_demo = run_remote_api_query(ssh, remote_parent, 'demand_signals', 1)
        print("\n🔍 2. demand_signals (include_demo=1):")
        if isinstance(res_ds_demo, dict):
            print(f"   - Is Insufficient: {res_ds_demo.get('insufficient_data')}")
            print(f"   - Total Signals: {res_ds_demo.get('total')}")
            print(f"   - include_demo value: {res_ds_demo.get('include_demo')}")
        else:
            print(f"   - Raw: {res_ds_demo}")

        # 3. Verificar endpoint leakage sin demo
        res_lk_real = run_remote_api_query(ssh, remote_parent, 'leakage', 0)
        print("\n🔍 3. leakage (include_demo=0):")
        if isinstance(res_lk_real, dict):
            print(f"   - Is Insufficient: {res_lk_real.get('insufficient_data')}")
            print(f"   - Total interested: {res_lk_real.get('total')}")
            print(f"   - include_demo value: {res_lk_real.get('include_demo')}")
        else:
            print(f"   - Raw: {res_lk_real}")

        # 4. Verificar endpoint leakage con demo
        res_lk_demo = run_remote_api_query(ssh, remote_parent, 'leakage', 1)
        print("\n🔍 4. leakage (include_demo=1):")
        if isinstance(res_lk_demo, dict):
            print(f"   - Is Insufficient: {res_lk_demo.get('insufficient_data')}")
            print(f"   - Total interested: {res_lk_demo.get('total')}")
            print(f"   - include_demo value: {res_lk_demo.get('include_demo')}")
        else:
            print(f"   - Raw: {res_lk_demo}")

    finally:
        ssh.close()

if __name__ == "__main__":
    main()
