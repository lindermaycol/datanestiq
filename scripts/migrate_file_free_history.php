<?php
/**
 * scripts/migrate_file_free_history.php — Migración e importación del histórico de archivos planos a SQLite.
 * Transfiere el contenido de chat_logs.jsonl, chat_raw.jsonl, leads_datanestiq.csv y daily_usage_*.json a las tablas
 * conversations, chat_raw, leads_extracted y usage_daily.
 * Tras la importación exitosa, traslada los archivos procesados a secure_leads/_archive/.
 * Uso: php scripts/migrate_file_free_history.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    die("❌ Error: Base de datos no encontrada en $db_path. Ejecuta init_crm_db.php primero.\n");
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');

    $archive_dir = __DIR__ . '/../secure_leads/_archive';
    if (!is_dir($archive_dir)) {
        mkdir($archive_dir, 0700, true);
    }

    echo "🚀 Iniciando migración de histórico de archivos planos a SQLite (Spec 022)...\n\n";

    // --- 1. Migrar chat_logs.jsonl y other_logs.jsonl -> conversations ---
    $jsonl_files = [
        __DIR__ . '/../chat_logs.jsonl',
        __DIR__ . '/../other_logs.jsonl'
    ];

    $conversations_count = 0;
    $stmt_conv = $db->prepare("INSERT INTO conversations (session_id, role, content_redacted, backend_used, created_at) VALUES (?, ?, ?, ?, ?)");

    foreach ($jsonl_files as $file) {
        if (!file_exists($file)) continue;
        
        $handle = fopen($file, 'r');
        if ($handle) {
            $db->beginTransaction();
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;
                $data = json_decode($line, true);
                if (!$data) continue;

                $session_id = $data['session_id'] ?? 'unknown';
                $backend = $data['backend'] ?? $data['backend_used'] ?? 'unknown';
                $ts = $data['created_at'] ?? $data['timestamp'] ?? date('Y-m-d H:i:s');
                $messages = $data['messages'] ?? [];

                foreach ($messages as $msg) {
                    $role = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';
                    if (!empty($content) && in_array($role, ['user', 'assistant', 'system'])) {
                        $stmt_conv->execute([$session_id, $role, $content, $backend, $ts]);
                        $conversations_count++;
                    }
                }
            }
            $db->commit();
            fclose($handle);

            // Archivar
            $basename = basename($file);
            rename($file, $archive_dir . '/' . $basename . '.' . date('Ymd_His') . '.bak');
            echo "  ✅ Migrado y archivado: $basename\n";
        }
    }

    // --- 2. Migrar secure_leads/chat_raw.jsonl -> chat_raw (privada) ---
    $raw_file = __DIR__ . '/../secure_leads/chat_raw.jsonl';
    $raw_count = 0;
    if (file_exists($raw_file)) {
        $stmt_raw = $db->prepare("INSERT INTO chat_raw (session_id, role, content_raw, created_at) VALUES (?, ?, ?, ?)");
        $handle = fopen($raw_file, 'r');
        if ($handle) {
            $db->beginTransaction();
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;
                $data = json_decode($line, true);
                if (!$data) continue;

                $session_id = $data['session_id'] ?? 'unknown';
                $ts = $data['created_at'] ?? $data['timestamp'] ?? date('Y-m-d H:i:s');
                $messages = $data['messages'] ?? [];

                foreach ($messages as $msg) {
                    $role = $msg['role'] ?? 'user';
                    $content = $msg['content'] ?? '';
                    if (!empty($content) && in_array($role, ['user', 'assistant', 'system'])) {
                        $stmt_raw->execute([$session_id, $role, $content, $ts]);
                        $raw_count++;
                    }
                }
            }
            $db->commit();
            fclose($handle);

            rename($raw_file, $archive_dir . '/chat_raw.jsonl.' . date('Ymd_His') . '.bak');
            echo "  ✅ Migrado y archivado: chat_raw.jsonl\n";
        }
    }

    // --- 3. Migrar secure_leads/leads_datanestiq.csv -> leads_extracted ---
    $csv_file = __DIR__ . '/../secure_leads/leads_datanestiq.csv';
    $extracted_count = 0;
    if (file_exists($csv_file)) {
        $stmt_ext = $db->prepare("INSERT OR IGNORE INTO leads_extracted (session_id, nombre, email, telefono, intencion, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $handle = fopen($csv_file, 'r');
        if ($handle) {
            $db->beginTransaction();
            $header = fgetcsv($handle); // omitir cabecera
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 5) {
                    $session_id = trim($row[0]);
                    $nombre = trim($row[1] ?? '');
                    $email = trim($row[2] ?? '');
                    $telefono = trim($row[3] ?? '');
                    $intencion = trim($row[4] ?? '');
                    $ts = trim($row[5] ?? date('Y-m-d H:i:s'));

                    if (!empty($session_id) || !empty($email)) {
                        $stmt_ext->execute([$session_id, $nombre, $email, $telefono, $intencion, $ts]);
                        $extracted_count++;
                    }
                }
            }
            $db->commit();
            fclose($handle);

            rename($csv_file, $archive_dir . '/leads_datanestiq.csv.' . date('Ymd_His') . '.bak');
            echo "  ✅ Migrado y archivado: leads_datanestiq.csv\n";
        }
    }

    // --- 4. Migrar secure_leads/daily_usage_*.json -> usage_daily ---
    $usage_files = glob(__DIR__ . '/../secure_leads/daily_usage_*.json');
    $usage_count = 0;
    if (!empty($usage_files)) {
        $stmt_usage = $db->prepare("INSERT INTO usage_daily (usage_date, total_tokens, request_count, updated_at) VALUES (?, ?, ?, datetime('now')) ON CONFLICT(usage_date) DO UPDATE SET total_tokens = excluded.total_tokens, request_count = excluded.request_count");
        $db->beginTransaction();
        foreach ($usage_files as $ufile) {
            $content = file_get_contents($ufile);
            $udata = json_decode($content, true);
            if ($udata) {
                $date = $udata['date'] ?? preg_replace('/.*daily_usage_(.*)\.json/', '$1', $ufile);
                $total_tokens = intval($udata['total_tokens'] ?? 0);
                $request_count = intval($udata['request_count'] ?? 0);

                $stmt_usage->execute([$date, $total_tokens, $request_count]);
                $usage_count++;
            }
            rename($ufile, $archive_dir . '/' . basename($ufile) . '.' . date('Ymd_His') . '.bak');
        }
        $db->commit();
        echo "  ✅ Migrados y archivados: " . count($usage_files) . " archivos daily_usage_\n";
    }

    // Archivar leads_wizard.csv si existe
    $wiz_file = __DIR__ . '/../secure_leads/leads_wizard.csv';
    if (file_exists($wiz_file)) {
        rename($wiz_file, $archive_dir . '/leads_wizard.csv.' . date('Ymd_His') . '.bak');
        echo "  ✅ Archivado fallback redundante: leads_wizard.csv\n";
    }

    echo "\n🎉 MIGRACIÓN COMPLETA (COUNT Report):\n";
    echo "  - Registros en 'conversations': " . $db->query("SELECT COUNT(*) FROM conversations")->fetchColumn() . " (nuevos: $conversations_count)\n";
    echo "  - Registros en 'chat_raw' (privada): " . $db->query("SELECT COUNT(*) FROM chat_raw")->fetchColumn() . " (nuevos: $raw_count)\n";
    echo "  - Registros en 'leads_extracted': " . $db->query("SELECT COUNT(*) FROM leads_extracted")->fetchColumn() . " (nuevos: $extracted_count)\n";
    echo "  - Registros en 'usage_daily': " . $db->query("SELECT COUNT(*) FROM usage_daily")->fetchColumn() . " (nuevos: $usage_count)\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "❌ Error durante la migración: " . $e->getMessage() . "\n");
    exit(1);
}
