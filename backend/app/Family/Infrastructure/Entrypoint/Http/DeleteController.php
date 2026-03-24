<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\DeleteFamily\DeleteFamily;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteFamily $deleteFamily,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteFamily)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Family not found.'], 404);
        }

        return new JsonResponse(null, 204);
    }
}
