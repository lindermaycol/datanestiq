# Walkthrough: Integración del Registro Único de Proveedores LLM (`llm-core`) en `chat.php`

Se ha completado la migración y hardening de `public/api/chat.php` sobre la arquitectura unificada de `llm-core` (`public/api/lib/llm_core.php` y `public/api/lib/providers.php`), incorporando las correcciones post-auditoría H1, H2 y H3.

---

## 1. Arquitectura y Componentes Creados

### 1.1 Sincronizador de Registro (`scripts/sync-llm-registry.php`)
- **Fuente de verdad:** `C:\Users\Lisbeth\llm-core\providers.json` (v2) y `parity_cases.json` (sobrescribible con la variable `LLM_CORE_DIR`).
- **Destino público seguro:** En lugar de exponer un `.json` descargable sin `.htaccess`, genera `public/api/lib/providers.php` (`<?php return [...];`) con cabecera de verificación SHA-256.
- **Fixture de conformidad:** Copia `parity_cases.json` a `scripts/fixtures/parity_cases.json` fuera del webroot.
- **Integridad y Pre-deploy Guard:** El flag `--check` recalcula el hash SHA-256 del maestro y verifica byte a byte el contenido. Si difiere o fue alterado, sale con exit code 1.

### 1.2 Cliente PHP LLM Core (`public/api/lib/llm_core.php`)
- **Firma principal:** `llm_complete(string $profile, array $messages, array $opts = []): array`.
- **Chequeo de claves:** `llm_has_any_key(string $profile, $registry = null): bool` para verificación temprana con HTTP 500 idéntico.
- **Failover determinista:**
  - Omite proveedores sin clave (`key_env`).
  - Permite override de URLs mediante `<NOMBRE_PROVEEDOR>_BASE_URL`.
  - Reintentos con backoff exponencial (`min(2^intento, 8)` s) para estados 0, 429, 500, 502, 503, 504.
  - **Corrección H1 (Espera solo entre intentos):** La llamada a `sleep` se ejecuta únicamente si `$attempt < $retries`, eliminando la espera artificial de 1 s en el último intento o cuando `retries=0`. El failover es ahora inmediato.
  - Rotación a siguiente clave del mismo proveedor en 401, 403 y 429 agotados.
  - Failover al siguiente proveedor en 400, 5xx agotados o contenido vacío.
  - Filtrado automático de bloques `<think>...</think>`.
- **Sin secretos:** Utiliza exclusivamente `getenv()`.

### 1.3 Suite de Paridad (`scripts/test_llm_core_parity.php`)
- Ejecuta los **15 casos de paridad** definidos en `parity_cases.json` mediante transporte HTTP y temporizadores simulados (sin red, sin dependencias externas).
- **Validación de esperas H1:** Registra cada intervalo de espera y comprueba `expect.sleeps` en cada caso de prueba, además de `calls`, `provider`, `key_index`, `text`, `n_failures` y `error`.

---

## 2. Refactorización en `public/api/chat.php`

1. **Eliminación de hardcoding:**
   - Se eliminaron las variables `$groq_api_key`, `$dashscope_api_key`, `$gemini_api_key`, el array `$providers` y la función `callOpenAICompatible`.
   - Se erradicaron todas las cadenas directas de modelos (`llama-3.1-8b-instant`, `llama-3.3-70b-versatile`, `qwen-plus`, `gemini-2.5-flash`) y URLs (`api.groq.com`, `dashscope-intl.aliyuncs.com`).
2. **Selección de perfil:**
   - `$profile = $is_demo_run ? 'chat_demo' : 'chat_rapido';`
   - Validación de claves: `if (!llm_has_any_key($profile)) { ... HTTP 500 ... }` evaluado tras el rate limiting y parseo del payload.
3. **Llamada de compleción:**
   - Invocación con `retries: 0` (preserva comportamiento interactivo inmediato de chat) y `timeout: 20` (previene cuelgues indefinidos en cURL).
4. **Conservación de telemetría y gobernanza:**
   - Registro de alertas de tipo `failover` en la tabla `alerts` de SQLite por cada falla intermedia.
   - Registro de conversación en `conversations` y `chat_raw` mediante `logInteraction`.
   - Inserción de métricas en `chat_metrics` (`backend_used = strtolower(provider)`).
   - Acumulación diaria en `usage_daily` y preservación de kill-switches.
   - Retorno del cuerpo crudo del proveedor ganador (`echo $response`).
5. **Preservación de GraphRAG (Spec 025):**
   - El bloque de grounded context de la Spec 025 (`subgraph_context`, reglas §2 de honestidad radical y URLs de origen) se mantuvo 100% intacto.

---

## 3. Pruebas y Evidencia de Ejecución

### 3.1 Verificación de Sintaxis (`php -l`)
```text
No syntax errors detected in public/api/lib/providers.php
No syntax errors detected in public/api/lib/llm_core.php
No syntax errors detected in scripts/sync-llm-registry.php
No syntax errors detected in scripts/test_llm_core_parity.php
No syntax errors detected in public/api/chat.php
```

