# Spec 009 — OpenWiki (LangChain): Runbook de Uso Manual (Opción A)

> **Por qué manual:** `openwiki v0.0.3` es una herramienta **interactiva** (UI Ink) sin modo headless funcional (`--init`/`--update` requieren TTY y crashean en CI; `-p` no existe). La automatización en CI se descartó (workflow eliminado). Se usa **manualmente en un terminal real** por un desarrollador.

Todos los comandos de abajo están **verificados** en este entorno (Windows, Python 3.12, PowerShell). Usa **PowerShell** (no Git Bash) porque openwiki necesita un TTY real.

---

## 0. Qué genera y cuándo usarlo
Genera documentación **para agentes de IA** (`openwiki/` + `AGENTS.md` en la raíz), que ayuda a Claude Code / Antigravity a entender el código. **No** es la wiki humana `/wiki/` (esa es la Spec 005, independiente). Córrelo cuando quieras crear o refrescar esa doc de agentes.

## 1. Seguridad — LÉELO antes de correr
OpenWiki **escanea el directorio y envía el contenido al proveedor LLM** para indexarlo. **No honra `.openwikiignore`** (indocumentado); **sí respeta `.gitignore`**. Ya está reforzado y verificado:
- `.env`, `secure_leads/`, `*.jsonl`, `wp-config.php`, `remote_extract.py`, core WP → **gitignored** (no se escanean). Verificado: el host de IONOS de `.env` dio **0 coincidencias** en un índice de prueba ⇒ los secretos NO se filtran.
- **Regla de oro:** antes de correr, cualquier archivo con secretos/PII debe estar en `.gitignore`. Compruébalo con: `git check-ignore <ruta>`.

---

## 2. Prerrequisitos (una sola vez)
```powershell
# Node/npx (para openwiki) y Python 3.12 (para el gateway opcional) ya están instalados.
# Verifica:
node --version ; npx --version ; python --version
```

---

## Camino A — Proveedor único (lo más simple, recomendado para empezar)
No requiere LiteLLM. Apunta openwiki directo a DashScope (Qwen), que tiene mejor cuota que el free tier de Gemini (que daba 429 al indexar todo el repo).

```powershell
# 1) Config del proveedor (DashScope / qwen-plus)
$env:OPENWIKI_PROVIDER   = "openai-compatible"
$env:OPENAI_COMPATIBLE_BASE_URL = "https://dashscope-intl.aliyuncs.com/compatible-mode/v1"
$env:OPENWIKI_MODEL_ID   = "qwen-plus"
$env:OPENAI_COMPATIBLE_API_KEY = "<pega aquí tu DASHSCOPE_API_KEY del .env>"

# 2) Generar la doc por primera vez (abre UI interactiva)
npx -y openwiki --init
```
Salta al paso **§5 (usar la UI)**. Para refrescar luego: `npx -y openwiki --update`.

---

## Camino B — Con gateway LiteLLM (modelo fuerte + failover)
Usa un **modelo primario consistente** (Groq `gpt-oss-120b`, fuerte en tool-use) con **failover** a DashScope `qwen-max` y Gemini `gemini-2.5-flash`. Config en [`scripts/openwiki-llm.yaml`](../../scripts/openwiki-llm.yaml).
> ⚠️ **No es round-robin.** openwiki es un agente; repartir turnos entre modelos distintos rompe su loop y lo hace entrar en **bucle**. Por eso: un primario + respaldos (ver [GUIA-GATEWAY-Y-BALANCEO.md](GUIA-GATEWAY-Y-BALANCEO.md) §1 y §5).

### B.0 — Atajo automático: `openwiki-camino-b.bat` (recomendado)
En vez de hacer B.1–B.4 a mano, hay un script que lo encapsula todo: [`scripts/openwiki-camino-b.bat`](../../scripts/openwiki-camino-b.bat).

