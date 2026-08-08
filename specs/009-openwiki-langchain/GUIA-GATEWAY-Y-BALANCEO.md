# Guía técnica: gateway LiteLLM, el script `.bat` y verificación del balanceo (Spec 009, Camino B)

Complemento del [runbook operativo](USO-MANUAL.md). Aquí se documenta **cómo está construido** el launcher, **cómo ejecutarlo**, **cómo sincronizar la documentación con openwiki** y **cómo comprobar que el balanceo reparte de verdad** entre los tres proveedores (no que uno solo hace todo).

---

## 1. Panorama
`langchain-ai/openwiki` habla con **un** endpoint OpenAI-compatible. Se interpone un **gateway LiteLLM** (proxy OpenAI-compatible) al que openwiki apunta. El script [`scripts/openwiki-camino-b.bat`](../../scripts/openwiki-camino-b.bat) automatiza levantar ese gateway y dejar una terminal lista.

```
openwiki  ─►  http://localhost:4000/v1  (gateway LiteLLM)  ─►  PRIMARIO: Groq gpt-oss-120b
                                                               (si falla) → DashScope qwen-max
                                                               (si falla) → Gemini 2.5-flash
```

> ⚠️ **NO es round-robin (y por qué).** openwiki es un **agente** con un loop de tool-calls. Si el gateway reparte cada turno entre modelos distintos, se **rompe la coherencia** y el agente entra en **bucle** (repite "Checked the file tree" sin avanzar). Por eso se usa **un modelo primario consistente + failover**: openwiki siempre habla con el primario (`gpt-oss-120b`, fuerte en tool-use); solo si ese modelo falla (error/429) LiteLLM conmuta a los de respaldo. Así se gana resiliencia sin sacrificar coherencia.

---

## 2. Cómo está construido el `.bat` (diseño)
Es **un solo archivo** que se auto-despacha según el primer argumento (3 modos):

| Modo | Invocación | Qué hace |
|---|---|---|
| **principal** | `openwiki-camino-b.bat` | Orquesta todo (ver abajo). |
| **`/proxy`** | el `.bat` se **re-invoca a sí mismo** para la ventana del gateway | Levanta litellm. **Seguridad:** los secretos NO viajan por línea de comandos — esa ventana **re-lee `.env` por sí misma**. |
| **`/check`** | `openwiki-camino-b.bat /check` | Diagnóstico: valida `.env`, config, claves y litellm **sin arrancar nada**. |

