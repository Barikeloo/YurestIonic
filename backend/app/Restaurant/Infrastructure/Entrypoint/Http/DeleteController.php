<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\DeleteRestaurant\DeleteRestaurant;
use Illuminate\Http\JsonResponse;

final class DeleteController
{
    public function __construct(
        private readonly DeleteRestaurant $deleteRestaurant,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteRestaurant)($id);

        if (!$deleted) {
            return new JsonResponse(['message' => 'Restaurant not found.'], 404);
        }

        return new JsonResponse(status: 204);
    }
}
