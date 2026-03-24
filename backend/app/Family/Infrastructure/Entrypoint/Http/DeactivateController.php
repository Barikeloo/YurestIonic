<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\SetFamilyActive\SetFamilyActive;
use Illuminate\Http\JsonResponse;

class DeactivateController
{
    public function __construct(
        private SetFamilyActive $setFamilyActive,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->setFamilyActive)($id, false);

        if ($response === null) {
            return new JsonResponse(['message' => 'Family not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
