<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminSelectRestaurantContextController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
        ]);

        $restaurant = EloquentRestaurant::query()->where('uuid', $validated['restaurant_id'])->first();

        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Restaurant not found.',
            ], 404);
        }

        $request->session()->put('tenant_restaurant_uuid', $restaurant->uuid);

        return new JsonResponse([
            'success' => true,
            'restaurant_id' => $restaurant->uuid,
            'name' => $restaurant->name,
        ]);
    }
}
