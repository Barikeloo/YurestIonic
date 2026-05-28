<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\DeleteAuditSavedView;

final readonly class DeleteAuditSavedViewCommand
{
    public function __construct(
        public string $restaurantId,
        public string $userId,
        public string $uuid,
    ) {}
}
