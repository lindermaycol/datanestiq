@echo off
setlocal EnableExtensions
REM ============================================================================
REM  openwiki-camino-b.bat
REM  Spec 009 - Camino B: arranca el gateway LiteLLM (balanceo Qwen/Qwen/Gemini)
REM  y abre una terminal lista para correr openwiki apuntando a ese gateway.
REM
REM  QUE HACE (paso a paso):
REM    1) Ubica la raiz del repo (este .bat vive en scripts\).
REM    2) Carga GROQ/DASHSCOPE/GEMINI_API_KEY desde .env (sin imprimirlas).
REM    3) Localiza el ejecutable litellm.
REM    4) Levanta el proxy LiteLLM en :4000 en su propia ventana
REM       (se auto-reinvoca con /proxy; los secretos NO viajan por linea de
REM        comandos: la ventana del proxy re-lee .env por si misma).
REM    5) Espera el healthcheck del proxy.
REM    6) Abre una terminal con OPENWIKI_* ya configurado apuntando al gateway.
REM
REM  REQUISITOS: Python + litellm (python -m pip install "litellm[proxy]"),
REM              Node/npx, y curl.exe (incluido en Windows 10/11).
REM
REM  USO:
REM    Doble clic, o desde cmd:  scripts\openwiki-camino-b.bat
REM    Diagnostico (no arranca nada):  scripts\openwiki-camino-b.bat /check
REM ============================================================================

set "SELFDIR=%~dp0"
set "SELF=%~f0"

REM --- Despacho de modos (auto-reinvocacion) ---
if /i "%~1"=="/proxy" goto :PROXYMODE
if /i "%~1"=="/check" goto :CHECKMODE

REM =================== MODO PRINCIPAL (orquestador) ===================
call :LOCATE
echo === OpenWiki Camino B (gateway LiteLLM) ===
echo Repo: %ROOT%

if not exist "%ENVFILE%" ( echo ERROR: no existe %ENVFILE% & goto :FAIL )
if not exist "%LLMCFG%" ( echo ERROR: no existe %LLMCFG% & goto :FAIL )

call :LOADENV
if not defined GROQ_API_KEY      echo ADVERTENCIA: GROQ_API_KEY no esta en .env
if not defined DASHSCOPE_API_KEY echo ADVERTENCIA: DASHSCOPE_API_KEY no esta en .env
if not defined GEMINI_API_KEY    echo ADVERTENCIA: GEMINI_API_KEY no esta en .env
if not defined GROQ_API_KEY if not defined DASHSCOPE_API_KEY if not defined GEMINI_API_KEY (
    echo ERROR: ninguna clave de proveedor cargada desde .env. Abortando.
    goto :FAIL
)

call :FINDLITELLM
if not defined LITELLM (
    echo ERROR: no se encontro 'litellm'. Instalalo con:
    echo    python -m pip install "litellm[proxy]"
    goto :FAIL
)
echo litellm: %LITELLM%

REM --- Cerrar cualquier proxy LiteLLM previo para NO reutilizar uno con claves
REM     viejas/malas (causaba 401 Invalid API Key). Siempre arrancamos fresco.
REM     (taskkill es idempotente; ping como sleep robusto sin depender de timeout.)
echo Cerrando cualquier proxy LiteLLM previo para arrancar limpio...
taskkill /f /im litellm.exe >nul 2>&1
ping -n 3 127.0.0.1 >nul
echo Arrancando gateway LiteLLM en una ventana nueva...
start "OpenWiki LiteLLM Proxy (Ctrl+C para cerrar)" cmd /k call "%SELF%" /proxy

REM --- Esperar el healthcheck (hasta ~60s) ---
echo Esperando healthcheck del proxy en http://localhost:4000 ...
set /a _tries=0
:WAITHEALTH
curl.exe -s -f http://localhost:4000/health/liveliness >nul 2>&1
if %errorlevel%==0 goto :HEALTHOK
set /a _tries+=1
if %_tries% geq 30 (
    echo ERROR: el proxy no respondio a tiempo. Revisa su ventana.
    goto :FAIL
)
ping -n 3 127.0.0.1 >nul
goto :WAITHEALTH
:HEALTHOK
echo Proxy ARRIBA.

REM --- Config de openwiki apuntando al gateway (se hereda a la nueva ventana) ---
set "OPENWIKI_PROVIDER=openai-compatible"
set "OPENAI_COMPATIBLE_BASE_URL=http://localhost:4000/v1"
set "OPENWIKI_MODEL_ID=openwiki-model"
set "OPENAI_COMPATIBLE_API_KEY=sk-anything"
REM Las claves reales viven solo en la ventana del proxy: las limpiamos aqui
REM para que NO queden en la terminal interactiva de openwiki.
set "GROQ_API_KEY="
set "DASHSCOPE_API_KEY="
set "GEMINI_API_KEY="

