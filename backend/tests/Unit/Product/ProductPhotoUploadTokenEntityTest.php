<?php

namespace Tests\Unit\Product;

use App\Product\Domain\Entity\ProductPhotoUploadToken;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ProductPhotoUploadTokenEntityTest extends TestCase
{
    public function test_ddd_create_builds_a_usable_64_hex_token(): void
    {
        $token = ProductPhotoUploadToken::dddCreate(Uuid::generate(), Uuid::generate(), 10);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token->token());
        $this->assertFalse($token->isUsed());
        $this->assertFalse($token->isExpired());
        $this->assertTrue($token->isUsable());
    }

    public function test_token_with_zero_ttl_is_immediately_expired_and_unusable(): void
    {
        $token = ProductPhotoUploadToken::dddCreate(Uuid::generate(), Uuid::generate(), 0);

        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isUsable());
    }

    public function test_mark_used_makes_token_unusable(): void
    {
        $token = ProductPhotoUploadToken::dddCreate(Uuid::generate(), Uuid::generate(), 10);

        $token->markUsed();

        $this->assertTrue($token->isUsed());
        $this->assertNotNull($token->usedAt());
        $this->assertFalse($token->isUsable());
    }

    public function test_from_persistence_round_trips_state(): void
    {
        $id = Uuid::generate()->value();
        $productId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $token = ProductPhotoUploadToken::fromPersistence(
            id: $id,
            token: str_repeat('a', 64),
            productId: $productId,
            restaurantId: $restaurantId,
            expiresAt: $expiresAt,
            usedAt: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        $this->assertSame($id, $token->id()->value());
        $this->assertSame($productId, $token->productId()->value());
        $this->assertSame($restaurantId, $token->restaurantId()->value());
        $this->assertTrue($token->isUsable());
    }
}
