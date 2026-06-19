<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GenerateTableQrToken;

use App\GuestOrder\Domain\Entity\TableQrToken;
use App\GuestOrder\Domain\Exception\TableNotFoundException;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class GenerateTableQrToken
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly string $guestAppBaseUrl,
    ) {}

    public function __invoke(GenerateTableQrTokenCommand $command): GenerateTableQrTokenResponse
    {
        $tableId = Uuid::create($command->tableId);

        $table = $this->tableRepository->findById($command->tableId)
            ?? throw TableNotFoundException::withId($command->tableId);

        $existing = $this->tableQrTokenRepository->findByTableId($command->tableId);

        if ($existing !== null) {
            $existing->regenerate();
            $this->tableQrTokenRepository->save($existing);
            $this->eventBus->publish(...$existing->pullDomainEvents());

            return $this->buildResponse($existing);
        }

        $restaurantId = Uuid::create($command->restaurantId);
        $qrToken = TableQrToken::dddCreate($tableId, $restaurantId);

        $this->tableQrTokenRepository->save($qrToken);
        $this->eventBus->publish(...$qrToken->pullDomainEvents());

        return $this->buildResponse($qrToken);
    }

    private function buildResponse(TableQrToken $qrToken): GenerateTableQrTokenResponse
    {
        return GenerateTableQrTokenResponse::create(
            id: $qrToken->id()->value(),
            tableId: $qrToken->tableId()->value(),
            token: $qrToken->token()->value(),
            catalogVersion: $qrToken->catalogVersion(),
            url: rtrim($this->guestAppBaseUrl, '/') . '/s/' . $qrToken->token()->value(),
            createdAt: $qrToken->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $qrToken->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
