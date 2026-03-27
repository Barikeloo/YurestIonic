<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
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

        $superAdminUuid = $request->session()->get('super_admin_id');

        if (is_string($superAdminUuid) && $superAdminUuid !== '') {
            $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

            if ($superAdmin !== null) {
                $request->session()->put('tenant_restaurant_uuid', $restaurant->uuid);

                return new JsonResponse([
                    'success' => true,
                    'restaurant_id' => $restaurant->uuid,
                    'name' => $restaurant->name,
                ]);
            }
        }

        $authUserUuid = $request->session()->get('auth_user_id');

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = EloquentUser::query()->where('uuid', $authUserUuid)->first();

        if ($user === null || ! is_numeric($user->restaurant_id)) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $linkedRestaurant = EloquentRestaurant::query()->where('id', (int) $user->restaurant_id)->first();

        if ($linkedRestaurant === null) {
            return new JsonResponse([
                'message' => 'Linked restaurant not found.',
            ], 404);
        }

        if (! is_string($linkedRestaurant->tax_id) || $linkedRestaurant->tax_id === '') {
            return new JsonResponse([
                'message' => 'Linked restaurant has no tax id.',
            ], 422);
        }

        if ($restaurant->tax_id !== $linkedRestaurant->tax_id) {
            return new JsonResponse([
                'message' => 'Forbidden for this restaurant context.',
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
