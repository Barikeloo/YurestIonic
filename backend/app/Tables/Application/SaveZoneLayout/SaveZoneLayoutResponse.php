<?php

declare(strict_types=1);

namespace App\Tables\Application\SaveZoneLayout;

final readonly class SaveZoneLayoutResponse
{
    public function __construct(public int $saved) {}

    public function toArray(): array
    {
        return ['saved' => $this->saved];
    }
}
