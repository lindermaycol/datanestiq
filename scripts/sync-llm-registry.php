<?php
/**
 * scripts/sync-llm-registry.php
 * Sincroniza el registro único de proveedores LLM desde C:\Users\Lisbeth\llm-core\
 * hacia public/api/lib/providers.php (formato PHP sin secretos)
 * y copia parity_cases.json a scripts/fixtures/parity_cases.json.
 *
 * Uso:
 *   php scripts/sync-llm-registry.php          # Sincroniza/genera archivos
 *   php scripts/sync-llm-registry.php --check  # Valida sincronización e integridad por hash (exit 1 si difiere)
 */

$is_check = in_array('--check', $argv, true);

$source_dir = getenv('LLM_CORE_DIR') ?: 'C:\Users\Lisbeth\llm-core';

$source_providers = $source_dir . DIRECTORY_SEPARATOR . 'providers.json';
$source_parity = $source_dir . DIRECTORY_SEPARATOR . 'parity_cases.json';

if (!file_exists($source_providers)) {
    fwrite(STDERR, "ERROR: No existe el archivo maestro de proveedores en: $source_providers\n");
    exit(1);
}

if (!file_exists($source_parity)) {
    fwrite(STDERR, "ERROR: No existe el archivo maestro de parity_cases en: $source_parity\n");
    exit(1);
}

$target_providers_php = dirname(__DIR__) . '/public/api/lib/providers.php';
$target_fixtures_dir = __DIR__ . '/fixtures';
$target_parity_json = $target_fixtures_dir . '/parity_cases.json';

$providers_raw = file_get_contents($source_providers);
$providers_hash = hash('sha256', $providers_raw);
$providers_data = json_decode($providers_raw, true);

if (!is_array($providers_data) || !isset($providers_data['providers']) || !isset($providers_data['profiles'])) {
    fwrite(STDERR, "ERROR: providers.json no tiene una estructura JSON válida.\n");
    exit(1);
}

$expected_php = "<?php\n// GENERATED — sha256 de providers.json = " . $providers_hash . " — no editar a mano\nreturn " . var_export($providers_data, true) . ";\n";

$parity_raw = file_get_contents($source_parity);
$parity_hash = hash('sha256', $parity_raw);

if ($is_check) {
    // 1. Validar existencia y contenido de providers.php
    if (!file_exists($target_providers_php)) {
        fwrite(STDERR, "ERROR: El archivo generado public/api/lib/providers.php no existe.\n");
        exit(1);
    }

    $current_php = file_get_contents($target_providers_php);
    if (!preg_match('/GENERATED — sha256 de providers\.json = ([a-f0-9]+)/', $current_php, $m)) {
        fwrite(STDERR, "ERROR: providers.php no contiene la cabecera de hash sha256 esperada.\n");
        exit(1);
    }

    $current_hash = $m[1];
    if ($current_hash !== $providers_hash) {
        fwrite(STDERR, "ERROR: Desincronización en providers.php. Hash esperado: $providers_hash, encontrado: $current_hash\n");
        exit(1);
    }

    if ($current_php !== $expected_php) {
        fwrite(STDERR, "ERROR: El contenido de providers.php ha sido alterado respecto a la fuente maestra.\n");
        exit(1);
    }

    // 2. Validar existencia y contenido de fixtures/parity_cases.json
    if (!file_exists($target_parity_json)) {
        fwrite(STDERR, "ERROR: El fixture scripts/fixtures/parity_cases.json no existe.\n");
        exit(1);
    }

    $current_parity_hash = hash_file('sha256', $target_parity_json);
    if ($current_parity_hash !== $parity_hash) {
        fwrite(STDERR, "ERROR: Desincronización en parity_cases.json. Hash esperado: $parity_hash, encontrado: $current_parity_hash\n");
        exit(1);
    }

    echo "OK: registro LLM y fixtures sincronizados con maestro (sha256: $providers_hash)\n";
    exit(0);
}

// Modo sincronización
$lib_dir = dirname($target_providers_php);
if (!is_dir($lib_dir)) {
    mkdir($lib_dir, 0755, true);
}
if (!is_dir($target_fixtures_dir)) {
    mkdir($target_fixtures_dir, 0755, true);
}

if (file_put_contents($target_providers_php, $expected_php) === false) {
    fwrite(STDERR, "ERROR: No se pudo escribir en $target_providers_php\n");
    exit(1);
}

if (file_put_contents($target_parity_json, $parity_raw) === false) {
    fwrite(STDERR, "ERROR: No se pudo escribir en $target_parity_json\n");
    exit(1);
}

echo "Sincronización exitosa:\n";
echo "  - $target_providers_php (sha256: $providers_hash)\n";
echo "  - $target_parity_json (sha256: $parity_hash)\n";
exit(0);
