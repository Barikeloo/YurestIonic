<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Entity\ChargeSessionPayment;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\ChargeSessionModel;
use App\Sale\Infrastructure\Persistence\Models\ChargeSessionPaymentModel;
use App\Shared\Domain\ValueObject\Uuid;

final class EloquentChargeSessionRepository implements ChargeSessionRepositoryInterface
{
    public function save(ChargeSession $chargeSession): void
    {
        // Save session
        ChargeSessionModel::updateOrCreate(
            ['id' => $chargeSession->id()->value()],
            [
                'restaurant_id' => $chargeSession->restaurantId()->value(),
                'order_id' => $chargeSession->orderId()->value(),
                'opened_by_user_id' => $chargeSession->openedByUserId()->value(),
                'diners_count' => $chargeSession->dinersCount(),
                'total_cents' => $chargeSession->totalCents(),
                'amount_per_diner' => $chargeSession->amountPerDiner()->value(),
                'paid_diners_count' => $chargeSession->paidDinersCount(),
                'status' => $chargeSession->status()->value(),
                'cancelled_by_user_id' => $chargeSession->cancelledByUserId()?->value(),
                'cancellation_reason' => $chargeSession->cancellationReason(),
                'cancelled_at' => $chargeSession->cancelledAt()?->value(),
            ]
        );

        // Save payments
        foreach ($chargeSession->payments() as $payment) {
            ChargeSessionPaymentModel::updateOrCreate(
                ['id' => $payment->id()->value()],
                [
                    'charge_session_id' => $payment->chargeSessionId()->value(),
                    'diner_number' => $payment->dinerNumber(),
                    'amount_cents' => $payment->amount(),
                    'payment_method' => $payment->paymentMethod(),
                    'status' => $payment->status(),
                ]
            );
        }
    }

    public function findById(Uuid $id): ?ChargeSession
    {
        $model = ChargeSessionModel::with('payments')->find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findActiveByOrderId(Uuid $orderId): ?ChargeSession
    {
        $model = ChargeSessionModel::with('payments')
            ->where('order_id', $orderId->value())
            ->where('status', 'active')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByOrderId(Uuid $orderId): array
    {
        $models = ChargeSessionModel::with('payments')
            ->where('order_id', $orderId->value())
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $this->toEntity($model))->toArray();
    }

    public function delete(Uuid $id): void
    {
        ChargeSessionModel::destroy($id->value());
    }

    private function toEntity(ChargeSessionModel $model): ChargeSession
    {
        $payments = [];
        foreach ($model->payments as $paymentModel) {
            $payments[] = ChargeSessionPayment::fromPersistence(
                $paymentModel->id,
                $paymentModel->charge_session_id,
                $paymentModel->diner_number,
                $paymentModel->amount_cents,
                $paymentModel->payment_method,
                $paymentModel->status,
                $this->toImmutable($paymentModel->created_at),
                $this->toImmutable($paymentModel->updated_at),
            );
        }

        $entity = ChargeSession::fromPersistence(
            $model->id,
            $model->restaurant_id,
            $model->order_id,
            $model->opened_by_user_id,
            $model->diners_count,
            $model->total_cents,
            $model->amount_per_diner,
            $model->paid_diners_count,
            $model->status,
            $this->toImmutable($model->created_at),
            $this->toImmutable($model->updated_at),
            $this->toImmutable($model->deleted_at),
            $model->cancelled_by_user_id,
            $model->cancellation_reason,
            $this->toImmutable($model->cancelled_at),
        );

        $entity->loadPayments($payments);

        return $entity;
    }

    private function toImmutable(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        return new \DateTimeImmutable((string) $value);
    }
}
