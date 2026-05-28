<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\UpdateAuditSavedView;

final readonly class UpdateAuditSavedViewCommand
{
    /**
     * @param array<string, mixed>|null $filters
     */
    public function __construct(
        public string $restaurantId,
        public string $userId,
        public string $uuid,
        public ?string $name,
        public ?string $icon,
        public ?array $filters,
    ) {}
}
