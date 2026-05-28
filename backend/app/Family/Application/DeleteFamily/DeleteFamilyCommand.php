<?php

namespace App\Family\Application\DeleteFamily;

final readonly class DeleteFamilyCommand
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