**Qué hace (verificado end-to-end):**
1. Ubica la raíz del repo y **carga `GROQ`/`DASHSCOPE`/`GEMINI_API_KEY` desde `.env`** (sin imprimirlas; el `for /f` de batch descarta el CR de CRLF y los comentarios `#`).
2. Localiza `litellm` (en PATH, o deriva `Scripts\litellm.exe` del Python actual).
3. **Arranca el proxy LiteLLM** en :4000 en una ventana propia. Se auto-reinvoca con `/proxy` para esa ventana, de modo que **los secretos NO viajan por línea de comandos** (la ventana del proxy re-lee `.env` por sí misma) y aplica el fix `PYTHONUTF8=1`.
4. Espera el **healthcheck** del proxy.
5. Abre una **terminal con `OPENWIKI_*` ya configurado** apuntando al gateway (y limpia las claves de proveedor de esa terminal). Ahí solo corres `npx -y openwiki --init`.

**Uso:**
```powershell
# Doble clic en el .bat, o desde PowerShell/cmd:
scripts\openwiki-camino-b.bat

# Diagnóstico sin arrancar nada (verifica .env, config, claves y litellm):
scripts\openwiki-camino-b.bat /check
```
Requisitos: `python -m pip install "litellm[proxy]"`, Node/npx, y `curl.exe` (viene con Windows 10/11). Si algo falta, el script lo avisa. Para el paso a paso manual equivalente, sigue B.1–B.4.

> 📘 **Guía técnica ampliada:** [GUIA-GATEWAY-Y-BALANCEO.md](GUIA-GATEWAY-Y-BALANCEO.md) explica **cómo está construido el `.bat`** (los 3 modos y su diseño de seguridad), **cómo sincronizar la doc con openwiki**, y **cómo comprobar que el balanceo reparte** entre los 3 proveedores (con comando de verificación).

### B.1 Instalar LiteLLM (una sola vez)
```powershell
python -m pip install "litellm[proxy]"
# Verifica (debe imprimir una versión, ej. 1.91.1):
litellm --version
```
> Si `litellm` no se reconoce, el ejecutable está en el dir *Scripts* de Python. Añádelo al PATH, o úsalo por ruta completa:
> `& "$env:LOCALAPPDATA\Programs\Python\Python312\Scripts\litellm.exe" --version`

### B.2 Arrancar el proxy (en una ventana de PowerShell dedicada)
```powershell
# ⚠️ CRÍTICO en Windows: sin UTF-8 el proxy CRASHEA al arrancar (UnicodeEncodeError charmap).
$env:PYTHONUTF8 = "1"

# Las 3 claves de proveedor (el YAML las lee vía os.environ):
$env:GROQ_API_KEY      = "<tu GROQ_API_KEY>"
$env:DASHSCOPE_API_KEY = "<tu DASHSCOPE_API_KEY>"
$env:GEMINI_API_KEY    = "<tu GEMINI_API_KEY>"

# Levantar el gateway en el puerto 4000 (deja esta ventana abierta):
litellm --config scripts/openwiki-llm.yaml --port 4000
```

### B.3 Verificar que está arriba (en OTRA ventana PowerShell)
```powershell
# Health (GET simple, curl.exe va bien):
curl.exe http://localhost:4000/health/liveliness

# Prueba de ruteo — usa Invoke-RestMethod (NO curl.exe con -d '{...}': en PowerShell
# las comillas del JSON se rompen → "unmatched close brace / Invalid JSON payload"):
$body = @{
  model      = "openwiki-model"
  messages   = @(@{ role = "user"; content = "Reply with exactly: OK" })
  max_tokens = 20
} | ConvertTo-Json
$r = Invoke-RestMethod -Uri "http://localhost:4000/v1/chat/completions" `
  -Method Post -Headers @{ Authorization = "Bearer sk-anything" } `
  -ContentType "application/json" -Body $body
$r.choices[0].message.content   # -> debe imprimir: OK
```
> **Verificado end-to-end:** devuelve `OK` y enruta a los backends Qwen (Groq/DashScope). Si ves `401 Invalid API Key`, revisa que las 3 `$env:*_API_KEY` del proxy (paso B.2) estén bien pegadas (sin espacios/comillas de más).

### B.4 Apuntar openwiki al gateway (en la 2ª ventana)
```powershell
$env:OPENWIKI_PROVIDER = "openai-compatible"
$env:OPENAI_COMPATIBLE_BASE_URL = "http://localhost:4000/v1"
$env:OPENWIKI_MODEL_ID = "openwiki-model"
$env:OPENAI_COMPATIBLE_API_KEY = "sk-anything"   # el proxy no exige auth real aquí
npx -y openwiki --init
```

