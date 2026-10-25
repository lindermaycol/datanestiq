<?php
/**
 * scripts/test_llm_core_parity.php
 * Ejecuta los 15 casos de paridad y conformidad de parity_cases.json
 * con transporte y temporizador simulados (sin red, sin dependencias externas).
 * Debe imprimir "15/15" y salir con código 0 si todos los casos pasan.
 */

require_once __DIR__ . '/../public/api/lib/llm_core.php';

$fixtures_path = __DIR__ . '/fixtures/parity_cases.json';
if (!file_exists($fixtures_path)) {
    fwrite(STDERR, "ERROR: No existe el fixture de paridad en $fixtures_path\n");
    exit(1);
}

$fixture_data = json_decode(file_get_contents($fixtures_path), true);
if (!is_array($fixture_data) || !isset($fixture_data['cases']) || !isset($fixture_data['registry'])) {
    fwrite(STDERR, "ERROR: Formato inválido en parity_cases.json\n");
    exit(1);
}

$registry = $fixture_data['registry'];
$cases = $fixture_data['cases'];
$total = count($cases);
$passed = 0;

$all_known_keys = ['P1_KEY', 'P1_KEY_2', 'P2_KEY'];

foreach ($cases as $idx => $case) {
    $caseNum = $idx + 1;
    $name = $case['name'] ?? "caso_$caseNum";
    $expect = $case['expect'];
    $retries = $case['retries'] ?? 2;

    // 1. Limpiar variables de entorno conocidas y aplicar las del caso
    foreach ($all_known_keys as $k) {
        putenv($k);
        unset($_ENV[$k], $_SERVER[$k]);
    }
    foreach ($case['keys'] ?? [] as $k => $v) {
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }

    // 2. Preparar colas de respuestas simuladas
    $queues = [];
    foreach ($case['responses'] ?? [] as $cid => $items) {
        $queues[$cid] = array_values($items);
    }

    $calls = [];
    $sleeps = [];

    $mock_transport = function(string $url, string $key, array $payload, float $timeout) use (&$queues, &$calls): array {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $p_name = explode('.', $host)[0];
        $cid = "{$p_name}#{$key}";
        $calls[] = $cid;

        if (!isset($queues[$cid]) || empty($queues[$cid])) {
            throw new RuntimeException("Sin respuesta simulada en cola para $cid");
        }

        $item = array_shift($queues[$cid]);

        if ($item === 0) {
            throw new RuntimeException("Error de red simulado (0)");
        }

        if (is_string($item)) {
            $parts = explode(':', $item, 2);
            $content = $parts[1] ?? '';
            $body = json_encode([
                'choices' => [
                    [
                        'message' => [
                            'content' => $content
                        ]
                    ]
                ],
                'usage' => []
            ]);
            return ['status' => 200, 'body' => $body];
        }

        return ['status' => (int)$item, 'body' => 'err'];
    };

    $mock_sleep = function($seconds) use (&$sleeps) {
        $sleeps[] = $seconds;
    };

    // 3. Ejecutar llamada
    $messages = [['role' => 'user', 'content' => 'hola']];
    $res = llm_complete('t', $messages, [
        'registry'  => $registry,
        'retries'   => $retries,
        'transport' => $mock_transport,
        'sleep'     => $mock_sleep,
    ]);

    // 4. Validar aserciones
    $errors = [];

    // Llamadas exactas
    if ($calls !== $expect['calls']) {
        $errors[] = "calls esperado " . json_encode($expect['calls']) . " pero fue " . json_encode($calls);
    }

    // Esperas exactas
    if (isset($expect['sleeps'])) {
        if ($sleeps !== $expect['sleeps']) {
            $errors[] = "sleeps esperado " . json_encode($expect['sleeps']) . " pero fue " . json_encode($sleeps);
        }
    }

    if (isset($expect['error'])) {
        if ($res['ok'] !== false) {
            $errors[] = "se esperaba ok=false pero retornó ok=true";
        }
        if (($res['error'] ?? '') !== $expect['error']) {
            $errors[] = "error esperado '{$expect['error']}' pero retornó '" . ($res['error'] ?? '') . "'";
        }
        if (isset($expect['n_failures'])) {
            $numFailures = count($res['failures'] ?? []);
            if ($numFailures !== $expect['n_failures']) {
                $errors[] = "n_failures esperado {$expect['n_failures']} pero tiene $numFailures";
            }
        }
    } else {
        if ($res['ok'] !== true) {
            $errors[] = "se esperaba ok=true pero retornó ok=false (error: " . ($res['error'] ?? '') . ")";
        }
        if (($res['provider'] ?? '') !== $expect['provider']) {
            $errors[] = "provider esperado '{$expect['provider']}' pero fue '{$res['provider']}'";
        }
        if (($res['key_index'] ?? null) !== $expect['key_index']) {
            $errors[] = "key_index esperado {$expect['key_index']} pero fue " . var_export($res['key_index'], true);
        }
        if (($res['text'] ?? '') !== $expect['text']) {
            $errors[] = "text esperado '{$expect['text']}' pero fue '{$res['text']}'";
        }
    }

    if (empty($errors)) {
        $passed++;
        echo "  [PASS] {$caseNum}/{$total} - {$name}\n";
    } else {
        echo "  [FAIL] {$caseNum}/{$total} - {$name}\n";
        foreach ($errors as $err) {
            echo "         -> $err\n";
        }
    }
}

// Limpiar variables de entorno al finalizar
foreach ($all_known_keys as $k) {
    putenv($k);
    unset($_ENV[$k], $_SERVER[$k]);
}

echo "\nResultado: {$passed}/{$total}\n";

if ($passed === $total) {
    exit(0);
} else {
    exit(1);
}
