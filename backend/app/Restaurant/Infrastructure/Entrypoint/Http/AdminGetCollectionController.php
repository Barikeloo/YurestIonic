<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Http\JsonResponse;

final class AdminGetCollectionController
{
    public function __invoke(): JsonResponse
    {
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
