<?php

declare(strict_types=1);

namespace App\Tables\Application\SaveZoneLayout;

use App\Shared\Application\Event\EventBusInterface;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableLayout;
use Illuminate\Support\Facades\DB;

final class SaveZoneLayout
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly EventBusInterface        $eventBus,
    ) {}

    public function __invoke(SaveZoneLayoutCommand $command): SaveZoneLayoutResponse
    {
        $saved = DB::transaction(function () use ($command): int {
            $count = 0;

            foreach ($command->tables as $dto) {
                $table = $this->tableRepository->findById($dto->uuid);

                // Skip tables that don't exist or belong to a different zone
                if ($table === null || $table->zoneId()->value() !== $command->zoneId) {
                    continue;
                }

                $table->updateLayout(TableLayout::create(
                    $dto->posX,
                    $dto->posY,
                    $dto->width,
                    $dto->height,
                    $dto->shape,
                ));

                $this->tableRepository->save($table);
                $this->eventBus->publish(...$table->pullDomainEvents());

                $count++;
            }

            return $count;
        });

        return new SaveZoneLayoutResponse($saved);
    }
}
