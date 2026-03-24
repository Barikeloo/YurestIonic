<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\CreateTable\CreateTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController
{
    public function __construct(
        private CreateTable $createTable,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => [
                'required',
                'uuid',
                Rule::exists('zones', 'uuid')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $response = ($this->createTable)($validated['zone_id'], $validated['name']);

        return new JsonResponse($response->toArray(), 201);
    }
}
