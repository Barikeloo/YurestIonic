<?php

declare(strict_types=1);

namespace App\Printer\Application\PrintPreTicket;

use App\Order\Application\GetOrderPreTicket\GetOrderPreTicket;
use App\Order\Application\GetOrderPreTicket\GetOrderPreTicketCommand;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Printer\Domain\Entity\PrinterConfig;
use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;
use App\Printer\Domain\Interfaces\PrinterServiceInterface;
use App\Printer\Infrastructure\Printing\EscPosTicketBuilder;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class PrintPreTicket
{
    public function __construct(
        private readonly GetOrderPreTicket $getOrderPreTicket,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TableRepositoryInterface $tableRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly PrinterConfigRepositoryInterface $printerConfigRepository,
        private readonly PrinterServiceInterface $printerService,
        private readonly EscPosTicketBuilder $ticketBuilder,
    ) {}

    public function __invoke(PrintPreTicketCommand $command): void
    {
        $orderUuid = Uuid::create($command->orderId);
        $order = $this->orderRepository->findByUuid($orderUuid);

        if ($order === null) {
            return;
        }

        $printerConfig = $this->resolvePrinter($order);

        if ($printerConfig === null) {
            return;
        }

        $preTicketResponse = ($this->getOrderPreTicket)(new GetOrderPreTicketCommand(
            orderId: $command->orderId,
            format: 'text',
            width: (string) $printerConfig->paperWidth()->charWidth(),
        ));

        $bytes = $this->ticketBuilder->buildPlainText(
            text: $preTicketResponse->text,
            charWidth: $printerConfig->paperWidth()->charWidth(),
        );

        $this->printerService->send(
            $printerConfig->ip()->value(),
            $printerConfig->port()->value(),
            $bytes,
        );
    }

    private function resolvePrinter(\App\Order\Domain\Entity\Order $order): ?PrinterConfig
    {
        $table = $this->tableRepository->findById($order->tableId()->value());
        if ($table !== null) {
            $printer = $this->printerConfigRepository->findByZoneUuid($table->zoneId()->value());
            if ($printer !== null) {
                return $printer;
            }
        }

        $dto = $this->restaurantRepository->findByUuidWithInternalId($order->restaurantId());
        if ($dto === null) {
            return null;
        }

        return $this->printerConfigRepository->findDefaultForRestaurant($dto->internalId);
    }
}