### 3.2 Casos de Paridad (`scripts/test_llm_core_parity.php` -> 15/15)
```text
  [PASS] 1/15 - primario_ok
  [PASS] 2/15 - 5xx_reintenta_3_veces_y_pasa_al_siguiente
  [PASS] 3/15 - 429_y_luego_exito_en_la_misma_clave
  [PASS] 4/15 - 401_rota_a_la_segunda_clave_sin_reintentar
  [PASS] 5/15 - 429_agotado_rota_a_la_segunda_clave
  [PASS] 6/15 - 400_no_rota_claves_pasa_al_siguiente_proveedor
  [PASS] 7/15 - contenido_vacio_cuenta_como_fallo
  [PASS] 8/15 - solo_think_cuenta_como_vacio
  [PASS] 9/15 - elimina_bloque_think
  [PASS] 10/15 - error_de_red_se_reintenta_y_pasa_al_siguiente
  [PASS] 11/15 - sin_clave_se_omite
  [PASS] 12/15 - todos_fallan
  [PASS] 13/15 - ninguna_clave
  [PASS] 14/15 - retries_0_no_reintenta_y_pasa_al_siguiente
  [PASS] 15/15 - retries_0_429_rota_clave_sin_reintentar

Resultado: 15/15
```

### 3.3 Demostración de Detección de Mutación (H1)
Al forzar una espera incondicional en el último intento (eliminando `if ($attempt < $retries)`), el suite de paridad falló en 6 casos (casos 2, 5, 10, 12, 14, 15) con resultado `9/15`, comprobando la sensibilidad del test runner ante regresiones en la lógica de esperas. Tras revertir la mutación, el suite retornó a `15/15`.

### 3.4 Verificación de Sincronización e Integridad (`sync-llm-registry.php --check`)
```text
OK: registro LLM y fixtures sincronizados con maestro (sha256: 4cb38b0592ec0eedaaca111276a99191b304e70289d65f8a65777a09884a0bbe)
```

---

## 4. Verificación en Vivo Local (H2: Modelos Registro v2)

Con el backend corriendo en `http://127.0.0.1:8081` y `.env` respaldado y verificado con `fc.exe`:

### 4.1 Sesión Normal (`chat_rapido`)
- **HTTP Status:** 200 OK
- **Tiempo:** 2.068 s
- **Modelo retornado:** `openai/gpt-oss-20b`
- **Proveedor:** `backend_used = 'groq'`
- **Alertas en `alerts`:** 0 alertas (camino feliz sin failover).
- **Métricas:** `tokens_est = 1880`, `prompt_tokens = 1490`, `completion_tokens = 390`, `latency_ms = 1843`.

### 4.2 Sesión Copilot (`chat_demo`)
- **HTTP Status:** 200 OK
- **Tiempo:** 2.464 s
- **Modelo retornado:** `openai/gpt-oss-120b`
- **Proveedor:** `backend_used = 'groq'`
- **Alertas en `alerts`:** 0 alertas (camino feliz sin failover).
- **Métricas:** `tokens_est = 2291`, `prompt_tokens = 1491`, `completion_tokens = 800`, `latency_ms = 2414`.

### 4.3 Groq Inalcanzable (`GROQ_BASE_URL=http://127.0.0.1:9/v1`)
- **HTTP Status:** 200 OK
- **Tiempo total:** 4.988 s (tiempo de procesamiento y streaming directo de DashScope; sin espera artificial de 1 s).
- **Modelo:** `qwen-plus`
- **Proveedor:** `backend_used = 'dashscope'`
- **Alerta registrada:** `ALERTA FAILOVER: Fallo en proveedor Groq (0). Sesion ...`

---

## 5. Observaciones Documentadas (H3)

1. **`DASHSCOPE_BASE_URL` en `.env` local:**
   El archivo `.env` local apunta al endpoint de workspace (`…ap-southeast-1.maas.aliyuncs.com`). Previamente `chat.php` tenía hardcodeado `dashscope-intl.aliyuncs.com`. Ahora `llm_core.php` respeta prioritariamente `DASHSCOPE_BASE_URL`. Ambos endpoints responden 200 con la clave autorizada.
2. **Ubicación del chequeo "sin claves":**
   El chequeo `if (!llm_has_any_key($profile))` ocurre tras el control de rate limiting y el parseo del payload (`$sessionId` / `$profile`). Se mantiene en este orden para determinar con exactitud el perfil solicitado.
3. **Referencia en `tech_debt.md`:**
   Se corrigió la referencia numérica en `specs/002-microexperiencias-ia/tech_debt.md` para situar `$is_simple` en la línea 297 de `public/api/chat.php`.
4. **Regla en `.gitignore`:**
   Se incorporó la regla `!public/api/lib/*.php` para asegurar que los scripts de soporte PHP en `public/api/lib/` se mantengan versionados correctamente sin colisionar con la regla global `*.php`.
