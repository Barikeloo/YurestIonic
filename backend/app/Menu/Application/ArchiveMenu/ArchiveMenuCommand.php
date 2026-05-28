<?php

declare(strict_types=1);

namespace App\Menu\Application\ArchiveMenu;

final readonly class ArchiveMenuCommand
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
