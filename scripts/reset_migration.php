<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only be run from the command line.\n");
    exit(1);
}

$options = getopt('', ['base-dir::', 'force', 'yes', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Usage: php scripts/reset_migration.php [--base-dir=PATH] [--force] [--dry-run]\n";
    echo "  --base-dir  Project root to use when resolving files. Defaults to the current working directory.\n";
    echo "  --force     Skip the confirmation prompt.\n";
    echo "  --dry-run   Print the actions without modifying the database.\n";
    exit(0);
}

$workingDir = getcwd();
$baseDirOption = $options['base-dir'] ?? null;
$candidateRoots = [];

if (is_string($baseDirOption) && trim($baseDirOption) !== '') {
    $candidateRoots[] = $baseDirOption;
}

if ($workingDir !== false && $workingDir !== '') {
    $candidateRoots[] = $workingDir;
}

$candidateRoots[] = dirname(__DIR__);

$projectRoot = null;
foreach ($candidateRoots as $candidateRoot) {
    $resolvedRoot = realpath($candidateRoot);
    if ($resolvedRoot !== false && is_file($resolvedRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
        $projectRoot = $resolvedRoot;
        break;
    }
}

if ($projectRoot === null) {
    fwrite(STDERR, "Unable to resolve the project root. Run the script from the repository root or pass --base-dir=PATH.\n");
    exit(1);
}

$configPath = $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once $configPath;

if (trim((string)DB_NAME) === '') {
    fwrite(STDERR, "DB_NAME is not configured in .env.\n");
    exit(1);
}

$isDryRun = isset($options['dry-run']);
$isForced = isset($options['force']) || isset($options['yes']);
$databaseName = DB_NAME;

echo "========================================\n";
echo "Reset Migration\n";
echo "========================================\n";
echo "Project root: {$projectRoot}\n";
echo "Working dir: " . ($workingDir !== false ? $workingDir : '(unknown)') . "\n";
echo "Database: {$databaseName}\n";
echo "Mode: " . ($isDryRun ? 'DRY RUN' : 'EXECUTE') . "\n";

if (!$isForced && !$isDryRun) {
    echo "This will DROP and recreate the database, then reapply the schema. Continue? [y/N]: ";
    $answer = strtolower(trim((string)fgets(STDIN)));
    if (!in_array($answer, ['y', 'yes'], true)) {
        echo "Aborted.\n";
        exit(0);
    }
}

$serverDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
$schemaFile = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'schema.sql';

if (!is_file($schemaFile)) {
    fwrite(STDERR, "Missing schema file: {$schemaFile}\n");
    exit(1);
}

$schemaSql = file_get_contents($schemaFile);
if ($schemaSql === false || trim($schemaSql) === '') {
    fwrite(STDERR, "Schema file is empty or unreadable: {$schemaFile}\n");
    exit(1);
}

if ($isDryRun) {
    echo "Dry run selected. The following SQL would be executed:\n";
    echo "----------------------------------------\n";
    echo $schemaSql . "\n";
    exit(0);
}

try {
    $serverPdo = new PDO(
        $serverDsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    fwrite(STDERR, 'Database connection failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';

    foreach (preg_split('/\R/', $sql) ?: [] as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }

        $buffer .= $line . "\n";

        if (str_ends_with($trimmed, ';')) {
            $statement = trim(substr($buffer, 0, -1));
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $leftover = trim($buffer);
    if ($leftover !== '') {
        $statements[] = $leftover;
    }

    return $statements;
}

function executeStatements(PDO $pdo, string $sql): void
{
    foreach (splitSqlStatements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

try {
    $serverPdo->exec('DROP DATABASE IF EXISTS `' . str_replace('`', '``', $databaseName) . '`');
    $serverPdo->exec('CREATE DATABASE `' . str_replace('`', '``', $databaseName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $serverPdo->exec('USE `' . str_replace('`', '``', $databaseName) . '`');
    executeStatements($serverPdo, $schemaSql);
} catch (PDOException $exception) {
    fwrite(STDERR, 'Reset migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo "Reset migration completed successfully.\n";
exit(0);