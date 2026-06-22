<?php

declare(strict_types=1);

namespace Tests\Unit\GuestOrder;

use App\GuestOrder\Domain\ValueObject\QrToken;
use PHPUnit\Framework\TestCase;

class QrTokenValueObjectTest extends TestCase
{
    public function test_generate_creates_64_char_hex_token(): void
    {
        $token = QrToken::generate();

        $this->assertSame(64, strlen($token->value()));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->value());
    }

    public function test_create_accepts_valid_64_char_hex(): void
    {
        $raw   = str_repeat('a', 64);
        $token = QrToken::create($raw);

        $this->assertSame($raw, $token->value());
    }

    public function test_create_rejects_token_shorter_than_64(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QrToken::create(str_repeat('a', 63));
    }

    public function test_create_rejects_non_hex_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QrToken::create(str_repeat('z', 64));
    }

    public function test_value_is_lowercase(): void
    {
        $upper = strtoupper(str_repeat('ab', 32));
        $token = QrToken::create($upper);

        $this->assertSame(strtolower($upper), $token->value());
    }

    public function test_two_generated_tokens_are_unique(): void
    {
        $this->assertNotSame(QrToken::generate()->value(), QrToken::generate()->value());
    }
}
