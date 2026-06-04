<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Export;

use App\Audit\Domain\Entity\AuditLog;

interface AuditExportFormatter
{
    /**
     * Bytes to emit before the first row (e.g. CSV header + BOM).
     * Empty string if the format has no preamble.
     */
    public function header(): string;

    /**
     * Bytes for a single audit log row in the chosen format.
     */
    public function formatRow(AuditLog $log): string;

    /**
     * Bytes to emit after the last row. Empty string for line-delimited
     * formats; here for future formats (e.g. wrapping a JSON array).
     */
    public function footer(): string;
}
