<?php

namespace Tests\Unit\SuperAdmin;

use App\SuperAdmin\Domain\ValueObject\SuperAdminName;
use PHPUnit\Framework\TestCase;

class SuperAdminNameValueObjectTest extends TestCase
{
    public function test_create_valid_name(): void
    {
        $name = SuperAdminName::create('Platform Superadmin');

        $this->assertSame('Platform Superadmin', $name->value());
    }

    public function test_create_empty_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SuperAdminName::create('   ');
    }
}
