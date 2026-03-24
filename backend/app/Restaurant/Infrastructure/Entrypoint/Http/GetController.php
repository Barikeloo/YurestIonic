<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\GetRestaurant\GetRestaurant;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private readonly GetRestaurant $getRestaurant,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getRestaurant)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Restaurant not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
