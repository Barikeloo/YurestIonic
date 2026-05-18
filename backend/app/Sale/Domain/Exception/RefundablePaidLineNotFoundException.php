<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class RefundablePaidLineNotFoundException extends \DomainException
{
    public static function forLine(string $orderLineId, string $chargeSessionId): self
    {
        return new self(
            "No active paid sale found for order line {$orderLineId} in charge session {$chargeSessionId}."
        );
    }
}
