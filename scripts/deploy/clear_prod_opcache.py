#!/usr/bin/env python3
"""
clear_prod_opcache.py — Resetea OPcache en el entorno web de IONOS (PHP-FPM)
creando un script transitorio en la carpeta web accesible y llamándolo por HTTP.
Uso: python scripts/deploy/clear_prod_opcache.py
"""
import os
import sys
import posixpath
import urllib.request
import ssl

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

    print(f"🔗 Conectando SSH a {user}@{host} para resetear OPcache...")
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

        # 1. Crear el script temporal clear_opcache.php accesible vía HTTP
        opcache_file = posixpath.join(remote_base, "admin", "clear_opcache.php")
        php_code = '<?php header("Content-Type: text/plain"); if(function_exists("opcache_reset")){ opcache_reset(); echo "OPCACHE_RESET_SUCCESS"; } else { echo "OPCACHE_RESET_NOT_SUPPORTED"; }'
        
        # Escribir código usando SSH exec
        cmd_create = f"echo '{php_code}' > {opcache_file}"
        print(f"  📝 Creando script remoto transitorio en: {opcache_file}")
        ssh.exec_command(cmd_create)

        # 2. Hacer la solicitud HTTP al script creado
        url = "https://app.datanestiq.com/admin/clear_opcache.php"
        print(f"  🌐 Solicitando HTTP: {url}")
        
        # Deshabilitar verificación SSL por si acaso para evitar problemas de certificado en el entorno CLI
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
            with urllib.request.urlopen(req, context=ctx, timeout=15) as response:
                result = response.read().decode('utf-8').strip()
                print(f"  👉 Respuesta OPcache: {result}")
        except Exception as e_http:
            print(f"  ⚠️  Error HTTP durante el reset de OPcache: {e_http}")
            result = "FAILED"

        # 3. Eliminar el script temporal por seguridad
        cmd_delete = f"rm -f {opcache_file}"
        print("  🧹 Eliminando script remoto transitorio...")
        ssh.exec_command(cmd_delete)

        if "OPCACHE_RESET_SUCCESS" in result:
            print("\n🎉 OPcache reseteado exitosamente en el servidor web de producción.")
        else:
            print("\n⚠️  No se pudo confirmar el reset de OPcache o no es soportado.")

    finally:
        ssh.close()

if __name__ == "__main__":
    main()
