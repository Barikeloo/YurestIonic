<?php

namespace App\SuperAdmin\Domain\Interfaces;

use App\SuperAdmin\Domain\Entity\SuperAdmin;

interface SuperAdminRepositoryInterface
{
    public function findByEmail(string $email): ?SuperAdmin;

    public function findById(string $id): ?SuperAdmin;
}
