<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only be run from the command line.\n");
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$isCheckOnly = in_array('--check', $argv, true) || in_array('--dry-run', $argv, true);

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/src/config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$phpBinary = $_ENV['PHP_BINARY'] ?? (getenv('PHP_BINARY') ?: 'C:\\xampp\\php\\php.exe');
$taskName = $_ENV['TASK_NAME'] ?? (getenv('TASK_NAME') ?: 'ML Branch Daily Depreciation');
$taskTime = $_ENV['TASK_SCHEDULER_RUN_TIME'] ?? (getenv('TASK_SCHEDULER_RUN_TIME') ?: '07:00');
$taskWorkingDir = $_ENV['TASK_SCHEDULER_WORKING_DIR'] ?? (getenv('TASK_SCHEDULER_WORKING_DIR') ?: dirname(__DIR__));

echo '========================================' . PHP_EOL;
echo 'Daily Depreciation Job' . PHP_EOL;
echo '========================================' . PHP_EOL;
echo 'Mode: ' . ($isCheckOnly ? 'CHECK ONLY' : 'EXECUTE') . PHP_EOL;
echo 'Project root: ' . $taskWorkingDir . PHP_EOL;
echo 'PHP binary: ' . $phpBinary . PHP_EOL;
echo 'Task name: ' . $taskName . PHP_EOL;
echo 'Task time: ' . $taskTime . PHP_EOL;
echo 'DB host: ' . DB_HOST . PHP_EOL;
echo 'DB name: ' . DB_NAME . PHP_EOL;
echo 'Base URL: ' . BASE_URL . PHP_EOL;

if ($isCheckOnly) {
    echo 'Environment check passed. No database rows were updated.' . PHP_EOL;
    exit(0);
}

function isLastDayOfMonth(DateTimeImmutable $date): bool
{
    return $date->format('Y-m-d') === $date->modify('last day of this month')->format('Y-m-d');
}

function buildDueDateForMonth(DateTimeImmutable $monthStart, string $schedule, int $specificDay): ?DateTimeImmutable
{
    if ($schedule === 'FIRST_DAY') {
        return $monthStart;
    }

    if ($schedule === 'LAST_DAY') {
        return $monthStart->modify('last day of this month');
    }

    if ($schedule === 'SPECIFIC_DATE') {
        if ($specificDay < 1 || $specificDay > 31) {
            return null;
        }

        $lastDay = (int)$monthStart->modify('last day of this month')->format('j');
        $day = min($specificDay, $lastDay);
        return $monthStart->setDate(
            (int)$monthStart->format('Y'),
            (int)$monthStart->format('n'),
            $day
        );
    }

    return null;
}

function buildCatchUpDueDates(
    DateTimeImmutable $startDate,
    ?DateTimeImmutable $endDate,
    ?DateTimeImmutable $lastDepreciationDate,
    DateTimeImmutable $today,
    string $schedule,
    int $specificDay,
    int $maxPeriods
): array {
    $upperBound = $today;
    if ($endDate !== null && $endDate < $upperBound) {
        $upperBound = $endDate;
    }

    if ($upperBound < $startDate || $maxPeriods <= 0) {
        return [];
    }

    $cursor = new DateTimeImmutable($startDate->format('Y-m-01'));
    $limitMonth = new DateTimeImmutable($upperBound->format('Y-m-01'));
    $dueDates = [];

    while ($cursor <= $limitMonth && count($dueDates) < $maxPeriods) {
        $dueDate = buildDueDateForMonth($cursor, $schedule, $specificDay);

        if (
            $dueDate !== null
            && $dueDate >= $startDate
            && $dueDate <= $upperBound
            && ($lastDepreciationDate === null || $dueDate > $lastDepreciationDate)
        ) {
            $dueDates[] = $dueDate;
        }

        $cursor = $cursor->modify('+1 month');
    }

    return $dueDates;
}

