<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Subscriber;

use App\GuestOrder\Domain\Interfaces\CustomerAccountRepositoryInterface;
use App\Order\Domain\Event\OrderInvoiced;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LoyaltyPointsSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly CustomerAccountRepositoryInterface $customerAccountRepository,
    ) {}

    public function subscribedTo(): array
    {
        return [OrderInvoiced::class];
    }

    public function handle(DomainEvent $event): void
    {
        if (! $event instanceof OrderInvoiced) {
            return;
        }

        $orderUuid = $event->auditEntityId();

        $order = DB::table('orders')->where('uuid', $orderUuid)->first();
        if ($order === null) {
            return;
        }

        $totalCents = (int) DB::table('sales')
            ->where('order_id', $order->id)
            ->whereNull('deleted_at')
            ->sum('total');

        if ($totalCents <= 0) {
            return;
        }

        $customerInternalIds = DB::table('guest_sessions')
            ->where('order_id', $order->id)
            ->whereNotNull('customer_account_id')
            ->distinct()
            ->pluck('customer_account_id');

        foreach ($customerInternalIds as $internalId) {
            $uuid    = DB::table('customer_accounts')->where('id', $internalId)->value('uuid');
            if (! $uuid) {
                continue;
            }

            $account = $this->customerAccountRepository->findById($uuid);
            if (! $account) {
                continue;
            }

            $account->creditVisit($totalCents);
            $this->customerAccountRepository->save($account);

            DB::table('customer_visits')->insert([
                'uuid'                => (string) Str::uuid(),
                'customer_account_id' => $internalId,
                'restaurant_id'       => $order->restaurant_id,
                'order_id'            => $orderUuid,
                'guest_session_id'    => null,
                'points_earned'       => (int) floor($totalCents / 100),
                'amount_cents'        => $totalCents,
                'visited_at'          => now(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
}
