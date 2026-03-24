<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private readonly CreateRestaurant $createRestaurant,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:restaurants,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $response = ($this->createRestaurant)(
            name: $validated['name'],
            legalName: $validated['legal_name'] ?? null,
            taxId: $validated['tax_id'] ?? null,
            email: $validated['email'],
            password: $validated['password'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
