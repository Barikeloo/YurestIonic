<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Repositories;

use App\Product\Domain\Entity\ProductPhotoUploadToken;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Product\Infrastructure\Persistence\Models\EloquentProductPhotoUploadToken;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;

class EloquentProductPhotoUploadTokenRepository implements ProductPhotoUploadTokenRepositoryInterface
{
    public function __construct(
        private EloquentProductPhotoUploadToken $model,
    ) {}

    public function findByToken(string $token): ?ProductPhotoUploadToken
    {
        $model = $this->model->newQuery()
            ->with(['product', 'restaurant'])
            ->where('token', $token)
            ->first();

        if ($model === null || $model->product === null || $model->restaurant === null) {
            return null;
        }

        return ProductPhotoUploadToken::fromPersistence(
            id: $model->uuid,
            token: $model->token,
            productId: $model->product->uuid,
            restaurantId: $model->restaurant->uuid,
            expiresAt: $model->expires_at->toDateTimeImmutable(),
            usedAt: $model->used_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function save(ProductPhotoUploadToken $token): void
    {
        $product = EloquentProduct::query()
            ->withoutGlobalScopes()
            ->where('uuid', $token->productId()->value())
            ->firstOrFail();

        $restaurant = EloquentRestaurant::query()
            ->where('uuid', $token->restaurantId()->value())
            ->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $token->id()->value()],
            [
                'token' => $token->token(),
                'product_id' => $product->id,
                'restaurant_id' => $restaurant->id,
                'expires_at' => $token->expiresAt()->value(),
                'used_at' => $token->usedAt()?->value(),
                'created_at' => $token->createdAt()->value(),
                'updated_at' => $token->updatedAt()->value(),
            ],
        );
    }

    public function markAsUsed(ProductPhotoUploadToken $token): void
    {
        $affected = $this->model->newQuery()
            ->where('uuid', $token->id()->value())
            ->whereNull('used_at')
            ->update([
                'used_at' => $token->usedAt()?->value() ?? now(),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            throw ProductPhotoUploadTokenAlreadyUsedException::withToken($token->token());
        }
    }

    public function deleteExpired(): int
    {
        return $this->model->newQuery()
            ->where('expires_at', '<', now())
            ->delete();
    }
}
