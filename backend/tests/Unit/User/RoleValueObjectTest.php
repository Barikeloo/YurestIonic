<?php

namespace Tests\Unit\User;

use App\User\Domain\ValueObject\Role;
use PHPUnit\Framework\TestCase;

class RoleValueObjectTest extends TestCase
{
    public function test_static_constructors_create_expected_roles(): void
    {
        $operator = Role::operator();
        $supervisor = Role::supervisor();
        $admin = Role::admin();

        $this->assertSame(Role::OPERATOR, $operator->value());
        $this->assertSame(Role::SUPERVISOR, $supervisor->value());
        $this->assertSame(Role::ADMIN, $admin->value());
    }

    public function test_role_helper_methods_match_current_role(): void
    {
        $role = Role::create(Role::SUPERVISOR);

        $this->assertFalse($role->isOperator());
        $this->assertTrue($role->isSupervisor());
        $this->assertFalse($role->isAdmin());
    }

    public function test_create_with_invalid_role_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role: guest');

        Role::create('guest');
    }
}
