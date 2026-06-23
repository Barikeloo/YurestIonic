<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetLoyaltyStats;

use Illuminate\Support\Facades\DB;

final class GetLoyaltyStats
{
    public function __invoke(GetLoyaltyStatsCommand $command): array
    {
        $restaurantInternalId = DB::table('restaurants')
            ->where('uuid', $command->restaurantId)
            ->value('id');

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
            'total_customers'         => (int) ($totals->total_customers ?? 0),
            'total_points_outstanding' => (int) ($totals->total_points_outstanding ?? 0),
            'total_visits'            => (int) ($totals->total_visits ?? 0),
            'total_spent_cents'       => (int) ($totals->total_spent_cents ?? 0),
            'avg_ticket_cents'        => (int) ($totals->avg_ticket_cents ?? 0),
            'new_customers'           => (int) ($totals->new_customers ?? 0),
            'returning_customers'     => (int) ($totals->returning_customers ?? 0),
            'visits_last_30_days'     => $recentVisits,
            'top_customers'           => $topCustomers,
        ];
    }
}
