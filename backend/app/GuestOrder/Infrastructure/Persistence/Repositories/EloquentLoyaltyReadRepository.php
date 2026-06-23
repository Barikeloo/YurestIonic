<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Interfaces\LoyaltyReadRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentLoyaltyReadRepository implements LoyaltyReadRepositoryInterface
{
    public function listCustomers(
        string $restaurantId,
        ?string $search,
        int $perPage,
        int $page,
    ): array {
        $restaurantInternalId = $this->resolveRestaurantId($restaurantId);

        $query = DB::table('customer_accounts')
            ->where('restaurant_id', $restaurantInternalId)
            ->orderByDesc('last_visit_at')
            ->orderByDesc('points');

        if ($search) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        return $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->select(['uuid', 'name', 'email', 'points', 'total_spent_cents', 'visits_count', 'last_visit_at', 'created_at'])
            ->get()
            ->map(fn ($r) => [
                'id'                => $r->uuid,
                'name'              => $r->name,
                'email'             => $r->email,
                'points'            => (int) $r->points,
                'total_spent_cents' => (int) $r->total_spent_cents,
                'visits_count'      => (int) $r->visits_count,
                'last_visit_at'     => $r->last_visit_at,
                'created_at'        => $r->created_at,
            ])->all();
    }

    public function countCustomers(string $restaurantId, ?string $search): int
    {
        $restaurantInternalId = $this->resolveRestaurantId($restaurantId);

        $query = DB::table('customer_accounts')->where('restaurant_id', $restaurantInternalId);

        if ($search) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        return $query->count();
    }

    public function getStats(string $restaurantId): array
    {
        $restaurantInternalId = $this->resolveRestaurantId($restaurantId);

        $totals = DB::table('customer_accounts')
            ->where('restaurant_id', $restaurantInternalId)
            ->selectRaw('
                COUNT(*) as total_customers,
                SUM(points) as total_points_outstanding,
                SUM(visits_count) as total_visits,
                SUM(total_spent_cents) as total_spent_cents,
                AVG(CASE WHEN visits_count > 0 THEN total_spent_cents / visits_count ELSE NULL END) as avg_ticket_cents,
                COUNT(CASE WHEN visits_count = 1 THEN 1 END) as new_customers,
                COUNT(CASE WHEN visits_count > 1 THEN 1 END) as returning_customers
            ')
            ->first();

        $recentVisits = DB::table('customer_visits')
            ->where('restaurant_id', $restaurantInternalId)
            ->where('visited_at', '>=', now()->subDays(30))
            ->count();

        $topCustomers = DB::table('customer_accounts')
            ->where('restaurant_id', $restaurantInternalId)
            ->orderByDesc('total_spent_cents')
            ->limit(5)
            ->select(['uuid', 'name', 'email', 'points', 'total_spent_cents', 'visits_count'])
            ->get()
            ->map(fn ($r) => [
                'id'                => $r->uuid,
                'name'              => $r->name,
                'email'             => $r->email,
                'points'            => (int) $r->points,
                'total_spent_cents' => (int) $r->total_spent_cents,
                'visits_count'      => (int) $r->visits_count,
            ])->all();

        return [
            'total_customers'          => (int) ($totals->total_customers ?? 0),
            'total_points_outstanding' => (int) ($totals->total_points_outstanding ?? 0),
            'total_visits'             => (int) ($totals->total_visits ?? 0),
            'total_spent_cents'        => (int) ($totals->total_spent_cents ?? 0),
            'avg_ticket_cents'         => (int) ($totals->avg_ticket_cents ?? 0),
            'new_customers'            => (int) ($totals->new_customers ?? 0),
            'returning_customers'      => (int) ($totals->returning_customers ?? 0),
            'visits_last_30_days'      => $recentVisits,
            'top_customers'            => $topCustomers,
        ];
    }

    public function getCustomerDetail(string $customerUuid, string $restaurantId): ?array
    {
        $restaurantInternalId = $this->resolveRestaurantId($restaurantId);

        $account = DB::table('customer_accounts')
            ->where('uuid', $customerUuid)
            ->where('restaurant_id', $restaurantInternalId)
            ->select(['id', 'uuid', 'name', 'email', 'points', 'total_spent_cents', 'visits_count', 'last_visit_at', 'created_at'])
            ->first();

        if ($account === null) {
            return null;
        }

        $visits = DB::table('customer_visits')
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

        return [
            'id'                => $account->uuid,
            'name'              => $account->name,
            'email'             => $account->email,
            'points'            => (int) $account->points,
            'total_spent_cents' => (int) $account->total_spent_cents,
            'visits_count'      => (int) $account->visits_count,
            'last_visit_at'     => $account->last_visit_at,
            'created_at'        => $account->created_at,
            'visits'            => $visits,
        ];
    }

    private function resolveRestaurantId(string $restaurantUuid): ?int
    {
        return DB::table('restaurants')->where('uuid', $restaurantUuid)->value('id');
    }
}
