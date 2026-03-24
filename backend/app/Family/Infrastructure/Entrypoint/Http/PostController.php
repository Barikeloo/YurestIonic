<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController
{
    public function __construct(
        private CreateFamily $createFamily,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('families', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $response = ($this->createFamily)($validated['name']);

        return new JsonResponse($response->toArray(), 201);
    }
}
