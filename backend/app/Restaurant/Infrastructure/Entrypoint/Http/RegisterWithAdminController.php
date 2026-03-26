<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\RegisterRestaurantWithAdmin\RegisterRestaurantWithAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RegisterWithAdminController
{
    public function __construct(
        private readonly RegisterRestaurantWithAdmin $registerRestaurantWithAdmin,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:restaurants,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Email is already registered.',
        ]);

        $response = ($this->registerRestaurantWithAdmin)(
            restaurantName: $validated['restaurant_name'],
            legalName: $validated['legal_name'] ?? null,
            taxId: $validated['tax_id'] ?? null,
            email: $validated['email'],
            plainPassword: $validated['password'],
            adminName: $validated['admin_name'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}