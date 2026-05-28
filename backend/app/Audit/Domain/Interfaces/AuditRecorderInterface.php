<?php

declare(strict_types=1);

namespace App\Audit\Domain\Interfaces;

use App\Audit\Domain\AuditEventDraft;

interface AuditRecorderInterface
{
    /**
     * Persists an audit event. Resolves category/severity/summary from the catalog,
     * runs the anomaly detector, chains the integrity hash, and writes the row.
     *
     * Per project policy, this method MUST NOT throw. If recording fails (DB down,
     * catalog mismatch, etc.) the implementation logs via report() and returns
     * silently — the business operation must never be rolled back by an audit failure.
     */
    public function record(AuditEventDraft $draft): void;
}
