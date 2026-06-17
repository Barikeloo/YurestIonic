<?php

declare(strict_types=1);

namespace App\Printer\Application\DeletePrinterConfig;

use App\Printer\Domain\Exception\PrinterConfigNotFoundException;
use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;

final class DeletePrinterConfig
{
    public function __construct(
        private readonly PrinterConfigRepositoryInterface $repository,
    ) {}

    public function __invoke(DeletePrinterConfigCommand $command): void
    {
        $config = $this->repository->findByUuid($command->uuid)
            ?? throw PrinterConfigNotFoundException::withUuid($command->uuid);

        $this->repository->delete($config);
    }
}
