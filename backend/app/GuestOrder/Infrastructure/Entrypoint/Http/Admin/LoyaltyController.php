<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin;

use App\GuestOrder\Application\GetLoyaltyStats\GetLoyaltyStats;
use App\GuestOrder\Application\GetLoyaltyStats\GetLoyaltyStatsCommand;
use App\GuestOrder\Application\ListCustomerAccounts\ListCustomerAccounts;
use App\GuestOrder\Application\ListCustomerAccounts\ListCustomerAccountsCommand;
use App\GuestOrder\Domain\Interfaces\LoyaltyReadRepositoryInterface;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoyaltyController
{
    public function __construct(
        private readonly ListCustomerAccounts $listCustomerAccounts,
        private readonly GetLoyaltyStats $getLoyaltyStats,
        private readonly LoyaltyReadRepositoryInterface $loyaltyReadRepository,
    ) {}

    public function customers(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId();
        if ($restaurantId === null) {
            return new JsonResponse(['message' => 'Tenant context required.'], 401);
        }

        try {
            $response = ($this->listCustomerAccounts)(new ListCustomerAccountsCommand(
                restaurantId: $restaurantId,
                search: $request->query('search'),
                perPage: (int) ($request->query('per_page', 20)),
                page: (int) ($request->query('page', 1)),
            ));
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }

    public function customerDetail(string $customerUuid): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId();
        if ($restaurantId === null) {
            return new JsonResponse(['message' => 'Tenant context required.'], 401);
        }

        $detail = $this->loyaltyReadRepository->getCustomerDetail($customerUuid, $restaurantId);

        if ($detail === null) {
            return new JsonResponse(['message' => 'Customer not found.'], 404);
        }

        return new JsonResponse($detail, 200);
    }

    public function stats(): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId();
        if ($restaurantId === null) {
            return new JsonResponse(['message' => 'Tenant context required.'], 401);
        }

        try {
            $stats = ($this->getLoyaltyStats)(new GetLoyaltyStatsCommand($restaurantId));
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($stats, 200);
    }

    private function requireRestaurantId(): ?string
    {
        return app(TenantContext::class)->restaurantUuid();
    }
}
