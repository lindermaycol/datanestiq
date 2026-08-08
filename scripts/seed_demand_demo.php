<?php
/**
 * Seeder de datos demo para Inteligencia de Demanda y Journey Reconstructor
 * Genera ≥20 demand_signals con ≥10 offered=1 en session_ids distintos
 * Cumple guards §2: Bucket 1 (≥20 señales) + Bucket 2 (≥10 offered=1 en sesiones distintas)
 * Uso: php scripts/seed_demand_demo.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    die("Error: Base de datos no encontrada en $db_path. Corre init_crm_db.php primero.\n");
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Limpiar datos demo previos (idempotente)
    $db->exec("DELETE FROM demand_signals WHERE session_id LIKE 'demoseed%'");
    $db->exec("DELETE FROM interaction_events WHERE session_id LIKE 'demoseed%'");
    // chat_sessions es opcional (puede no existir en entornos locales)
    $has_cs_cleanup = (bool) $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='chat_sessions'")->fetchColumn();
    if ($has_cs_cleanup) {
        $db->exec("DELETE FROM chat_sessions WHERE session_id LIKE 'demoseed%'");
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // GAPS (offered=0): Brechas en el catálogo — no resueltas por local-routes
    // ─────────────────────────────────────────────────────────────────────────────
    $gaps = [
        [
            'query'       => '[DEMO] Busco analítica predictiva en tiempo real para predecir merma de inventario',
            'intent'      => 'complejo', 'confidence' => 0.85, 'service' => 'business-intelligence',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'retail', 'role' => 'ceo',
            'score'       => 0.38, 'faq_score' => 0.15, 'days_ago' => 5
        ],
        [
            'query'       => '[DEMO] Requiero implementar algoritmos de computer vision para clasificar frutas en anaqueles',
            'intent'      => 'complejo', 'confidence' => 0.92, 'service' => 'ai-data-science',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'retail', 'role' => 'coo',
            'score'       => 0.35, 'faq_score' => 0.05, 'days_ago' => 4
        ],
        [
            'query'       => '[DEMO] ¿Tienen sistemas para conectar sensores IoT en plantas mineras subterráneas?',
            'intent'      => 'complejo', 'confidence' => 0.88, 'service' => 'sistemas-digitales',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'mineria', 'role' => 'coo',
            'score'       => 0.39, 'faq_score' => 0.10, 'days_ago' => 6
        ],
        [
            'query'       => '[DEMO] Necesito migrar pipelines cobol legados a snowflake en la nube pública de perú',
            'intent'      => 'complejo', 'confidence' => 0.94, 'service' => 'data-engineering',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'finanzas', 'role' => 'cdo',
            'score'       => 0.37, 'faq_score' => 0.02, 'days_ago' => 3
        ],
        [
            'query'       => '[DEMO] Busco simuladores de impacto actuarial usando montecarlo para siniestros de autos',
            'intent'      => 'complejo', 'confidence' => 0.89, 'service' => 'ai-data-science',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'seguros', 'role' => 'cfo',
            'score'       => 0.34, 'faq_score' => 0.08, 'days_ago' => 2
        ],
        [
            'query'       => '[DEMO] ¿Pueden auditar modelos de scoring crediticio con explicabilidad SHAP/LIME?',
            'intent'      => 'complejo', 'confidence' => 0.91, 'service' => 'ai-data-science',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'finanzas', 'role' => 'cro',
            'score'       => 0.36, 'faq_score' => 0.06, 'days_ago' => 7
        ],
        [
            'query'       => '[DEMO] Quiero construir un digital twin del proceso de fundición para simulación de fallas',
            'intent'      => 'complejo', 'confidence' => 0.87, 'service' => 'estrategia-datos-ia',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'manufactura', 'role' => 'cto',
            'score'       => 0.33, 'faq_score' => 0.04, 'days_ago' => 8
        ],
        [
            'query'       => '[DEMO] Necesito sistema de detección de fraude en tarjetas en tiempo real con ML en edge',
            'intent'      => 'complejo', 'confidence' => 0.93, 'service' => 'ai-data-science',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'finanzas', 'role' => 'ciso',
            'score'       => 0.31, 'faq_score' => 0.09, 'days_ago' => 9
        ],
        [
            'query'       => '[DEMO] ¿Pueden desarrollar un modelo NLP que procese contratos legales en español?',
            'intent'      => 'complejo', 'confidence' => 0.86, 'service' => 'ai-data-science',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'legal', 'role' => 'clo',
            'score'       => 0.40, 'faq_score' => 0.07, 'days_ago' => 10
        ],
        [
            'query'       => '[DEMO] Busco plataforma de observabilidad MLOps integrada con nuestro k8s en GKE',
            'intent'      => 'complejo', 'confidence' => 0.90, 'service' => 'estrategia-datos-ia',
            'offered'     => 0, 'route' => 'llm', 'sector' => 'tecnologia', 'role' => 'vpe',
            'score'       => 0.32, 'faq_score' => 0.03, 'days_ago' => 11
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────────
    // OFFERED (offered=1): Atendidos por catálogo o LLM — ≥10 en sesiones distintas
    // ─────────────────────────────────────────────────────────────────────────────
    $offered = [
        [
            'query'       => '[DEMO] Quiero crear dashboards interactivos con power bi para el comité de finanzas',
            'intent'      => 'complejo', 'confidence' => 0.95, 'service' => 'business-intelligence',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'finanzas', 'role' => 'cfo',
            'score'       => 0.88, 'faq_score' => 0.22, 'days_ago' => 1
        ],
        [
            'query'       => '[DEMO] ¿Cómo estructuran un data lake empresarial robusto en AWS con controles de gobierno?',
            'intent'      => 'complejo', 'confidence' => 0.91, 'service' => 'data-engineering',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'retail', 'role' => 'cdo',
            'score'       => 0.82, 'faq_score' => 0.18, 'days_ago' => 2
        ],
        [
            'query'       => '[DEMO] Me interesa automatizar el procesamiento de facturas en pdf usando RPA cognitivo',
            'intent'      => 'complejo', 'confidence' => 0.96, 'service' => 'hiperautomatizacion',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'logistica', 'role' => 'coo',
            'score'       => 0.85, 'faq_score' => 0.11, 'days_ago' => 3
        ],
        [
            'query'       => '[DEMO] ¿Cuáles son sus horarios de atención?',
            'intent'      => 'faq', 'confidence' => 0.98, 'service' => 'sistemas-digitales',
            'offered'     => 1, 'route' => 'faq', 'sector' => 'publico', 'role' => 'ceo',
            'score'       => 0.42, 'faq_score' => 0.95, 'days_ago' => 4
        ],
        [
            'query'       => '[DEMO] Quiero agendar una cita técnica con un arquitecto mañana',
            'intent'      => 'cita', 'confidence' => 0.99, 'service' => 'estrategia-datos-ia',
            'offered'     => 1, 'route' => 'cita', 'sector' => 'retail', 'role' => 'ceo',
            'score'       => 0.41, 'faq_score' => 0.85, 'days_ago' => 1
        ],
        [
            'query'       => '[DEMO] ¿Cómo es su metodología de trabajo de fábrica de agentes?',
            'intent'      => 'guiado', 'confidence' => 0.97, 'service' => 'estrategia-datos-ia',
            'offered'     => 1, 'route' => 'guiado', 'sector' => 'finanzas', 'role' => 'cfo',
            'score'       => 0.45, 'faq_score' => 0.72, 'days_ago' => 2
        ],
        [
            'query'       => '[DEMO] Necesito una estrategia de gobierno de datos bajo marco DAMA-DMBOK',
            'intent'      => 'complejo', 'confidence' => 0.90, 'service' => 'estrategia-datos-ia',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'finanzas', 'role' => 'cdo',
            'score'       => 0.84, 'faq_score' => 0.20, 'days_ago' => 5
        ],
        [
            'query'       => '[DEMO] ¿Ofrecen capacitación en Power BI y Tableau para equipos de negocio?',
            'intent'      => 'complejo', 'confidence' => 0.88, 'service' => 'business-intelligence',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'educacion', 'role' => 'chro',
            'score'       => 0.80, 'faq_score' => 0.60, 'days_ago' => 6
        ],
        [
            'query'       => '[DEMO] Quiero implementar un pipeline de datos en tiempo real con Apache Kafka y Spark',
            'intent'      => 'complejo', 'confidence' => 0.93, 'service' => 'data-engineering',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'tecnologia', 'role' => 'cto',
            'score'       => 0.86, 'faq_score' => 0.14, 'days_ago' => 7
        ],
        [
            'query'       => '[DEMO] Necesito implementar sistemas de automatización RPA para RRHH y nóminas',
            'intent'      => 'complejo', 'confidence' => 0.92, 'service' => 'hiperautomatizacion',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'salud', 'role' => 'chro',
            'score'       => 0.83, 'faq_score' => 0.19, 'days_ago' => 8
        ],
        [
            'query'       => '[DEMO] ¿Pueden construir un chatbot conversacional multicanal para soporte al cliente?',
            'intent'      => 'complejo', 'confidence' => 0.94, 'service' => 'ai-data-science',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'servicios', 'role' => 'cmo',
            'score'       => 0.87, 'faq_score' => 0.25, 'days_ago' => 9
        ],
        [
            'query'       => '[DEMO] Busco auditoría de calidad de datos y linaje end-to-end con DataHub',
            'intent'      => 'complejo', 'confidence' => 0.89, 'service' => 'data-engineering',
            'offered'     => 1, 'route' => 'llm', 'sector' => 'gobierno', 'role' => 'cdo',
            'score'       => 0.81, 'faq_score' => 0.17, 'days_ago' => 10
        ],
    ];

    $samples = array_merge($gaps, $offered);

    $stmt_ds = $db->prepare("INSERT INTO demand_signals (
        session_id, query_redacted, intent, confidence, matched_service, offered, resolved, resolved_route, sector, role, score, faq_score, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt_ie = $db->prepare("INSERT INTO interaction_events (
        session_id, event_type, event_target, event_value, created_at
    ) VALUES (?, ?, ?, ?, ?)");

    // Verificar si chat_sessions existe en el esquema (via sqlite_master)
    $has_chat_sessions = (bool) $db->query(
        "SELECT 1 FROM sqlite_master WHERE type='table' AND name='chat_sessions'"
    )->fetchColumn();

    if ($has_chat_sessions) {
        $stmt_cs = $db->prepare("INSERT OR IGNORE INTO chat_sessions (session_id, sector, role, first_message, created_at) VALUES (?, ?, ?, ?, ?)");
    }

    $count = 0;
    $gaps_count = count($gaps);
    $offered_count = count($offered);

    foreach ($samples as $i => $s) {
        $sid = 'demoseed_' . md5("session_v2_$i");
        $timestamp = date('Y-m-d H:i:s', strtotime("-{$s['days_ago']} days"));

        // 1. Inyectar señal de demanda
        $stmt_ds->execute([
            $sid,
            $s['query'],
            $s['intent'],
            $s['confidence'],
            $s['service'],
            $s['offered'],
            $s['route'] !== 'llm' ? 'resolved' : 'unresolved',
            $s['route'],
            $s['sector'],
            $s['role'],
            $s['score'],
            $s['faq_score'],
            $timestamp
        ]);

        // 2. Inyectar eventos de comportamiento para Journey Reconstructor
        $stmt_ie->execute([$sid, 'click', 'context-chip-sector', $s['sector'], date('Y-m-d H:i:s', strtotime($timestamp . ' -5 minutes'))]);
        $stmt_ie->execute([$sid, 'click', 'context-chip-role',   $s['role'],   date('Y-m-d H:i:s', strtotime($timestamp . ' -4 minutes'))]);
        $stmt_ie->execute([$sid, 'submit', 'chatbot', $s['query'], $timestamp]);

        if ($s['route'] === 'faq') {
            $stmt_ie->execute([$sid, 'view', 'faq-match', 'faq_horarios', date('Y-m-d H:i:s', strtotime($timestamp . ' +10 seconds'))]);
        } elseif ($s['route'] === 'cita') {
            $stmt_ie->execute([$sid, 'view', 'appointment-picker', 'picker_visible', date('Y-m-d H:i:s', strtotime($timestamp . ' +10 seconds'))]);
        } elseif ($s['offered'] === 1) {
            $stmt_ie->execute([$sid, 'view', 'llm-response', 'response_received', date('Y-m-d H:i:s', strtotime($timestamp . ' +3 seconds'))]);
        }

        // 3. Inyectar entrada en chat_sessions (para Journey Reconstructor)
        if ($has_chat_sessions) {
            try {
                $stmt_cs->execute([$sid, $s['sector'], $s['role'], $s['query'], $timestamp]);
            } catch (\PDOException $e) {
                // Fallar silenciosamente si hay incompatibilidad de esquema
            }
        }

        $count++;
    }

    echo "✅ Sembrado completado: $count señales de demanda demo en $count session_ids distintos.\n";
    echo "   - GAPS (offered=0, Bucket 1 brechas): $gaps_count\n";
    echo "   - OFFERED (offered=1, Bucket 2 fuga): $offered_count\n";
    echo "   Guards §2: Bucket 1 ≥20 (" . ($gaps_count + $offered_count >= 20 ? '✅' : '❌') . "), Bucket 2 ≥10 offered=1 distintos (" . ($offered_count >= 10 ? '✅' : '❌') . ")\n";

} catch (PDOException $e) {
    die("Error durante el sembrado de datos: " . $e->getMessage() . "\n");
}
