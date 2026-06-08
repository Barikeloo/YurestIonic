<?php

declare(strict_types=1);

namespace App\Audit\Domain\Interfaces;

use App\Audit\Domain\AuditEventDraft;

interface AuditRecorderInterface
{

    public function record(AuditEventDraft $draft): void;
}
