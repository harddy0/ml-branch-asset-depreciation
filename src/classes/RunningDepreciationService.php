<?php
namespace App;

class RunningDepreciationService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Called once at asset creation inside the createAsset transaction.
     * Sets up the initial state row — zero elapsed, full periods remaining.
     */
    public function initializeForAsset(int $assetId, float $acquisitionCost, int $months): void
    {
        if ($months <= 0) {
            throw new \RuntimeException('Cannot initialize running depreciation: months must be greater than zero.');
        }

        $stmt = $this->db->prepare('
            INSERT INTO running_depreciation (
                asset_id,
                periods_elapsed,
                periods_remaining,
                accumulated_depreciation,
                book_value,
                last_depreciation_date,
                is_fully_depreciated,
                fully_depreciated_at
            ) VALUES (
                :asset_id,
                0,
                :periods_remaining,
                0.00,
                :book_value,
                NULL,
                0,
                NULL
            )
        ');

        $stmt->execute([
            ':asset_id'          => $assetId,
            ':periods_remaining' => $months,
            ':book_value'        => round($acquisitionCost, 2),
        ]);
    }

    /**
     * Advances running_depreciation for a single asset by syncing it to the
     * matching pre-generated depreciation_ledger row for the given period date.
     *
     * Called by the daily depreciation script (or on-demand runner) instead of
     * doing the math inline in raw SQL.
     *
     * Returns true if the row was advanced, false if nothing was posted
     * (already posted, no ledger row found, or asset fully depreciated).
     */
    public function advanceByLedger(int $assetId, string $periodDate): bool
    {
        // Fetch the matching pre-generated ledger entry
        $ledgerStmt = $this->db->prepare('
            SELECT periods_elapsed,
                   periods_remaining,
                   period_depreciation_expense,
                   accumulated_depreciation,
                   book_value
            FROM depreciation_ledger
            WHERE asset_id  = :asset_id
              AND period_date = :period_date
            LIMIT 1
        ');
        $ledgerStmt->execute([
            ':asset_id'    => $assetId,
            ':period_date' => $periodDate,
        ]);
        $ledger = $ledgerStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$ledger) {
            return false;
        }

        // Guard: do not re-post if already posted on or after this date
        $rdStmt = $this->db->prepare('
            SELECT last_depreciation_date, is_fully_depreciated
            FROM running_depreciation
            WHERE asset_id = :asset_id
            LIMIT 1
        ');
        $rdStmt->execute([':asset_id' => $assetId]);
        $rd = $rdStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$rd) {
            return false;
        }

        if ((int)$rd['is_fully_depreciated'] === 1) {
            return false;
        }

        if (!empty($rd['last_depreciation_date']) && $rd['last_depreciation_date'] >= $periodDate) {
            return false;
        }

        $periodsRemaining   = (int)$ledger['periods_remaining'];
        $isFullyDepreciated = ($periodsRemaining === 0 || round((float)$ledger['book_value'], 2) <= 0.0) ? 1 : 0;
        $fullyDepreciatedAt = $isFullyDepreciated === 1 ? $periodDate : null;

        $updateStmt = $this->db->prepare('
            UPDATE running_depreciation
            SET periods_elapsed          = :periods_elapsed,
                periods_remaining        = :periods_remaining,
                accumulated_depreciation = :accumulated_depreciation,
                book_value               = :book_value,
                last_depreciation_date   = :last_depreciation_date,
                is_fully_depreciated     = :is_fully_depreciated,
                fully_depreciated_at     = :fully_depreciated_at
            WHERE asset_id = :asset_id
        ');

        $updateStmt->execute([
            ':periods_elapsed'          => (int)$ledger['periods_elapsed'],
            ':periods_remaining'        => $periodsRemaining,
            ':accumulated_depreciation' => round((float)$ledger['accumulated_depreciation'], 2),
            ':book_value'               => round((float)$ledger['book_value'], 2),
            ':last_depreciation_date'   => $periodDate,
            ':is_fully_depreciated'     => $isFullyDepreciated,
            ':fully_depreciated_at'     => $fullyDepreciatedAt,
            ':asset_id'                 => $assetId,
        ]);

        return true;
    }

    /**
     * Returns the current state row for an asset.
     */
    public function getState(int $assetId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT *
            FROM running_depreciation
            WHERE asset_id = :asset_id
            LIMIT 1
        ');
        $stmt->execute([':asset_id' => $assetId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}