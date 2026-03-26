<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurant;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Application\CreateRestaurantUser\CreateRestaurantUser;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
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
        ]);

        // If creating user is an admin, use their restaurant's tax_id
        $finalTaxId = $validated['tax_id'] ?? null;
        $authUserId = $request->session()->get('auth_user_id');
        if (is_string($authUserId)) {
            $authUser = EloquentUser::query()->where('uuid', $authUserId)->first();
            if ($authUser?->role === 'admin' && is_numeric($authUser?->restaurant_id)) {
                $authUserRestaurant = EloquentRestaurant::query()->find((int) $authUser->restaurant_id);
                if ($authUserRestaurant?->tax_id) {
                    $finalTaxId = $authUserRestaurant->tax_id;
                }
            }
        }

        $response = ($this->createRestaurant)(
            name: $validated['name'],
            legalName: $validated['legal_name'] ?? null,
            taxId: $finalTaxId,
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
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
