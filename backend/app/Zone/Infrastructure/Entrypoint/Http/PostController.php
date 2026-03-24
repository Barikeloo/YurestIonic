<?php

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\CreateZone\CreateZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController
{
    public function __construct(
        private CreateZone $createZone,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('zones', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $response = ($this->createZone)($validated['name']);

        return new JsonResponse($response->toArray(), 201);
    }
}
