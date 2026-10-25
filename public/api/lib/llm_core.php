<?php
/**
 * public/api/lib/llm_core.php
 * Cliente PHP y failover multi-proveedor LLM a partir del registro único de proveedores (providers.php).
 * Implementación de referencia equivalente a llm_core.py y sujeta a los casos de paridad de parity_cases.json.
 */

if (!defined('LLM_RETRY_STATUSES')) {
    define('LLM_RETRY_STATUSES', [0, 429, 500, 502, 503, 504]);
    define('LLM_KEY_ROTATION_STATUSES', [401, 403, 429]);
}

/**
 * Carga el registro de proveedores y perfiles.
 *
 * @param array|string|null $registry Ruta a archivo .php/.json o array estructurado.
 * @return array
 */
function llm_load_registry($registry = null): array {
    if (is_array($registry)) {
        return $registry;
    }
    if (is_string($registry)) {
        if (str_ends_with($registry, '.json')) {
            $raw = file_get_contents($registry);
            $parsed = json_decode($raw, true);
            if (!is_array($parsed)) {
                throw new RuntimeException("Error parseando registro JSON: $registry");
            }
            return $parsed;
        }
        if (file_exists($registry)) {
            $loaded = require $registry;
            if (is_array($loaded)) {
                return $loaded;
            }
        }
        throw new RuntimeException("No se pudo cargar el registro desde: $registry");
    }

    $default_path = __DIR__ . '/providers.php';
    if (file_exists($default_path)) {
        $loaded = require $default_path;
        if (is_array($loaded)) {
            return $loaded;
        }
    }

    throw new RuntimeException("Registro único de proveedores no encontrado en $default_path");
}

/**
 * Obtiene las claves configuradas para un proveedor a partir de sus variables de entorno en key_env.
 *
 * @param array $provider
 * @return array<string>
 */
function llm_keys_for(array $provider): array {
    $keys = [];
    foreach ($provider['key_env'] ?? [] as $envName) {
        $val = getenv($envName);
        if ($val !== false && trim($val) !== '') {
            $keys[] = trim($val);
        }
    }
    return $keys;
}

/**
 * Determina la URL base para el proveedor, permitiendo overrides por variable de entorno.
 *
 * @param string $providerName
 * @param array $provider
 * @return string
 */
function llm_base_url(string $providerName, array $provider): string {
    $overrideVar = strtoupper($providerName) . '_BASE_URL';
    $url = getenv($overrideVar) ?: ($provider['base_url'] ?? '');
    return rtrim($url, '/');
}

/**
 * Transporte HTTP predeterminado basado en cURL.
 *
 * @param string $url
 * @param string $key
 * @param array $payload
 * @param float $timeout
 * @return array{status: int, body: string}
 */
function llm_curl_transport(string $url, string $key, array $payload, float $timeout): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)ceil($timeout));

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($errno !== 0 || $body === false) {
        return [
            'status' => 0,
            'body'   => "cURL error ($errno): $error"
        ];
    }

    return [
        'status' => $status,
        'body'   => (string)$body
    ];
}

/**
 * Verifica si existe al menos una clave API configurada para los proveedores del perfil indicado.
 *
 * @param string $profile
 * @param array|string|null $registry
 * @return bool
 */
