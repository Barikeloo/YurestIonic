<?php

namespace Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_create_with_valid_email(): void
    {
        $email = Email::create('test@example.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame('test@example.com', $email->value());
    }

    public function test_create_with_invalid_email_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::create('not-an-email');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::create('');
    }

    public function test_create_with_email_with_plus_sign(): void
    {
        $email = Email::create('test+tag@example.com');

        $this->assertSame('test+tag@example.com', $email->value());
    }

    public function test_create_with_subdomain_email(): void
    {
        $email = Email::create('user@sub.example.co.uk');

        $this->assertSame('user@sub.example.co.uk', $email->value());
    }
}
