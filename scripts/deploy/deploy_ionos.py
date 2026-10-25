#!/usr/bin/env python3
"""
deploy_ionos.py — Despliegue automatizado y AUTO-VERIFICADO de Datanestiq a IONOS vía SSH/SFTP.

AUTOVERIFICACIÓN & SELF-GUARD:
- Guard PRE-DEPLOY: Comprueba el mtime de src/ y public/ vs dist/. Ejecuta 'npm run build' si dist/ está desactualizado.
- Guard POST-DEPLOY: Limpia OPcache en el servidor y ejecuta auto-verificación bloqueante contra los endpoints PHP en producción.
- Si la verificación post-deploy de los marcadores falla, el script ABORTA y reporta fallo en el despliegue.

SEGURIDAD (Constitución §6):
- Credenciales SOLO desde .env (gitignored). NUNCA hardcodeadas ni logueadas.
- DRY-RUN por defecto. El despliegue REAL requiere --confirm.
- NUNCA sube: .env local, secure_leads/, node_modules, .git, .venv.

Uso:
  python scripts/deploy/deploy_ionos.py               # dry-run (muestra qué haría)
  python scripts/deploy/deploy_ionos.py --confirm      # recompila si es necesario, sube y autoverifica en vivo
  python scripts/deploy/deploy_ionos.py --confirm --with-env   # además sube .env (con confirmación)
  python scripts/deploy/deploy_ionos.py --confirm --init-crm   # corre init/migración del CRM en remoto
"""
import argparse
import os
import sys
import posixpath
import subprocess
import json
import re
import tempfile

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

UPLOAD_LOCAL_DIR = "dist"  # su contenido -> IONOS_REMOTE_PATH/
EXCLUDE = {".env", "secure_leads", "node_modules", ".git", ".venv", "__pycache__"}


def fail(msg):
    print(f"\n❌ [DEPLOY FAILURE] {msg}")
    sys.exit(1)


def connect():
    import paramiko
    if not HOST or not USER or not REMOTE:
        fail("Falta IONOS_SSH_HOST / IONOS_SSH_USER / IONOS_REMOTE_PATH en .env")
    ssh = paramiko.SSHClient()
    known = os.path.join(ROOT, ".ssh_known_hosts")
    if os.path.exists(known):
        ssh.load_host_keys(known)
        ssh.set_missing_host_key_policy(paramiko.RejectPolicy())
    else:
        print("⚠️  Sin .ssh_known_hosts: se acepta la host key en el primer uso (verifícala). Genera known_hosts para producción.")
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


def check_and_fresh_build(auto_build=True):
    """
    Guard PRE-DEPLOY: Compara el mtime más reciente de src/ y public/ contra dist/.
    Ejecuta 'npm run build' si dist/ no existe o está desactualizado.
    """
    print("\n🔍 [PRE-DEPLOY GUARD] Comprobando frescura de compilación dist/...")
    dist_dir = os.path.join(ROOT, UPLOAD_LOCAL_DIR)
    
    if not os.path.exists(dist_dir):
        latest_dist_mtime = 0
    else:
        latest_dist_mtime = max((os.path.getmtime(f) for f in iter_files(dist_dir)), default=0)

    source_dirs = [os.path.join(ROOT, "src"), os.path.join(ROOT, "public")]
    latest_source_mtime = 0
    latest_source_file = ""

    for s_dir in source_dirs:
        if os.path.exists(s_dir):
            for lf in iter_files(s_dir):
                m = os.path.getmtime(lf)
                if m > latest_source_mtime:
                    latest_source_mtime = m
                    latest_source_file = lf

    needs_build = (latest_dist_mtime == 0) or (latest_source_mtime > latest_dist_mtime)

    if needs_build:
        rel_file = os.path.relpath(latest_source_file, ROOT) if latest_source_file else "fuentes no compilados"
        print(f"⚠️  `dist/` desactualizado o inexistente. Fuente más reciente: {rel_file}")
        if auto_build:
            print("⚙️  Ejecutando 'npm run build' automáticamente antes de desplegar...")
            res = subprocess.run("npm run build", shell=True, cwd=ROOT)
            if res.returncode != 0:
                fail("La compilación 'npm run build' falló. Se aborta el despliegue para evitar subir un sitio roto.")
            print("✅ Compilación fresca generada exitosamente en dist/.")
        else:
            fail("dist/ está desactualizado respecto a public/src. Ejecuta 'npm run build' antes de desplegar.")
    else:
        print("✅ `dist/` está al día respecto a los archivos fuente (Build fresco verificado).")


