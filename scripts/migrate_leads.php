<?php
/**
 * migrate_leads.php — Importa leads_wizard.csv a SQLite (idempotente por session_id)
 * Uso: php scripts/migrate_leads.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
$csv_path = __DIR__ . '/../secure_leads/leads_wizard.csv';

if (!file_exists($db_path)) {
    fwrite(STDERR, "Error: BD no encontrada. Ejecuta primero: php scripts/init_crm_db.php\n");
    exit(1);
}

if (!file_exists($csv_path)) {
    echo "No se encontro leads_wizard.csv. Nada que migrar.\n";
    exit(0);
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    $fp = fopen($csv_path, 'r');
    if (!$fp) {
        fwrite(STDERR, "Error: No se pudo abrir $csv_path\n");
        exit(1);
    }

    // Leer encabezados
    $headers = fgetcsv($fp);
    if (!$headers) {
        echo "CSV vacio. Nada que migrar.\n";
        fclose($fp);
        exit(0);
    }

    // Normalizar encabezados a minúsculas
    $headers = array_map('strtolower', $headers);

    $insert = $db->prepare("INSERT OR IGNORE INTO leads
        (session_id, email, nombre, telefono, organizacion, reto, stack, score, source, status, created_at, updated_at)
        VALUES (:session_id, :email, :nombre, :telefono, :organizacion, :reto, :stack, :score, 'csv_migration', 'nuevo', :created_at, :created_at)");

    $imported = 0;
    $skipped = 0;

    $db->beginTransaction();

    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) < count($headers)) {
            $skipped++;
            continue;
        }

        $data = array_combine($headers, $row);

        $session_id = $data['sessionid'] ?? $data['session_id'] ?? ('migrated_' . md5(($data['email'] ?? '') . ($data['timestamp'] ?? '')));
        $created_at = $data['timestamp'] ?? date('c');

        $insert->execute([
            ':session_id' => $session_id,
            ':email' => $data['email'] ?? '',
            ':nombre' => $data['nombre'] ?? '',
            ':telefono' => $data['telefono'] ?? '',
            ':organizacion' => $data['organizacion'] ?? '',
            ':reto' => $data['reto'] ?? '',
            ':stack' => $data['stack'] ?? '',
            ':score' => (int)($data['score'] ?? 0),
            ':created_at' => $created_at,
        ]);

        if ($insert->rowCount() > 0) {
            $imported++;
        } else {
            $skipped++;
        }
    }

    $db->commit();
    fclose($fp);

    echo "Migracion completada: $imported leads importados, $skipped omitidos (duplicados o mal formados).\n";

} catch (PDOException $e) {
    fwrite(STDERR, "Error de BD: " . $e->getMessage() . "\n");
    exit(1);
}
