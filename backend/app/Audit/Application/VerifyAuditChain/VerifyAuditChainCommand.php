<?php

declare(strict_types=1);

namespace App\Audit\Application\VerifyAuditChain;

final readonly class VerifyAuditChainCommand
{
    public function __construct(
        public string $restaurantId,
    ) {}
}
