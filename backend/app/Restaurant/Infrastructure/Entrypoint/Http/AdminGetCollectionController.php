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

        // Case 1: Platform admin (no restaurant assigned) - can see all restaurants
        if (! is_numeric($user->restaurant_id)) {
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

        // Case 2: Restaurant admin (assigned to a restaurant) - can see restaurants with same tax_id
        $userRestaurant = EloquentRestaurant::query()->find((int) $user->restaurant_id);

        if ($userRestaurant === null) {
            return new JsonResponse([
                'message' => 'Linked restaurant not found.',
            ], 404);
        }

        $taxId = $userRestaurant->tax_id;

        if (! is_string($taxId) || $taxId === '') {
            return new JsonResponse([
                'message' => 'Linked restaurant has no tax id.',
            ], 422);
        }

        $restaurants = EloquentRestaurant::query()
            ->where('tax_id', $taxId)
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