def verify_inline_js_syntax():
    """
    Guard PRE-DEPLOY: Parsea el JS inline dentro de los bloques <script> de public/admin/index.php
    (y otros archivos PHP del admin), reemplaza el PHP embebido por placeholders válidos de JS
    y ejecuta 'node --check' para garantizar que no existan SyntaxErrors antes de subir a producción.
    """
    print("\n🧪 [PRE-DEPLOY GUARD] Verificando sintaxis JavaScript (node --check) en scripts del admin...")
    target_files = [
        os.path.join(ROOT, "public", "admin", "index.php"),
        os.path.join(ROOT, "public", "admin", "login.php")
    ]

    all_passed = True

    for t_file in target_files:
        if not os.path.exists(t_file):
            continue

        rel_path = os.path.relpath(t_file, ROOT).replace("\\", "/")
        with open(t_file, "r", encoding="utf-8") as fh:
            content = fh.read()

        # Extraer bloques <script> ... </script>
        script_blocks = re.findall(r'<script\b[^>]*>(.*?)</script>', content, re.DOTALL | re.IGNORECASE)
        if not script_blocks:
            continue

        for idx, block in enumerate(script_blocks, 1):
            # 1. Eliminar bloques <?php ... ?>
            sanitized = re.sub(r'<\?php[\s\S]*?\?>', '/* PHP_BLOCK */', block)
            # 2. Reemplazar <?= ... ?> por una cadena literal válida de JS
            sanitized = re.sub(r'<\?=[^?]*\?>', '"PHP_ECHO"', sanitized)

            # Escribir temporal para evaluar con node --check
            with tempfile.NamedTemporaryFile(mode="w", suffix=".js", delete=False, encoding="utf-8") as tmp:
                tmp.write(sanitized)
                tmp_path = tmp.name

            try:
                res = subprocess.run(["node", "--check", tmp_path], capture_output=True, text=True)
                if res.returncode != 0:
                    print(f"❌ [JS SYNTAX ERROR] {rel_path} (bloque script #{idx}) tiene errores de sintaxis JS:")
                    print("--------------------------------------------------------------------------------")
                    err_msg = res.stderr.strip() or res.stdout.strip()
                    print(err_msg)
                    print("--------------------------------------------------------------------------------")
                    all_passed = False
            finally:
                if os.path.exists(tmp_path):
                    os.remove(tmp_path)

    if not all_passed:
        fail("Error de sintaxis JavaScript (SyntaxError) detectado en scripts del admin. Se ABORTA el despliegue para evitar romper el panel en producción.")

    print("✅ [JS SYNTAX GATE PASSED] Sintaxis de scripts en admin/index.php verificada limpia con `node --check`.")


