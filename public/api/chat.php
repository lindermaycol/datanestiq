<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    $parsed_origin = parse_url($origin);
    $host = $parsed_origin['host'] ?? '';
    if ($host !== 'localhost' && $origin !== 'https://datanestiq.com') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    header("Access-Control-Allow-Origin: $origin");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple .env parser
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
    return true;
}

// Try to load .env from root
loadEnv(__DIR__ . '/../../.env');

$groq_api_key = getenv('GROQ_API_KEY');
$dashscope_api_key = getenv('DASHSCOPE_API_KEY');
$gemini_api_key = getenv('GEMINI_API_KEY');

// Verify that we have at least one key to attempt failover
if (!$groq_api_key && !$dashscope_api_key && !$gemini_api_key) {
    echo json_encode(['error' => 'No API keys configured']);
    http_response_code(500);
    exit;
}

// Rate Limiting (fail-open)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_file = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.json';
$rate_limit_threshold = 20;
$rate_limit_window = 600; // 10 minutes

if (is_writable(sys_get_temp_dir())) {
    $current_time = time();
    $rate_data = [];
    if (file_exists($rate_limit_file)) {
        $content = @file_get_contents($rate_limit_file);
        if ($content !== false) {
            $rate_data = json_decode($content, true) ?: [];
        }
    }
    
    // Filter old requests
    $rate_data = array_filter($rate_data, function($timestamp) use ($current_time, $rate_limit_window) {
        return ($current_time - $timestamp) < $rate_limit_window;
    });
    
    if (count($rate_data) >= $rate_limit_threshold) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests']);
        exit;
    }
    
    $rate_data[] = $current_time;
    @file_put_contents($rate_limit_file, json_encode($rate_data));
}

$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? [];
$sessionId = $input['session_id'] ?? 'unknown_session';

if (empty($messages)) {
    echo json_encode(['error' => 'No messages provided']);
    http_response_code(400);
    exit;
}

// Security: Limits
if (count($messages) > 20) {
    http_response_code(400);
    echo json_encode(['error' => 'Turn limit exceeded']);
    exit;
}
$last_message = end($messages);
if ($last_message && isset($last_message['content']) && strlen($last_message['content']) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Message too long']);
    exit;
}

// Security: Isolate System Prompt
$services_catalog = "";
$services_file = __DIR__ . '/services.json';
if (file_exists($services_file)) {
    $services_catalog = "\n\nCATÁLOGO DE SERVICIOS DATANESTIQ:\n" . file_get_contents($services_file);
}

define('SYSTEM_PROMPT', "Eres el asistente estratégico (AI Concierge) de Datanestiq, una consultora de IA y Datos B2B. Eres profesional y conciso. Tu objetivo es entender las necesidades del usuario, ofrecer soluciones y captar sus datos (correo y número de celular/fijo). REGLAS IMPORTANTES: 1. Al preguntar por su Necesidad/Desafío principal o al proponer Soluciones, DEBES presentarle opciones en formato de lista para que elija, aclarando siempre que puede escribir otra si ninguna aplica. 2. Antes de concluir o agendar una reunión, ES OBLIGATORIO pedirle un correo electrónico y un número de celular o fijo para contactarlo. Usa párrafos cortos. 3. SIEMPRE relaciona la necesidad del usuario con los servicios de Datanestiq del Catálogo. 4. RECOMIENDA y ENLAZA obligatoriamente el servicio pertinente usando Markdown con el NOMBRE REAL del servicio como texto del enlace (ej. [Sistemas Digitales Premium](/soluciones/sistemas-digitales)), nunca el texto literal \"Nombre del Servicio\". 5. EXPLICA brevemente en qué consiste el servicio basándote en la descripción provista. 6. Si detectas un sector (ej. Educación, Finanzas), menciónalo y adapta tu tono consultivo." . $services_catalog);

$messages = array_filter($messages, function($msg) {
    return isset($msg['role']) && $msg['role'] !== 'system';
});
$messages = array_values($messages);

$context = $input['context'] ?? null;
$context_msg = null;
if ($context && is_array($context)) {
    $parts = [];
    if (!empty($context['sector'])) $parts[] = "El usuario pertenece al sector: " . $context['sector'];
    if (!empty($context['step'])) $parts[] = "Estado de conversación actual: " . $context['step'];
    if (!empty($context['problem'])) $parts[] = "Problema principal reportado: " . $context['problem'];
    
    if (!empty($parts)) {
        $context_str = "CONTEXTO DE LA MÁQUINA DE ESTADOS:\n" . implode("\n", $parts) . "\nPor favor, adapta tu respuesta a este contexto específico.";
        $context_msg = ['role' => 'system', 'content' => $context_str];
    }
}

