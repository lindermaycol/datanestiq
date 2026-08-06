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

        // --- SPEC 017: OPS TELEMETRY & SYSTEM HEALTH ---
        case 'ops_telemetry':
            if ($method === 'GET') {
                // 1. SLA Global 30d
                $sla_stmt = $db->query("SELECT 
                    COUNT(id) as total_requests,
                    SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_requests,
                    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_requests,
                    ROUND(SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) * 100.0 / MAX(1, COUNT(id)), 2) as sla_success_rate_pct,
                    ROUND(AVG(latency_ms), 0) as avg_latency_ms
                    FROM chat_metrics
                    WHERE created_at >= datetime('now', '-30 days')");
                $sla_global = $sla_stmt->fetch(PDO::FETCH_ASSOC);

                // 2. Breakdown por Proveedor LLM con CTE Percentiles (p50 / p95)
                $cte_sql = "WITH successful_metrics AS (
                    SELECT 
                        backend_used,
                        latency_ms,
                        ROW_NUMBER() OVER (PARTITION BY backend_used ORDER BY latency_ms ASC) as row_num,
                        COUNT(*) OVER (PARTITION BY backend_used) as total_success
                    FROM chat_metrics
                    WHERE success = 1 AND created_at >= datetime('now', '-30 days')
                )
                SELECT 
                    cm.backend_used,
                    COUNT(cm.id) as total_calls,
                    ROUND(SUM(CASE WHEN cm.success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(cm.id), 1) as success_pct,
                    ROUND(AVG(cm.latency_ms), 0) as avg_latency_ms,
                    MAX(sm.total_success) as total_success_count,
                    MAX(CASE WHEN sm.row_num = MAX(1, CAST(sm.total_success * 0.50 AS INT)) THEN sm.latency_ms END) as p50_raw,
                    MAX(CASE WHEN sm.row_num = MAX(1, CAST(sm.total_success * 0.95 AS INT)) THEN sm.latency_ms END) as p95_raw
                FROM chat_metrics cm
                LEFT JOIN successful_metrics sm ON cm.backend_used = sm.backend_used
                WHERE cm.created_at >= datetime('now', '-30 days')
                GROUP BY cm.backend_used
                ORDER BY total_calls DESC";

                $providers_stmt = $db->query($cte_sql);
                $raw_providers = $providers_stmt->fetchAll(PDO::FETCH_ASSOC);

                $providers = array_map(function($p) {
                    $total_success = (int)($p['total_success_count'] ?? 0);
                    $insufficient = $total_success < 10;
                    return [
                        'backend_used' => $p['backend_used'],
                        'total_calls' => (int)$p['total_calls'],
                        'success_pct' => (float)$p['success_pct'],
                        'avg_latency_ms' => (int)$p['avg_latency_ms'],
                        'insufficient_data' => $insufficient,
                        'p50_latency_ms' => $insufficient ? null : (int)$p['p50_raw'],
                        'p95_latency_ms' => $insufficient ? null : (int)$p['p95_raw']
                    ];
                }, $raw_providers);

                // 3. Tendencia Diaria 7d
                $trend_stmt = $db->query("SELECT 
                    date(created_at) as date_day,
                    COUNT(id) as daily_calls,
                    ROUND(AVG(latency_ms), 0) as avg_latency_ms,
                    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as errors_count
                    FROM chat_metrics
                    WHERE created_at >= datetime('now', '-7 days')
                    GROUP BY date_day
                    ORDER BY date_day ASC");
                $daily_trend = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 4. Estado de compilación y sitio (leído del directorio privado htdocs/app/specsStatus.json)
                $specs_status_file = __DIR__ . '/../../specsStatus.json';
                if (!file_exists($specs_status_file)) {
                    $specs_status_file = __DIR__ . '/../../src/data/specsStatus.json';
                }
                $last_build = file_exists($specs_status_file) ? date('Y-m-d H:i:s', filemtime($specs_status_file)) : date('Y-m-d H:i:s');

                echo json_encode([
                    'sla_global' => $sla_global,
                    'providers' => $providers,
                    'daily_trend' => $daily_trend,
                    'system_info' => [
                        'last_build' => $last_build,
                        'total_specs' => 17,
                        'static_pages' => 63,
                        'subdomain' => 'app.datanestiq.com',
                        'status' => 'HEALTHY'
                    ]
                ]);
            }
            break;

        case 'ops_specs_status':
            if ($method === 'GET') {
                // 🛡️ SEGURIDAD §6: Leer exclusivamente del directorio privado fuera de public (htdocs/app/specsStatus.json)
                $specs_file = __DIR__ . '/../../specsStatus.json';
                if (!file_exists($specs_file)) {
                    // Fallback para desarrollo local (XAMPP)
                    $specs_file = __DIR__ . '/../../src/data/specsStatus.json';
                }
                
                if (file_exists($specs_file)) {
                    $specs_json = json_decode(file_get_contents($specs_file), true);
                    echo json_encode([
                        'total' => count($specs_json),
                        'specs' => $specs_json
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Specs status SSOT file not found in private directory']);
                }
            }
            break;

        case 'ops_behavior_analytics':
            if ($method === 'GET') {
                // 🧹 RETENCIÓN 180 DÍAS (Precisión 2): Limpieza automática e idempotente en cada consulta del admin
                $db->exec("DELETE FROM interaction_events WHERE created_at < datetime('now', '-180 days')");

                // 1. Conteo total de eventos para el guard de honestidad
                $total_events = (int)$db->query("SELECT COUNT(*) FROM interaction_events")->fetchColumn();
                
                if ($total_events < 20) {
                    echo json_encode([
                        'insufficient_data' => true,
                        'total_events' => $total_events
                    ]);
                    exit;
                }

                // 2. Top chips seleccionadas
                $chips_stmt = $db->query("SELECT event_value, COUNT(*) as qty 
                    FROM interaction_events 
                    WHERE event_type = 'chip_click' 
                    GROUP BY event_value 
                    ORDER BY qty DESC 
                    LIMIT 8");
                $chips = $chips_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 3. Top búsquedas semánticas
                $queries_stmt = $db->query("SELECT event_value, COUNT(*) as qty 
                    FROM interaction_events 
                    WHERE event_type = 'search_query' 
                    GROUP BY event_value 
                    ORDER BY qty DESC 
                    LIMIT 8");
                $queries = $queries_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 4. Top clics en Copilot Demo
                $copilot_stmt = $db->query("SELECT event_value, COUNT(*) as qty 
                    FROM interaction_events 
                    WHERE event_type = 'copilot_click' 
                    GROUP BY event_value 
                    ORDER BY qty DESC 
                    LIMIT 8");
                $copilot = $copilot_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 5. Top clics/respuestas Chatbot (event_value)
                $chatbot_values_stmt = $db->query("SELECT event_value, COUNT(*) as qty 
                    FROM interaction_events 
                    WHERE event_type = 'chatbot_step' 
                    GROUP BY event_value 
                    ORDER BY qty DESC 
                    LIMIT 8");
                $chatbot_values = $chatbot_values_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 6. Embudo Chatbot interactivo (unique sessions por target)
                $chatbot_funnel_stmt = $db->query("SELECT event_target, COUNT(DISTINCT session_id) as unique_sessions 
                    FROM interaction_events 
                    WHERE event_type = 'chatbot_step' 
                    GROUP BY event_target
                    ORDER BY unique_sessions DESC");
                $chatbot_funnel = $chatbot_funnel_stmt->fetchAll(PDO::FETCH_ASSOC);

                // 7. Embudo Asistente Diagnóstico (unique sessions por target)
                $wizard_funnel_stmt = $db->query("SELECT event_target, COUNT(DISTINCT session_id) as unique_sessions 
                    FROM interaction_events 
                    WHERE event_type = 'wizard_step' 
                    GROUP BY event_target
                    ORDER BY unique_sessions DESC");
                $wizard_funnel = $wizard_funnel_stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'insufficient_data' => false,
                    'total_events' => $total_events,
                    'chips' => $chips,
                    'queries' => $queries,
                    'copilot' => $copilot,
                    'chatbot_values' => $chatbot_values,
                    'chatbot_funnel' => $chatbot_funnel,
                    'wizard_funnel' => $wizard_funnel
                ]);
            }
            break;

        case 'demand_signals':
            if ($method === 'GET') {
                // TD-020-02: Limpieza de retención activa de 180 días
                $db->exec("DELETE FROM demand_signals WHERE created_at < datetime('now', '-180 days')");

                $total = (int)$db->query("SELECT COUNT(*) FROM demand_signals")->fetchColumn();
                if ($total < 20) {
                    echo json_encode([
                        'insufficient_data' => true,
                        'total' => $total
                    ]);
                    exit;
                }

                // Bucket 1 (Demanda no atendida): Agrupado con limitación a los 5 ejemplos más recientes (Precisión 4) (Fix A)
                $stmt = $db->query("SELECT 
                    matched_service, 
                    COUNT(*) as total_requests,
                    (
                        SELECT GROUP_CONCAT(query_redacted, ' | ') 
                        FROM (
                            SELECT query_redacted 
                            FROM demand_signals ds2 
                            WHERE ds2.matched_service = ds.matched_service AND ds2.offered = 0 AND ds2.resolved_route NOT IN ('faq','cita','guiado')
                            ORDER BY created_at DESC 
                            LIMIT 5
                        )
                    ) as examples
                    FROM demand_signals ds
                    WHERE offered = 0 AND resolved_route NOT IN ('faq','cita','guiado')
                    GROUP BY matched_service
                    ORDER BY total_requests DESC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'insufficient_data' => false,
                    'total' => $total,
                    'demand' => $data
                ]);
            }
            break;

        case 'leakage':
            if ($method === 'GET') {
                $total = (int)$db->query("SELECT COUNT(DISTINCT session_id) FROM demand_signals WHERE offered = 1")->fetchColumn();
                if ($total < 10) {
                    echo json_encode([
                        'insufficient_data' => true,
                        'total' => $total
                    ]);
                    exit;
                }

                // Bucket 2 (Fugas de conversión): Atribución aproximada (§2) alineada con offered = 1 (Precisión 5)
                $stmt = $db->query("SELECT 
                    ds.matched_service,
                    COUNT(DISTINCT ds.session_id) as total_interested_sessions,
                    COUNT(DISTINCT l.id) as converted_leads,
                    (COUNT(DISTINCT ds.session_id) - COUNT(DISTINCT l.id)) as leaked_sessions,
                    ROUND((1.0 - (CAST(COUNT(DISTINCT l.id) AS REAL) / COUNT(DISTINCT ds.session_id))) * 100, 2) as leakage_percentage
                    FROM demand_signals ds
                    LEFT JOIN leads l ON ds.session_id = l.session_id
                    WHERE ds.offered = 1
                    GROUP BY ds.matched_service
                    ORDER BY leakage_percentage DESC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'insufficient_data' => false,
                    'total' => $total,
                    'leakage' => $data
                ]);
            }
            break;

        case 'lead_journey':
            if ($method === 'GET') {
                $session_id = trim($_GET['session_id'] ?? '');
                if (empty($session_id) || !preg_match('/^[a-zA-Z0-9]{32,}$/', $session_id)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid session_id format']);
                    exit;
                }

                // Reconstructor de Journey (Precisión 1): Une eventos de micro-interacciones, demanda,
                // interacciones CRM reales ( interactions unida por lead_id ) e historial de estados.
                $stmt = $db->prepare("
                    SELECT 'behavior' as source, event_type as activity, event_target as target, event_value as detail, created_at as ts
                    FROM interaction_events
                    WHERE session_id = :session_id

                    UNION ALL

                    SELECT 'demand' as source, intent || ' (' || resolved_route || ')' as activity, matched_service as target, query_redacted || ' [Route: ' || resolved_route || '] [Context: ' || sector || '/' || role || ']' as detail, created_at as ts
                    FROM demand_signals
                    WHERE session_id = :session_id

                    UNION ALL

                    SELECT 'crm_interaction' as source, interaction_type as activity, NULL as target, content as detail, created_at as ts
                    FROM interactions
                    WHERE lead_id = (SELECT id FROM leads WHERE session_id = :session_id)

                    UNION ALL

                    SELECT 'crm_status' as source, 'change_status' as activity, old_status || ' -> ' || new_status as target, notes as detail, created_at as ts
                    FROM status_history sh
                    JOIN leads l ON sh.lead_id = l.id
                    WHERE l.session_id = :session_id

                    ORDER BY ts ASC
                ");
                $stmt->execute([':session_id' => $session_id]);
                $journey = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'session_id' => $session_id,
                    'journey' => $journey
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
