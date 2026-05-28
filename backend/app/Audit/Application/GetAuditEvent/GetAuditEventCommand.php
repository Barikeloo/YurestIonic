<?php

declare(strict_types=1);

namespace App\Audit\Application\GetAuditEvent;

final readonly class GetAuditEventCommand
{
    public function __construct(
        public string $restaurantId,
        public string $uuid,
    ) {}
}