// Inserción en orden inverso (para que SYSTEM_PROMPT quede en índice 0)
if ($context_msg) {
    array_unshift($messages, $context_msg);
}
array_unshift($messages, ['role' => 'system', 'content' => SYSTEM_PROMPT]);

// Log function to save interactions for Excel extraction
function logInteraction($sessionId, $messages, $aiResponse = null) {
    $is_demo = strpos($sessionId, 'copilot_') === 0 || strpos($sessionId, 'unknown_') === 0;
    $redacted_logFile = __DIR__ . '/../../' . ($is_demo ? 'other_logs.jsonl' : 'chat_logs.jsonl');
    
    // Redact PII
    $redact = function($text) {
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
        $text = preg_replace('/(\+?\d[\d\s-]{7,14}\d)/', '[PHONE_REDACTED]', $text);
        return $text;
    };
    
    $redacted_messages = [];
    $raw_messages = [];
    
    foreach ($messages as $msg) {
        if (isset($msg['content'])) {
            $raw_messages[] = $msg;
            $msg['content'] = $redact($msg['content']);
        }
        $redacted_messages[] = $msg;
    }

    $redacted_data = [
        'timestamp' => date('c'),
        'session_id' => $sessionId,
        'messages' => $redacted_messages
    ];
    
    $raw_data = [
        'timestamp' => date('c'),
        'session_id' => $sessionId,
        'messages' => $raw_messages
    ];
    
    if ($aiResponse) {
        $redacted_data['messages'][] = ['role' => 'assistant', 'content' => $redact($aiResponse)];
        $raw_data['messages'][] = ['role' => 'assistant', 'content' => $aiResponse];
    }
    
    // Sink 1: Redactado (Público/Analítica)
    @file_put_contents($redacted_logFile, json_encode($redacted_data) . "\n", FILE_APPEND);

    // Sink 2: Crudo (Seguro, solo Concierge)
    if (!$is_demo) {
        $raw_logFile = __DIR__ . '/../../secure_leads/chat_raw.jsonl';
        @file_put_contents($raw_logFile, json_encode($raw_data) . "\n", FILE_APPEND);
    }
}

// --- GOBERNANZA DE COSTOS (FR-020, FR-022) ---
$daily_cap_file = __DIR__ . '/../../secure_leads/daily_usage_' . date('Y-m-d') . '.json';
$daily_call_cap = getenv('DAILY_CALL_CAP') ?: 5000;
$daily_token_cap = getenv('DAILY_TOKEN_CAP') ?: 2000000;
$burst_threshold = getenv('BURST_THRESHOLD') ?: 15;
$alert_webhook = getenv('ALERT_WEBHOOK_URL');
$cheap_llm_url = getenv('CHEAP_LLM_URL');
$cheap_llm_key = getenv('CHEAP_LLM_KEY');
$cheap_llm_model = getenv('CHEAP_LLM_MODEL');

$usage_data = ['calls' => 0, 'tokens' => 0, 'sessions' => []];
if (file_exists($daily_cap_file)) {
    $content = @file_get_contents($daily_cap_file);
    if ($content) {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) $usage_data = array_merge($usage_data, $decoded);
    }
}

// Burst check and logging (FR-022)
$session_calls = $usage_data['sessions'][$sessionId] ?? [];
$current_time = time();
$session_calls = array_filter($session_calls, function($t) use ($current_time) { return ($current_time - $t) < 180; }); // 3 mins
$session_calls[] = $current_time;
$usage_data['sessions'][$sessionId] = array_values($session_calls);

$burst_alert = count($session_calls) >= $burst_threshold;
$cap_alert = $usage_data['tokens'] >= ($daily_token_cap * 0.8) || $usage_data['calls'] >= ($daily_call_cap * 0.8);

