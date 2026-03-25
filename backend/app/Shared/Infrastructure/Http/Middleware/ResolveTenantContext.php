<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
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

        $isAdmin = $user->role === 'admin';

        if ($isAdmin) {
            $selectedRestaurantUuid = $request->header('X-Restaurant-Id');

            if (! is_string($selectedRestaurantUuid) || $selectedRestaurantUuid === '') {
                $selectedRestaurantUuid = $request->session()->get('tenant_restaurant_uuid');
            }

            if (! is_string($selectedRestaurantUuid) || $selectedRestaurantUuid === '') {
                return new JsonResponse([
                    'message' => 'Admin must select a restaurant context before operating tenant modules.',
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

        if ($user->restaurant_id === null) {
            return new JsonResponse([
                'message' => 'Authenticated user does not have an assigned restaurant.',
            ], 403);
        }

        $restaurantUuid = EloquentRestaurant::query()->where('id', $user->restaurant_id)->value('uuid');

        if (! is_string($restaurantUuid) || $restaurantUuid === '') {
            return new JsonResponse([
                'message' => 'Authenticated user has an invalid restaurant assignment.',
            ], 403);
        }

        $requestRestaurantUuid = $request->input('restaurant_id');

        if (is_string($requestRestaurantUuid) && $requestRestaurantUuid !== '' && $requestRestaurantUuid !== $restaurantUuid) {
            return new JsonResponse([
                'message' => 'restaurant_id does not match authenticated tenant context.',
            ], 422);
        }

        $this->tenantContext->set((int) $user->restaurant_id, $restaurantUuid, false);
        $request->merge(['restaurant_id' => $restaurantUuid]);

        return $next($request);
    }
}
