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
import stat
import posixpath

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

# Qué se sube (rutas locales relativas a ROOT). NUNCA secretos/PII.
UPLOAD_DIRS = ["dist", "public/api", "public/admin"]
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
    print(f"== Despliegue Datanestiq → {HOST}:{REMOTE}  ({'DRY-RUN' if dry_run else 'REAL'}) ==")
    planned = []
    for d in UPLOAD_DIRS:
        ld = os.path.join(ROOT, d)
        if not os.path.isdir(ld):
            print(f"  (omito {d}/ — no existe; ¿corriste npm run build?)")
            continue
        for lf in iter_files(ld):
            rel = os.path.relpath(lf, ROOT).replace("\\", "/")
            planned.append((lf, posixpath.join(REMOTE, rel)))
    print(f"  Archivos a subir: {len(planned)} (dist/ + backend PHP)")
    if with_env:
        planned.append((os.path.join(ROOT, ".env"), posixpath.join(REMOTE, ".env")))
        print("  + .env (⚠️ contiene secretos; solo por SFTP, nunca al repo)")

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
        print(f"  ✅ Subidos {len(planned)} archivos.")
        # Permisos del .env remoto (600) si se subió
        if with_env:
            sftp.chmod(posixpath.join(REMOTE, ".env"), 0o600)
        if init_crm:
            print("  Inicializando CRM en remoto (init_crm_db.php + migrate_leads.php)...")
            for script in ["scripts/init_crm_db.php", "scripts/migrate_leads.php"]:
                cmd = f"cd {REMOTE} && php {script}"
                _, out, err = ssh.exec_command(cmd)
                print("   ", out.read().decode(errors="replace").strip())
                e = err.read().decode(errors="replace").strip()
                if e:
                    print("    stderr:", e)
        print("\n✅ Despliegue completado. Verifica el sitio y el panel /admin/ (IP+auth).")
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
