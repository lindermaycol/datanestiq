# Prompt para Antigravity: Remediación de credenciales SSH en texto plano (`remote_extract.py`)

Actúa como **ingeniero de seguridad**.

## Contexto (auditado por Claude / Sonnet 5)
`remote_extract.py` (raíz del proyecto) contiene, hardcodeadas en texto plano, credenciales SSH de **producción** (host de IONOS webspace, usuario, contraseña) usadas para extraer la BD de WordPress del hosting.

Hallazgos verificados:
- El archivo **nunca se commiteó a git** (0 coincidencias en `git log --all`) y está en `.gitignore` (línea 25). **Buena noticia:** la contraseña NO está en el historial del repo. La exposición es únicamente en el disco local.
- Aun así, una contraseña de producción en texto plano en un archivo del proyecto es una deuda de seguridad **crítica** (Constitution §5: prohíbe PII/secretos en claro).

## Acción del USUARIO (imprescindible, no la puede hacer Antigravity)
> ⚠️ La contraseña debe considerarse **potencialmente comprometida** (estuvo en claro en disco). El usuario DEBE **rotarla en el panel de IONOS** (cambiar la contraseña SSH/hosting) antes o inmediatamente después de esta remediación. Ningún cambio de código sustituye la rotación. Deja esta instrucción destacada en tu reporte final.

## Acciones de código (Antigravity)

### 1. Refactorizar `remote_extract.py` para leer de `.env`
Reemplaza las credenciales hardcodeadas por lectura desde variables de entorno. Usa `python-dotenv` si está disponible, o `os.environ` con un parser mínimo del `.env` en la raíz:
```python
import os
# Cargar .env (parser mínimo si no hay python-dotenv)
def load_env(path=".env"):
    if not os.path.exists(path): return
    for line in open(path, encoding="utf-8"):
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line: continue
        k, v = line.split("=", 1)
        os.environ.setdefault(k.strip(), v.strip())

load_env()
host     = os.environ["IONOS_SSH_HOST"]
user     = os.environ["IONOS_SSH_USER"]
password = os.environ["IONOS_SSH_PASSWORD"]
```
Elimina por completo los valores literales del host, usuario y contraseña del código. Si alguna variable falta, el script debe abortar con un mensaje claro ("Falta IONOS_SSH_* en .env"), no continuar con valores vacíos.

### 2. Poblar `.env` y `.env.example`
- En `.env` (gitignoreado), agrega las claves con los **valores reales** (la contraseña **nueva** tras rotación): `IONOS_SSH_HOST`, `IONOS_SSH_USER`, `IONOS_SSH_PASSWORD`.
- En `.env.example` (versionado), confirma que existen esas 3 claves como **placeholders sin secretos** (ej. `IONOS_SSH_PASSWORD=your_password`). Si ya están, no las dupliques.

### 3. Confirmar gitignore y limpieza
- Verifica que `remote_extract.py` sigue en `.gitignore` (debe estarlo).
- Confirma por grep que **ningún** archivo versionable del proyecto (fuera de `.env`, gitignoreado) contiene ya la contraseña, el usuario ni el host de IONOS en claro. Escanea **todas** las extensiones relevantes, incluidos `*.md`, `planes/` y `prompts/` (no solo `*.py/*.php/*.js`): busca el patrón del proveedor de hosting y del usuario/contraseña reales en todo el árbol (excluyendo `node_modules`, `wp-*`, `.env`). Debe dar 0 en archivos versionables.

### 4. Documentar
Actualiza el `tech_debt.md` de la Spec 008: marca la deuda de credenciales SSH como **[RESUELTO — código migrado a `.env`; PENDIENTE rotación de contraseña por el usuario en IONOS]**. No la marques 100% resuelta hasta que el usuario confirme la rotación.

## Verificación
- `grep` no encuentra la contraseña/host en claro en ningún archivo versionable.
- `remote_extract.py` importa las 3 variables de `.env` y aborta limpio si faltan.
- `.env.example` tiene los placeholders correctos.

## Forma de respuesta
- Reporta qué cambiaste y el resultado del grep de secretos (debe ser 0).
- **Destaca en negrita la acción pendiente del usuario: rotar la contraseña en IONOS.**
- No toques `src/` ni el core WP. Sección final "Hallazgos adicionales".
