<?php
/**
 * scripts/redact_historical_pii.php — Limpieza y redacción retrospectiva de PII
 * (Emails, teléfonos de 10+ dígitos y móviles de Perú de 9 dígitos) en demand_signals históricas.
 * Uso: php scripts/redact_historical_pii.php
 */

$db_path = __DIR__ . '/../secure_leads/crm.sqlite';
if (!file_exists($db_path)) {
    die("Error: Base de datos no encontrada en $db_path.\n");
}

function redactPii($text) {
    // Redactar Emails
    $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
    // Redactar Teléfonos (patrón estándar internacional de 10+ dígitos)
    $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[PHONE_REDACTED]', $text);
    // Móvil Perú: 9XX XXX XXX (con o sin +51 y separadores) (Fix B)
    $text = preg_replace('/(?<!\d)(\+?51[\s.\-]?)?9\d{2}[\s.\-]?\d{3}[\s.\-]?\d{3}(?!\d)/', '[PHONE_REDACTED]', $text);
    return $text;
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener todas las consultas de demand_signals
    $stmt = $db->query("SELECT id, query_redacted FROM demand_signals");
    $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    $stmt_update = $db->prepare("UPDATE demand_signals SET query_redacted = ? WHERE id = ?");

    foreach ($signals as $sig) {
        $original = $sig['query_redacted'];
        $redacted = redactPii($original);
        if ($original !== $redacted) {
            $stmt_update->execute([$redacted, $sig['id']]);
            $updated++;
        }
    }

    echo "✅ Redacción retrospectiva completada. Fila(s) corregidas: $updated\n";

} catch (PDOException $e) {
    die("Error durante la redacción: " . $e->getMessage() . "\n");
}
