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

// Recorrido del usuario (journey path) — enviado por Chatbot.jsx en confirmLead
$journey = isset($data['journey']) ? $data['journey'] : [];
$source = isset($data['source']) ? substr(strip_tags($data['source']), 0, 50) : 'chatbot';

// --- 1. Persistencia SQLite (primaria, Spec 014) ---
$sqlite_ok = false;
$db_path = __DIR__ . '/../../secure_leads/crm.sqlite';

if (file_exists($db_path)) {
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');

        $db->beginTransaction();

        // Upsert lead (INSERT OR UPDATE por session_id)
        $existing = $db->prepare("SELECT id FROM leads WHERE session_id = ?");
        $existing->execute([$session_id]);
        $lead_id = $existing->fetchColumn();

        if ($lead_id) {
            $upd = $db->prepare("UPDATE leads SET email = ?, telefono = ?, nombre = ?, organizacion = ?, reto = ?, stack = ?, score = ?, updated_at = datetime('now') WHERE id = ?");
            $upd->execute([$email, $telefono, $nombre, $organizacion, $reto, $stack, $score, $lead_id]);
        } else {
            $ins = $db->prepare("INSERT INTO leads (session_id, email, telefono, nombre, organizacion, reto, stack, score, source, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'nuevo', datetime('now'), datetime('now'))");
            $ins->execute([$session_id, $email, $telefono, $nombre, $organizacion, $reto, $stack, $score, $source]);
            $lead_id = (int)$db->lastInsertId();
        }

        // Guardar interacciones del recorrido
        if (is_array($journey) && count($journey) > 0) {
            $int_ins = $db->prepare("INSERT INTO interactions (lead_id, interaction_type, content, created_at) VALUES (?, ?, ?, datetime('now'))");
            foreach ($journey as $step) {
                $type = is_array($step) ? ($step['type'] ?? 'flow_step') : 'flow_step';
                $content = is_array($step) ? json_encode($step) : (string)$step;
                $int_ins->execute([$lead_id, $type, $content]);
            }
        }

        $db->commit();
        $sqlite_ok = true;

    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        error_log('save_wizard SQLite error: ' . $e->getMessage());
        // Continúa al CSV como fallback
    }
}

// --- 2. CSV fallback (heredado — se retirará cuando SQLite esté validado en prod) ---
$csv_file = __DIR__ . '/../../secure_leads/leads_wizard.csv';
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
}

echo json_encode(['status' => 'success', 'sqlite' => $sqlite_ok]);
