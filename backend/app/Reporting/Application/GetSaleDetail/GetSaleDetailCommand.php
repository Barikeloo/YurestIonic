<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetSaleDetail;

final readonly class GetSaleDetailCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $saleUuid,
    ) {}
}
