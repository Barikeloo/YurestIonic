<?php

declare(strict_types=1);

namespace App\Cash\Application\StartClosingCashSession;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\OpenOperationsPreventClosingException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class StartClosingCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(StartClosingCashSessionCommand $command): StartClosingCashSessionResponse
    {
        return $this->transactionManager->run(function () use ($command) {
            $cashSession = $this->cashSessionRepository->findByUuid(Uuid::create($command->cashSessionId))
                ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

            $activeOrders = $this->orderRepository->countActiveByRestaurantId($cashSession->restaurantId());

            if ($activeOrders > 0) {
                throw new OpenOperationsPreventClosingException($activeOrders);
            }

            $beforeStatus = $cashSession->status()->value();
            $cashSession->startClosing();
            $this->cashSessionRepository->save($cashSession);

            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: $cashSession->restaurantId(),
                slug: ActionSlug::create('caja.closing_started'),
                entityType: 'cash_session',
                entityId: $cashSession->id()->value(),
                userId: null,
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
                before: ['status' => $beforeStatus],
                after: ['status' => $cashSession->status()->value()],
            ));

            return StartClosingCashSessionResponse::create(
                id: $cashSession->id()->value(),
                status: $cashSession->status()->value(),
            );
        });
    }
}
