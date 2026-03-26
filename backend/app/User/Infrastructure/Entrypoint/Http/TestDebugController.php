<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestDebugController
{
    public function __invoke(Request $request): JsonResponse
    {
        // Buscar el primer restaurante (o uno específico si lo especifican)
        $restaurantQuery = EloquentRestaurant::query();

        $restaurantName = $request->query('name');
        if ($restaurantName) {
            $restaurantQuery->where('name', $restaurantName);
        }

        $restaurant = $restaurantQuery->first();

        if (! $restaurant) {
            // Si no encuentra restaurante específico, devolver lista de todos
            $allRestaurants = EloquentRestaurant::query()
                ->select('id', 'uuid', 'name')
                ->get()
                ->toArray();

            return new JsonResponse([
                'error' => 'Restaurant not found',
                'query_name' => $restaurantName,
                'available_restaurants' => $allRestaurants,
            ], 404);
        }

        // Buscar todos los usuarios de ese restaurante
        $users = EloquentUser::query()
            ->where('restaurant_id', $restaurant->id)
            ->get(['uuid', 'name', 'email', 'role', 'restaurant_id']);

        // Verificar si los usuarios tienen restaurant_id
        $usersList = $users->map(function ($user) {
            return [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'restaurant_id' => $user->restaurant_id,
                'restaurant_id_is_numeric' => is_numeric($user->restaurant_id),
            ];
        })->toArray();

        return new JsonResponse([
            'restaurant_id' => $restaurant->id,
            'restaurant_uuid' => $restaurant->uuid,
            'restaurant_name' => $restaurant->name,
            'users_count' => $users->count(),
            'users' => $usersList,
        ]);
    }
}

