<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only be run from the command line.\n");
    exit(1);
}

$projectRoot = __DIR__;
$scriptsDir = $projectRoot . DIRECTORY_SEPARATOR . 'scripts';
$argv = $_SERVER['argv'] ?? [];
$scriptName = $argv[1] ?? '';

if ($scriptName === '' || in_array($scriptName, ['-h', '--help'], true)) {
    echo "Usage: php run.php <script-name> [script-args...]\n";
    echo "Examples:\n";
    echo "  php run.php reset_migration --dry-run\n";
    echo "  php run.php run_daily_depreciation\n";
    exit($scriptName === '' ? 1 : 0);
}

$scriptPath = $scriptsDir . DIRECTORY_SEPARATOR . $scriptName;
if (!str_ends_with($scriptPath, '.php')) {
    $scriptPath .= '.php';
}

$realScriptPath = realpath($scriptPath);
$realScriptsDir = realpath($scriptsDir);

if ($realScriptPath === false || $realScriptsDir === false || !is_file($realScriptPath) || !str_starts_with($realScriptPath, $realScriptsDir . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "Unknown script: {$scriptName}\n");
    fwrite(STDERR, "Available scripts live under: scripts/\n");
    exit(1);
}

$phpBinary = PHP_BINARY;
$scriptArgs = array_slice($argv, 2);
$commandParts = [escapeshellarg($phpBinary), escapeshellarg($realScriptPath)];

foreach ($scriptArgs as $scriptArg) {
    $commandParts[] = escapeshellarg($scriptArg);
}

$command = implode(' ', $commandParts);
passthru($command, $exitCode);
exit($exitCode);