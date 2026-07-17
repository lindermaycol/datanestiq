<?php
// save_wizard.php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Basic CORS to only allow datanestiq.com or localhost
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !preg_match('/https?:\/\/(localhost|.*datanestiq\.com)(:[0-9]+)?$/', $origin)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden origin']);
    exit;
}

$input = file_get_contents('php://input');
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No payload']);
    exit;
}

$data = json_decode($input, true);
if (!$data || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Security: limit input lengths
$email = substr(strip_tags($data['email']), 0, 100);
$nombre = isset($data['nombre']) ? substr(strip_tags($data['nombre']), 0, 100) : '';
$organizacion = isset($data['organizacion']) ? substr(strip_tags($data['organizacion']), 0, 100) : '';
$telefono = isset($data['telefono']) ? substr(strip_tags($data['telefono']), 0, 50) : '';
$reto = isset($data['reto']) ? substr(strip_tags($data['reto']), 0, 500) : '';
$stack = isset($data['stack']) ? substr(strip_tags($data['stack']), 0, 500) : '';
$score = isset($data['score']) ? (int)$data['score'] : 0;
$session_id = isset($data['session_id']) ? substr(strip_tags($data['session_id']), 0, 50) : 'unknown';

// We write to the secure directory, OUTSIDE of public/
// public/api/save_wizard.php -> ../../secure_leads/leads_wizard.csv
$csv_file = __DIR__ . '/../../secure_leads/leads_wizard.csv';

// If file doesn't exist, create it and write headers
$is_new = !file_exists($csv_file);
$fp = fopen($csv_file, 'a');
if ($fp) {
    if ($is_new) {
        fputcsv($fp, ['Timestamp', 'SessionID', 'Email', 'Nombre', 'Telefono', 'Organizacion', 'Reto', 'Stack', 'Score']);
    }
    fputcsv($fp, [
        date('c'),
        $session_id,
        $email,
        $nombre,
        $telefono,
        $organizacion,
        $reto,
        $stack,
        $score
    ]);
    fclose($fp);
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error', 'details' => 'Could not write lead.']);
}
