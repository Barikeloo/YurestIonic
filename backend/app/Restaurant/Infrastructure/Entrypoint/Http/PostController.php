<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurant;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
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
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            return new JsonResponse([
                'message' => 'Forbidden. Only superadmins can create restaurants.',
            ], 403);
        }

        $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

        if ($superAdmin === null) {
            return new JsonResponse([
                'message' => 'Forbidden. Only superadmins can create restaurants.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:50'],
            'company_mode' => ['sometimes', 'string', 'in:existing,new'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:restaurants,email'],
            'password' => ['required', 'string', 'min:8'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ]);

        $companyMode = $validated['company_mode'] ?? 'new';
        $taxId = trim($validated['tax_id']);
        $companyExists = EloquentRestaurant::query()->where('tax_id', $taxId)->exists();

        if ($companyMode === 'new' && $companyExists) {
            return new JsonResponse([
                'message' => 'The tax_id already exists. Use the existing company action to add a branch.',
            ], 422);
        }

        if ($companyMode === 'existing' && ! $companyExists) {
            return new JsonResponse([
                'message' => 'The tax_id does not exist yet. Use New Company to create the first restaurant.',
            ], 422);
        }

        $adminPin = $validated['pin'] ?? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $response = ($this->createRestaurant)(
            name: $validated['name'],
            legalName: $validated['legal_name'] ?? null,
            taxId: $taxId,
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
