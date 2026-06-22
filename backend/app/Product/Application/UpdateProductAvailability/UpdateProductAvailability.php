<?php

declare(strict_types=1);

namespace App\Product\Application\UpdateProductAvailability;

use App\Product\Domain\Event\ProductAvailabilityChanged;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Shared\Application\Event\EventBusInterface;
use Illuminate\Support\Facades\DB;

final class UpdateProductAvailability
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateProductAvailabilityCommand $command): UpdateProductAvailabilityResponse
    {
        $product = DB::table('products')
            ->join('restaurants', 'restaurants.id', '=', 'products.restaurant_id')
            ->where('products.uuid', $command->productId)
            ->whereNull('products.deleted_at')
            ->select(['products.id', 'products.uuid', 'products.name', 'restaurants.uuid as restaurant_uuid'])
            ->first();

        if ($product === null) {
            throw ProductNotFoundException::withId($command->productId);
        }

        DB::table('products')
            ->where('id', $product->id)
            ->update(['available' => $command->available, 'updated_at' => now()]);

        $this->eventBus->publish(new ProductAvailabilityChanged(
            productId: $product->uuid,
            productName: $product->name,
            available: $command->available,
            restaurantId: $product->restaurant_uuid,
        ));

        return UpdateProductAvailabilityResponse::create($product->uuid, $command->available);
    }
}
