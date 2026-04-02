<?php

namespace App\SuperAdmin\Infrastructure\Persistence\Repositories;

use App\SuperAdmin\Domain\Entity\SuperAdmin;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;

final class EloquentSuperAdminRepository implements SuperAdminRepositoryInterface
{
    public function findByEmail(string $email): ?SuperAdmin
    {
        $model = EloquentSuperAdmin::query()->where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return SuperAdmin::fromPersistence(
            $model->uuid,
            $model->name,
            $model->email,
            $model->password,
        );
    }

    public function findById(string $id): ?SuperAdmin
    {
        $model = EloquentSuperAdmin::query()->where('uuid', $id)->first();

        if ($model === null) {
            return null;
        }

        return SuperAdmin::fromPersistence(
            $model->uuid,
            $model->name,
            $model->email,
            $model->password,
        );
    }
}
