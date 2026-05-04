<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Domain\Entity\OrderFinalTicket;
use App\Sale\Domain\Interfaces\OrderFinalTicketRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentOrderFinalTicket;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentOrderFinalTicketRepository implements OrderFinalTicketRepositoryInterface
{
    public function __construct(
        private EloquentOrderFinalTicket $model,
    ) {}

    public function save(OrderFinalTicket $ticket): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $ticket->restaurantId()->value())->value('id');
        $orderId = EloquentOrder::query()->where('uuid', $ticket->orderId()->value())->value('id');
        $closedByUserId = EloquentUser::query()->where('uuid', $ticket->closedByUserId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $ticket->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'closed_by_user_id' => $closedByUserId,
                'ticket_number' => $ticket->ticketNumber(),
                'total_consumed_cents' => $ticket->totalConsumedCents(),
                'total_paid_cents' => $ticket->totalPaidCents(),
                'payments_snapshot' => $ticket->paymentsSnapshot(),
            ],
        );
    }

    public function findByUuid(Uuid $uuid): ?OrderFinalTicket
    {
        $model = $this->model->newQuery()
            ->with(['restaurant', 'order', 'closedByUser'])
            ->where('uuid', $uuid->value())
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderId(Uuid $orderId): ?OrderFinalTicket
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return null;
        }

        $model = $this->model->newQuery()
            ->with(['restaurant', 'order', 'closedByUser'])
            ->where('order_id', $orderInternalId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function nextTicketNumber(Uuid $restaurantId): int
    {
        $restaurantInternalId = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        $max = $this->model->newQuery()
            ->where('restaurant_id', $restaurantInternalId)
            ->max('ticket_number');

        return $max !== null ? (int) $max + 1 : 1;
    }

    private function toDomain(EloquentOrderFinalTicket $model): OrderFinalTicket
    {
        $restaurantUuid = $model->restaurant?->uuid ?? '';
        $orderUuid = $model->order?->uuid ?? '';
        $closedByUserUuid = $model->closedByUser?->uuid ?? '';

        return OrderFinalTicket::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $orderUuid,
            $closedByUserUuid,
            (int) $model->ticket_number,
            (int) $model->total_consumed_cents,
            (int) $model->total_paid_cents,
            $model->payments_snapshot ?? [],
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
