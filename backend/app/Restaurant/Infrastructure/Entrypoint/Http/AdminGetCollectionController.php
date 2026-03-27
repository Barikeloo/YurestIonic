<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminGetCollectionController
{
    public function __invoke(Request $request): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (is_string($superAdminUuid) && $superAdminUuid !== '') {
            $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

            if ($superAdmin !== null) {
                $restaurants = EloquentRestaurant::query()
                    ->orderBy('name')
                    ->get(['uuid', 'name', 'legal_name', 'tax_id', 'email'])
                    ->map(static fn (EloquentRestaurant $restaurant): array => [
                        'uuid' => $restaurant->uuid,
                        'name' => $restaurant->name,
                        'legal_name' => $restaurant->legal_name,
                        'tax_id' => $restaurant->tax_id,
                        'email' => $restaurant->email,
                    ])
                    ->all();

                return new JsonResponse([
                    'data' => $restaurants,
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

        $restaurant = EloquentRestaurant::query()
            ->where('id', (int) $user->restaurant_id)
            ->first();

        if ($restaurant === null) {
            return new JsonResponse([
                'message' => 'Linked restaurant not found.',
            ], 404);
        }

        if (! is_string($restaurant->tax_id) || $restaurant->tax_id === '') {
            return new JsonResponse([
                'message' => 'Linked restaurant has no tax id.',
            ], 422);
        }

        $restaurants = EloquentRestaurant::query()
            ->where('tax_id', $restaurant->tax_id)
            ->orderBy('name')
            ->get(['uuid', 'name', 'legal_name', 'tax_id', 'email'])
            ->map(static fn (EloquentRestaurant $row): array => [
                'uuid' => $row->uuid,
                'name' => $row->name,
                'legal_name' => $row->legal_name,
                'tax_id' => $row->tax_id,
                'email' => $row->email,
            ])
            ->all();

        return new JsonResponse([
            'data' => $restaurants,
        ]);
    }
}
