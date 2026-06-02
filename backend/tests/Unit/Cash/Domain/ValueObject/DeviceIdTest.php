<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\DeviceId;
use PHPUnit\Framework\TestCase;

class DeviceIdTest extends TestCase
{
    public function test_create_with_valid_device_id(): void
    {
        $deviceId = DeviceId::create('device-abc-123');

        $this->assertInstanceOf(DeviceId::class, $deviceId);
        $this->assertSame('device-abc-123', $deviceId->value());
    }

    public function test_create_trims_whitespace(): void
    {
        $deviceId = DeviceId::create('  device-abc  ');

        $this->assertSame('device-abc', $deviceId->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DeviceId::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DeviceId::create('   ');
    }

    public function test_create_with_exceeding_max_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DeviceId::create(str_repeat('a', 101));
    }

    public function test_create_with_max_length(): void
    {
        $value = str_repeat('a', 100);
        $deviceId = DeviceId::create($value);

        $this->assertSame($value, $deviceId->value());
    }
}