function llm_has_any_key(string $profile, $registry = null): bool {
    try {
        $reg = llm_load_registry($registry);
        if (!isset($reg['profiles'][$profile]['chain'])) {
            return false;
        }

        foreach ($reg['profiles'][$profile]['chain'] as $step) {
            $pName = $step['provider'] ?? '';
            if (isset($reg['providers'][$pName])) {
                $keys = llm_keys_for($reg['providers'][$pName]);
                if (!empty($keys)) {
                    return true;
                }
            }
        }
        return false;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Ejecuta una compleción LLM consultando la cadena de proveedores del perfil seleccionado.
 *
 * @param string $profile Nombre del perfil (ej. 'chat_rapido', 'chat_demo')
 * @param array $messages Mensajes OpenAI-compatibles
 * @param array $opts Opciones de ejecución (temperature, max_tokens, timeout, retries, registry, transport, sleep, model_overrides)
 * @return array
 */
function llm_complete(string $profile, array $messages, array $opts = []): array {
    $failures = [];

    try {
        $reg = llm_load_registry($opts['registry'] ?? null);
    } catch (\Throwable $e) {
        return [
            'ok'         => false,
            'error'      => 'registro_invalido',
            'http'       => 0,
            'body'       => '',
            'text'       => '',
            'provider'   => '',
            'model'      => '',
            'key_index'  => 0,
            'latency_ms' => 0,
            'failures'   => [[
                'provider'  => '',
                'model'     => '',
                'key_index' => 0,
                'http'      => 0,
                'message'   => $e->getMessage()
            ]]
        ];
    }

    if (!isset($reg['profiles'][$profile])) {
        return [
            'ok'         => false,
            'error'      => 'perfil_desconocido',
            'http'       => 0,
            'body'       => '',
            'text'       => '',
            'provider'   => '',
            'model'      => '',
            'key_index'  => 0,
            'latency_ms' => 0,
            'failures'   => []
        ];
    }

    $prof = $reg['profiles'][$profile];
    $defaults = $prof['defaults'] ?? [];

    $temperature = $opts['temperature'] ?? ($defaults['temperature'] ?? 0.7);
    $max_tokens = $opts['max_tokens'] ?? ($defaults['max_tokens'] ?? 800);
    $timeout = $opts['timeout'] ?? ($defaults['timeout'] ?? 30);
    $retries = isset($opts['retries']) ? (int)$opts['retries'] : 2;
    $model_overrides = $opts['model_overrides'] ?? [];

    $transport = $opts['transport'] ?? 'llm_curl_transport';
    $sleep = $opts['sleep'] ?? function($seconds) {
        usleep((int)($seconds * 1000000));
    };

    $any_key = false;

    foreach ($prof['chain'] as $step) {
        $pName = $step['provider'];
        $model = $model_overrides[$pName] ?? $step['model'];

        if (!isset($reg['providers'][$pName])) {
            continue;
        }

        $prov = $reg['providers'][$pName];
        $keys = llm_keys_for($prov);

        if (empty($keys)) {
            continue;
        }

        $any_key = true;
        $url = llm_base_url($pName, $prov) . '/chat/completions';

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $max_tokens,
        ];
        if (isset($opts['response_format'])) {
            $payload['response_format'] = $opts['response_format'];
        }

        foreach ($keys as $ki => $key) {
            $status = null;
            $body = '';
            $t0 = microtime(true);

            for ($attempt = 0; $attempt <= $retries; $attempt++) {
                try {
                    $resp = call_user_func($transport, $url, $key, $payload, $timeout);
                    $status = (int)($resp['status'] ?? 0);
                    $body = (string)($resp['body'] ?? '');
                } catch (\Throwable $e) {
                    $status = 0;
                    $body = get_class($e) . ': ' . $e->getMessage();
                }

                // Si es 2xx o no es un status reintentable (ej: 400, 401, 403), salimos del bucle de reintentos
                if (($status >= 200 && $status < 300) || !in_array($status, LLM_RETRY_STATUSES, true)) {
                    break;
                }

                // Espera exponencial acotada a 8 segundos (solo entre intentos; failover inmediato en último intento)
                if ($attempt < $retries) {
                    call_user_func($sleep, min(pow(2, $attempt), 8));
                }
            }

            // Caso éxito 2xx
            if ($status >= 200 && $status < 300) {
                $data = json_decode($body, true);
                $rawText = $data['choices'][0]['message']['content'] ?? '';
                $text = preg_replace('/<think>.*?<\/think>/s', '', $rawText);
                $text = trim($text);

                if ($text === '') {
                    // Contenido vacío: no es respuesta válida (ej. modelo de razonamiento agotó tokens)
                    $failures[] = [
                        'provider'  => $pName,
                        'model'     => $model,
                        'key_index' => $ki,
                        'http'      => $status,
                        'message'   => "$pName/$model -> 200 pero contenido vacío (¿max_tokens insuficiente para razonar?)"
                    ];
                    // Pasa al SIGUIENTE proveedor
                    break;
                }

                // Si se eliminó <think>, actualizamos el contenido en $body para que el cliente reciba el texto limpio
                if ($text !== $rawText && is_array($data)) {
                    $data['choices'][0]['message']['content'] = $text;
                    $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                return [
                    'ok'         => true,
                    'http'       => $status,
                    'body'       => $body,
                    'text'       => $text,
                    'provider'   => $pName,
                    'model'      => $model,
                    'key_index'  => $ki,
                    'latency_ms' => (int)round((microtime(true) - $t0) * 1000),
                    'failures'   => $failures
                ];
            }

            // Fallo no-2xx
            $failures[] = [
                'provider'  => $pName,
                'model'     => $model,
                'key_index' => $ki,
                'http'      => $status,
                'message'   => "$pName/$model clave#$ki -> HTTP $status: " . substr($body, 0, 160)
            ];

            // Si el estado no justifica probar otra clave del mismo proveedor (ej. 400, 5xx agotado), pasamos al siguiente proveedor
            if (!in_array($status, LLM_KEY_ROTATION_STATUSES, true)) {
                break;
            }
        }
    }

    if (!$any_key) {
        return [
            'ok'         => false,
            'error'      => 'sin_claves',
            'http'       => 0,
            'body'       => '',
            'text'       => '',
            'provider'   => '',
            'model'      => '',
            'key_index'  => 0,
            'latency_ms' => 0,
            'failures'   => []
        ];
    }

    return [
        'ok'         => false,
        'error'      => 'todos_fallaron',
        'http'       => 0,
        'body'       => '',
        'text'       => '',
        'provider'   => '',
        'model'      => '',
        'key_index'  => 0,
        'latency_ms' => 0,
        'failures'   => $failures
    ];
}
