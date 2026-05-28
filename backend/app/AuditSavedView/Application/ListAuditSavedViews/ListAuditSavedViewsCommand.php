<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\ListAuditSavedViews;

final readonly class ListAuditSavedViewsCommand
{
    public function __construct(
        public string $restaurantId,
        public string $userId,
    ) {}
}
