# SPEC · Retomar despliegue IONOS post-rotación SSH (Specs 014 + 015)

> Versión revisada por Claude (Opus). Mantiene la estructura por fases con gates humanos del borrador
> del usuario, pero **corrige 6 desajustes con la máquina real** (ver "⚠️ CORRECCIONES" abajo). Dos son
> riesgos críticos (R1 `.env` fuera del webroot, R2 `scripts/*.php` no se despliegan) que darían
> "verde falso" o romperían el chatbot/CRM si se ignoran.

## ⚠️ CORRECCIONES vs. el borrador (respétalas — son la realidad del código)
- **R1 (crítico) — `.env` y `secure_leads/` van al PADRE del webroot.** El backend los lee en
  `__DIR__/../../` (verificado: `public/api/save_wizard.php:50`, `public/api/book_appointment.php:34`,
  `public/admin/auth.php:27` → `../../.env` y `../../secure_leads/crm.sqlite`). Como `dist/` se sube a
  `IONOS_REMOTE_PATH` (webroot) y los PHP quedan en `{webroot}/api|admin/`, entonces `../../` apunta a
  **`{padre del webroot}`** (el home de la cuenta IONOS, típicamente un nivel arriba de `htdocs`, que
  **sí** es escribible). ⇒ El `.env` de prod y `secure_leads/` deben quedar en **el padre del webroot**,
  NO dentro. `deploy_ionos.py --with-env` hoy sube el `.env` a `IONOS_REMOTE_PATH/.env` (dentro) — hay
  que **reconciliarlo** (ver FASE 1/2). Verifica que el chatbot cargue sus claves y que `secure_leads/`
  no sea servible.
- **R2 (crítico) — `scripts/*.php` NO están en `dist/`.** Astro solo copia `public/` a `dist/`. Por eso
  `--init-crm` (que corre `php scripts/init_crm_db.php` desde el webroot) **fallaría**: esos scripts no
  se despliegan. Súbelos aparte a una ruta ejecutable y asegúrate de que su path a `secure_leads/`
  resuelva al padre del webroot (o corre la creación del schema por otra vía verificada). Reconcilia en
  el plan de FASE 1.
- **Variables reales:** `IONOS_SSH_HOST`, `IONOS_SSH_USER`, `IONOS_SSH_KEY_PATH` **o** `IONOS_SSH_PASSWORD`,
  `IONOS_REMOTE_PATH`. (El borrador usaba `SSH_*`/`SSH_PORT`; el script no los lee — conexión en el puerto 22.)
- **Flags reales de `deploy_ionos.py`:** `--confirm`, `--with-env`, `--init-crm`. **No existe `--spec`.**
  El deploy es **UN solo upload del contenido de `dist/`** (sitio + `api/` + `admin/` juntos: 014 y 015
  viajan en el mismo build). Los **smoke tests** sí se hacen por spec.
- **URLs desplegadas:** `/api/save_wizard.php`, `/api/book_appointment.php`, `/api/availability.php`,
  `/admin/` (**no** `/public/api/...` — `public/` desaparece en el build).
- **Archivos de deuda:** `specs/014-*/tech_debt.md` y `specs/015-*/tech_debt.md` **no existen** → créalos.
  Usa la **fecha real** del deploy (no hardcodear). `npm run docs:sync` sí existe (`docs-generator.mjs --target=wiki`).

## CONTEXTO (leer antes de empezar)
1. Constitución **`.specify/memory/constitution.md`** (manda) · reglas del rol **`AGENTS.md`**.
2. Guía operativa **`planes/DESPLIEGUE-IONOS.md`** · estado **`planes/ESTADO-SPECS.md`** (014/015 en 🟠).
3. Helper **`scripts/deploy/deploy_ionos.py`** (paramiko; dry-run por defecto; `--confirm` explícito).

## ESTADO PREVIO (verificado por el usuario)
- Contraseña SSH de IONOS **rotada** en el panel; **`.env` local actualizado** con la nueva credencial
  (`IONOS_SSH_KEY_PATH` preferido, o `IONOS_SSH_PASSWORD`). La vieja sigue en el historial git → FASE 7 (opcional).

