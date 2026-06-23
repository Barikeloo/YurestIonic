<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin;

use App\GuestOrder\Application\GetLoyaltyStats\GetLoyaltyStats;
use App\GuestOrder\Application\GetLoyaltyStats\GetLoyaltyStatsCommand;
use App\GuestOrder\Application\ListCustomerAccounts\ListCustomerAccounts;
use App\GuestOrder\Application\ListCustomerAccounts\ListCustomerAccountsCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoyaltyController
{
    public function __construct(
        private readonly ListCustomerAccounts $listCustomerAccounts,
        private readonly GetLoyaltyStats $getLoyaltyStats,
    ) {}

    public function customers(Request $request): JsonResponse
    {
        $tenantContext  = app(TenantContext::class);
        $restaurantId   = $tenantContext->restaurantUuid();

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
        $tenantContext = app(TenantContext::class);
        $restaurantId  = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            return new JsonResponse(['message' => 'Tenant context required.'], 401);
        }

        $restaurantInternalId = \Illuminate\Support\Facades\DB::table('restaurants')
            ->where('uuid', $restaurantId)
            ->value('id');

        $account = \Illuminate\Support\Facades\DB::table('customer_accounts')
            ->where('uuid', $customerUuid)
            ->where('restaurant_id', $restaurantInternalId)
            ->select(['id', 'uuid', 'name', 'email', 'points', 'total_spent_cents', 'visits_count', 'last_visit_at', 'created_at'])
            ->first();

        if ($account === null) {
            return new JsonResponse(['message' => 'Customer not found.'], 404);
        }

        $visits = \Illuminate\Support\Facades\DB::table('customer_visits')
            ->where('customer_account_id', $account->id)
            ->orderByDesc('visited_at')
            ->limit(20)
            ->select(['uuid', 'order_id', 'points_earned', 'amount_cents', 'visited_at'])
            ->get()
            ->map(fn ($v) => [
                'id'            => $v->uuid,
                'order_id'      => $v->order_id,
                'points_earned' => (int) $v->points_earned,
                'amount_cents'  => (int) $v->amount_cents,
                'visited_at'    => $v->visited_at,
            ])->all();

        return new JsonResponse([
            'id'                => $account->uuid,
            'name'              => $account->name,
            'email'             => $account->email,
            'points'            => (int) $account->points,
            'total_spent_cents' => (int) $account->total_spent_cents,
            'visits_count'      => (int) $account->visits_count,
            'last_visit_at'     => $account->last_visit_at,
            'created_at'        => $account->created_at,
            'visits'            => $visits,
        ], 200);
    }

    public function stats(): JsonResponse
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId  = $tenantContext->restaurantUuid();

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
}
