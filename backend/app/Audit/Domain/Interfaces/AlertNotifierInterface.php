<?php

declare(strict_types=1);

namespace App\Audit\Domain\Interfaces;

use App\Audit\Domain\AuditEventDraft;

interface AlertNotifierInterface
{
    public function notifyCriticalAnomaly(AuditEventDraft $draft, string $anomalyKind, ?string $auditLogUuid = null): void;
}
