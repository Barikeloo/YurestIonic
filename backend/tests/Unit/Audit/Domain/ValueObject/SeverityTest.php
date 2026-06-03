<?php

namespace Tests\Unit\Audit\Domain\ValueObject;

use App\Audit\Domain\ValueObject\Severity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SeverityTest extends TestCase
{
    #[DataProvider('validSeverityProvider')]
    public function test_create_with_valid_severity(string $value): void
    {
        $severity = Severity::create($value);

        $this->assertSame($value, $severity->value());
    }

    public static function validSeverityProvider(): array
    {
        return [
            'info' => ['info'],
            'warning' => ['warning'],
            'danger' => ['danger'],
            'critical' => ['critical'],
            'success' => ['success'],
        ];
    }

    public function test_create_with_invalid_severity_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid audit severity');

        Severity::create('invalid');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Severity::create('');
    }

    public function test_equals(): void
    {
        $s1 = Severity::create('info');
        $s2 = Severity::create('info');
        $s3 = Severity::create('critical');

        $this->assertTrue($s1->equals($s2));
        $this->assertFalse($s1->equals($s3));
    }
}
