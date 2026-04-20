<?php
$envFile = dirname(__DIR__, 2) . '/.env';
if (!file_exists($envFile)) die('Missing .env — copy .env.example to .env');

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;
    if (str_contains($line, '=')) {
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

define('DB_HOST',  $_ENV['DB_HOST']  ?? 'localhost');
define('DB_NAME',  $_ENV['DB_NAME']  ?? '');
define('DB_USER',  $_ENV['DB_USER']  ?? '');
define('DB_PASS',  $_ENV['DB_PASS']  ?? '');
define('DB2_HOST', $_ENV['DB2_HOST'] ?? 'localhost');
define('DB2_NAME', $_ENV['DB2_NAME'] ?? '');
define('DB2_USER', $_ENV['DB2_USER'] ?? '');
define('DB2_PASS', $_ENV['DB2_PASS'] ?? '');
define('BASE_URL',  $_ENV['BASE_URL'] ?? '');
define('ASSET_URL', BASE_URL . '/public/assets/');
