<?php

namespace App\Restaurant\Infrastructure\Services;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Domain\Interfaces\RestaurantCascadeDeleteInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Support\Facades\DB;

final class EloquentRestaurantCascadeDeleteService implements RestaurantCascadeDeleteInterface
{
    public function deleteByRestaurantUuid(string $restaurantUuid): bool
    {
        $restaurantModel = EloquentRestaurant::query()->where('uuid', $restaurantUuid)->first();

        if ($restaurantModel === null) {
            return false;
        }

        DB::transaction(function () use ($restaurantModel): void {
            EloquentSaleLine::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($saleLine) => $saleLine->delete());

            EloquentSale::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($sale) => $sale->delete());

            EloquentOrderLine::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($orderLine) => $orderLine->delete());

            EloquentOrder::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($order) => $order->delete());

            EloquentProduct::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($product) => $product->delete());

            EloquentTable::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($table) => $table->delete());

            EloquentFamily::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($family) => $family->delete());

            EloquentZone::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($zone) => $zone->delete());

            EloquentTax::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($tax) => $tax->delete());

            EloquentUser::query()->where('restaurant_id', $restaurantModel->id)->get()
                ->each(static fn ($user) => $user->delete());

            $restaurantModel->delete();
        });

        return true;
    }
}
