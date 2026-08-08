<?php
/**
 * Purga de datos demo para señales de demanda y comportamiento
 * Uso: php scripts/purge_demand_demo.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    die("Error: Base de datos no encontrada.\n");
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ejecutar purga de cualquier registro demo
    $stmt_ds = $db->prepare("DELETE FROM demand_signals WHERE session_id LIKE 'demoseed%'");
    $stmt_ds->execute();
    $purged_ds = $stmt_ds->rowCount();

    $stmt_ie = $db->prepare("DELETE FROM interaction_events WHERE session_id LIKE 'demoseed%'");
    $stmt_ie->execute();
    $purged_ie = $stmt_ie->rowCount();

    echo "✅ Purga completada: $purged_ds registros de demanda y $purged_ie registros de eventos demo eliminados de crm.sqlite.\n";

} catch (PDOException $e) {
    die("Error durante la purga de datos: " . $e->getMessage() . "\n");
}
