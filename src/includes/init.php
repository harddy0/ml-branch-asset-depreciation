<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Primary DB — fatal on failure
try {
    $pdo = new \PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS
    );
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Secondary DB — optional, silent fail
$pdo2 = null;
try {
    if (DB2_NAME) {
        $pdo2 = new \PDO(
            "mysql:host=" . DB2_HOST . ";dbname=" . DB2_NAME . ";charset=utf8mb4",
            DB2_USER, DB2_PASS
        );
        $pdo2->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
} catch (\PDOException $e) { /* silent */ }

$auth = new \App\AuthService($pdo);

// Auth middleware
$path     = strtolower(rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')) ?: '/';
$basePath = strtolower(rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/')) ?: '/';

$isPublic =
    in_array($path, [$basePath, $basePath . '/public', $basePath . '/public/index.php', '/'], true)
    || str_contains($path, '/login')
    || str_contains($path, '/forgot_password')
    || str_contains($path, '/actions/login.php')
    || str_contains($path, '/actions/reset_password.php');

if (!$auth->isLoggedIn() && !$isPublic) {
    header('Location: ' . BASE_URL . '/public/login/');
    exit;
}

// Force password-change middleware
if ($auth->isLoggedIn() && !empty($_SESSION['must_change_password'])) {
    $allowed =
        str_contains($path, '/change_password')
        || str_contains($path, '/actions/update_password.php')
        || str_contains($path, '/actions/logout.php');
    if (!$allowed) {
        header('Location: ' . BASE_URL . '/public/change_password/');
        exit;
    }
}

// ── Output buffer + layout/Tailwind injection ─────────────────
ob_start();
register_shutdown_function(function () {
    $content = ob_get_clean();
    global $noLayout;

    if (isset($noLayout) && $noLayout === true) {
        // If this request is intended to return JSON (API), do not inject any HTML
        $isJson = false;
        foreach (headers_list() as $h) {
            if (stripos($h, 'application/json') !== false) { $isJson = true; break; }
        }

        if ($isJson) {
            echo $content;
            return;
        }

        // No layout and not JSON: inject Tailwind into <head> if not already present
        $tailwindTag = '<script src="https://cdn.tailwindcss.com"></script>';
        if (stripos($content, 'cdn.tailwindcss.com') === false) {
            $content = str_ireplace('</head>', $tailwindTag . "\n</head>", $content);
        }
        echo $content;
        return;
    }

    // Normal layout wrap
    $lp = dirname(__DIR__) . '/layouts/main.php';
    file_exists($lp) ? require $lp : print $content;
});