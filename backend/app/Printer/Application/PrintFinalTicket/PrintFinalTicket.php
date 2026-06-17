<?php

declare(strict_types=1);

namespace App\Printer\Application\PrintFinalTicket;

use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;
use App\Printer\Domain\Interfaces\PrinterServiceInterface;
use App\Printer\Infrastructure\Printing\EscPosTicketBuilder;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Sale\Application\GetFinalTicketPrint\GetFinalTicketPrint;
use App\Sale\Application\GetFinalTicketPrint\GetFinalTicketPrintCommand;
use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class PrintFinalTicket
{
    public function __construct(
        private readonly GetFinalTicketPrint $getTicket,
        private readonly TableRepositoryInterface $tableRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly PrinterConfigRepositoryInterface $printerConfigRepository,
        private readonly PrinterServiceInterface $printerService,
        private readonly EscPosTicketBuilder $ticketBuilder,
    ) {}

    public function __invoke(PrintFinalTicketCommand $command): void
    {
        try {
            $response = ($this->getTicket)(new GetFinalTicketPrintCommand($command->orderId));
        } catch (OrderFinalTicketNotFoundException) {
            // Ticket not ready yet — skip silently
            return;
        }

        $printerConfig = $this->resolvePrinter($response);

        if ($printerConfig === null) {
            return;
        }

        $bytes = $this->ticketBuilder->build(
            $response->toArray(),
            $printerConfig->paperWidth()->charWidth(),
        );

        $this->printerService->send(
            $printerConfig->ip()->value(),
            $printerConfig->port()->value(),
            $bytes,
        );
    }

    private function resolvePrinter(
        \App\Sale\Application\GetFinalTicketPrint\GetFinalTicketPrintResponse $response,
    ): ?\App\Printer\Domain\Entity\PrinterConfig {
        // Try zone-assigned printer first
        $tableId = $response->table['id'] ?? null;
        if ($tableId !== null) {
            $table = $this->tableRepository->findById($tableId);
            if ($table !== null) {
                $printer = $this->printerConfigRepository->findByZoneUuid($table->zoneId()->value());
                if ($printer !== null) {
                    return $printer;
                }
            }
        }

        // Fallback to restaurant-level default printer
        $restaurantUuid = $response->restaurant['id'] ?? null;
        if ($restaurantUuid === null) {
            return null;
        }

        $dto = $this->restaurantRepository->findByUuidWithInternalId(Uuid::create($restaurantUuid));
        if ($dto === null) {
            return null;
        }

        return $this->printerConfigRepository->findDefaultForRestaurant($dto->internalId);
    }
}