### B.5 Apagar el gateway al terminar
En la ventana del proxy: `Ctrl+C`. (O `Get-Process litellm | Stop-Process`.)

---

## 5. Usar la UI de OpenWiki (aplica a ambos caminos)
> ⚠️ **Importante (v0.0.4):** `openwiki --init` **a secas solo EXPLORA** el repo (indexa y hace un pase de inspección) y **sale sin escribir docs**. Para que genere de verdad hay que **darle una instrucción explícita** como argumento (o en el chat interactivo).

1. Corre openwiki **con una instrucción de generación**, por ejemplo:
   ```powershell
   npx -y openwiki --init "Genera la documentacion para agentes de este repositorio (arquitectura Astro + WordPress hibrido, Content Collections, microexperiencias de IA, scripts y specs). Escribe los archivos markdown dentro de la carpeta openwiki/ y crea/actualiza AGENTS.md."
   ```
   (o `npx -y openwiki` para el chat interactivo y escribes ahí la misma instrucción).
2. El agente indexa (`~/.openwiki/openwiki.sqlite`) y **escribe** archivos en `openwiki/` + `AGENTS.md`, haciendo varias llamadas al LLM (verás muchos `200 OK` en la ventana del proxy).
3. Verifica que `openwiki/` tenga `.md` reales (no vacío): `dir openwiki`.
4. Si quedó incompleto, vuelve a correr con `--update "..."` pidiendo lo que falte.
5. Sal con `/exit` si estás en modo interactivo.
6. Commitea `openwiki/` y `AGENTS.md` si quieres versionar la doc de agentes.

---

## 6. Troubleshooting (problemas reales encontrados)
| Síntoma | Causa | Solución |
|---|---|---|
| `UnicodeEncodeError: 'charmap' codec…` al arrancar litellm | stdout cp1252 en Windows vs banner Unicode | `$env:PYTHONUTF8 = "1"` antes de arrancar el proxy |
| `Raw mode is not supported…` (Ink) | openwiki corrido sin TTY (background, `< /dev/null`, CI) | Córrelo en un terminal real (PowerShell), nunca en background |
| `429 MODEL_RATE_LIMIT` al indexar | free tier de Gemini agotado por el escaneo | Usa DashScope `qwen-plus` (Camino A) o el balanceo (Camino B) |
| `Invalid JSON payload` / `unmatched close brace/bracket in URL` al probar con `curl.exe -d '{...}'` | PowerShell rompe las comillas del JSON inline | Usa **`Invoke-RestMethod`** con `ConvertTo-Json` (ver B.3), no `curl.exe -d '{...}'` |
| `401 Invalid API Key` / `Incorrect API key` en TODOS los proveedores | había un **proxy viejo con claves malas** ya escuchando en :4000 (de un intento previo) y se estaba reutilizando | Cierra las ventanas de proxy viejas (o `taskkill /f /im litellm.exe`) y vuelve a correr el `.bat` — ahora siempre mata el proxy previo y arranca fresco |
| `401` solo al probar por tu cuenta con `$env:*_API_KEY` a mano | valores pegados con espacios/comillas o vacíos | Re-pégalos limpios; o usa el `.bat` (carga desde `.env` sin esos errores) |
| `litellm` no reconocido | Scripts de Python no está en PATH | Usa la ruta completa a `litellm.exe` (ver B.1) |
| openwiki entra en **bucle** ("Checked the file tree" repetido, alternando voces) | el gateway hacía **round-robin** (cambiaba de modelo por turno) y/o modelos débiles rompían el loop del agente | Ya corregido: config de **primario `gpt-oss-120b` + failover** (no round-robin). Si persiste con otro modelo, usa uno más capaz de primario |

## 7. Caché local
El índice vive en `~/.openwiki/openwiki.sqlite` (decenas de MB). Para reindexar limpio:
```powershell
Remove-Item "$env:USERPROFILE\.openwiki\openwiki.sqlite*"
```

## 8. Estado
- Rename a `src/content/wiki/`, `.gitignore` reforzado, y gateway Qwen (`scripts/openwiki-llm.yaml`): **verificados**.
- Generación real de `openwiki/`: **paso manual tuyo** en PowerShell (no automatizable con v0.0.3).
