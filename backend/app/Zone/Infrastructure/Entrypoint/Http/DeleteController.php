<?php

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\DeleteZone\DeleteZone;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteZone $deleteZone,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteZone)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Zone not found.'], 404);
        }

        return new JsonResponse(null, 204);
    }
}
