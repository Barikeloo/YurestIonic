<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetTableQrToken;

use App\GuestOrder\Domain\Entity\TableQrToken;
use App\GuestOrder\Domain\Exception\TableNotFoundException;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class GetTableQrToken
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly string $guestAppBaseUrl,
    ) {}

    public function __invoke(GetTableQrTokenCommand $command): GetTableQrTokenResponse
    {
        $table = $this->tableRepository->findById($command->tableId)
            ?? throw TableNotFoundException::withId($command->tableId);

        $existing = $this->tableQrTokenRepository->findByTableId($command->tableId);

        if ($existing !== null) {
            return GetTableQrTokenResponse::create(
                id: $existing->id()->value(),
                tableId: $existing->tableId()->value(),
                token: $existing->token()->value(),
                catalogVersion: $existing->catalogVersion(),
                url: rtrim($this->guestAppBaseUrl, '/') . '/s/' . $existing->token()->value(),
                updatedAt: $existing->updatedAt()->format(\DateTimeInterface::ATOM),
            );
        }

        $qrToken = TableQrToken::dddCreate(
            tableId: Uuid::create($command->tableId),
            restaurantId: Uuid::create($command->restaurantId),
        );

        $this->tableQrTokenRepository->save($qrToken);
        $this->eventBus->publish(...$qrToken->pullDomainEvents());

        return GetTableQrTokenResponse::create(
            id: $qrToken->id()->value(),
            tableId: $qrToken->tableId()->value(),
            token: $qrToken->token()->value(),
            catalogVersion: $qrToken->catalogVersion(),
            url: rtrim($this->guestAppBaseUrl, '/') . '/s/' . $qrToken->token()->value(),
            updatedAt: $qrToken->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
