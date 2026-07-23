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
