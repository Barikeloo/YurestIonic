<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\GetFamily\GetFamily;
use Illuminate\Http\JsonResponse;

class GetController
{
    public function __construct(
        private GetFamily $getFamily,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getFamily)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Family not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
