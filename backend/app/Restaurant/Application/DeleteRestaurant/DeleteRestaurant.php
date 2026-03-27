<?php

namespace App\Restaurant\Application\DeleteRestaurant;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;
use Illuminate\Support\Facades\DB;

final class DeleteRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return false;
        }

        $restaurantModel = EloquentRestaurant::where('uuid', $id)->first();
        if ($restaurantModel === null) {
            return false;
        }

        // Soft delete all related data in cascading order to respect foreign keys
        DB::transaction(function () use ($restaurantModel) {
            // 1. Delete sales lines (depends on sales, orders, order_lines, products, users)
            EloquentSaleLine::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($saleLine) => $saleLine->delete());

            // 2. Delete sales (depends on sales)
            EloquentSale::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($sale) => $sale->delete());

            // 3. Delete order lines (depends on orders)
            EloquentOrderLine::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($orderLine) => $orderLine->delete());

            // 4. Delete orders
            EloquentOrder::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($order) => $order->delete());

            // 5. Delete products (depends on families and taxes)
            EloquentProduct::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($product) => $product->delete());

            // 6. Delete tables (depends on zones)
            EloquentTable::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($table) => $table->delete());

            // 7. Delete families
            EloquentFamily::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($family) => $family->delete());

            // 8. Delete zones
            EloquentZone::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($zone) => $zone->delete());

            // 9. Delete taxes
            EloquentTax::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($tax) => $tax->delete());

            // 10. Delete users
            EloquentUser::where('restaurant_id', $restaurantModel->id)->get()
                ->each(fn ($user) => $user->delete());

            // 11. Finally, delete the restaurant
            $restaurantModel->delete();
        });

        return true;
    }
}
