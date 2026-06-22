<?php

namespace Tests\Unit\User;

use App\User\Domain\ValueObject\UserName;
use PHPUnit\Framework\TestCase;

class UserNameValueObjectTest extends TestCase
{
    public function test_create_trims_user_name(): void
    {
        $name = UserName::create('  John Doe  ');

        $this->assertSame('John Doe', $name->value());
    }

    public function test_create_with_empty_user_name_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User name cannot be empty.');

        UserName::create('   ');
    }

    public function test_create_with_name_over_max_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserName::create(str_repeat('a', 256));
    }
}