if ($burst_alert || $cap_alert) {
    $alert_msg = "ALERTA: " . ($burst_alert ? "Burst detectado en sesion $sessionId" : "Consumo diario superó 80%");
    $alerts_file = __DIR__ . '/../../secure_leads/alerts.jsonl';
    @file_put_contents($alerts_file, json_encode(['timestamp' => date('c'), 'alert' => $alert_msg]) . "\n", FILE_APPEND);
    if ($alert_webhook) {
        $wch = curl_init($alert_webhook);
        curl_setopt($wch, CURLOPT_POST, true);
        curl_setopt($wch, CURLOPT_POSTFIELDS, json_encode(['text' => $alert_msg]));
        curl_setopt($wch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($wch, CURLOPT_TIMEOUT, 2);
        @curl_exec($wch);
        @curl_close($wch);
    }
}

// Check caps (FR-020)
if ($usage_data['calls'] >= $daily_call_cap || $usage_data['tokens'] >= $daily_token_cap) {
    http_response_code(200);
    echo json_encode([
        'choices' => [
            ['message' => ['content' => 'Actualmente estamos experimentando alta demanda. Por favor, déjanos tus datos de contacto y un arquitecto de datos se comunicará contigo a la brevedad.']]
        ]
    ]);
    exit;
}

// Cleanup old files (>7 days) fail-open
$files = glob(__DIR__ . '/../../secure_leads/daily_usage_*.json');
if (is_array($files)) {
    foreach ($files as $f) {
        if (time() - filemtime($f) > 7 * 86400) {
            @unlink($f);
        }
    }
}

// --- ENRUTAMIENTO POR INTENCION (FR-009, FR-013) ---
$is_demo_run = strpos($sessionId, 'copilot_') === 0 || strpos($sessionId, 'unknown_') === 0;
$last_user_msg = '';
foreach (array_reverse($messages) as $m) {
    if ($m['role'] === 'user') { $last_user_msg = $m['content']; break; }
}

$word_count = str_word_count($last_user_msg);
$is_simple = $word_count < 6 && preg_match('/\b(hola|precio|estado|gracias|ayuda|buenas)\b/i', $last_user_msg);

function callOpenAICompatible($url, $api_key, $model, $messages, $max_tokens) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => $max_tokens
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $latency_ms = round((microtime(true) - $start) * 1000);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [$httpcode, $response, $latency_ms, $model];
}

$httpcode = 500;
$response = '';
$latency_ms = 0;
$actual_model = '';

$target_model = $is_demo_run ? 'llama-3.3-70b-versatile' : 'llama-3.1-8b-instant';

// Failover chain: Groq -> DashScope -> Gemini
$providers = [
    [
        'name' => 'Groq',
        'url' => 'https://api.groq.com/openai/v1/chat/completions',
        'key' => $groq_api_key,
        'model' => $target_model
    ],
    [
        'name' => 'DashScope',
        'url' => 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions',
        'key' => $dashscope_api_key,
        'model' => 'qwen-plus'
    ],
    [
        'name' => 'Gemini',
        'url' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
        'key' => $gemini_api_key,
        'model' => 'gemini-2.5-flash'
    ]
];

$success = false;

foreach ($providers as $provider) {
    if (!$provider['key']) continue;
    
    list($httpcode, $response, $latency_ms, $actual_model) = callOpenAICompatible($provider['url'], $provider['key'], $provider['model'], $messages, 800);
    
    if ($httpcode >= 200 && $httpcode < 300) {
        $success = true;
        break; // Success, exit loop
    } else {
        // Log the failure silently and continue to next provider
        $alert_msg = "ALERTA FAILOVER: Fallo en proveedor " . $provider['name'] . " ($httpcode). Sesion $sessionId";
        $alerts_file = __DIR__ . '/../../secure_leads/alerts.jsonl';
        @file_put_contents($alerts_file, json_encode(['timestamp' => date('c'), 'alert' => $alert_msg]) . "\n", FILE_APPEND);
    }
}

if (!$success) {
    // All providers failed
    http_response_code(200);
    echo json_encode([
        'choices' => [
            ['message' => ['content' => 'En este momento estoy procesando muchas solicitudes. Por favor, déjame tus datos de contacto y un arquitecto de datos se comunicará contigo a la brevedad.']]
        ]
    ]);
    exit;
}

if ($httpcode >= 200 && $httpcode < 300) {
    $data = json_decode($response, true);
    $aiContent = $data['choices'][0]['message']['content'] ?? '';
    
    // Log the complete conversation including the new AI response
    logInteraction($sessionId, $messages, $aiContent);
    
    // Metering (FR-021)
    $usage = $data['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
    $metrics = [
        'timestamp' => date('c'),
        'session_id' => $sessionId,
        'model' => $actual_model,
        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
        'completion_tokens' => $usage['completion_tokens'] ?? 0,
        'total_tokens' => $usage['total_tokens'] ?? 0,
        'latency_ms' => $latency_ms
    ];
    @file_put_contents(__DIR__ . '/../../secure_leads/usage_metrics.jsonl', json_encode($metrics) . "\n", FILE_APPEND);
    
    // Increment daily usage
    $usage_data['calls']++;
    $usage_data['tokens'] += $usage['total_tokens'] ?? 0;
    @file_put_contents($daily_cap_file, json_encode($usage_data));
    
    echo $response;
} else {
    http_response_code($httpcode);
    echo $response;
}
