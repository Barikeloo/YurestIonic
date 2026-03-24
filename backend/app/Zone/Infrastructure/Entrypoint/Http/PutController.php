<?php

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\UpdateZone\UpdateZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PutController
{
    public function __construct(
        private UpdateZone $updateZone,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('zones', 'name')->ignore($id, 'uuid')->whereNull('deleted_at'),
            ],
        ]);

        $response = ($this->updateZone)($id, $validated['name']);

        if ($response === null) {
            return new JsonResponse(['message' => 'Zone not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
