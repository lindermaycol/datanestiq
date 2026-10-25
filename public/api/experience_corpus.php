<?php
/**
 * experience_corpus.php — Endpoint público solo-lectura de experiencias aprobadas
 * Consumido por Chatbot.jsx al iniciar para indexar en Web Worker (0-LLM)
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    $db_path = __DIR__ . '/../secure_leads/crm.sqlite';
}

try {
    if (!file_exists($db_path)) {
        echo json_encode(['total' => 0, 'experiences' => []]);
        exit;
    }

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, question_redacted, answer, cite_url 
            FROM experience_bank 
            WHERE status = 'aprobado' AND expires_at > datetime('now') 
            ORDER BY id ASC";
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $experiences = [];
    foreach ($rows as $r) {
        $item = [
            'id' => (int)$r['id'],
            'question' => $r['question_redacted'],
            'answer' => $r['answer']
        ];
        if (!empty($r['cite_url'])) {
            $item['cite_url'] = $r['cite_url'];
        }
        $experiences[] = $item;
    }

    echo json_encode([
        'total' => count($experiences),
        'experiences' => $experiences
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log('experience_corpus error: ' . $e->getMessage());
}
