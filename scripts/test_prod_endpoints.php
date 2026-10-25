<?php
/**
 * scripts/test_prod_endpoints.php
 * Script local para validar que los endpoints de producción devuelven respuestas correctas
 * y que la exclusión del demo funciona adecuadamente en demand_signals y leakage.
 */

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// Mock session y auth
$_SESSION = [
    'crm_authenticated' => true,
    'last_activity' => time()
];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // saltar IP whitelist
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "🧪 Validando API localmente vía CLI (Mock Auth)...\n";

function mockApiCall($action, $get_params = []) {
    // Resetear $_GET
    $_GET = array_merge(['action' => $action], $get_params);
    
    // Capturar output
    ob_start();
    try {
        // Usar include en lugar de require_once por si se llama varias veces,
        // pero api.php usa require_once auth.php. 
        // Como incluimos auth.php y define funciones, no podemos incluirlo múltiples veces.
        // Por ello, ejecutamos cada llamada en un proceso PHP CLI separado usando passthru/exec!
        // Eso es 100% limpio y no colisiona funciones de PHP.
    } catch (Exception $e) {
        ob_end_clean();
        return ['error' => $e->getMessage()];
    }
    ob_end_clean();
}

function runSubprocessApiCall($action, $get_params = []) {
    $queryString = "action=" . $action;
    foreach ($get_params as $k => $v) {
        $queryString .= "&" . urlencode($k) . "=" . urlencode($v);
    }
    
    $basePathEscaped = addslashes(dirname(__DIR__));
    $code = '
        $_SESSION = ["crm_authenticated" => true, "last_activity" => time()];
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_GET = [];
        parse_str("' . $queryString . '", $_GET);
        error_reporting(0);
        ini_set("display_errors", 0);
        
        $basePath = "' . $basePathEscaped . '";
        include $basePath . "/public/admin/api.php";
    ';
    
    $tmpFile = tempnam(sys_get_temp_dir(), 'php_api_test_');
    file_put_contents($tmpFile, "<?php " . $code);
    
    $cmd = "C:\\xampp\\php\\php.exe " . escapeshellarg($tmpFile) . " 2>&1";
    $output = shell_exec($cmd);
    @unlink($tmpFile);
    
    $decoded = json_decode($output, true);
    if ($decoded === null) {
        return ['error' => 'Not JSON', 'raw' => $output];
    }
    return $decoded;
}

// 1. Probar demand_signals con include_demo = 0 (debería dar datos insuficientes si no hay reales, o un total menor)
$res_demand_real = runSubprocessApiCall('demand_signals', ['include_demo' => 0]);
echo "1. demand_signals (include_demo=0): ";
if (is_array($res_demand_real) && isset($res_demand_real['insufficient_data']) && $res_demand_real['insufficient_data']) {
    echo "✅ Correcto: Datos insuficientes (" . $res_demand_real['total'] . " reales)\n";
} else {
    echo "⚠️  Datos suficientes sin demo? Total reales: " . (is_array($res_demand_real) ? ($res_demand_real['total'] ?? 0) : 'Error') . "\n";
}

// 2. Probar demand_signals con include_demo = 1 (debería dar datos suficientes por el demo, total >= 20)
$res_demand_demo = runSubprocessApiCall('demand_signals', ['include_demo' => 1]);
echo "2. demand_signals (include_demo=1): ";
if (is_array($res_demand_demo) && isset($res_demand_demo['insufficient_data']) && $res_demand_demo['insufficient_data'] === false) {
    echo "✅ Correcto: Datos suficientes (" . $res_demand_demo['total'] . " totales)\n";
} else {
    echo "❌ Fallo: Sigue marcando datos insuficientes con demo? " . json_encode($res_demand_demo) . "\n";
}

// 3. Probar leakage con include_demo = 0
$res_leakage_real = runSubprocessApiCall('leakage', ['include_demo' => 0]);
echo "3. leakage (include_demo=0): ";
if (is_array($res_leakage_real) && isset($res_leakage_real['insufficient_data']) && $res_leakage_real['insufficient_data']) {
    echo "✅ Correcto: Datos insuficientes (" . $res_leakage_real['total'] . " reales)\n";
} else {
    echo "⚠️  Datos suficientes sin demo? Total reales: " . (is_array($res_leakage_real) ? ($res_leakage_real['total'] ?? 0) : 'Error') . "\n";
}

// 4. Probar leakage con include_demo = 1
$res_leakage_demo = runSubprocessApiCall('leakage', ['include_demo' => 1]);
echo "4. leakage (include_demo=1): ";
if (is_array($res_leakage_demo) && isset($res_leakage_demo['insufficient_data']) && $res_leakage_demo['insufficient_data'] === false) {
    echo "✅ Correcto: Datos suficientes (" . $res_leakage_demo['total'] . " totales)\n";
} else {
    echo "❌ Fallo: Sigue marcando datos insuficientes con demo? " . json_encode($res_leakage_demo) . "\n";
}

// 5. Probar journey_sessions con include_demo = 0
$res_sessions_real = runSubprocessApiCall('journey_sessions', ['include_demo' => 0]);
echo "5. journey_sessions (include_demo=0): ";
$demo_found_real = false;
if (is_array($res_sessions_real) && isset($res_sessions_real['sessions'])) {
    foreach ($res_sessions_real['sessions'] as $s) {
        if (strpos($s['session_id'], 'demoseed') === 0) $demo_found_real = true;
    }
    echo ($demo_found_real ? "❌ Error: Encontradas sesiones demo" : "✅ Correcto: 0 sesiones demo encontradas") . " (Total: " . count($res_sessions_real['sessions']) . ")\n";
} else {
    echo "❌ Error cargando sesiones: " . json_encode($res_sessions_real) . "\n";
}

// 6. Probar journey_sessions con include_demo = 1
$res_sessions_demo = runSubprocessApiCall('journey_sessions', ['include_demo' => 1]);
echo "6. journey_sessions (include_demo=1): ";
$demo_found_demo = false;
if (is_array($res_sessions_demo) && isset($res_sessions_demo['sessions'])) {
    foreach ($res_sessions_demo['sessions'] as $s) {
        if (strpos($s['session_id'], 'demoseed') === 0) $demo_found_demo = true;
    }
    echo ($demo_found_demo ? "✅ Correcto: Sesiones demo encontradas" : "❌ Error: Ninguna sesión demo encontrada") . " (Total: " . count($res_sessions_demo['sessions']) . ")\n";
} else {
    echo "❌ Error cargando sesiones con demo: " . json_encode($res_sessions_demo) . "\n";
}