## WHY
Desbloquear Specs 014 (CRM SQLite) y 015 (Agendador), con código completo, para dejarlas **live en IONOS**
con evidencia verificable (leads con `journey` + citas desde el chatbot).

## CONSTRAINTS (guardarraíles duros — Constitución manda)
- **DRY-RUN por defecto.** Deploy real solo tras `--confirm` **y** "sí" escrito del usuario en chat. Nunca unilateral.
- **§6 Seguridad/PII:** nunca commitees `.env`, `secure_leads/`, `*.jsonl`, `wp-config.php`, claves. Reutiliza
  `save_wizard.php`/`chat.php`/`book_appointment.php` — sin canales nuevos de PII. **Nunca toques `remote_extract.py`**.
- **§2 Honestidad:** solo evidencia real (`grep`, `php -l`, `curl -I`, `SELECT`). Prohibido "OK" sin comando
  ejecutado. Lo no probado → "PENDIENTE: no verificado porque X". Sin false-green.
- **§5 No romper:** `npm run build` verde antes del doc-sync; islas intactas; consola limpia.
- **PII en logs:** `grep -E '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}' logs/*` debe dar 0; si aparece un email, es
  falla del pipeline de redacción → para y reporta.
- **Sintaxis PHP:** `C:/xampp/php/php.exe -l <archivo>.php` tras cualquier edit de `chat.php`/`save_wizard.php`/
  `book_appointment.php`/`availability.php`/`auth.php` (una comilla mal escapada ya rompió el chatbot antes).
- **No** skipear el pre-commit hook (`--no-verify`). Credenciales **solo** de `.env`, nunca hardcoded; preferir llave SSH.
- **Rama:** `007-multi-pagina`.

## OUT-OF-SCOPE
- NO reescribir historial git (la vieja pass ya está invalidada; FASE 7 solo si el usuario lo pide).
- NO tocar core WordPress (`wp-admin/`,`wp-includes/`,`wp-content/`) ni `remote_extract.py`.
- NO agregar dependencias sin ADR. NO hardcodear contenido en `.astro` (taxonomía = SSOT).
- NO enviar emails (Spec 015 = confirmación manual). NO desplegar Spec 008. NO tocar el baseline de lint.
- NO abrir PR hacia `main` sin decisión del usuario.

## TASKS

### FASE 0 · Pre-vuelo (sin tocar nada remoto)
- [ ] **0.1** Confirma que `.env` local tiene `IONOS_SSH_HOST`, `IONOS_SSH_USER`, `IONOS_REMOTE_PATH` y
  (`IONOS_SSH_KEY_PATH` **o** `IONOS_SSH_PASSWORD`). No imprimas los valores.
- [ ] **0.2** Prueba de conexión (read-only): `python scripts/deploy/deploy_ionos.py` (dry-run muestra el plan
  y valida el `.env`). Como el script no tiene `--check-connection`, para verificar SSH corre un one-shot
  paramiko que abra la conexión, ejecute `uname -a` + `df -h` + `pwd` (para ver el layout: dónde está el home
  vs. `IONOS_REMOTE_PATH`), y cierre. **No modifiques el helper.**
- [ ] **0.3** Reporta: ¿llave o password? latencia, espacio libre, y **el path del home vs. `IONOS_REMOTE_PATH`**
  (crítico para R1: confirmar que el padre del webroot es escribible). **Punto de revisión.**

### FASE 1 · Plan de deploy (entrega y ESPERA) — resuelve R1 y R2 aquí
- [ ] **1.1** Entrega, antes de tocar nada remoto, un plan con:
  - Archivos a subir (locales → remotos): contenido de `dist/` → `IONOS_REMOTE_PATH/`.
  - **R1:** dónde colocarás el `.env` de prod y `secure_leads/` = **padre del webroot** (path exacto), y cómo
    verificarás que `chat.php`/`auth.php` los encuentran (`../../`).
  - **R2:** cómo subirás y correrás `init_crm_db.php`/`migrate_leads.php` (ruta ejecutable + su path a `secure_leads/`).
  - Comandos remotos en orden (con dry-run donde aplique) + rollback por paso destructivo + estimación de tiempo.
