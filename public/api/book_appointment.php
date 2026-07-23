<?php
/**
 * book_appointment.php — Reserva de citas (Spec 015)
 * Endpoint público. Anti-doble-booking transaccional.
 * Zona horaria: America/Lima
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !preg_match('/https?:\/\/(localhost|.*datanestiq\.com)(:[0-9]+)?$/', $origin)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden origin']);
    exit;
}
if ($origin) header("Access-Control-Allow-Origin: $origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

date_default_timezone_set('America/Lima');

// Cargar .env
function loadEnvBook($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) putenv(trim($parts[0]) . '=' . trim($parts[1]));
    }
}
loadEnvBook(__DIR__ . '/../../.env');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No payload']);
    exit;
}

$session_id = substr(strip_tags($input['session_id'] ?? ''), 0, 100);
$email = substr(strip_tags($input['email'] ?? ''), 0, 200);
$requested_date = $input['requested_date'] ?? '';
$type = in_array($input['type'] ?? '', ['diagnostico', 'reunion']) ? $input['type'] : 'diagnostico';

if (!$requested_date || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and requested_date are required']);
    exit;
}

// Validar formato de fecha
$dt = DateTime::createFromFormat('Y-m-d H:i:s', $requested_date);
if (!$dt) {
    $dt = DateTime::createFromFormat('Y-m-d H:i', $requested_date);
    if ($dt) $requested_date = $dt->format('Y-m-d H:i:s');
}
if (!$dt) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use: YYYY-MM-DD HH:MM']);
    exit;
}

// No agendar en el pasado
if ($dt <= new DateTime()) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot book in the past']);
    exit;
}

$db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'System not initialized']);
    exit;
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    $db->beginTransaction();

    // Anti-doble-booking: verificar que el slot no esté ocupado
    $check = $db->prepare("SELECT COUNT(*) FROM appointments WHERE requested_date = ? AND status IN ('solicitada','confirmada')");
    $check->execute([$requested_date]);
    if ((int)$check->fetchColumn() > 0) {
        $db->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'Este horario ya no está disponible. Por favor elige otro.']);
        exit;
    }

    // Buscar o crear lead
    $lead_id = null;
    if ($session_id) {
        $find = $db->prepare("SELECT id FROM leads WHERE session_id = ?");
        $find->execute([$session_id]);
        $lead_id = $find->fetchColumn();
    }

    if (!$lead_id) {
        // Crear lead mínimo
        $ins = $db->prepare("INSERT INTO leads (session_id, email, status, source, created_at, updated_at) VALUES (?, ?, 'cita_solicitada', 'appointment', datetime('now'), datetime('now'))");
        $ins->execute([$session_id ?: ('appt_' . bin2hex(random_bytes(8))), $email]);
        $lead_id = (int)$db->lastInsertId();
    } else {
        // Actualizar estado del lead existente
        $db->prepare("UPDATE leads SET status = 'cita_solicitada', updated_at = datetime('now') WHERE id = ?")->execute([$lead_id]);
    }

    // Registrar status change
    $db->prepare("INSERT INTO status_history (lead_id, old_status, new_status, changed_by) VALUES (?, (SELECT status FROM leads WHERE id = ?), 'cita_solicitada', 'system')")
       ->execute([$lead_id, $lead_id]);

    // Crear la cita
    $appt = $db->prepare("INSERT INTO appointments (lead_id, session_id, requested_date, type, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'solicitada', datetime('now'), datetime('now'))");
    $appt->execute([$lead_id, $session_id, $requested_date, $type]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tu solicitud de cita ha sido registrada. Un arquitecto de datos te confirmará a la brevedad.',
        'appointment_id' => (int)$db->lastInsertId(),
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
    error_log('Booking error: ' . $e->getMessage());
}
