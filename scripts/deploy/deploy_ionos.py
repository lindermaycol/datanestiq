#!/usr/bin/env python3
"""
deploy_ionos.py — Despliegue automatizado de Datanestiq a IONOS vía SSH/SFTP (paramiko).

SEGURIDAD (Constitución §6):
- Credenciales SOLO desde .env (gitignored). NUNCA hardcodeadas ni logueadas.
- Preferir AUTENTICACIÓN POR CLAVE (IONOS_SSH_KEY_PATH). El password quedó comprometido
  en el historial de git -> rotarlo o migrar a clave (ver planes/DESPLIEGUE-IONOS.md).
- DRY-RUN por defecto. El despliegue REAL requiere --confirm (acción explícita del humano).
- NUNCA sube: .env local, secure_leads/, node_modules, .git, .venv (el .env remoto se
  sube por separado y solo con --with-env, ver abajo).

Uso:
  python scripts/deploy/deploy_ionos.py               # dry-run (muestra qué haría)
  python scripts/deploy/deploy_ionos.py --confirm      # despliega dist/ + backend PHP
  python scripts/deploy/deploy_ionos.py --confirm --with-env   # además sube .env (con confirmación)
  python scripts/deploy/deploy_ionos.py --confirm --init-crm   # corre init/migración del CRM en remoto

Variables .env requeridas:
  IONOS_SSH_HOST, IONOS_SSH_USER
  IONOS_SSH_KEY_PATH  (recomendado)  o  IONOS_SSH_PASSWORD (fallback, comprometido)
  IONOS_REMOTE_PATH   (ruta remota del sitio, p.ej. /kunden/homepages/.../htdocs)
"""
import argparse
import os
import sys
import posixpath

# Forzar UTF-8 en la consola (Windows cp1252 no codifica →/emojis)
try:
    sys.stdout.reconfigure(encoding="utf-8")
    sys.stderr.reconfigure(encoding="utf-8")
except Exception:
    pass

# --- Cargar .env (sin volcar valores) ---
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

HOST = os.environ.get("IONOS_SSH_HOST")
USER = os.environ.get("IONOS_SSH_USER")
KEY_PATH = os.environ.get("IONOS_SSH_KEY_PATH")
PASSWORD = os.environ.get("IONOS_SSH_PASSWORD")
REMOTE = os.environ.get("IONOS_REMOTE_PATH")

# Qué se sube: el CONTENIDO de dist/ va a la RAÍZ del webroot remoto.
# Astro ya copia public/ (api/, admin/) dentro de dist/, así que dist/ contiene todo el sitio + backend PHP.
# NUNCA secretos/PII.
UPLOAD_LOCAL_DIR = "dist"  # su contenido -> IONOS_REMOTE_PATH/
EXCLUDE = {".env", "secure_leads", "node_modules", ".git", ".venv", "__pycache__"}


def fail(msg):
    print(f"❌ {msg}")
    sys.exit(1)


def connect():
    import paramiko
    if not HOST or not USER or not REMOTE:
        fail("Falta IONOS_SSH_HOST / IONOS_SSH_USER / IONOS_REMOTE_PATH en .env")
    ssh = paramiko.SSHClient()
    # Verificación de host key: carga known_hosts si existe; si no, AutoAdd con aviso.
    known = os.path.join(ROOT, ".ssh_known_hosts")
    if os.path.exists(known):
        ssh.load_host_keys(known)
        ssh.set_missing_host_key_policy(paramiko.RejectPolicy())
    else:
        print("⚠️  Sin .ssh_known_hosts: se acepta la host key en el primer uso (verifícala). "
              "Genera known_hosts para producción.")
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    if KEY_PATH and os.path.exists(KEY_PATH):
        print(f"Conectando a {USER}@{HOST} con CLAVE ({os.path.basename(KEY_PATH)})...")
        ssh.connect(HOST, username=USER, key_filename=KEY_PATH, timeout=30)
    elif PASSWORD:
        print(f"Conectando a {USER}@{HOST} con PASSWORD (⚠️ comprometido; migra a clave)...")
        ssh.connect(HOST, username=USER, password=PASSWORD, timeout=30)
    else:
        fail("Falta IONOS_SSH_KEY_PATH o IONOS_SSH_PASSWORD en .env")
    return ssh


def iter_files(local_dir):
    for base, dirs, files in os.walk(local_dir):
        dirs[:] = [d for d in dirs if d not in EXCLUDE]
        for f in files:
            if f in EXCLUDE:
                continue
            yield os.path.join(base, f)


def ensure_remote_dir(sftp, remote_dir):
    parts = remote_dir.strip("/").split("/")
    cur = "/" if remote_dir.startswith("/") else ""
    for p in parts:
        cur = posixpath.join(cur, p) if cur else p
        try:
            sftp.stat(cur)
        except IOError:
            sftp.mkdir(cur)


