<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use App\User\Application\GetRestaurantUsers\GetRestaurantUsers;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGetCollectionController
{
    public function __construct(
        private GetRestaurantUsers $getRestaurantUsers,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (is_string($superAdminUuid) && $superAdminUuid !== '') {
            $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

            if ($superAdmin !== null) {
                $response = ($this->getRestaurantUsers)($uuid);

                return new JsonResponse($response->toArray(), 200);
            }
        }

        $authUserUuid = $request->session()->get('auth_user_id');

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = EloquentUser::query()->where('uuid', $authUserUuid)->first();

        if ($user === null || ! is_numeric($user->restaurant_id)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $linkedRestaurant = EloquentRestaurant::query()->where('id', (int) $user->restaurant_id)->first();
        $targetRestaurant = EloquentRestaurant::query()->where('uuid', $uuid)->first();

        if ($linkedRestaurant === null || $targetRestaurant === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Restaurant not found.',
            ], 404);
        }

        if (! is_string($linkedRestaurant->tax_id) || $linkedRestaurant->tax_id === '' || $targetRestaurant->tax_id !== $linkedRestaurant->tax_id) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $response = ($this->getRestaurantUsers)($uuid);

        return new JsonResponse($response->toArray(), 200);
    }
}