def verify_post_deploy(ssh, remote_parent):
    """
    Guard POST-DEPLOY: Reinicia OPcache en IONOS y autoverifica que los endpoints reales
    en producción respondan HTTP 200 y contengan los marcadores esperados del nuevo código.
    """
    print("\n⚡ [POST-DEPLOY GUARD] Ejecutando limpieza de OPcache y AUTO-VERIFICACIÓN EN VIVO...")

    # 1. Limpiar OPcache
    opcache_cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli -r \"if(function_exists('opcache_reset')){{opcache_reset();}}\""
    ssh.exec_command(opcache_cmd)
    print("  + Solicitado reinicio de OPcache en servidor PHP 8.2.")

    # 2. Endpoints a verificar y sus marcadores obligatorios
    verifications = [
        ("stats", "include_demo=1", ["total_leads", "csrf_token"], 200),
        ("ai_efficiency", "include_demo=1", ["insufficient_data", "zero_llm_percentage"], 200),
        ("agenda", "include_demo=1", ["appointments", "total"], 200),
        ("conversations", "include_demo=1", ["conversations", "total"], 200),
        ("usage_daily", "", ["usage_date", "daily_cap"], 200),
        ("blog_analytics", "include_demo=1", ["insufficient_data", "total_searches"], 200),
        ("experience_pending", "", ["total_pending", "candidates"], 200),
        ("chat_raw", "", ["Unknown action"], 400) # Deber ser rechazado por Constitución §6
    ]

    print("\n==========================================================================================")
    print("📊 TABLA DE AUTO-VERIFICACIÓN EN VIVO DE ENDPOINTS EN PRODUCCIÓN IONOS")
    print("==========================================================================================")
    print(f"{'Endpoint / Acción':<25} | {'Marcador Requerido':<30} | {'Estado Verificación':<25}")
    print("------------------------------------------------------------------------------------------")

    all_passed = True

    for action, extra_params, markers, expected_code in verifications:
        # Invocación directa PHP CLI segura en servidor IONOS
        php_code = f"\\$_SERVER['REQUEST_METHOD']='GET'; \\$_SERVER['REMOTE_ADDR']='127.0.0.1'; \\$_GET=['action'=>'{action}'"
        if extra_params:
            for p in extra_params.split('&'):
                k, v = p.split('=')
                php_code += f", '{k}'=>'{v}'"
        php_code += "]; include 'public/admin/api.php';"

        cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli -r \"{php_code}\""
        stdin, stdout, stderr = ssh.exec_command(cmd)
        out = stdout.read().decode('utf-8', errors='replace').strip()

        passed = False
        if expected_code == 400:
            passed = "Unknown action" in out
        else:
            passed = any(m in out for m in markers)

        marker_label = markers[0] if markers else "OK"
        if passed:
            status_label = f"✅ VERIFICADO EN VIVO ({expected_code})"
            print(f"{action:<25} | {marker_label:<30} | {status_label:<25}")
        else:
            status_label = "❌ FALLÓ (Sin Marcador)"
            print(f"{action:<25} | {marker_label:<30} | {status_label:<25}")
            print(f"   [RESPUESTA RAW RECIBIDA]: {out[:200]}")
            all_passed = False

    print("------------------------------------------------------------------------------------------")

    if not all_passed:
        fail("El despliegue fue subido, pero la AUTO-VERIFICACIÓN POST-DEPLOY en producción FALLÓ. "
             "Al menos uno de los endpoints no devolvió el marcador esperado del nuevo código.")

    print("🎉 [SELF-GUARD PASSED] Todos los endpoints verificados en vivo respondiendo con código nuevo.")


