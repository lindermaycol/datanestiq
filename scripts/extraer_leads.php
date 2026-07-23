<?php
/**
 * Extraer Leads Batch Script
 * Procesa chat_raw.jsonl, extrae información usando LLM y guarda en leads_datanestiq.csv
 * Uso: php extraer_leads.php
 */

$raw_log = __DIR__ . '/../secure_leads/chat_raw.jsonl';
$csv_out = __DIR__ . '/../secure_leads/leads_datanestiq.csv';

// Simple .env parser
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

if (!file_exists($raw_log)) {
    die("No hay archivo raw log: $raw_log\n");
}

$is_new_csv = !file_exists($csv_out);
$fp_csv = fopen($csv_out, 'a');
if ($is_new_csv) {
    fputcsv($fp_csv, ['SessionID', 'Nombre', 'Email', 'Telefono', 'Intencion']);
}

// Track procesados para evitar re-procesar (simple cache)
$processed_file = __DIR__ . '/../secure_leads/.processed_sessions';
$processed = file_exists($processed_file) ? json_decode(file_get_contents($processed_file), true) : [];
if (!is_array($processed)) $processed = [];

$lines = file($raw_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$new_leads_count = 0;

foreach ($lines as $line) {
    $data = json_decode($line, true);
    if (!$data || !isset($data['session_id'])) continue;
    
    $sid = $data['session_id'];
    
    // Skip si ya procesamos esta sesion y sabemos que tiene todos los datos,
    // o simplemente reprocesamos si queremos (aqui para simplificar saltamos)
    if (in_array($sid, $processed)) {
        continue;
    }

    $messages = $data['messages'];
    // Concatenate user messages to analyze
    $conversation = "";
    foreach ($messages as $m) {
        $role = $m['role'] ?? 'user';
        $content = $m['content'] ?? '';
        $conversation .= strtoupper($role) . ": " . $content . "\n";
    }

    echo "Analizando sesion: $sid...\n";

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
    
    // Limpiar markdown si el modelo lo agrega
    $content = str_replace(['```json', '```'], '', $content);
    $extracted = json_decode(trim($content), true);

    if (is_array($extracted) && (!empty($extracted['email']) || !empty($extracted['telefono']))) {
        fputcsv($fp_csv, [
            $sid,
            $extracted['nombre'] ?? '',
            $extracted['email'] ?? '',
            $extracted['telefono'] ?? '',
            $extracted['intencion'] ?? ''
        ]);
        echo "Lead encontrado y guardado: " . ($extracted['email'] ?? $extracted['telefono']) . "\n";
        $processed[] = $sid;
        $new_leads_count++;
    } else {
        echo "No hay lead claro en esta sesion.\n";
    }
}

fclose($fp_csv);
file_put_contents($processed_file, json_encode($processed));

echo "Proceso terminado. Se agregaron $new_leads_count leads nuevos.\n";
