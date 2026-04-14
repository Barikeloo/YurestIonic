<?php

namespace App\SuperAdmin\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Entity\SuperAdmin;

interface SuperAdminRepositoryInterface
{
    public function findByEmail(Email $email): ?SuperAdmin;

    public function findById(Uuid $id): ?SuperAdmin;
}