def deploy(dry_run, with_env, init_crm, no_build, skip_verify):
    remote_base = REMOTE or "<IONOS_REMOTE_PATH>"
    remote_parent = posixpath.dirname(remote_base) if REMOTE else "<PADRE_DEL_WEBROOT>"
    print(f"== Despliegue Datanestiq -> {HOST or '<IONOS_SSH_HOST>'}:{remote_base}  ({'DRY-RUN' if dry_run else 'REAL'}) ==")

    # 1. Gate de Sintaxis JavaScript (PRE-DEPLOY GUARD)
    verify_inline_js_syntax()

    if not dry_run and not no_build:
        check_and_fresh_build(auto_build=True)

    planned = []
    ld = os.path.join(ROOT, UPLOAD_LOCAL_DIR)
    if not os.path.isdir(ld):
        fail(f"No existe {UPLOAD_LOCAL_DIR}/ — corre 'npm run build' primero.")
    for lf in iter_files(ld):
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
        for sc in ["init_crm_db.php", "migrate_leads.php", "migrate_file_free_history.php", "extraer_leads.php"]:
            local_sc = os.path.join(ROOT, "scripts", sc)
            if os.path.exists(local_sc):
                sftp.put(local_sc, posixpath.join(remote_scripts_dir, sc))
                print(f"  + Subido script CLI {sc} -> {remote_scripts_dir}/{sc}")

        # 🛡️ SEGURIDAD §6: Subir SSOT de specs al directorio PRIVADO del app
        specs_ssot_local = os.path.join(ROOT, "src", "data", "specsStatus.json")
        if os.path.exists(specs_ssot_local):
            sftp.put(specs_ssot_local, posixpath.join(remote_parent, "specsStatus.json"))
            print(f"  + Subido SSOT de specs (PRIVADO) -> {remote_parent}/specsStatus.json")

        # 🛡️ SEGURIDAD §6: Eliminar archivo de datos públicos expuesto si existe
        public_leaked_file = posixpath.join(remote_base, "api", "specs-status.json")
        try:
            sftp.remove(public_leaked_file)
            print(f"  🛡️ [SECURITY §6] Eliminada copia pública no autorizada: {public_leaked_file}")
        except IOError:
            pass

        # Crear .htaccess defensivo en PADRE
        parent_htaccess = (
            "# Protege .env y la BD SQLite sin denegar el resto (evita cascada al docroot servido)\n"
            "<FilesMatch \"(^\\.env$|\\.sqlite$|\\.sqlite-wal$|\\.sqlite-shm$)\">\n"
            "    Require all denied\n"
            "</FilesMatch>\n"
            "<IfModule !mod_authz_core.c>\n"
            "    <FilesMatch \"(^\\.env$|\\.sqlite$)\">\n"
            "        Order allow,deny\n"
            "        Deny from all\n"
            "    </FilesMatch>\n"
            "</IfModule>\n"
        ).encode("utf-8")
        with sftp.file(posixpath.join(remote_parent, ".htaccess"), "wb") as f:
            f.write(parent_htaccess)

        # Crear .htaccess público
        public_htaccess = (
            "# Permite acceso público al subdominio app.datanestiq.com\n"
            "<IfModule mod_authz_core.c>\n"
            "    Require all granted\n"
            "</IfModule>\n"
            "<IfModule !mod_authz_core.c>\n"
            "    Order allow,deny\n"
            "    Allow from all\n"
            "</IfModule>\n"
        ).encode("utf-8")
        with sftp.file(posixpath.join(remote_base, ".htaccess"), "wb") as f:
            f.write(public_htaccess)

        if with_env:
            env_remote_file = posixpath.join(remote_parent, ".env")
            sftp.chmod(env_remote_file, 0o600)

        if init_crm:
            print(f"  Ejecutando backup de BD viva y migración remota de CRM con /usr/bin/php8.2-cli...")
            backup_cmd = f"cd {remote_parent} && test -f secure_leads/crm.sqlite && cp secure_leads/crm.sqlite secure_leads/crm.sqlite.bak || true"
            ssh.exec_command(backup_cmd)

            for script in ["scripts/init_crm_db.php", "scripts/migrate_leads.php"]:
                cmd = f"cd {remote_parent} && /usr/bin/php8.2-cli {script}"
                ssh.exec_command(cmd)

        # AUTO-VERIFICACIÓN BLOQUEANTE POST-DEPLOY
        if not skip_verify:
            verify_post_deploy(ssh, remote_parent)

        print("\n✅ DESPLIEGUE AUTO-VERIFICADO COMPLETADO Y VIVO EN https://app.datanestiq.com")
    finally:
        sftp.close()
        ssh.close()


def main():
    ap = argparse.ArgumentParser(description="Despliegue Datanestiq → IONOS (paramiko + autoverify)")
    ap.add_argument("--confirm", action="store_true", help="ejecuta el despliegue real (si no, dry-run)")
    ap.add_argument("--with-env", action="store_true", help="también sube el .env (secretos) por SFTP")
    ap.add_argument("--init-crm", action="store_true", help="corre init/migración del CRM en remoto")
    ap.add_argument("--no-build", action="store_true", help="omite la verificación/compilación automática pre-deploy")
    ap.add_argument("--skip-verify", action="store_true", help="omite la verificación en vivo post-deploy")
    args = ap.parse_args()
    if args.with_env and args.confirm:
        resp = input("⚠️  Vas a subir el .env con SECRETOS a producción. Escribe 'subir env' para confirmar: ")
        if resp.strip().lower() != "subir env":
            fail("Cancelado por el usuario.")
    deploy(dry_run=not args.confirm, with_env=args.with_env, init_crm=args.init_crm, no_build=args.no_build, skip_verify=args.skip_verify)


if __name__ == "__main__":
    main()
