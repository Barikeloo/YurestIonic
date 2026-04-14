<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use App\Shared\Infrastructure\Tenant\TenantContext;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->clear();

        if (! $request->hasSession()) {
            return new JsonResponse([
                'message' => 'Session is required for tenant routes.',
            ], 500);
        }

        $superAdminUuid = $request->session()->get('super_admin_id');

        if (is_string($superAdminUuid) && $superAdminUuid !== '') {
            $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

            if ($superAdmin === null) {
                $request->session()->forget('super_admin_id');

                return new JsonResponse([
                    'message' => 'Not authenticated as superadmin.',
                ], 401);
            }

            $selectedRestaurantUuid = $request->header('X-Restaurant-Id');

            if (! is_string($selectedRestaurantUuid) || $selectedRestaurantUuid === '') {
                $selectedRestaurantUuid = $request->session()->get('tenant_restaurant_uuid');
            }

            if (! is_string($selectedRestaurantUuid) || $selectedRestaurantUuid === '') {
                return new JsonResponse([
                    'message' => 'Superadmin must select a restaurant context before operating tenant modules.',
                ], 400);
            }

            $restaurant = EloquentRestaurant::query()->where('uuid', $selectedRestaurantUuid)->first();

            if ($restaurant === null) {
                return new JsonResponse([
                    'message' => 'Selected restaurant context does not exist.',
                ], 422);
            }

            $this->tenantContext->set((int) $restaurant->id, (string) $restaurant->uuid, true);
            $request->merge(['restaurant_id' => (string) $restaurant->uuid]);

            return $next($request);
        }

        $authUserUuid = $request->session()->get('auth_user_id');

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = EloquentUser::query()->where('uuid', $authUserUuid)->first();

        if ($user === null) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($user->restaurant_id === null) {
            return new JsonResponse([
                'message' => 'Authenticated user does not have an assigned restaurant.',
            ], 403);
        }

        $linkedRestaurant = EloquentRestaurant::query()->where('id', $user->restaurant_id)->first();

        if ($linkedRestaurant === null || ! is_string($linkedRestaurant->uuid) || $linkedRestaurant->uuid === '') {
            return new JsonResponse([
                'message' => 'Authenticated user has an invalid restaurant assignment.',
            ], 403);
        }

        $selectedRestaurantUuid = $request->header('X-Restaurant-Id');

        if (! is_string($selectedRestaurantUuid) || $selectedRestaurantUuid === '') {
            $selectedRestaurantUuid = $request->session()->get('tenant_restaurant_uuid');
        }

        $effectiveRestaurant = $linkedRestaurant;

        if (is_string($selectedRestaurantUuid) && $selectedRestaurantUuid !== '' && $selectedRestaurantUuid !== $linkedRestaurant->uuid) {
            $targetRestaurant = EloquentRestaurant::query()->where('uuid', $selectedRestaurantUuid)->first();

            if ($targetRestaurant === null) {
                return new JsonResponse([
                    'message' => 'Selected restaurant context does not exist.',
                ], 422);
            }

            $linkedTaxId = $linkedRestaurant->tax_id;

            if (! is_string($linkedTaxId) || $linkedTaxId === '') {
                return new JsonResponse([
                    'message' => 'Forbidden for this restaurant context.',
                ], 403);
            }

            if ($targetRestaurant->tax_id !== $linkedTaxId) {
                return new JsonResponse([
                    'message' => 'Forbidden for this restaurant context.',
                ], 403);
            }

            $effectiveRestaurant = $targetRestaurant;
        }

        $restaurantUuid = (string) $effectiveRestaurant->uuid;

        $requestRestaurantUuid = $request->input('restaurant_id');

        if (is_string($requestRestaurantUuid) && $requestRestaurantUuid !== '' && $requestRestaurantUuid !== $restaurantUuid) {
            return new JsonResponse([
                'message' => 'restaurant_id does not match authenticated tenant context.',
            ], 422);
        }

        $this->tenantContext->set((int) $effectiveRestaurant->id, $restaurantUuid, false);
        $request->merge(['restaurant_id' => $restaurantUuid]);

        return $next($request);
    }
}
