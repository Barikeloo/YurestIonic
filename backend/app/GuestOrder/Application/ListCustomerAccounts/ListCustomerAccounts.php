<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ListCustomerAccounts;

use Illuminate\Support\Facades\DB;

final class ListCustomerAccounts
{
    public function __invoke(ListCustomerAccountsCommand $command): ListCustomerAccountsResponse
    {
        $restaurantInternalId = DB::table('restaurants')
            ->where('uuid', $command->restaurantId)
            ->value('id');

        $query = DB::table('customer_accounts')
            ->where('customer_accounts.restaurant_id', $restaurantInternalId)
            ->orderByDesc('customer_accounts.last_visit_at')
            ->orderByDesc('customer_accounts.points');

        if ($command->search) {
            $term = '%' . $command->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        $total  = $query->count();
        $offset = ($command->page - 1) * $command->perPage;

        $rows = $query->offset($offset)->limit($command->perPage)
            ->select([
                'uuid', 'name', 'email', 'points',
                'total_spent_cents', 'visits_count', 'last_visit_at', 'created_at',
            ])
            ->get();

        $customers = $rows->map(fn ($r) => [
            'id'                => $r->uuid,
            'name'              => $r->name,
            'email'             => $r->email,
            'points'            => (int) $r->points,
            'total_spent_cents' => (int) $r->total_spent_cents,
            'visits_count'      => (int) $r->visits_count,
            'last_visit_at'     => $r->last_visit_at,
            'created_at'        => $r->created_at,
        ])->all();

        return ListCustomerAccountsResponse::create($customers, $total, $command->page, $command->perPage);
    }
}