function parseDateOrNull(?string $value): ?DateTimeImmutable
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00') {
        return null;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $e) {
        return null;
    }
}

function isDueToday(array $asset, DateTimeImmutable $today): bool
{
    $schedule = strtoupper(trim((string)($asset['depreciation_on'] ?? '')));

    if ($schedule === 'FIRST_DAY') {
        return $today->format('j') === '1';
    }

    if ($schedule === 'LAST_DAY') {
        return isLastDayOfMonth($today);
    }

    if ($schedule === 'SPECIFIC_DATE') {
        $specificDay = (int)($asset['depreciation_day'] ?? 0);

        if ($specificDay < 1 || $specificDay > 31) {
            return false;
        }

        return (int)$today->format('j') === $specificDay;
    }

    return false;
}

$today = new DateTimeImmutable('today');
$todayYmd = $today->format('Y-m-d');
$now = new DateTimeImmutable('now');

echo '[' . $now->format('Y-m-d H:i:s') . '] Daily depreciation run started for ' . $todayYmd . PHP_EOL;

$selectSql = "
    SELECT
        a.id,
        a.depreciation_on,
        a.depreciation_day,
        a.depreciation_start_date,
        a.depreciation_end_date,
        a.monthly_depreciation,
        a.acquisition_cost,
        rd.periods_elapsed,
        rd.periods_remaining,
        rd.accumulated_depreciation,
        rd.book_value,
        rd.last_depreciation_date,
        rd.is_fully_depreciated
    FROM assets a
    INNER JOIN running_depreciation rd ON rd.asset_id = a.id
    WHERE a.status = 'ACTIVE'
      AND COALESCE(rd.is_fully_depreciated, 0) = 0
      AND rd.periods_remaining > 0
          AND (a.depreciation_start_date IS NULL OR a.depreciation_start_date <= :today_start)
    ORDER BY a.id ASC
";

$selectStmt = $pdo->prepare($selectSql);
$selectStmt->execute([
        ':today_start' => $todayYmd,
]);
$assets = $selectStmt->fetchAll();

$updateSql = "
    UPDATE running_depreciation
    SET periods_elapsed = :periods_elapsed,
        periods_remaining = :periods_remaining,
        accumulated_depreciation = :accumulated_depreciation,
        book_value = :book_value,
        last_depreciation_date = :last_depreciation_date,
        is_fully_depreciated = :is_fully_depreciated,
        fully_depreciated_at = :fully_depreciated_at
    WHERE asset_id = :asset_id
";

$updateStmt = $pdo->prepare($updateSql);

$processed = 0;
$skipped = 0;
$failed = 0;
$periodsPosted = 0;