- [ ] **1.2** **STOP** hasta "aprobado, sigue" explícito del usuario.

### FASE 2 · `.env` de producción (con confirmación)
- [ ] **2.1** Verifica en el servidor si ya existe el `.env` en el padre del webroot y si tiene `ADMIN_PASSWORD_HASH`. Reporta el estado exacto.
- [ ] **2.2** Genera **localmente** el bcrypt con la clave que dé el usuario: `php -r 'echo password_hash("<PASS>", PASSWORD_BCRYPT);'`. **No** la inventes ni commitees el hash.
- [ ] **2.3** Pide al usuario `ALLOWED_IPS` real (whitelist; nunca `*` ni `127.0.0.1` en prod).
- [ ] **2.4** Sube el `.env` por SFTP al **padre del webroot** (R1), permisos `600`, owner del user SSH. Incluye
  `GROQ_API_KEY`/`DASHSCOPE_API_KEY`/`GEMINI_API_KEY` + `ADMIN_PASSWORD_HASH` + `ALLOWED_IPS`.
- [ ] **2.5** Verifica: `stat -c "%a %U %G" <padre>/.env` → `600 <user> <group>`.

### FASE 3 · Deploy del build (014 + 015 juntas) + init CRM
- [ ] **3.1** `npm run build` (verde). `php -l` sobre `public/admin/{auth,login,api,index}.php`,
  `public/api/{save_wizard,book_appointment,availability}.php`, `scripts/{init_crm_db,migrate_leads}.php`.
- [ ] **3.2** `python scripts/deploy/deploy_ionos.py` (dry-run) → reporta la lista exacta.
- [ ] **3.3** **STOP.** Confirmación explícita del usuario para el deploy real.
- [ ] **3.4** Con confirmación: `python scripts/deploy/deploy_ionos.py --confirm` (sube `dist/` → webroot).
  Luego resuelve R1/R2 según el plan: coloca `.env` (si no fue en FASE 2) y sube `init_crm_db.php`/`migrate_leads.php`.
- [ ] **3.5** Crea el schema: corre `init_crm_db.php` en remoto (por el mecanismo del plan). Verifica `crm.sqlite`
  en `secure_leads/` (padre del webroot) con `PRAGMA journal_mode` (WAL) y `PRAGMA foreign_keys` (ON). Muestra
  `SELECT sql FROM sqlite_master;`.
- [ ] **3.6** Si hay CSV/JSONL a migrar: `migrate_leads.php` **dry-run** primero (reporta conteos), luego real con luz verde.
- [ ] **3.7** `curl -I https://<dominio>/secure_leads/crm.sqlite` → **403** (con evidencia). Si `secure_leads/` está
  fuera del webroot, la URL no debería resolver a un archivo (404/403); documenta cuál y por qué.
- [ ] **3.8** `/admin/` sin sesión → **302 → /admin/login.php** (`curl -I -L`); desde IP no-whitelisted → **403**
  (si no puedes simular otra IP, documenta como PENDIENTE, no reportes false-green).

### FASE 4 · Smoke test Spec 014 (CRM)
- [ ] **4.1** `POST /api/save_wizard.php` con payload de prueba (email fake + `journey`). 200 + `SELECT * FROM leads
  ORDER BY id DESC LIMIT 1` remoto → lead con `journey` no-null.
- [ ] **4.2** Login a `/admin/` con la clave del hash → 302 correcto; `Set-Cookie` con `Secure; HttpOnly; SameSite=Strict` (reporta headers).
- [ ] **4.3** `grep -cE "[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}" logs/*.log` → **0**. Si >0, falla de redacción PII → para y reporta.

