<?php

declare(strict_types=1);

namespace Tests\Unit\GuestOrder;

use App\GuestOrder\Domain\ValueObject\IdentityMode;
use PHPUnit\Framework\TestCase;

class IdentityModeValueObjectTest extends TestCase
{
    public function test_anonymous_factory(): void
    {
        $mode = IdentityMode::anonymous();
        $this->assertSame('anonymous', $mode->value());
        $this->assertTrue($mode->isAnonymous());
        $this->assertFalse($mode->isNamed());
        $this->assertFalse($mode->isRegistered());
    }

    public function test_named_factory(): void
    {
        $mode = IdentityMode::named();
        $this->assertSame('named', $mode->value());
        $this->assertTrue($mode->isNamed());
    }

    public function test_registered_factory(): void
    {
        $mode = IdentityMode::registered();
        $this->assertSame('registered', $mode->value());
        $this->assertTrue($mode->isRegistered());
    }

    public function test_create_from_valid_string(): void
    {
        $this->assertSame('anonymous', IdentityMode::create('anonymous')->value());
        $this->assertSame('named', IdentityMode::create('named')->value());
        $this->assertSame('registered', IdentityMode::create('registered')->value());
    }

    public function test_create_rejects_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        IdentityMode::create('unknown');
    }
}
