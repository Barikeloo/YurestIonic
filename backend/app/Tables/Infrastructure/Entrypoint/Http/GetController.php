<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\GetTable\GetTable;
use Illuminate\Http\JsonResponse;

class GetController
{
    public function __construct(
        private GetTable $getTable,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getTable)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Table not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
