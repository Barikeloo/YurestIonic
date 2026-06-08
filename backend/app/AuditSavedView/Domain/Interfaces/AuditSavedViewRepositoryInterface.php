<?php

declare(strict_types=1);

namespace App\AuditSavedView\Domain\Interfaces;

use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\Shared\Domain\ValueObject\Uuid;

interface AuditSavedViewRepositoryInterface
{

    public function listByRestaurantAndUser(Uuid $restaurantId, Uuid $userId): array;

    public function findByUuid(Uuid $restaurantId, Uuid $uuid): ?AuditSavedView;

    public function save(AuditSavedView $auditSavedView): void;

    public function delete(Uuid $restaurantId, Uuid $uuid): void;
}
