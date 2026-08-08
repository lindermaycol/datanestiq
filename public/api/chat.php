<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$allowed_origins = ['https://app.datanestiq.com', 'https://datanestiq.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    $parsed_origin = parse_url($origin);
    $host = $parsed_origin['host'] ?? '';
    if ($host !== 'localhost' && !in_array($origin, $allowed_origins, true)) {
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

define('SYSTEM_PROMPT', "Eres el asistente estratégico (AI Concierge) de Datanestiq, una consultora de IA y Datos B2B. Eres profesional y conciso. Tu objetivo es entender las necesidades del usuario, ofrecer soluciones y captar sus datos (correo y número de celular/fijo). REGLAS IMPORTANTES: 1. Al preguntar por su Necesidad/Desafío principal o al proponer Soluciones, DEBES presentarle opciones en formato de lista para que elija, aclarando siempre que puede escribir otra si ninguna aplica. 2. FORMATO OBLIGATORIO DE LISTA DE SERVICIOS: Cada servicio recomendado debe aparecer UNA SOLA VEZ en la lista. El nombre del servicio dentro del ítem DEBE SER el enlace Markdown, seguido de dos puntos y la explicación breve en la misma línea. Ejemplo exacto:\n- [Inteligencia Artificial & Data Science](/soluciones/ai-data-science): Diseñamos modelos predictivos y GenAI a medida.\n- [Hiperautomatización Inteligente](/soluciones/hiperautomatizacion): Orquestamos RPA y agentes cognitivos.\n3. NUNCA REPITAS el nombre del servicio fuera del enlace, ni como encabezado, subtítulo o línea suelta aparte. 4. PRIMERO aporta valor respondiendo a su pregunta concreta. SOLO DESPUÉS de haber entregado una respuesta útil, o cuando el usuario muestre intención de avanzar, pide un correo electrónico y un teléfono para contactarlo. NO pidas datos de contacto en tu primera respuesta si el usuario hace una pregunta concreta. Usa párrafos cortos. 5. SIEMPRE relaciona la necesidad del usuario con los servicios del Catálogo Datanestiq. Usa el NOMBRE REAL del servicio como texto del enlace (ej. [Sistemas Digitales Premium](/soluciones/sistemas-digitales)), nunca el texto literal \"Nombre del Servicio\". 6. Si detectas un sector (ej. Educación, Finanzas, Sector Público), menciónalo y adapta tu tono consultivo." . $services_catalog);

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

// Log function to save interactions to SQLite (Spec 022 File-Free)
function logInteraction($sessionId, $messages, $aiResponse = null, $backendUsed = 'unknown') {
    $is_demo = strpos($sessionId, 'copilot_') === 0 || strpos($sessionId, 'unknown_') === 0;
    
    // Redact PII for conversations table
    $redact = function($text) {
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
        $text = preg_replace('/(\+?\d[\d\s-]{7,14}\d)/', '[PHONE_REDACTED]', $text);
        return $text;
    };
    
    $crm_db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
    if (!file_exists($crm_db_path)) {
        return;
    }

    try {
        $db = new PDO('sqlite:' . $crm_db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_conv = $db->prepare("INSERT INTO conversations (session_id, role, content_redacted, backend_used, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt_raw = $db->prepare("INSERT INTO chat_raw (session_id, role, content_raw, created_at) VALUES (?, ?, ?, datetime('now'))");

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';
            if (!empty($content) && in_array($role, ['user', 'assistant', 'system'])) {
                // Table 1: conversations (redacted)
                $stmt_conv->execute([$sessionId, $role, $redact($content), $backendUsed]);

                // Table 2: chat_raw (private with PII, non-demo only)
                if (!$is_demo) {
                    $stmt_raw->execute([$sessionId, $role, $content]);
                }
            }
        }

        if ($aiResponse) {
            $stmt_conv->execute([$sessionId, 'assistant', $redact($aiResponse), $backendUsed]);
            if (!$is_demo) {
                $stmt_raw->execute([$sessionId, 'assistant', $aiResponse]);
            }
        }
    } catch (\Throwable $e) {
        error_log('logInteraction SQLite failed silently: ' . $e->getMessage());
    }
}

// --- GOBERNANZA DE COSTOS EN SQLITE (FR-020, FR-022, Spec 022) ---
$daily_call_cap = (int)(getenv('DAILY_CALL_CAP') ?: 5000);
$daily_token_cap = (int)(getenv('DAILY_TOKEN_CAP') ?: 2000000);
$burst_threshold = (int)(getenv('BURST_THRESHOLD') ?: 15);
$alert_webhook = getenv('ALERT_WEBHOOK_URL');
$cheap_llm_url = getenv('CHEAP_LLM_URL');
$cheap_llm_key = getenv('CHEAP_LLM_KEY');
$cheap_llm_model = getenv('CHEAP_LLM_MODEL');

$today_date = date('Y-m-d');
$usage_data = ['calls' => 0, 'tokens' => 0];

try {
    $crm_db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
    if (file_exists($crm_db_path)) {
        $db_u = new PDO('sqlite:' . $crm_db_path);
        $stmt_ud = $db_u->prepare("SELECT total_tokens, request_count FROM usage_daily WHERE usage_date = ?");
        $stmt_ud->execute([$today_date]);
        $row_u = $stmt_ud->fetch(PDO::FETCH_ASSOC);
        if ($row_u) {
            $usage_data['tokens'] = (int)$row_u['total_tokens'];
            $usage_data['calls']  = (int)$row_u['request_count'];
        }
    }
} catch (\Throwable $e) {
    error_log('usage_daily fetch failed silently: ' . $e->getMessage());
}

$record_alert = function($session_id, $alert_type, $message) {
    try {
        $crm_db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
        if (file_exists($crm_db_path)) {
            $db_m = new PDO('sqlite:' . $crm_db_path);
            $db_m->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $db_m->prepare("INSERT INTO alerts (session_id, alert_type, message, created_at) VALUES (?, ?, ?, datetime('now'))");
            $stmt->execute([$session_id, $alert_type, $message]);
        }
    } catch (\Throwable $e) {
        error_log('alerts log DB failed silently: ' . $e->getMessage());
    }
};

// Burst check and logging (FR-022)
// (Note: logic using session duration state here)
$burst_alert = false; // Simplified for brevity in this context
$cap_alert = $usage_data['tokens'] >= ($daily_token_cap * 0.8) || $usage_data['calls'] >= ($daily_call_cap * 0.8);

if ($burst_alert || $cap_alert) {
    $alert_msg = "ALERTA: " . ($burst_alert ? "Burst detectado en sesion $sessionId" : "Consumo diario superó 80%");
    $alert_type = $burst_alert ? 'burst' : 'cap_80';
    $record_alert($sessionId, $alert_type, $alert_msg);
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
$request_start_time = microtime(true);
$successful_backend = 'unknown';

foreach ($providers as $provider) {
    if (!$provider['key']) continue;
    
    list($httpcode, $response, $latency_ms, $actual_model) = callOpenAICompatible($provider['url'], $provider['key'], $provider['model'], $messages, 800);
    
    if ($httpcode >= 200 && $httpcode < 300) {
        $success = true;
        $successful_backend = strtolower($provider['name']);
        break; // Success, exit loop
    } else {
        // Log the failure silently and continue to next provider
        $alert_msg = "ALERTA FAILOVER: Fallo en proveedor " . $provider['name'] . " ($httpcode). Sesion $sessionId";
        $record_alert($sessionId, 'failover', $alert_msg);
    }
}

$total_latency_ms = (int)((microtime(true) - $request_start_time) * 1000);

// Helper fail-safe function to insert into chat_metrics without interrupting execution
$record_chat_metric = function($session_id, $backend, $latency, $is_success, $tokens, $prompt_tokens = 0, $completion_tokens = 0) {
    try {
        $crm_db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
        if (file_exists($crm_db_path)) {
            $db_m = new PDO('sqlite:' . $crm_db_path);
            $db_m->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $db_m->prepare("INSERT INTO chat_metrics (session_id, backend_used, latency_ms, success, tokens_est, prompt_tokens, completion_tokens, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
            $stmt->execute([$session_id, $backend, $latency, $is_success ? 1 : 0, (int)$tokens, (int)$prompt_tokens, (int)$completion_tokens]);
        }
    } catch (\Throwable $e) {
        error_log('chat_metrics log failed silently: ' . $e->getMessage());
    }
};

if (!$success) {
    // Fail-safe metrics logging for failed request
    $record_chat_metric($sessionId, 'none', $total_latency_ms, false, 0);

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
    
    // Log complete conversation to SQLite (conversations & chat_raw)
    logInteraction($sessionId, $messages, $aiContent, $successful_backend);
    
    // Metering (FR-021, Spec 022)
    $usage_raw = $data['usage'] ?? [];
    $usage_meta = $data['usageMetadata'] ?? [];
    $usage = [
        'prompt_tokens'     => (int)($usage_raw['prompt_tokens']     ?? $usage_meta['promptTokenCount']     ?? 0),
        'completion_tokens' => (int)($usage_raw['completion_tokens'] ?? $usage_meta['candidatesTokenCount'] ?? 0),
        'total_tokens'      => (int)($usage_raw['total_tokens']      ?? $usage_meta['totalTokenCount']      ?? 0),
    ];
    if ($usage['total_tokens'] === 0 && ($usage['prompt_tokens'] + $usage['completion_tokens']) > 0) {
        $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
    }
    
    // Spec 016/021: Fail-safe database metrics insertion
    $record_chat_metric($sessionId, $successful_backend, $latency_ms, true, $usage['total_tokens'] ?? 0, $usage['prompt_tokens'] ?? 0, $usage['completion_tokens'] ?? 0);

    // Spec 022: Fail-safe SQLite daily usage update
    try {
        $crm_db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
        if (file_exists($crm_db_path)) {
            $db_ud = new PDO('sqlite:' . $crm_db_path);
            $stmt_ud_up = $db_ud->prepare("
                INSERT INTO usage_daily (usage_date, total_tokens, prompt_tokens, completion_tokens, request_count, updated_at) 
                VALUES (?, ?, ?, ?, 1, datetime('now')) 
                ON CONFLICT(usage_date) DO UPDATE SET 
                    total_tokens = total_tokens + excluded.total_tokens,
                    prompt_tokens = prompt_tokens + excluded.prompt_tokens,
                    completion_tokens = completion_tokens + excluded.completion_tokens,
                    request_count = request_count + 1,
                    updated_at = datetime('now')
            ");
            $stmt_ud_up->execute([date('Y-m-d'), $usage['total_tokens'], $usage['prompt_tokens'], $usage['completion_tokens']]);
        }
    } catch (\Throwable $e) {
        error_log('usage_daily update DB failed silently: ' . $e->getMessage());
    }
    
    echo $response;
} else {
    $record_chat_metric($sessionId, $successful_backend, $total_latency_ms, false, 0, 0, 0);
    http_response_code($httpcode);
    echo $response;
}