foreach ($assets as $asset) {
    $schedule = strtoupper(trim((string)($asset['depreciation_on'] ?? '')));
    $specificDay = (int)($asset['depreciation_day'] ?? 1);
    $startDate = parseDateOrNull((string)($asset['depreciation_start_date'] ?? ''));
    $endDate = parseDateOrNull((string)($asset['depreciation_end_date'] ?? ''));
    $lastDepreciationDate = parseDateOrNull((string)($asset['last_depreciation_date'] ?? ''));

    if ($startDate === null) {
        $skipped++;
        continue;
    }

    $monthlyDepreciation = round((float)($asset['monthly_depreciation'] ?? 0), 2);
    $bookValue = round((float)($asset['book_value'] ?? 0), 2);
    $acquisitionCost = round((float)($asset['acquisition_cost'] ?? 0), 2);
    $periodsElapsed = (int)($asset['periods_elapsed'] ?? 0);
    $periodsRemaining = (int)($asset['periods_remaining'] ?? 0);

    if ($monthlyDepreciation <= 0.0 || $bookValue <= 0.0) {
        try {
            $pdo->beginTransaction();

            $finalDate = $lastDepreciationDate?->format('Y-m-d') ?? $todayYmd;

            $updateStmt->execute([
                ':periods_elapsed' => $periodsElapsed,
                ':periods_remaining' => 0,
                ':accumulated_depreciation' => $acquisitionCost,
                ':book_value' => 0.00,
                ':last_depreciation_date' => $finalDate,
                ':is_fully_depreciated' => 1,
                ':fully_depreciated_at' => $finalDate,
                ':asset_id' => (int)$asset['id'],
            ]);

            $pdo->commit();
            $processed++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $failed++;
            fwrite(STDERR, 'Asset ' . (int)$asset['id'] . ' failed: ' . $e->getMessage() . PHP_EOL);
        }

        continue;
    }

    $dueDates = buildCatchUpDueDates(
        $startDate,
        $endDate,
        $lastDepreciationDate,
        $today,
        $schedule,
        $specificDay,
        $periodsRemaining
    );

    if (count($dueDates) === 0) {
        $skipped++;
        continue;
    }

    $newPeriodsElapsed = $periodsElapsed;
    $newPeriodsRemaining = $periodsRemaining;
    $newAccumulatedDepreciation = round((float)($asset['accumulated_depreciation'] ?? 0), 2);
    $newBookValue = $bookValue;
    $lastPostedDate = $lastDepreciationDate;
    $postedThisAsset = 0;

    foreach ($dueDates as $dueDate) {
        if ($newPeriodsRemaining <= 0 || $newBookValue <= 0.0) {
            break;
        }

        $postingAmount = round(min($monthlyDepreciation, $newBookValue), 2);
        if ($postingAmount <= 0.0) {
            break;
        }

        $newPeriodsElapsed++;
        $newPeriodsRemaining = max(0, $newPeriodsRemaining - 1);
        $newAccumulatedDepreciation = round($newAccumulatedDepreciation + $postingAmount, 2);
        $newBookValue = round(max(0.0, $newBookValue - $postingAmount), 2);
        $lastPostedDate = $dueDate;
        $postedThisAsset++;

        if ($newPeriodsRemaining === 0 || $newBookValue <= 0.0) {
            $newAccumulatedDepreciation = $acquisitionCost;
            $newBookValue = 0.00;
            break;
        }
    }

    if ($postedThisAsset === 0 || $lastPostedDate === null) {
        $skipped++;
        continue;
    }

    $isFullyDepreciated = ($newBookValue <= 0.00 || $newPeriodsRemaining === 0) ? 1 : 0;
    $fullyDepreciatedAt = $isFullyDepreciated === 1 ? $lastPostedDate->format('Y-m-d') : null;

    try {
        $pdo->beginTransaction();

        $updateStmt->execute([
            ':periods_elapsed' => $newPeriodsElapsed,
            ':periods_remaining' => $newPeriodsRemaining,
            ':accumulated_depreciation' => $newAccumulatedDepreciation,
            ':book_value' => $newBookValue,
            ':last_depreciation_date' => $lastPostedDate->format('Y-m-d'),
            ':is_fully_depreciated' => $isFullyDepreciated,
            ':fully_depreciated_at' => $fullyDepreciatedAt,
            ':asset_id' => (int)$asset['id'],
        ]);

        $pdo->commit();
        $processed++;
        $periodsPosted += $postedThisAsset;

        echo 'Asset ' . (int)$asset['id']
            . ' updated: periods=' . $postedThisAsset
            . ', last_period=' . $lastPostedDate->format('Y-m-d')
            . ', remaining=' . $newPeriodsRemaining
            . PHP_EOL;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $failed++;
        fwrite(STDERR, 'Asset ' . (int)$asset['id'] . ' failed: ' . $e->getMessage() . PHP_EOL);
    }
}

echo '[' . (new DateTimeImmutable('now'))->format('Y-m-d H:i:s') . '] Daily depreciation run finished. processed=' . $processed . ', skipped=' . $skipped . ', failed=' . $failed . ', periods_posted=' . $periodsPosted . PHP_EOL;

exit($failed > 0 ? 1 : 0);