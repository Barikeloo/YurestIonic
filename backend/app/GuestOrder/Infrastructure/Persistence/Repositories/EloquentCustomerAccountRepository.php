<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Entity\CustomerAccount;
use App\GuestOrder\Domain\Interfaces\CustomerAccountRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class EloquentCustomerAccountRepository implements CustomerAccountRepositoryInterface
{
    public function findByEmailAndRestaurant(string $email, string $restaurantId): ?CustomerAccount
    {
        $row = DB::table('customer_accounts')
            ->join('restaurants', 'restaurants.id', '=', 'customer_accounts.restaurant_id')
            ->where('customer_accounts.email', $email)
            ->where('restaurants.uuid', $restaurantId)
            ->select('customer_accounts.*')
            ->first();

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function findById(string $id): ?CustomerAccount
    {
        $row = DB::table('customer_accounts')->where('uuid', $id)->first();

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function save(CustomerAccount $account): void
    {
        $restaurant = EloquentRestaurant::query()
            ->where('uuid', $account->restaurantId()->value())
            ->firstOrFail();

        DB::table('customer_accounts')->updateOrInsert(
            ['uuid' => $account->id()->value()],
            [
                'restaurant_id'    => $restaurant->id,
                'name'             => $account->name(),
                'email'            => $account->email(),
                'password_hash'    => $account->passwordHash(),
                'points'           => $account->points(),
                'total_spent_cents' => $account->totalSpentCents(),
                'visits_count'     => $account->visitsCount(),
                'last_visit_at'    => $account->lastVisitAt()?->value(),
                'created_at'       => $account->createdAt()->value(),
                'updated_at'       => $account->updatedAt()->value(),
            ],
        );
    }

    public function saveAuthToken(string $accountId, string $token, \DateTimeImmutable $expiresAt): void
    {
        $ttl = max(0, $expiresAt->getTimestamp() - time());
        Cache::put("customer_auth_token:{$token}", $accountId, $ttl);
    }

    public function findByAuthToken(string $token): ?CustomerAccount
    {
        $accountId = Cache::get("customer_auth_token:{$token}");

        if ($accountId === null) {
            return null;
        }

        return $this->findById($accountId);
    }

    public function invalidateAuthToken(string $token): void
    {
        Cache::forget("customer_auth_token:{$token}");
    }

    public function getActiveOffers(string $restaurantId, int $customerPoints): array
    {
        $restaurantInternalId = DB::table('restaurants')->where('uuid', $restaurantId)->value('id');

        return DB::table('customer_offers')
            ->where('restaurant_id', $restaurantInternalId)
            ->where('active', true)
            ->where('min_points', '<=', $customerPoints)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->orderByDesc('discount_value')
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->uuid,
                'title'          => $r->title,
                'discount_type'  => $r->discount_type,
                'discount_value' => $r->discount_value,
            ])
            ->all();
    }

    private function hydrate(\stdClass $row): CustomerAccount
    {
        $restaurantUuid = DB::table('restaurants')->where('id', $row->restaurant_id)->value('uuid');

        return CustomerAccount::fromPersistence(
            id: $row->uuid,
            restaurantId: $restaurantUuid,
            name: $row->name,
            email: $row->email,
            passwordHash: $row->password_hash,
            points: (int) $row->points,
            totalSpentCents: (int) $row->total_spent_cents,
            visitsCount: (int) $row->visits_count,
            lastVisitAt: $row->last_visit_at ? new \DateTimeImmutable($row->last_visit_at) : null,
            createdAt: new \DateTimeImmutable($row->created_at),
            updatedAt: new \DateTimeImmutable($row->updated_at),
        );
    }
}
