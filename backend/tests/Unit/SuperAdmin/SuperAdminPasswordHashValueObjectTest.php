<?php

namespace Tests\Unit\SuperAdmin;

use App\SuperAdmin\Domain\ValueObject\SuperAdminPasswordHash;
use PHPUnit\Framework\TestCase;

class SuperAdminPasswordHashValueObjectTest extends TestCase
{
    public function test_create_valid_hash(): void
    {
        $hash = SuperAdminPasswordHash::create(str_repeat('a', 60));

        $this->assertSame(str_repeat('a', 60), $hash->value());
    }

    public function test_create_short_hash_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SuperAdminPasswordHash::create('short');
    }
}
