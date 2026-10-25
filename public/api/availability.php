<?php
/**
 * availability.php — Devuelve slots disponibles para agendamiento (Spec 015)
 * Endpoint público (sin auth), solo lectura. Zona horaria: America/Lima
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

date_default_timezone_set('America/Lima');

// Cargar .env
function loadEnvAvail($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) putenv(trim($parts[0]) . '=' . trim($parts[1]));
    }
}
loadEnvAvail(__DIR__ . '/../../.env');

$db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'System not initialized']);
    exit;
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener configuración de disponibilidad
    $config = $db->query("SELECT * FROM availability_config WHERE is_active = 1 ORDER BY day_of_week")->fetchAll(PDO::FETCH_ASSOC);

    // Obtener fechas bloqueadas
    $blocked = $db->query("SELECT blocked_date FROM blocked_dates")->fetchAll(PDO::FETCH_COLUMN);

    // Obtener citas ya tomadas (solicitada o confirmada) para los próximos 14 días
    $from = date('Y-m-d');
    $to = date('Y-m-d', strtotime('+14 days'));
    $taken_stmt = $db->prepare("SELECT requested_date FROM appointments WHERE status IN ('solicitada','confirmada') AND requested_date >= ? AND requested_date < ?");
    $taken_stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
    $taken_slots = $taken_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Generar slots disponibles
    $available = [];
    $current = new DateTime($from);
    $end = new DateTime($to);

    while ($current <= $end) {
        $date_str = $current->format('Y-m-d');
        $dow = (int)$current->format('N'); // 1=Lunes, 7=Domingo

        // Verificar si está bloqueado
        if (in_array($date_str, $blocked)) {
            $current->modify('+1 day');
            continue;
        }

        // Buscar config para este día
        foreach ($config as $c) {
            if ((int)$c['day_of_week'] !== $dow) continue;

            $start = new DateTime($date_str . ' ' . $c['start_time']);
            $slot_end = new DateTime($date_str . ' ' . $c['end_time']);
            $duration = (int)$c['slot_duration_minutes'];

            while ($start < $slot_end) {
                $slot_str = $start->format('Y-m-d H:i:s');

                // No ofrecer slots en el pasado
                if ($start <= new DateTime()) {
                    $start->modify("+{$duration} minutes");
                    continue;
                }

                // Verificar si está tomado
                if (!in_array($slot_str, $taken_slots)) {
                    $available[] = [
                        'date' => $start->format('Y-m-d'),
                        'time' => $start->format('H:i'),
                        'datetime' => $slot_str,
                    ];
                }

                $start->modify("+{$duration} minutes");
            }
        }

        $current->modify('+1 day');
    }

    echo json_encode([
        'timezone' => 'America/Lima',
        'slots' => $available,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
    error_log('Availability error: ' . $e->getMessage());
}
