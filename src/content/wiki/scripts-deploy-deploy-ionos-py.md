---
title: "Despliegue a IONOS con deploy_ionos.py"
description: "Documentación técnica completa del script de despliegue automatizado a servidores IONOS mediante SSH/SFTP, incluyendo seguridad, flujo de trabajo, variables "
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["deploy","ionos","ssh","sftp","paramiko","security","crm","astro","php"]
seoScore: 100
---
## Despliegue a IONOS con `deploy_ionos.py`

Este script (`scripts/deploy/deploy_ionos.py`) automatiza el despliegue de la aplicación Datanestiq en entornos de producción hospedados en **IONOS**, usando conexión segura SSH/SFTP vía [`paramiko`](https://www.paramiko.org/). Está diseñado bajo los principios de **seguridad por defecto**, **transparencia operativa** y **control explícito del humano**.

---

### ✅ Características clave

| Funcionalidad | Detalle |
|---------------|---------|
| **Dry-run por defecto** | Muestra exactamente qué se subiría — sin efecto real — hasta que se use `--confirm`. |
| **Autenticación segura** | Prioriza claves SSH (`IONOS_SSH_KEY_PATH`). El uso de contraseña está marcado como comprometido y requiere migración urgente. |
| **Exclusión automática** | Nunca sube `.env`, `secure_leads/`, `node_modules`, `.git`, `.venv` ni `__pycache__`. |
| **Protección de secretos** | El `.env` remoto se sube *solo* con `--with-env`, fuera del webroot público (en su directorio padre), con permisos `600` y bloqueo via `.htaccess`. |
| **Configuración Apache robusta** | Genera dos archivos `.htaccess`: uno defensivo en el padre (bloquea `.env`, `.sqlite*`) y otro permissivo en el docroot (`Require all granted`). |
| **Soporte CRM** | Ejecuta scripts PHP remotos (`init_crm_db.php`, `migrate_leads.php`) con `php8.2-cli` si se activa `--init-crm`. |

---

### 🛑 Requisitos previos

1. **Construcción local**: Ejecutar `npm run build` para generar `dist/`.
2. **Archivo `.env` válido** en la raíz del proyecto (gitignored), conteniendo:
   ```env
   IONOS_SSH_HOST=your-server.ionos.com
   IONOS_SSH_USER=your-username
   IONOS_SSH_KEY_PATH=./.ssh/id_rsa_ionos  # ✅ Recomendado
   # IONOS_SSH_PASSWORD=...                 # ⚠️ Solo fallback; rotar o eliminar
   IONOS_REMOTE_PATH=/kunden/homepages/XX/XXXXXX/htdocs/app
   ```
3. **Clave SSH configurada** (si se usa `IONOS_SSH_KEY_PATH`): debe tener permisos `600` y estar autorizada en IONOS.
4. **(Opcional) Archivos de soporte CRM**: `scripts/init_crm_db.php` y `scripts/migrate_leads.php` deben existir para usar `--init-crm`.

---

### 🚀 Uso básico

```bash
# 1. Simulación: ver qué se subiría (sin cambios reales)
python scripts/deploy/deploy_ionos.py

# 2. Despliegue real (dist/ + backend PHP)
python scripts/deploy/deploy_ionos.py --confirm

# 3. Con .env remoto (requiere confirmación interactiva)
python scripts/deploy/deploy_ionos.py --confirm --with-env

# 4. Inicialización completa del CRM (DB + migración)
python scripts/deploy/deploy_ionos.py --confirm --init-crm

# 5. Todo junto: despliegue + env + CRM
python scripts/deploy/deploy_ionos.py --confirm --with-env --init-crm
```

> 💡 **Nota sobre `--with-env`**: Se solicita confirmación textual (`subir env`) para evitar errores humanos con secretos.

---

### 📁 Estructura de despliegue remota

Suponiendo `IONOS_REMOTE_PATH=/kunden/homepages/XX/XXXXXX/htdocs/app`:

```
/kunden/homepages/XX/XXXXXX/htdocs/          ← remote_parent (padre del webroot)
├── .env                                    ← subido solo con --with-env (600)
├── .htaccess                                 ← bloquea .env y .sqlite*
├── scripts/
│   ├── init_crm_db.php
│   └── migrate_leads.php
└── app/                                      ← webroot servido (IONOS_REMOTE_PATH)
    ├── .htaccess                             ← permite acceso público (Require all granted)
    ├── index.html                            ← salida de Astro (dist/)
    ├── api/
    ├── admin/
    └── ...                                   ← todo el contenido de dist/
```

---

### 🔐 Seguridad (Constitución §6)

- **Nunca credenciales hardcodeadas**: todas las variables vienen exclusivamente de `.env`.
- **Clave SSH > Password**: el password está marcado como comprometido en el historial de git. [Ver planes/DESPLIEGUE-IONOS.md](./plans/DESPLIEGUE-IONOS.md) para migración.
- **Host key verification**: usa `.ssh_known_hosts` si existe; si no, advierte y acepta en primer uso (con política `AutoAddPolicy`).
- **Permisos estrictos**: `.env` remoto recibe `chmod 600` y está protegido por `FilesMatch` en `.htaccess`.
- **Rutas relativas seguras**: usa `posixpath` para compatibilidad cross-platform y evita inyecciones de ruta.

---

### 🧩 Flujo interno del script

1. **Carga de variables**: lee `.env` sin volcar valores en logs.
2. **Validación temprana**: verifica presencia de `IONOS_SSH_HOST`, `USER`, `REMOTE`.
3. **Conexión SSH**: con política de host keys adaptativa y autenticación por clave o password.
4. **Planificación de transferencia**: itera `dist/`, excluye patrones sensibles, calcula rutas remotas.
5. **Creación de directorios**: asegura estructura remota con `sftp.mkdir()` recursivo.
6. **Subida masiva**: `sftp.put()` para cada archivo planificado.
7. **Configuración Apache**: genera dos `.htaccess` con reglas compatibles con Apache 2.2/2.4.
8. **Ejecución remota (opcional)**: `ssh.exec_command()` para scripts PHP del CRM.
9. **Limpieza**: cierra SFTP y SSH en bloque `finally`.

---

### 🚨 Errores comunes y solución

| Error | Causa probable | Solución |
|-------|----------------|----------|
| `Falta IONOS_SSH_HOST / USER / REMOTE` | Variables ausentes o mal escritas en `.env` | Verificar mayúsculas, espacios y sintaxis `.env`. |
| `No existe dist/ — corre 'npm run build' primero` | Falta construcción previa | Ejecutar `npm run build` desde la raíz del proyecto. |
| `Conectando con PASSWORD (⚠️ comprometido...)` | Clave SSH no configurada o inválida | Generar clave nueva y añadirla al servidor IONOS; actualizar `.env`. |
| `Permission denied (publickey)` | Clave SSH no autorizada o permisos incorrectos | Verificar `chmod 600` en la clave local y autorización en IONOS. |
| `IOError: Failure` al crear directorios | Permisos insuficientes en `remote_parent` | Asegurar que el usuario SSH tenga permisos de escritura en el padre del webroot. |

---

### 📚 Referencias

- [Planes de migración a clave SSH](./plans/DESPLIEGUE-IONOS.md)
- [Guía de seguridad de credenciales](./docs/security/credentials.md)
- [Estructura de carpetas de Astro](./docs/astro/project-structure.md)
- [Configuración de Apache en IONOS](https://www.ionos.es/ayuda/servidores-virtuales/configuracion-de-apache/)

%%IGNORE_BLOCK_1%%