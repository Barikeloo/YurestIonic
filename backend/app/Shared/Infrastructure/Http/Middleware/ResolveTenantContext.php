<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Tenant\TenantContext;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SuperAdminRepositoryInterface $superAdminRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly UserRepositoryInterface $userRepository,
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
            $superAdmin = $this->superAdminRepository->findById(Uuid::create($superAdminUuid));

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

            $restaurant = $this->restaurantRepository->findByUuidWithInternalId(Uuid::create($selectedRestaurantUuid));

            if ($restaurant === null) {
                return new JsonResponse([
                    'message' => 'Selected restaurant context does not exist.',
                ], 422);
            }

            $this->tenantContext->set($restaurant->internalId, (string) $restaurant->restaurant->uuid()->value(), true);
            $request->merge(['restaurant_id' => (string) $restaurant->restaurant->uuid()->value()]);

            return $next($request);
        }

        $authUserUuid = $request->session()->get('auth_user_id');

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($user->restaurantId() === null) {
            return new JsonResponse([
                'message' => 'Authenticated user does not have an assigned restaurant.',
            ], 403);
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalIdWithInternalId((int) $user->restaurantId()->value());

        if ($linkedRestaurant === null) {
            return new JsonResponse([
                'message' => 'Authenticated user has an invalid restaurant assignment.',
            ], 403);
        }

        $selectedRestaurantUuid = $request->header('X-Restaurant-Id');

        if (! is_string($selectedRestaurantUuid) || $selectedRestaurantUuid === '') {
            $selectedRestaurantUuid = $request->session()->get('tenant_restaurant_uuid');
        }

        $effectiveRestaurant = $linkedRestaurant;

        if (is_string($selectedRestaurantUuid) && $selectedRestaurantUuid !== '' && $selectedRestaurantUuid !== $linkedRestaurant->restaurant->uuid()->value()) {
            $targetRestaurant = $this->restaurantRepository->findByUuidWithInternalId(Uuid::create($selectedRestaurantUuid));

            if ($targetRestaurant === null) {
                return new JsonResponse([
                    'message' => 'Selected restaurant context does not exist.',
                ], 422);
            }

            $linkedTaxId = $linkedRestaurant->restaurant->taxId()?->value();

            if (! is_string($linkedTaxId) || $linkedTaxId === '') {
                return new JsonResponse([
                    'message' => 'Forbidden for this restaurant context.',
                ], 403);
            }

            if ($targetRestaurant->restaurant->taxId()?->value() !== $linkedTaxId) {
                return new JsonResponse([
                    'message' => 'Forbidden for this restaurant context.',
                ], 403);
            }

            $effectiveRestaurant = $targetRestaurant;
        }

        $restaurantUuid = (string) $effectiveRestaurant->restaurant->uuid()->value();

        $requestRestaurantUuid = $request->input('restaurant_id');

        if (is_string($requestRestaurantUuid) && $requestRestaurantUuid !== '' && $requestRestaurantUuid !== $restaurantUuid) {
            return new JsonResponse([
                'message' => 'restaurant_id does not match authenticated tenant context.',
            ], 422);
        }

        $this->tenantContext->set($effectiveRestaurant->internalId, $restaurantUuid, false);
        $request->merge(['restaurant_id' => $restaurantUuid]);

        return $next($request);
    }
}