echo Abriendo terminal de OpenWiki...
start "OpenWiki (Camino B)" /D "%ROOT%" cmd /k "echo. & echo Gateway listo: http://localhost:4000/v1 (primario gpt-oss-120b + failover) & echo Variables OPENWIKI_* ya configuradas en esta ventana. & echo. & echo Para GENERAR docs (dale instruccion explicita, --init a secas solo explora): & echo    npx -y openwiki --init "Genera la documentacion para agentes y escribe los .md en openwiki/ y AGENTS.md" & echo Para ACTUALIZAR: npx -y openwiki --update "..." & echo (Sal de openwiki con /exit. Cierra el proxy con Ctrl+C en su ventana.) & echo."

echo.
echo Todo listo. Se abrieron: (1) ventana del proxy LiteLLM  (2) terminal de OpenWiki.
echo Esta ventana ya puede cerrarse.
ping -n 9 127.0.0.1 >nul
goto :EOF

REM =================== MODO PROXY (ventana del gateway) ===================
:PROXYMODE
call :LOCATE
call :LOADENV
call :FINDLITELLM
REM Fix de encoding Windows: sin esto litellm crashea (UnicodeEncodeError charmap).
set "PYTHONUTF8=1"
title OpenWiki LiteLLM Proxy
if not defined LITELLM ( echo ERROR: litellm no encontrado. & pause & goto :EOF )
echo Iniciando gateway LiteLLM (Qwen via Groq/DashScope + Gemini) en :4000 ...
"%LITELLM%" --config "%LLMCFG%" --port 4000
echo.
echo El proxy se detuvo. Pulsa una tecla para cerrar esta ventana.
pause >nul
goto :EOF

REM =================== MODO CHECK (diagnostico) ===================
:CHECKMODE
call :LOCATE
echo === Diagnostico Camino B ===
echo ROOT   : %ROOT%
if exist "%ENVFILE%" (echo .env   : OK) else (echo .env   : FALTA)
if exist "%LLMCFG%"  (echo config : OK  ^(%LLMCFG%^)) else (echo config : FALTA)
call :LOADENV
if defined GROQ_API_KEY      (echo GROQ_API_KEY      : cargada) else (echo GROQ_API_KEY      : FALTA)
if defined DASHSCOPE_API_KEY (echo DASHSCOPE_API_KEY : cargada) else (echo DASHSCOPE_API_KEY : FALTA)
if defined GEMINI_API_KEY    (echo GEMINI_API_KEY    : cargada) else (echo GEMINI_API_KEY    : FALTA)
call :FINDLITELLM
if defined LITELLM (echo litellm : %LITELLM%) else (echo litellm : NO ENCONTRADO)
goto :EOF

REM =================== SUBRUTINAS ===================
:LOCATE
REM Raiz del repo = carpeta padre de scripts\
pushd "%SELFDIR%.." >nul
set "ROOT=%CD%"
popd >nul
set "ENVFILE=%ROOT%\.env"
set "LLMCFG=%ROOT%\scripts\openwiki-llm.yaml"
exit /b 0

:LOADENV
REM Lee las 3 claves de .env. for /f descarta el CR de CRLF y (eol=#) ignora comentarios.
set "GROQ_API_KEY="
set "DASHSCOPE_API_KEY="
set "GEMINI_API_KEY="
if not exist "%ENVFILE%" exit /b 1
for /f "usebackq eol=# tokens=1,* delims==" %%A in ("%ENVFILE%") do (
    if /i "%%A"=="GROQ_API_KEY"      set "GROQ_API_KEY=%%B"
    if /i "%%A"=="DASHSCOPE_API_KEY" set "DASHSCOPE_API_KEY=%%B"
    if /i "%%A"=="GEMINI_API_KEY"    set "GEMINI_API_KEY=%%B"
)
exit /b 0

:FINDLITELLM
REM Busca litellm en PATH; si no, deriva Scripts\litellm.exe del Python actual.
set "LITELLM="
for /f "delims=" %%i in ('where litellm 2^>nul') do if not defined LITELLM set "LITELLM=%%i"
if not defined LITELLM for /f "delims=" %%i in ('python -c "import sys,os;print(os.path.join(os.path.dirname(sys.executable),'Scripts','litellm.exe'))" 2^>nul') do if not defined LITELLM set "LITELLM=%%i"
if defined LITELLM if not exist "%LITELLM%" set "LITELLM="
exit /b 0

:FAIL
echo.
echo Fallo la preparacion. Revisa los mensajes de arriba.
pause
goto :EOF
