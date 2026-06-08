<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Export;

use App\Audit\Domain\Entity\AuditLog;

interface AuditExportFormatter
{

    public function header(): string;

    public function formatRow(AuditLog $log): string;

    public function footer(): string;
}
