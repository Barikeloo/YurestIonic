<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class OrderFinalTicketNotFoundException extends \DomainException
{
    public static function withOrderId(string $orderId): self
    {
        return new self("Order final ticket for order {$orderId} not found.");
    }
}
