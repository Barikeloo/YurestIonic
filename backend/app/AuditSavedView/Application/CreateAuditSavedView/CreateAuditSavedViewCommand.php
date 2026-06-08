<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\CreateAuditSavedView;

final readonly class CreateAuditSavedViewCommand
{

    public function __construct(
        public string $restaurantId,
        public string $userId,
        public string $name,
        public ?string $icon,
        public array $filters,
    ) {}
}
