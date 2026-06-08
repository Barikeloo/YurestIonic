<?php

declare(strict_types=1);

namespace App\Menu\Application\UpdateMenu;

use App\Menu\Application\Shared\MenuSectionInput;

final readonly class UpdateMenuCommand
{

    public function __construct(
        public string $id,
        public string $taxId,
        public string $name,
        public ?string $description,
        public int $price,
        public ?string $validityFrom,
        public ?string $validityTo,
        public int $availableDays,
        public ?string $availableFromTime,
        public ?string $availableToTime,
        public bool $active,
        public array $sections,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
