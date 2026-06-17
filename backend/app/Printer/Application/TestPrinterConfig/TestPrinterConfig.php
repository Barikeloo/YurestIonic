<?php

declare(strict_types=1);

namespace App\Printer\Application\TestPrinterConfig;

use App\Printer\Domain\Exception\PrinterConfigNotFoundException;
use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;
use App\Printer\Domain\Interfaces\PrinterServiceInterface;
use App\Printer\Infrastructure\Printing\EscPosTicketBuilder;

final class TestPrinterConfig
{
    public function __construct(
        private readonly PrinterConfigRepositoryInterface $repository,
        private readonly PrinterServiceInterface $printerService,
        private readonly EscPosTicketBuilder $ticketBuilder,
    ) {}

    public function __invoke(TestPrinterConfigCommand $command): void
    {
        $config = $this->repository->findByUuid($command->uuid)
            ?? throw PrinterConfigNotFoundException::withUuid($command->uuid);

        $bytes = $this->ticketBuilder->buildTest(
            $config->name(),
            $config->paperWidth()->charWidth(),
        );

        $this->printerService->send(
            $config->ip()->value(),
            $config->port()->value(),
            $bytes,
        );
    }
}
