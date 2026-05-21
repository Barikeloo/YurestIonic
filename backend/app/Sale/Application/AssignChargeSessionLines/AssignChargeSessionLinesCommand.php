<?php

declare(strict_types=1);

namespace App\Sale\Application\AssignChargeSessionLines;

final readonly class AssignChargeSessionLinesCommand
{
    /**
     * @param  array<int, array{order_line_id: string, diner_number: int}>  $assignments
     */
    public function __construct(
        public string $chargeSessionId,
        public array $assignments,
    ) {}
}
