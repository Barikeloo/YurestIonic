<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\UpdateRestaurant\UpdateRestaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PutController
{
    public function __construct(
        private readonly UpdateRestaurant $updateRestaurant,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'string', 'email'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        $response = ($this->updateRestaurant)(
            id: $id,
            name: $validated['name'] ?? null,
            legalName: $validated['legal_name'] ?? null,
            taxId: $validated['tax_id'] ?? null,
            email: $validated['email'] ?? null,
            plainPassword: $validated['password'] ?? null,
        );

        if ($response === null) {
            return new JsonResponse(['message' => 'Restaurant not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
