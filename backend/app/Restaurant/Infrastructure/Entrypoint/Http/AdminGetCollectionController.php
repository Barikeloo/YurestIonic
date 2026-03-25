<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminGetCollectionController
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

        $restaurants = EloquentRestaurant::query()
            ->orderBy('name')
            ->get(['uuid', 'name', 'legal_name', 'tax_id'])
            ->map(static fn (EloquentRestaurant $restaurant): array => [
                'uuid' => $restaurant->uuid,
                'name' => $restaurant->name,
                'legal_name' => $restaurant->legal_name,
                'tax_id' => $restaurant->tax_id,
            ])
            ->all();

        return new JsonResponse([
            'data' => $restaurants,
        ]);
    }
}
