<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminSelectRestaurantContextController
{
    public function __invoke(Request $request): JsonResponse
    {
        $authUserId = $request->session()->get('auth_user_id');

        if (! is_string($authUserId) || $authUserId === '') {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = EloquentUser::query()->where('uuid', $authUserId)->first();

        if ($user === null) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return new JsonResponse([
                'message' => 'Forbidden.',
            ], 403);
        }

        if (! is_numeric($user->restaurant_id)) {
            return new JsonResponse([
                'message' => 'Admin user has no linked restaurant.',
            ], 403);
        }

        $userRestaurant = EloquentRestaurant::query()->find((int) $user->restaurant_id);

        if ($userRestaurant === null) {
            return new JsonResponse([
                'message' => 'Linked restaurant not found.',
            ], 404);
        }

        $userTaxId = $userRestaurant->tax_id;

        if (! is_string($userTaxId) || $userTaxId === '') {
            return new JsonResponse([
                'message' => 'Linked restaurant has no tax id.',
            ], 422);
        }

        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
        ]);

        $restaurant = EloquentRestaurant::query()->where('uuid', $validated['restaurant_id'])->first();

        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Restaurant not found.',
            ], 404);
        }

        if ($restaurant->tax_id !== $userTaxId) {
            return new JsonResponse([
                'message' => 'Forbidden for this tax id.',
            ], 403);
        }

        $request->session()->put('tenant_restaurant_uuid', $restaurant->uuid);

        return new JsonResponse([
            'success' => true,
            'restaurant_id' => $restaurant->uuid,
            'name' => $restaurant->name,
        ]);
    }
}
