<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\CreateTax\CreateTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController
{
    public function __construct(
        private CreateTax $createTax,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'name')->whereNull('deleted_at'),
            ],
            'percentage' => ['required', 'integer', 'between:0,100'],
        ]);

        $response = ($this->createTax)($validated['name'], $validated['percentage']);

        return new JsonResponse($response->toArray(), 201);
    }
}
