<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\DeleteTable\DeleteTable;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteTable $deleteTable,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteTable)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Table not found.'], 404);
        }

        return new JsonResponse(null, 204);
    }
}
