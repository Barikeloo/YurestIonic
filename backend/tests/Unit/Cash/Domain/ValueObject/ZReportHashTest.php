<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\ZReportHash;
use PHPUnit\Framework\TestCase;

class ZReportHashTest extends TestCase
{
    public function test_create_with_valid_hash(): void
    {
        $hash = ZReportHash::create('abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890');

        $this->assertInstanceOf(ZReportHash::class, $hash);
    }

    public function test_create_lowercases_value(): void
    {
        $hash = ZReportHash::create('ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890ABCDEF1234567890');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash->value());
    }

    public function test_value_returns_hash_string(): void
    {
        $hex = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';
        $hash = ZReportHash::create($hex);

        $this->assertSame($hex, $hash->value());
    }

    public function test_create_with_too_short_hash_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZReportHash::create('abc');
    }

    public function test_create_with_non_hex_characters_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZReportHash::create('zbcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZReportHash::create('');
    }
}
