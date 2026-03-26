<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestDebugLoginController
{
    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->query('email');

        if (! $email) {
            return new JsonResponse([
                'error' => 'Email parameter required',
            ], 400);
        }

        $user = EloquentUser::query()->where('email', $email)->first();

        if (! $user) {
            return new JsonResponse([
                'error' => 'User not found',
                'email' => $email,
            ], 404);
        }

        $restaurantData = null;
        if (is_numeric($user->restaurant_id)) {
            $restaurant = EloquentRestaurant::query()->find((int) $user->restaurant_id);
            if ($restaurant) {
                $restaurantData = [
                    'id' => $restaurant->id,
                    'uuid' => $restaurant->uuid,
                    'name' => $restaurant->name,
                ];
            }
        }

        return new JsonResponse([
            'user_uuid' => $user->uuid,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'restaurant_id_in_db' => $user->restaurant_id,
            'restaurant_id_is_numeric' => is_numeric($user->restaurant_id),
            'restaurant_found' => $restaurantData ? true : false,
            'restaurant_data' => $restaurantData,
        ]);
    }
}
