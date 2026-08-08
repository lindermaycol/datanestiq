<?php
/**
 * login.php — Página de autenticación del panel CRM
 * Seguridad: IP whitelist (via auth.php) + bcrypt + rate-limit + CSRF
 */
require_once __DIR__ . '/auth.php';

// Si ya está autenticado, redirigir al panel
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Recarga la página.';
    }
    // Verificar rate-limit
    elseif (!checkLoginRateLimit()) {
        $error = 'Demasiados intentos. Espera 5 minutos.';
    }
    else {
        $password = $_POST['password'] ?? '';
        $hash = getenv('ADMIN_PASSWORD_HASH');

        if (!$hash) {
            $error = 'Credenciales no configuradas. Define ADMIN_PASSWORD_HASH en .env';
        } elseif (password_verify($password, $hash)) {
            // Login exitoso
            session_regenerate_id(true);
            $_SESSION['crm_authenticated'] = true;
            $_SESSION['last_activity'] = time();
            clearLoginAttempts();
            header('Location: index.php');
            exit;
        } else {
            recordLoginAttempt();
            $error = 'Contraseña incorrecta.';
        }
    }
}

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CRM Datanestiq — Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0a0a0f;
            color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #12121a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
        }
        .login-card h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .login-card p {
            color: #888;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .login-card input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            margin-bottom: 1rem;
            outline: none;
        }
        .login-card input[type="password"]:focus {
            border-color: #22d3ee;
        }
        .login-card button {
            width: 100%;
            padding: 0.75rem;
            background: #22d3ee;
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .login-card button:hover {
            background: #06b6d4;
        }
        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .ip-badge {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.75rem;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🔒 CRM Datanestiq</h1>
        <p>Panel interno de gestión de leads. Acceso restringido.</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="password" name="password" placeholder="Contraseña del panel" required autofocus>
            <button type="submit">Acceder</button>
        </form>

        <div class="ip-badge">IP: <?= htmlspecialchars($client_ip) ?></div>
    </div>
</body>
</html>
