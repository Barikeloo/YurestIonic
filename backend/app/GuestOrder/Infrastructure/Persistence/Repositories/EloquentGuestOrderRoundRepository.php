<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Entity\GuestOrderRound;
use App\GuestOrder\Domain\Interfaces\GuestOrderRoundRepositoryInterface;
use App\GuestOrder\Domain\ReadModel\CartLineData;
use App\GuestOrder\Domain\ReadModel\RoundData;
use Illuminate\Support\Facades\DB;

final class EloquentGuestOrderRoundRepository implements GuestOrderRoundRepositoryInterface
{
    public function save(GuestOrderRound $round): void
    {
        $sessionInternalId = DB::table('guest_sessions')
            ->where('uuid', $round->guestSessionId()->value())
            ->value('id');

        $orderInternalId = DB::table('orders')
            ->where('uuid', $round->orderId()->value())
            ->value('id');

        $restaurantInternalId = DB::table('restaurants')
            ->where('uuid', $round->restaurantId()->value())
            ->value('id');

        DB::table('guest_order_rounds')->insert([
            'uuid'             => $round->id()->value(),
            'guest_session_id' => $sessionInternalId,
            'order_id'         => $orderInternalId,
            'restaurant_id'    => $restaurantInternalId,
            'round_number'     => $round->roundNumber(),
            'label'            => $round->label(),
            'idempotency_key'  => $round->idempotencyKey(),
            'submitted_at'     => $round->submittedAt()->value(),
            'created_at'       => $round->createdAt()->value(),
            'updated_at'       => $round->updatedAt()->value(),
        ]);
    }

    public function findByIdempotencyKey(string $key): ?GuestOrderRound
    {
        $row = DB::table('guest_order_rounds')->where('idempotency_key', $key)->first();

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function getNextRoundNumber(string $sessionUuid): int
    {
        return DB::transaction(function () use ($sessionUuid): int {
            $sessionId = DB::table('guest_sessions')->where('uuid', $sessionUuid)->value('id');

            $max = DB::table('guest_order_rounds')
                ->where('guest_session_id', $sessionId)
                ->lockForUpdate()
                ->max('round_number');

            return ($max ?? 0) + 1;
        });
    }

    public function getRoundsWithLinesBySession(string $sessionUuid): array
    {
        $rounds = DB::table('guest_order_rounds')
            ->join('guest_sessions', 'guest_sessions.id', '=', 'guest_order_rounds.guest_session_id')
            ->where('guest_sessions.uuid', $sessionUuid)
            ->orderBy('guest_order_rounds.round_number')
            ->select('guest_order_rounds.*')
            ->get();

        return $rounds->map(function (\stdClass $r) use ($sessionUuid): RoundData {
            $lines = DB::table('order_lines')
                ->where('guest_round_id', $r->uuid)
                ->where('guest_session_id', $sessionUuid)
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->get()
                ->map(fn (\stdClass $l): CartLineData => $this->hydrateCartLine($l))
                ->values()
                ->all();

            return new RoundData(
                roundId: $r->uuid,
                roundNumber: (int) $r->round_number,
                label: $r->label,
                submittedAt: $r->submitted_at,
                lines: $lines,
            );
        })->all();
    }

    private function hydrate(\stdClass $row): GuestOrderRound
    {
        $sessionUuid    = DB::table('guest_sessions')->where('id', $row->guest_session_id)->value('uuid');
        $orderUuid      = DB::table('orders')->where('id', $row->order_id)->value('uuid');
        $restaurantUuid = DB::table('restaurants')->where('id', $row->restaurant_id)->value('uuid');

        return GuestOrderRound::fromPersistence(
            id: $row->uuid,
            guestSessionId: $sessionUuid,
            orderId: $orderUuid,
            restaurantId: $restaurantUuid,
            roundNumber: (int) $row->round_number,
            label: $row->label,
            idempotencyKey: $row->idempotency_key,
            submittedAt: new \DateTimeImmutable($row->submitted_at),
            createdAt: new \DateTimeImmutable($row->created_at),
            updatedAt: new \DateTimeImmutable($row->updated_at),
        );
    }

    private function hydrateCartLine(\stdClass $row): CartLineData
    {
        $productName = null;
        $productUuid = null;

        if ($row->product_id !== null) {
            $product     = DB::table('products')->where('id', $row->product_id)->first();
            $productUuid = $product?->uuid;
            $productName = $product?->name;
        }

        return new CartLineData(
            id: $row->uuid,
            productId: $productUuid,
            productName: $productName,
            menuId: $row->menu_id,
            menuName: $row->menu_name,
            variantId: $row->variant_id,
            variantName: $row->variant_name,
            modifiers: $row->modifiers ? json_decode($row->modifiers, true) : [],
            quantity: (int) $row->quantity,
            unitPrice: (int) $row->price,
            notes: $row->notes,
            sendStatus: $row->send_status,
            roundId: $row->guest_round_id,
        );
    }
}