### FASE 5 · Smoke test Spec 015 (Agendador) + anti-doble-booking
- [ ] **5.1** Verifica `appointments` en el mismo `crm.sqlite` (FK a `leads`).
- [ ] **5.2** `GET /api/availability.php?date=<mañana>` → JSON con slots, zona **America/Lima** (verifica offset/slots).
- [ ] **5.3** `POST /api/book_appointment.php` slot X → 200 + estado `solicitada`; `SELECT` muestra la fila con `lead_id` (crea-o-linkea).
- [ ] **5.4** `POST /api/book_appointment.php` mismo slot X → **409** (anti-doble-booking).
- [ ] **5.5** Simultaneidad (fixture o `xargs -P 2`): 2 requests al mismo slot → exactamente 1 éxito + 1 conflicto.
  Si no se puede simular, documenta el diseño transaccional (BEGIN IMMEDIATE / UNIQUE index) como evidencia.
- [ ] **5.6** Slot que cruza medianoche UTC pero mismo día `America/Lima` → el día del picker es el correcto.

### FASE 6 · Doc-sync (§11)
- [ ] **6.1** `planes/ESTADO-SPECS.md`: filas 014 y 015 de 🟠 → ✅ con **fecha real** y referencias a las evidencias.
- [ ] **6.2** `planes/Fases.md` actualizado.
- [ ] **6.3** **Crea** `specs/014-crm-lead-lifecycle/tech_debt.md` y `specs/015-agendador-citas/tech_debt.md` con las deudas menores que queden.
- [ ] **6.4** `npm run docs:sync` (wiki, Spec 010) → build OK.
- [ ] **6.5** Commit en `007-multi-pagina`: `deploy(014,015): CRM SQLite + Agendador live tras rotación SSH IONOS`. Respeta el pre-commit hook. **No** abras PR a `main` sin decisión del usuario.

### FASE 7 · (OPCIONAL, gateada) Higiene git-history de la vieja contraseña
- [ ] **7.1** **NO EJECUTAR sin pedido explícito.** Si el usuario lo pide: referencia la decisión hermana en
  `vault/ionos/architecture/decisions.md` (2026-05-28, `git-filter-repo`); plan con backup branch + `--replace-text`;
  entrega el plan, **no ejecutes** (afecta el PR #1 → main).

## GATES HUMANOS (no proceder sin "sí" escrito en chat)
1. Tras FASE 1 (plan, incl. R1/R2 resueltos). 2. Tras FASE 3.3 (deploy real). 3. Tras FASE 3.6 (migración de leads).
4. FASE 5 usa el mismo deploy (no re-deploy). 5. FASE 7 gateada.

## Decisiones que requieren al usuario (no unilaterales)
- Clave admin (para el bcrypt) e `ALLOWED_IPS`. · Migrar leads existentes vs. arrancar `crm.sqlite` vacío.
- Ejecutar FASE 7. · Abrir PR a `main` (probablemente NO en este cambio).

## EVIDENCIA REQUERIDA (reporte final)
```
## Resultado por fase (0–6, y 7 si aplica) — comandos + outputs reales
## Antes/después: filas en leads y appointments · archivos deployados · tamaño de crm.sqlite
## Verificaciones críticas (con output real o PENDIENTE):
- [ ] R1 chat.php carga el .env (chatbot responde / claves presentes)
- [ ] 403/404 sobre secure_leads/crm.sqlite
- [ ] 302 sobre /admin/ sin sesión
- [ ] 403 sobre /admin/ desde IP no-whitelisted (o PENDIENTE)
- [ ] PII redactada en logs (grep=0)
- [ ] Anti-doble-booking (409 en 2do request)
- [ ] Timezone America/Lima
## Hallazgos adicionales (bugs, deudas nuevas, riesgos)
```

---
**Nota:** Claude (Opus 4.8) auditará el reporte contra evidencia real: R1 (chatbot con `.env` correcto) y R2
(scripts corridos) resueltos, `/admin/` gated (302/403), `secure_leads/` protegido, anti-doble-booking, zona
America/Lima, y **cero secretos** en repo/logs/vault. Deploy = dry-run → `--confirm` humano, nunca autónomo.
