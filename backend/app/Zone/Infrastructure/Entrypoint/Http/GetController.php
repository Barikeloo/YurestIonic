<?php

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\GetZone\GetZone;
use Illuminate\Http\JsonResponse;

class GetController
{
    public function __construct(
        private GetZone $getZone,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getZone)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Zone not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
