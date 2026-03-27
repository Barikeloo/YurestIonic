<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurant;
use App\User\Application\CreateRestaurantUser\CreateRestaurantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private readonly CreateRestaurant $createRestaurant,
        private readonly CreateRestaurantUser $createRestaurantUser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:restaurants,email'],
            'password' => ['required', 'string', 'min:8'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ]);

        $adminPin = $validated['pin'] ?? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $response = ($this->createRestaurant)(
            name: $validated['name'],
            legalName: $validated['legal_name'] ?? null,
            taxId: $validated['tax_id'] ?? null,
            email: $validated['email'],
            password: $validated['password'],
        );

        // Create admin user for this restaurant
        ($this->createRestaurantUser)(
            name: $validated['name'],
            email: $validated['email'],
            plainPassword: $validated['password'],
            restaurantUuid: $response->uuid,
            role: 'admin',
            plainPin: $adminPin,
        );

        return new JsonResponse([
            ...$response->toArray(),
            'admin_pin' => $adminPin,
        ], 201);
    }
}