**Flujo del modo principal:**
1. **Ubica la raíz** del repo (`%~dp0..`, el `.bat` vive en `scripts\`).
2. **Carga `GROQ`/`DASHSCOPE`/`GEMINI_API_KEY` desde `.env`** con `for /f "eol=# tokens=1,* delims=="` — descarta el `\r` de CRLF e ignora comentarios `#`. (No imprime los valores.)
3. **Localiza `litellm`**: primero en el PATH (`where`), si no, deriva `Scripts\litellm.exe` del Python actual.
4. **Mata cualquier proxy previo** (`taskkill /f /im litellm.exe`) y **arranca uno fresco**. *Por qué:* reutilizar un proxy viejo con claves obsoletas causaba `401 Invalid API Key`.
5. **Espera el healthcheck** (`curl http://localhost:4000/health/liveliness`, con reintentos vía `ping` como sleep robusto).
6. **Abre la terminal de openwiki** con `OPENWIKI_*` ya configurado apuntando al gateway (y **limpia las claves de proveedor** de esa terminal, que solo viven en la ventana del proxy).

**Decisiones de diseño clave:**
- `PYTHONUTF8=1` **obligatorio** antes de litellm: en Windows, sin esto, el proxy crashea al imprimir su banner (`UnicodeEncodeError: charmap`).
- Sleeps con `ping -n N 127.0.0.1` en vez de `timeout` (no depende de que `timeout` exista/tenga TTY).
- Subrutinas reutilizadas por los modos: `:LOCATE`, `:LOADENV`, `:FINDLITELLM`.

---

## 3. Cómo ejecutarlo
```powershell
# Arrancar todo (abre 2 ventanas: proxy + terminal de openwiki):
scripts\openwiki-camino-b.bat

# Solo diagnóstico (no arranca nada):
scripts\openwiki-camino-b.bat /check
```
Requisitos: `python -m pip install "litellm[proxy]"`, Node/npx, `curl.exe` (viene con Windows). Para detener el gateway: `Ctrl+C` en su ventana (o `taskkill /f /im litellm.exe`).

---

## 4. Cómo sincronizar la documentación con openwiki
En la **terminal de openwiki** que abre el `.bat`:

```powershell
# Primera generación (dale una INSTRUCCIÓN EXPLÍCITA; --init a secas solo explora):
npx -y openwiki --init "Genera la documentacion para agentes de este repositorio (arquitectura Astro + WordPress hibrido, Content Collections, microexperiencias de IA, scripts y specs). Escribe los archivos markdown dentro de la carpeta openwiki/ y crea AGENTS.md."

# Refrescar tras cambios en el código:
npx -y openwiki --update "Actualiza la documentacion reflejando los cambios recientes del repositorio."
```
- Verás muchos `POST /v1/chat/completions 200 OK` en la ventana del proxy: son las llamadas del agente.
- Al terminar, verifica que escribió de verdad: `dir openwiki` (debe tener `.md`, no estar vacío) y `type AGENTS.md`.
- Commitea `openwiki/` + `AGENTS.md` si quieres versionar la doc de agentes.
- ⚠️ Recuerda: openwiki respeta `.gitignore` (no `.openwikiignore`). Los secretos (`.env`, `secure_leads/`, `wp-config.php`) ya están gitignored y **no** se escanean.

---

## 5. Cómo comprobar qué modelo atiende (y que el failover está armado)
Cada respuesta del gateway trae el header **`x-litellm-model-id`** con el id del backend que la atendió. Los ids están en [`scripts/openwiki-llm.yaml`](../../scripts/openwiki-llm.yaml): `groq-gpt-oss-120b` (primario), `dashscope-qwen-max`, `gemini-2.5-flash` (failover).

**Comando de verificación (PowerShell, con el proxy levantado):**
```powershell
$body = '{"model":"openwiki-model","messages":[{"role":"user","content":"hi"}],"max_tokens":5}'
$counts = @{}
1..10 | ForEach-Object {
  $r = Invoke-WebRequest -Uri "http://localhost:4000/v1/chat/completions" -Method Post `
        -Headers @{ Authorization = "Bearer sk-x" } -ContentType "application/json" -Body $body -UseBasicParsing
  $id = [string]$r.Headers["x-litellm-model-id"]
  if ($counts.ContainsKey($id)) { $counts[$id]++ } else { $counts[$id] = 1 }
}
$counts.GetEnumerator() | Sort-Object Value -Descending | ForEach-Object { "  {0,-22} {1}" -f $_.Name, $_.Value }
```

**Cómo interpretar el resultado (con la config de failover):**
- **Normal y correcto:** ves **un solo id, el primario** `groq-gpt-oss-120b` en TODAS las llamadas. Eso es lo que un agente necesita (coherencia). Verificado: 10/10 llamadas → `groq-gpt-oss-120b`. ✔
- Si aparece `dashscope-qwen-max` o `gemini-2.5-flash`, significa que el **primario estaba fallando** (error/429) y LiteLLM conmutó — resiliencia funcionando, pero conviene ver por qué falló Groq (revisa la ventana del proxy).
- Para **forzar y probar el failover**: pon temporalmente una `GROQ_API_KEY` inválida y repite; deberías ver `dashscope-qwen-max`.

> Nota: esto **no** es "reparto de carga" entre modelos (eso rompía el agente, ver §1). Es "un primario + respaldos". Para cambiar el primario o el orden de failover, edita `scripts/openwiki-llm.yaml` y reinicia el proxy.

**Alternativa sin PowerShell:** en la ventana del proxy, cada `POST .../chat/completions 200 OK` es una llamada servida; los `LiteLLM:WARNING register_model ... not in built-in cost map` son cosméticos (LiteLLM solo avisa que no sabe el precio de esos modelos) — no son errores.
