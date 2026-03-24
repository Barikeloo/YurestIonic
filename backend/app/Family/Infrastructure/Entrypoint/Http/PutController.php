<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\UpdateFamily\UpdateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PutController
{
    public function __construct(
        private UpdateFamily $updateFamily,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('families', 'name')->ignore($id, 'uuid')->whereNull('deleted_at'),
            ],
        ]);

        $response = ($this->updateFamily)($id, $validated['name']);

        if ($response === null) {
            return new JsonResponse(['message' => 'Family not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
