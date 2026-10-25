<?php
/**
 * experience_hit.php — Incrementa usage_count al servir una respuesta 0-LLM (Residual R1)
 * Restringido estrictamente a método POST (§2 Secundario Fix)
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    $db_path = __DIR__ . '/../secure_leads/crm.sqlite';
}

try {
    if (!file_exists($db_path)) {
        echo json_encode(['success' => false]);
        exit;
    }

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("UPDATE experience_bank SET usage_count = usage_count + 1 WHERE id = :exp_id AND status = 'aprobado'");
    $stmt->execute([':exp_id' => $id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
    error_log('experience_hit error: ' . $e->getMessage());
}
