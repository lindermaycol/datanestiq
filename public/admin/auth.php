<?php
/**
 * auth.php — Middleware de seguridad para el panel CRM interno
 * Incluir al inicio de cada archivo del panel.
 *
 * Seguridad endurecida:
 * 1. IP whitelist como PRIMERA barrera (antes de todo)
 * 2. Sesión PHP segura (HttpOnly, Secure, SameSite=Strict, timeout)
 * 3. Bcrypt password_verify (ADMIN_PASSWORD_HASH en .env)
 * 4. Rate-limiting anti-fuerza-bruta en login
 * 5. CSRF token en POSTs
 */

// --- Cargar .env ---
function loadEnvAuth($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
        }
    }
}
loadEnvAuth(__DIR__ . '/../../.env');

// --- 1. IP WHITELIST (PRIMERA BARRERA) ---
$allowed_ips_raw = getenv('ALLOWED_IPS') ?: '127.0.0.1,::1';
$allowed_ips = array_map('trim', explode(',', $allowed_ips_raw));
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

$is_allowed = in_array('*', $allowed_ips, true) || in_array($client_ip, $allowed_ips, true);
if (!$is_allowed) {
    foreach ($allowed_ips as $ip_pattern) {
        if ($ip_pattern !== '' && strpos($ip_pattern, '*') !== false) {
            $pattern_regex = '/^' . str_replace('\*', '[0-9]+', preg_quote($ip_pattern, '/')) . '$/';
            if (preg_match($pattern_regex, $client_ip)) {
                $is_allowed = true;
                break;
            }
        }
    }
}

if (!$is_allowed) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1></body></html>';
    exit;
}

// --- 2. SESION SEGURA ---
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
if ($is_https) {
    ini_set('session.cookie_secure', '1');
}

$session_timeout = 1800; // 30 minutos de inactividad

session_start();

// Verificar timeout de inactividad
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $session_timeout) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// --- 3. FUNCIONES DE AUTH ---
function isAuthenticated() {
    return isset($_SESSION['crm_authenticated']) && $_SESSION['crm_authenticated'] === true;
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

// --- 4. RATE-LIMITING PARA LOGIN ---
function checkLoginRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_file = sys_get_temp_dir() . '/crm_login_' . md5($ip) . '.json';
    $max_attempts = 5;
    $window = 300; // 5 minutos

    $attempts = [];
    if (file_exists($rate_file)) {
        $content = @file_get_contents($rate_file);
        if ($content) {
            $attempts = json_decode($content, true) ?: [];
        }
    }

    $now = time();
    $attempts = array_filter($attempts, function ($t) use ($now, $window) {
        return ($now - $t) < $window;
    });

    if (count($attempts) >= $max_attempts) {
        return false; // Bloqueado
    }

    return true;
}

function recordLoginAttempt() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_file = sys_get_temp_dir() . '/crm_login_' . md5($ip) . '.json';
    $window = 300;

    $attempts = [];
    if (file_exists($rate_file)) {
        $content = @file_get_contents($rate_file);
        if ($content) {
            $attempts = json_decode($content, true) ?: [];
        }
    }

    $now = time();
    $attempts = array_filter($attempts, function ($t) use ($now, $window) {
        return ($now - $t) < $window;
    });
    $attempts[] = $now;

    @file_put_contents($rate_file, json_encode(array_values($attempts)));
}

function clearLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_file = sys_get_temp_dir() . '/crm_login_' . md5($ip) . '.json';
    @unlink($rate_file);
}

// --- 5. CSRF ---
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// --- 6. CONEXION A BD ---
function getCrmDb() {
    $db_path = __DIR__ . '/../../secure_leads/crm.sqlite';
    if (!file_exists($db_path)) {
        throw new RuntimeException('CRM database not found. Run: php scripts/init_crm_db.php');
    }
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
    return $db;
}
