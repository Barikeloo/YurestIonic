<?php

namespace Tests\Unit\SuperAdmin;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Entity\SuperAdmin;
use App\SuperAdmin\Domain\ValueObject\SuperAdminName;
use App\SuperAdmin\Domain\ValueObject\SuperAdminPasswordHash;
use PHPUnit\Framework\TestCase;

class SuperAdminEntityTest extends TestCase
{
    public function test_hydrate_builds_entity_with_value_objects(): void
    {
        $id = Uuid::generate();

        $superAdmin = SuperAdmin::hydrate(
            id: $id,
            name: SuperAdminName::create('Platform Superadmin'),
            email: Email::create('superadmin@example.test'),
            passwordHash: SuperAdminPasswordHash::create(str_repeat('a', 60)),
        );

        $this->assertSame($id->value(), $superAdmin->id()->value());
        $this->assertSame('Platform Superadmin', $superAdmin->name()->value());
        $this->assertSame('superadmin@example.test', $superAdmin->email()->value());
        $this->assertSame(str_repeat('a', 60), $superAdmin->passwordHash()->value());
    }
}
