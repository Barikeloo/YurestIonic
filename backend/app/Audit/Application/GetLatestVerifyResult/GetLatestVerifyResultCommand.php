<?php

declare(strict_types=1);

namespace App\Audit\Application\GetLatestVerifyResult;

final readonly class GetLatestVerifyResultCommand
{
    public function __construct(
        public string $restaurantId,
    ) {}
}
