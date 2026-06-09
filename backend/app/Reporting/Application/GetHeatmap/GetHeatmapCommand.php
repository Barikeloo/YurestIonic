<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetHeatmap;

final readonly class GetHeatmapCommand
{
    public function __construct(
        public int $restaurantId,
    ) {}
}
