<?php

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailValueObjectTest extends TestCase
{
    public function test_create_with_valid_email(): void
    {
        $email = Email::create('user+test@example.com');

        $this->assertSame('user+test@example.com', $email->value());
    }

    public function test_create_with_invalid_email_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email: invalid-email');

        Email::create('invalid-email');
    }
}
