<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Entity\GuestSession;
use App\GuestOrder\Domain\Interfaces\GuestSessionRepositoryInterface;
use App\GuestOrder\Infrastructure\Persistence\Models\EloquentGuestSession;
use App\GuestOrder\Infrastructure\Persistence\Models\EloquentTableQrToken;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;

final class EloquentGuestSessionRepository implements GuestSessionRepositoryInterface
{
    public function __construct(
        private EloquentGuestSession $model,
    ) {}

    public function save(GuestSession $session): void
    {
        $qrToken    = EloquentTableQrToken::query()->where('uuid', $session->tableQrTokenId()->value())->firstOrFail();
        $restaurant = EloquentRestaurant::query()->where('uuid', $session->restaurantId()->value())->firstOrFail();

        $orderId = null;
        if ($session->orderId() !== null) {
            $orderId = EloquentOrder::query()->where('uuid', $session->orderId()->value())->value('id');
        }

        $customerAccountInternalId = null;
        if ($session->customerAccountId() !== null) {
            $customerAccountInternalId = \Illuminate\Support\Facades\DB::table('customer_accounts')
                ->where('uuid', $session->customerAccountId())
                ->value('id');
        }

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $session->id()->value()],
            [
                'table_qr_token_id'   => $qrToken->id,
                'order_id'            => $orderId,
                'restaurant_id'       => $restaurant->id,
                'session_token'       => $session->sessionToken()->value(),
                'identity_mode'       => $session->identityMode()->value(),
                'guest_name'          => $session->guestName(),
                'opened_table'        => $session->openedTable(),
                'diners_count'        => $session->dinersCount(),
                'customer_account_id' => $customerAccountInternalId,
                'check_requested_at'  => $session->checkRequestedAt()?->value(),
                'expires_at'          => $session->expiresAt()->value(),
                'created_at'          => $session->createdAt()->value(),
                'updated_at'          => $session->updatedAt()->value(),
            ],
        );
    }

    public function findBySessionToken(string $sessionToken): ?GuestSession
    {
        $model = $this->model->newQuery()
            ->with('tableQrToken.restaurant')
            ->where('session_token', $sessionToken)
            ->first();

        return $model !== null ? $this->hydrate($model) : null;
    }

    private function hydrate(EloquentGuestSession $model): GuestSession
    {
        $qrTokenUuid    = $model->tableQrToken->uuid;
        $restaurantUuid = $model->tableQrToken->restaurant->uuid;

        $orderUuid = null;
        if ($model->order_id !== null) {
            $orderUuid = EloquentOrder::query()->where('id', $model->order_id)->value('uuid');
        }

        $customerAccountUuid = null;
        if ($model->customer_account_id !== null) {
            $customerAccountUuid = \Illuminate\Support\Facades\DB::table('customer_accounts')
                ->where('id', $model->customer_account_id)
                ->value('uuid');
        }

        return GuestSession::fromPersistence(
            id: $model->uuid,
            tableQrTokenId: $qrTokenUuid,
            orderId: $orderUuid,
            restaurantId: $restaurantUuid,
            sessionToken: $model->session_token,
            identityMode: $model->identity_mode,
            guestName: $model->guest_name,
            openedTable: (bool) $model->opened_table,
            dinersCount: $model->diners_count,
            customerAccountId: $customerAccountUuid,
            checkRequestedAt: $model->check_requested_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
            expiresAt: $model->expires_at->toDateTimeImmutable(),
        );
    }
}
