<?php

namespace App\SuperAdmin\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Entity\SuperAdmin;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\SuperAdmin\Domain\ValueObject\SuperAdminName;
use App\SuperAdmin\Domain\ValueObject\SuperAdminPasswordHash;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;

final class EloquentSuperAdminRepository implements SuperAdminRepositoryInterface
{
    public function __construct(
        private EloquentSuperAdmin $model,
    ) {}

    public function findByEmail(Email $email): ?SuperAdmin
    {
        $model = $this->model->newQuery()->where('email', $email->value())->first();

        if ($model === null) {
            return null;
        }

        return SuperAdmin::hydrate(
            Uuid::create($model->uuid),
            SuperAdminName::create($model->name),
            Email::create($model->email),
            SuperAdminPasswordHash::create($model->password),
        );
    }

    public function findById(Uuid $id): ?SuperAdmin
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();

        if ($model === null) {
            return null;
        }

        return SuperAdmin::hydrate(
            Uuid::create($model->uuid),
            SuperAdminName::create($model->name),
            Email::create($model->email),
            SuperAdminPasswordHash::create($model->password),
        );
    }
}
