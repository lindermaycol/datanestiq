<?php
/**
 * Extraer Leads Batch Script (Spec 022 File-Free)
 * Procesa la tabla privada chat_raw de SQLite, extrae información usando LLM
 * y guarda de forma idempotente en la tabla leads_extracted.
 * Uso: php scripts/extraer_leads.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    die("Error: Base de datos no encontrada en $db_path.\n");
}

function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

loadEnv(__DIR__ . '/../.env');
$groq_api_key = getenv('GROQ_API_KEY');

if (!$groq_api_key) {
    die("Error: GROQ_API_KEY no configurada.\n");
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener session_ids de chat_raw no procesados en leads_extracted
    $stmt_sessions = $db->query("
        SELECT DISTINCT cr.session_id 
        FROM chat_raw cr 
        LEFT JOIN leads_extracted le ON cr.session_id = le.session_id 
        WHERE le.id IS NULL
    ");
    $pending_sessions = $stmt_sessions->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pending_sessions)) {
        echo "No hay sesiones pendientes de análisis en chat_raw.\n";
        exit;
    }

    echo "Analizando " . count($pending_sessions) . " sesiones pendientes en SQLite...\n";
    $new_leads_count = 0;

    $stmt_msgs = $db->prepare("SELECT role, content_raw FROM chat_raw WHERE session_id = ? ORDER BY id ASC");
    $stmt_insert_lead = $db->prepare("INSERT OR IGNORE INTO leads_extracted (session_id, nombre, email, telefono, intencion, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))");

    foreach ($pending_sessions as $sid) {
        $stmt_msgs->execute([$sid]);
        $rows = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);

        $conversation = "";
        foreach ($rows as $r) {
            $conversation .= strtoupper($r['role']) . ": " . $r['content_raw'] . "\n";
        }

        echo "Analizando sesión: $sid...\n";

        // Call Groq API
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $groq_api_key,
            'Content-Type: application/json'
        ]);

        $prompt = "Eres un extractor de datos de conversaciones. Analiza la siguiente conversacion y extrae en formato JSON exacto las siguientes claves: 'nombre', 'email', 'telefono', 'intencion'. Si un dato no esta, pon null.\n\nConversacion:\n" . $conversation;

        $payload = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'system', 'content' => 'Responde SOLAMENTE con un objeto JSON valido. Sin texto markdown adicional.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        curl_close($ch);

        $res_data = json_decode($response, true);
        $content = $res_data['choices'][0]['message']['content'] ?? '';
        
        $content = str_replace(['```json', '```'], '', $content);
        $extracted = json_decode(trim($content), true);

        if (is_array($extracted) && (!empty($extracted['email']) || !empty($extracted['telefono']))) {
            $stmt_insert_lead->execute([
                $sid,
                $extracted['nombre'] ?? '',
                $extracted['email'] ?? '',
                $extracted['telefono'] ?? '',
                $extracted['intencion'] ?? ''
            ]);
            echo "✅ Lead encontrado e insertado en SQLite: " . ($extracted['email'] ?? $extracted['telefono']) . "\n";
            $new_leads_count++;
        } else {
            echo "  No hay lead claro en esta sesion.\n";
        }
    }

    echo "\nProceso completado. Se insertaron $new_leads_count leads en leads_extracted.\n";

} catch (Exception $e) {
    die("Error durante la extracción: " . $e->getMessage() . "\n");
}

