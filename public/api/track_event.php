<?php
/**
 * track_event.php — Public write-only endpoint for Spec 018 UI Micro-Interactions
 *
 * REGLAS DE SEGURIDAD Y PRIVACIDAD:
 * - 0-LLM (§5): No se llama a ningún LLM.
 * - PII (§6): Redacta correos y teléfonos de campos de texto libre antes de guardar.
 * - Same-Origin: Solo acepta solicitudes originadas en el dominio app.datanestiq.com o local.
 * - Rate Limiting: Máximo 60 peticiones de micro-interacciones por IP cada 10 minutos para evitar flooding.
 * - Write-Only: Prohibido listar, consultar o filtrar eventos desde el cliente (GET retorna 405).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 🛡️ SEGURIDAD §6: Bloquear solicitudes que no sean POST (Write-Only)
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 🛡️ SEGURIDAD §6: Same-Origin Check (Referer / Origin validation)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$is_valid_origin = false;

$allowed_hosts = ['app.datanestiq.com', 'localhost', '127.0.0.1'];
foreach ($allowed_hosts as $host) {
    if (strpos($origin, $host) !== false || strpos($referer, $host) !== false) {
        $is_valid_origin = true;
        break;
    }
}

if (!$is_valid_origin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Invalid origin']);
    exit;
}

// 🛡️ SEGURIDAD §6: Rate Limiting por IP (Máximo 60 eventos / 10 minutos)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_file = sys_get_temp_dir() . '/rate_limit_micro_' . md5($ip) . '.json';
$rate_limit_threshold = 60;
$rate_limit_window = 600; // 10 minutos

if (is_writable(sys_get_temp_dir())) {
    $current_time = time();
    $rate_data = [];
    if (file_exists($rate_limit_file)) {
        $content = @file_get_contents($rate_limit_file);
        if ($content !== false) {
            $rate_data = json_decode($content, true) ?: [];
        }
    }
    
    // Filtrar solicitudes fuera de la ventana
    $rate_data = array_filter($rate_data, function($timestamp) use ($current_time, $rate_limit_window) {
        return ($current_time - $timestamp) < $rate_limit_window;
    });
    
    if (count($rate_data) >= $rate_limit_threshold) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    $rate_data[] = $current_time;
    @file_put_contents($rate_limit_file, json_encode($rate_data));
}

// Leer payload JSON o fallback a POST tradicional
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$session_id = trim($input['session_id'] ?? '');
$event_type = trim($input['event_type'] ?? '');
$event_target = trim($input['event_target'] ?? '');
$event_value = trim($input['event_value'] ?? '');

// Validación básica
if (empty($session_id) || empty($event_type) || empty($event_target)) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: Missing parameters']);
    exit;
}

// 🛡️ SEGURIDAD §6: Validar formato del session_id (alfanumérico y mínimo 32 caracteres)
if (!preg_match('/^[a-zA-Z0-9]{32,}$/', $session_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: Invalid session_id format']);
    exit;
}

// 🛡️ SEGURIDAD §6: Redacción PII para texto libre antes de guardar en SQLite
if (in_array($event_type, ['search_query', 'chatbot_step'])) {
    $event_value = redactPii($event_value);
}

// Función de saneamiento de PII
function redactPii($text) {
    try {
        // Redactar Emails
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
        // Redactar Teléfonos
        $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[PHONE_REDACTED]', $text);
        return $text;
    } catch (Exception $e) {
        // Guardarraíl fail-closed: Si la redacción falla, borramos el valor
        return '[REDACTION_ERROR]';
    }
}

// Guardar en la base de datos de manera fail-safe
$db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    // Fallback para desarrollo local (dentro de htdocs)
    $db_path = __DIR__ . '/../secure_leads/crm.sqlite';
}

try {
    if (!file_exists($db_path)) {
        throw new Exception("CRM Database not found");
    }

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("INSERT INTO interaction_events (session_id, event_type, event_target, event_value) VALUES (?, ?, ?, ?)");
    $stmt->execute([$session_id, $event_type, $event_target, $event_value]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // 0 UX degradation: Retornamos éxito simulado si falla la escritura para que no afecte al cliente
    http_response_code(200);
    echo json_encode(['status' => 'success', 'warning' => 'silenced_error']);
}
