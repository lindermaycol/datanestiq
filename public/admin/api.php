<?php
/**
 * api.php — API interna del CRM (CRUD leads, interactions, appointments)
 * Seguridad: IP whitelist + sesión autenticada + CSRF en POSTs
 */
require_once __DIR__ . '/auth.php';

// Verificar autenticación en CADA request
requireAuth();

header('Content-Type: application/json');

$db = getCrmDb();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// --- CSRF para POST/PUT/DELETE ---
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $csrf = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token invalid']);
        exit;
    }
}

try {
    switch ($action) {

        // --- LEADS ---
        case 'leads':
            if ($method === 'GET') {
                $status = $_GET['status'] ?? '';
                $search = $_GET['search'] ?? '';
                $page = max(1, (int)($_GET['page'] ?? 1));
                $per_page = 25;
                $offset = ($page - 1) * $per_page;

                $where = [];
                $params = [];

                if ($status) {
                    $where[] = 'l.status = :status';
                    $params[':status'] = $status;
                }
                if ($search) {
                    $where[] = '(l.email LIKE :search OR l.organizacion LIKE :search OR l.reto LIKE :search)';
                    $params[':search'] = '%' . $search . '%';
                }

                $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

                // Count
                $count_stmt = $db->prepare("SELECT COUNT(*) FROM leads l $where_sql");
                $count_stmt->execute($params);
                $total = (int)$count_stmt->fetchColumn();

                // Data
                $stmt = $db->prepare("SELECT l.*, 
                    (SELECT COUNT(*) FROM appointments a WHERE a.lead_id = l.id) as appointment_count
                    FROM leads l $where_sql 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit OFFSET :offset");
                foreach ($params as $k => $v) {
                    $stmt->bindValue($k, $v);
                }
                $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();

                echo json_encode([
                    'leads' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page,
                    'csrf_token' => generateCsrfToken(),
                ]);
            }
            break;

        case 'lead_detail':
            if ($method === 'GET') {
                $id = (int)($_GET['id'] ?? 0);
                if (!$id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID required']);
                    exit;
                }

                $lead = $db->prepare("SELECT * FROM leads WHERE id = ?");
                $lead->execute([$id]);
                $lead_data = $lead->fetch(PDO::FETCH_ASSOC);

                if (!$lead_data) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Lead not found']);
                    exit;
                }

                $interactions = $db->prepare("SELECT * FROM interactions WHERE lead_id = ? ORDER BY created_at ASC");
                $interactions->execute([$id]);

                $history = $db->prepare("SELECT * FROM status_history WHERE lead_id = ? ORDER BY created_at DESC");
                $history->execute([$id]);

                $appointments = $db->prepare("SELECT * FROM appointments WHERE lead_id = ? ORDER BY requested_date DESC");
                $appointments->execute([$id]);

                echo json_encode([
                    'lead' => $lead_data,
                    'interactions' => $interactions->fetchAll(PDO::FETCH_ASSOC),
                    'status_history' => $history->fetchAll(PDO::FETCH_ASSOC),
                    'appointments' => $appointments->fetchAll(PDO::FETCH_ASSOC),
                    'csrf_token' => generateCsrfToken(),
                ]);
            }
            break;

        case 'update_lead':
            if ($method === 'POST') {
                $id = (int)($input['id'] ?? 0);
                $new_status = $input['status'] ?? '';
                $next_action = $input['next_action'] ?? '';
                $notes = $input['notes'] ?? '';

                if (!$id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID required']);
                    exit;
                }

                $valid_statuses = ['nuevo', 'contactado', 'cita_solicitada', 'ganado', 'perdido', 'no_interesado'];

                // Get current status
                $current = $db->prepare("SELECT status FROM leads WHERE id = ?");
                $current->execute([$id]);
                $old_status = $current->fetchColumn();

                if (!$old_status) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Lead not found']);
                    exit;
                }

                $db->beginTransaction();

                // Update lead
                $update = $db->prepare("UPDATE leads SET status = ?, next_action = ?, notes = ?, updated_at = datetime('now') WHERE id = ?");
                $update->execute([$new_status ?: $old_status, $next_action, $notes, $id]);

                // Record status change
                if ($new_status && $new_status !== $old_status) {
                    $hist = $db->prepare("INSERT INTO status_history (lead_id, old_status, new_status, changed_by) VALUES (?, ?, ?, 'admin')");
                    $hist->execute([$id, $old_status, $new_status]);
                }

                $db->commit();
                echo json_encode(['success' => true]);
            }
            break;

        // --- APPOINTMENTS ---
        case 'update_appointment':
            if ($method === 'POST') {
                $id = (int)($input['id'] ?? 0);
                $new_status = $input['status'] ?? '';

                if (!$id || !$new_status) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID and status required']);
                    exit;
                }

                $valid = ['solicitada', 'confirmada', 'cancelada'];
                if (!in_array($new_status, $valid)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid appointment status']);
                    exit;
                }

                $stmt = $db->prepare("UPDATE appointments SET status = ?, updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$new_status, $id]);

                // If confirmed, update lead status
                if ($new_status === 'confirmada') {
                    $appt = $db->prepare("SELECT lead_id FROM appointments WHERE id = ?");
                    $appt->execute([$id]);
                    $lead_id = $appt->fetchColumn();
                    if ($lead_id) {
                        $db->prepare("UPDATE leads SET status = 'cita_solicitada', updated_at = datetime('now') WHERE id = ?")->execute([$lead_id]);
                    }
                }

                echo json_encode(['success' => true]);
            }
            break;

        // --- AVAILABILITY CONFIG ---
        case 'availability_config':
            if ($method === 'GET') {
                $stmt = $db->query("SELECT * FROM availability_config ORDER BY day_of_week ASC");
                $blocked = $db->query("SELECT * FROM blocked_dates ORDER BY blocked_date ASC");
                echo json_encode([
                    'slots' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    'blocked_dates' => $blocked->fetchAll(PDO::FETCH_ASSOC),
                    'csrf_token' => generateCsrfToken(),
                ]);
            }
            break;

        case 'update_availability':
            if ($method === 'POST') {
                $slots = $input['slots'] ?? [];
                $db->exec("DELETE FROM availability_config");
                $ins = $db->prepare("INSERT INTO availability_config (day_of_week, start_time, end_time, slot_duration_minutes, is_active) VALUES (?, ?, ?, ?, ?)");
                foreach ($slots as $s) {
                    $ins->execute([$s['day_of_week'], $s['start_time'], $s['end_time'], $s['slot_duration_minutes'] ?? 30, $s['is_active'] ?? 1]);
                }
                echo json_encode(['success' => true]);
            }
            break;

        case 'block_date':
            if ($method === 'POST') {
                $date = $input['date'] ?? '';
                $reason = $input['reason'] ?? '';
                if (!$date) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Date required']);
                    exit;
                }
                $stmt = $db->prepare("INSERT OR IGNORE INTO blocked_dates (blocked_date, reason) VALUES (?, ?)");
                $stmt->execute([$date, $reason]);
                echo json_encode(['success' => true]);
            }
            break;

        // --- STATS ---
        case 'stats':
            $stats = [];
            foreach (['nuevo', 'contactado', 'cita_solicitada', 'ganado', 'perdido', 'no_interesado'] as $s) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM leads WHERE status = ?");
                $stmt->execute([$s]);
                $stats[$s] = (int)$stmt->fetchColumn();
            }
            $total = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            $appts = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'solicitada'")->fetchColumn();

            echo json_encode([
                'status_counts' => $stats,
                'total_leads' => (int)$total,
                'pending_appointments' => (int)$appts,
            ]);
            break;

        // --- ANALÍTICA DE CONVERSIÓN Y LOOP LEARN (Spec 016) ---
        case 'analytics_funnel':
            if ($method === 'GET') {
                $total_leads = (int)$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
                $funnel = [];
                $statuses = ['nuevo', 'contactado', 'cita_solicitada', 'ganado', 'perdido', 'no_interesado'];
                
                foreach ($statuses as $st) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM leads WHERE status = ?");
                    $stmt->execute([$st]);
                    $count = (int)$stmt->fetchColumn();
                    $pct = $total_leads > 0 ? round(($count / $total_leads) * 100, 2) : 0;
                    $funnel[] = [
                        'status' => $st,
                        'count' => $count,
                        'percentage' => $pct
                    ];
                }

                // Citas solicitadas o confirmadas
                $citas_count = (int)$db->query("SELECT COUNT(*) FROM leads WHERE status IN ('cita_solicitada', 'ganado')")->fetchColumn();
                $citas_pct = $total_leads > 0 ? round(($citas_count / $total_leads) * 100, 2) : 0;

                // Clientes ganados
                $ganados_count = (int)$db->query("SELECT COUNT(*) FROM leads WHERE status = 'ganado'")->fetchColumn();
                $ganados_pct = $total_leads > 0 ? round(($ganados_count / $total_leads) * 100, 2) : 0;

                // Tiempo promedio de permanencia por etapa (en horas)
                $stmt_transitions = $db->query("SELECT 
                    old_status, new_status, 
                    AVG((julianday(created_at) - julianday(COALESCE((SELECT created_at FROM status_history sh2 WHERE sh2.lead_id = sh.lead_id AND sh2.id < sh.id ORDER BY id DESC LIMIT 1), (SELECT created_at FROM leads WHERE id = sh.lead_id)))) * 24) as avg_hours
                    FROM status_history sh
                    WHERE old_status IS NOT NULL
                    GROUP BY old_status, new_status");
                $transitions = $stmt_transitions ? $stmt_transitions->fetchAll(PDO::FETCH_ASSOC) : [];

                echo json_encode([
                    'total_leads' => $total_leads,
                    'funnel' => $funnel,
                    'tasa_citas_pct' => $citas_pct,
                    'tasa_ganados_pct' => $ganados_pct,
                    'transiciones_avg_horas' => $transitions,
                ]);
            }
            break;

        case 'analytics_by_dimension':
            if ($method === 'GET') {
                $stmt = $db->query("SELECT 
                    CASE WHEN sector IS NULL OR sector = '' THEN 'no_especificado' ELSE sector END as sector_name,
                    CASE WHEN rol IS NULL OR rol = '' THEN 'no_especificado' ELSE rol END as rol_name,
                    COUNT(id) as total_leads,
                    SUM(CASE WHEN status = 'ganado' THEN 1 ELSE 0 END) as ganados,
                    SUM(CASE WHEN status IN ('cita_solicitada', 'ganado') THEN 1 ELSE 0 END) as citas,
                    ROUND(SUM(CASE WHEN status = 'ganado' THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) as tasa_ganados_pct
                    FROM leads
                    GROUP BY sector_name, rol_name
                    ORDER BY total_leads DESC");
                $breakdown = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

                echo json_encode([
                    'dimensions' => $breakdown
                ]);
            }
            break;

        case 'analytics_llm_metrics':
            if ($method === 'GET') {
                $total_calls = (int)$db->query("SELECT COUNT(*) FROM chat_metrics")->fetchColumn();
                $stmt = $db->query("SELECT 
                    backend_used,
                    COUNT(id) as calls,
                    ROUND(AVG(latency_ms), 0) as avg_latency_ms,
                    ROUND(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 1) as success_rate_pct,
                    SUM(tokens_est) as total_tokens
                    FROM chat_metrics
                    GROUP BY backend_used
                    ORDER BY calls DESC");
                $backends = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

                $avg_latency_total = (int)$db->query("SELECT ROUND(AVG(latency_ms), 0) FROM chat_metrics")->fetchColumn();

                echo json_encode([
                    'total_calls' => $total_calls,
                    'avg_latency_ms' => $avg_latency_total,
                    'backends' => $backends
                ]);
            }
            break;

        case 'lead_chat_history':
            if ($method === 'GET') {
                $session_id = $_GET['session_id'] ?? '';
                if (!$session_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'session_id required']);
                    exit;
                }
                
                // Buscar interacciones asociadas a session_id
                $stmt = $db->prepare("SELECT i.* FROM interactions i JOIN leads l ON i.lead_id = l.id WHERE l.session_id = ? ORDER BY i.created_at ASC");
                $stmt->execute([$session_id]);
                $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar métricas de LLM asociadas
                $stmt_m = $db->prepare("SELECT * FROM chat_metrics WHERE session_id = ? ORDER BY created_at ASC");
                $stmt_m->execute([$session_id]);
                $metrics = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'session_id' => $session_id,
                    'interactions' => $interactions,
                    'metrics' => $metrics
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    // Log sin PII
    error_log('CRM API error: ' . $e->getMessage());
}