def deploy(dry_run, with_env, init_crm):
    # En dry-run se permite REMOTE ausente (solo para mostrar el plan); el deploy real lo exige en connect().
    remote_base = REMOTE or "<IONOS_REMOTE_PATH>"
    remote_parent = posixpath.dirname(remote_base) if REMOTE else "<PADRE_DEL_WEBROOT>"
    print(f"== Despliegue Datanestiq -> {HOST or '<IONOS_SSH_HOST>'}:{remote_base}  ({'DRY-RUN' if dry_run else 'REAL'}) ==")
    if dry_run and not REMOTE:
        print("  (aviso: IONOS_REMOTE_PATH no está en .env; el destino se muestra como placeholder)")
    planned = []
    ld = os.path.join(ROOT, UPLOAD_LOCAL_DIR)
    if not os.path.isdir(ld):
        fail(f"No existe {UPLOAD_LOCAL_DIR}/ — corre 'npm run build' primero.")
    for lf in iter_files(ld):
        # Contenido de dist/ -> raíz del webroot del subdominio (app.datanestiq.com)
        rel = os.path.relpath(lf, ld).replace("\\", "/")
        planned.append((lf, posixpath.join(remote_base, rel)))
    print(f"  Archivos a subir: {len(planned)} (contenido de dist/ -> {remote_base})")
    if with_env:
        planned.append((os.path.join(ROOT, ".env"), posixpath.join(remote_parent, ".env")))
        print(f"  + .env -> {remote_parent}/.env (⚠️ contiene secretos; solo por SFTP, fuera del webroot público)")

    if dry_run:
        for lf, rf in planned[:15]:
            print(f"    would upload  {os.path.relpath(lf, ROOT)}  ->  {rf}")
        if len(planned) > 15:
            print(f"    ... y {len(planned)-15} más")
        print("\nDry-run: nada se subió. Repite con --confirm para desplegar de verdad.")
        return

    ssh = connect()
    sftp = ssh.open_sftp()
    seen_dirs = set()
    try:
        for lf, rf in planned:
            rdir = posixpath.dirname(rf)
            if rdir not in seen_dirs:
                ensure_remote_dir(sftp, rdir)
                seen_dirs.add(rdir)
            sftp.put(lf, rf)
        print(f"  ✅ Subidos {len(planned)} archivos a {remote_base}.")

        # Subir scripts de mantenimiento e init CRM al PADRE del webroot
        remote_scripts_dir = posixpath.join(remote_parent, "scripts")
        ensure_remote_dir(sftp, remote_scripts_dir)
        for sc in ["init_crm_db.php", "migrate_leads.php"]:
            local_sc = os.path.join(ROOT, "scripts", sc)
            if os.path.exists(local_sc):
                sftp.put(local_sc, posixpath.join(remote_scripts_dir, sc))
                print(f"  + Subido script CLI {sc} -> {remote_scripts_dir}/{sc}")

        # Crear .htaccess de denegación en el PADRE por defensa en profundidad
        htaccess_content = b"Require all denied\nDeny from all\n"
        with sftp.file(posixpath.join(remote_parent, ".htaccess"), "wb") as f:
            f.write(htaccess_content)
        print(f"  + Creado .htaccess Deny from all en {remote_parent}/.htaccess")

        # Permisos del .env remoto (600) si se subió
        if with_env:
            env_remote_file = posixpath.join(remote_parent, ".env")
            sftp.chmod(env_remote_file, 0o600)
            print(f"  + Permisos chmod 600 aplicados a {env_remote_file}")

        if init_crm:
            print(f"  Inicializando CRM en remoto con /usr/bin/php8.2-cli...")
            for script in ["scripts/init_crm_db.php", "scripts/migrate_leads.php"]:
                cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli {script}"
                _, out, err = ssh.exec_command(cmd)
                print("   ", out.read().decode(errors="replace").strip())
                e = err.read().decode(errors="replace").strip()
                if e:
                    print("    stderr:", e)
        print("\n✅ Despliegue completado. Verifica el sitio en https://app.datanestiq.com y el panel /admin/ (IP+auth).")
    finally:
        sftp.close()
        ssh.close()


def main():
    ap = argparse.ArgumentParser(description="Despliegue Datanestiq → IONOS (paramiko)")
    ap.add_argument("--confirm", action="store_true", help="ejecuta el despliegue real (si no, dry-run)")
    ap.add_argument("--with-env", action="store_true", help="también sube el .env (secretos) por SFTP")
    ap.add_argument("--init-crm", action="store_true", help="corre init/migración del CRM en remoto")
    args = ap.parse_args()
    if args.with_env and args.confirm:
        resp = input("⚠️  Vas a subir el .env con SECRETOS a producción. Escribe 'subir env' para confirmar: ")
        if resp.strip().lower() != "subir env":
            fail("Cancelado por el usuario.")
    deploy(dry_run=not args.confirm, with_env=args.with_env, init_crm=args.init_crm)


if __name__ == "__main__":
    main()
