<?php
/**
 * init_crm_db.php — Crea/inicializa el esquema SQLite para el mini-CRM (Specs 014+015)
 * Ubicación de la BD: secure_leads/crm.sqlite (fuera del webroot)
 * Uso: php scripts/init_crm_db.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
$dir = dirname($db_path);

if (!is_dir($dir)) {
    mkdir($dir, 0700, true);
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // WAL mode para mejor concurrencia
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    // --- Tabla: leads ---
    $db->exec("CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id VARCHAR(100) UNIQUE,
        email VARCHAR(200),
        telefono VARCHAR(50),
        nombre VARCHAR(100) DEFAULT '',
        organizacion VARCHAR(200) DEFAULT '',
        reto TEXT DEFAULT '',
        stack TEXT DEFAULT '',
        score INTEGER DEFAULT 0,
        source VARCHAR(50) DEFAULT 'chatbot',
        status VARCHAR(50) DEFAULT 'nuevo',
        next_action TEXT DEFAULT '',
        notes TEXT DEFAULT '',
        created_at DATETIME DEFAULT (datetime('now')),
        updated_at DATETIME DEFAULT (datetime('now'))
    )");

    // --- Tabla: interactions ---
    $db->exec("CREATE TABLE IF NOT EXISTS interactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        interaction_type VARCHAR(50) NOT NULL,
        content TEXT DEFAULT '',
        created_at DATETIME DEFAULT (datetime('now')),
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
    )");

    // --- Tabla: status_history ---
    $db->exec("CREATE TABLE IF NOT EXISTS status_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50) NOT NULL,
        changed_by VARCHAR(100) DEFAULT 'system',
        notes TEXT DEFAULT '',
        created_at DATETIME DEFAULT (datetime('now')),
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
    )");

    // --- Tabla: appointments (Spec 015) ---
    $db->exec("CREATE TABLE IF NOT EXISTS appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL,
        session_id VARCHAR(100),
        requested_date DATETIME NOT NULL,
        duration_minutes INTEGER DEFAULT 30,
        type VARCHAR(50) DEFAULT 'diagnostico',
        status VARCHAR(50) DEFAULT 'solicitada',
        notes TEXT DEFAULT '',
        created_at DATETIME DEFAULT (datetime('now')),
        updated_at DATETIME DEFAULT (datetime('now')),
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
    )");

    // --- Tabla: availability_config (Spec 015) ---
    $db->exec("CREATE TABLE IF NOT EXISTS availability_config (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        day_of_week INTEGER NOT NULL,
        start_time VARCHAR(5) NOT NULL,
        end_time VARCHAR(5) NOT NULL,
        slot_duration_minutes INTEGER DEFAULT 30,
        is_active INTEGER DEFAULT 1
    )");

    // --- Tabla: blocked_dates (Spec 015) ---
    $db->exec("CREATE TABLE IF NOT EXISTS blocked_dates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        blocked_date DATE NOT NULL UNIQUE,
        reason TEXT DEFAULT ''
    )");

    // Migración idempotente para Spec 016: Añadir columnas sector y rol a leads si no existen
    $columns = $db->query("PRAGMA table_info(leads)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('sector', $columns)) {
        $db->exec("ALTER TABLE leads ADD COLUMN sector VARCHAR(100) DEFAULT ''");
    }
    if (!in_array('rol', $columns)) {
        $db->exec("ALTER TABLE leads ADD COLUMN rol VARCHAR(100) DEFAULT ''");
    }

    // --- Tabla: chat_metrics (Spec 016) ---
    $db->exec("CREATE TABLE IF NOT EXISTS chat_metrics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id VARCHAR(100) NOT NULL,
        backend_used VARCHAR(50) NOT NULL,
        latency_ms INTEGER NOT NULL,
        success INTEGER DEFAULT 1,
        tokens_est INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT (datetime('now'))
    )");

    // --- Tabla: interaction_events (Spec 018 - Analítica de Micro-Interacciones) ---
    $db->exec("CREATE TABLE IF NOT EXISTS interaction_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id VARCHAR(100) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        event_target VARCHAR(100) NOT NULL,
        event_value TEXT NOT NULL,
        created_at DATETIME DEFAULT (datetime('now'))
    )");

    // --- Tabla: demand_signals (Spec 020 - Inteligencia de Demanda) ---
    $db->exec("CREATE TABLE IF NOT EXISTS demand_signals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id VARCHAR(100) NOT NULL,
        query_redacted TEXT NOT NULL,
        intent VARCHAR(50) NOT NULL,
        confidence REAL NOT NULL,
        matched_service VARCHAR(100) DEFAULT NULL,
        offered INTEGER NOT NULL,
        resolved VARCHAR(50) NOT NULL,
        resolved_route VARCHAR(50) DEFAULT 'llm',
        sector VARCHAR(100) DEFAULT '',
        role VARCHAR(100) DEFAULT '',
        score REAL DEFAULT 0.0,
        faq_score REAL DEFAULT 0.0,
        created_at DATETIME DEFAULT (datetime('now'))
    )");

    // Migración idempotente para Spec 020 Fixes (A/C)
    $ds_columns = $db->query("PRAGMA table_info(demand_signals)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('resolved_route', $ds_columns)) {
        $db->exec("ALTER TABLE demand_signals ADD COLUMN resolved_route VARCHAR(50) DEFAULT 'llm'");
    }
    if (!in_array('sector', $ds_columns)) {
        $db->exec("ALTER TABLE demand_signals ADD COLUMN sector VARCHAR(100) DEFAULT ''");
    }
    if (!in_array('role', $ds_columns)) {
        $db->exec("ALTER TABLE demand_signals ADD COLUMN role VARCHAR(100) DEFAULT ''");
    }
    if (!in_array('score', $ds_columns)) {
        $db->exec("ALTER TABLE demand_signals ADD COLUMN score REAL DEFAULT 0.0");
    }
    if (!in_array('faq_score', $ds_columns)) {
        $db->exec("ALTER TABLE demand_signals ADD COLUMN faq_score REAL DEFAULT 0.0");
    }

    // Índices para performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_sector ON leads(sector)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_rol ON leads(rol)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_leads_email ON leads(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_interactions_lead ON interactions(lead_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_appointments_lead ON appointments(lead_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(requested_date)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_chat_metrics_session ON chat_metrics(session_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_chat_metrics_backend ON chat_metrics(backend_used)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_chat_metrics_created ON chat_metrics(created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_interaction_events_session ON interaction_events(session_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_interaction_events_type ON interaction_events(event_type)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_interaction_events_created ON interaction_events(created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_demand_signals_session ON demand_signals(session_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_demand_signals_offered ON demand_signals(offered)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_demand_signals_created ON demand_signals(created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_demand_signals_resolved_route ON demand_signals(resolved_route)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_demand_signals_sector ON demand_signals(sector)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_demand_signals_role ON demand_signals(role)");

    // Insertar disponibilidad por defecto (Lunes-Viernes, 9:00-18:00, America/Lima)
    $stmt = $db->query("SELECT COUNT(*) FROM availability_config");
    if ((int)$stmt->fetchColumn() === 0) {
        $insert = $db->prepare("INSERT INTO availability_config (day_of_week, start_time, end_time, slot_duration_minutes, is_active) VALUES (?, ?, ?, ?, ?)");
        for ($day = 1; $day <= 5; $day++) { // Lunes=1 a Viernes=5
            $insert->execute([$day, '09:00', '18:00', 30, 1]);
        }
        echo "Disponibilidad por defecto insertada (Lun-Vie, 09:00-18:00).\n";
    }

    echo "CRM SQLite inicializado correctamente en: $db_path\n";

} catch (PDOException $e) {
    fwrite(STDERR, "Error al inicializar la BD: " . $e->getMessage() . "\n");
    exit(1);
}
