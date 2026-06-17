<?php

declare(strict_types=1);

namespace App\Printer\Application\UpsertPrinterConfig;

use App\Printer\Domain\Entity\PrinterConfig;
use App\Printer\Domain\Exception\PrinterConfigNotFoundException;
use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;
use App\Printer\Domain\ValueObject\PrinterIp;
use App\Printer\Domain\ValueObject\PrinterPaperWidth;
use App\Printer\Domain\ValueObject\PrinterPort;

final class UpsertPrinterConfig
{
    public function __construct(
        private readonly PrinterConfigRepositoryInterface $repository,
    ) {}

    public function __invoke(UpsertPrinterConfigCommand $command): UpsertPrinterConfigResponse
    {
        $ip         = PrinterIp::create($command->ip);
        $port       = PrinterPort::create($command->port);
        $paperWidth = PrinterPaperWidth::create($command->paperWidth);

        if ($command->uuid !== null) {
            $config = $this->repository->findByUuid($command->uuid)
                ?? throw PrinterConfigNotFoundException::withUuid($command->uuid);

            $config->update(
                name:       $command->name,
                ip:         $ip,
                port:       $port,
                paperWidth: $paperWidth,
                enabled:    $command->enabled,
                isDefault:  $command->isDefault,
                zoneUuid:   $command->zoneUuid,
            );
        } else {
            $config = PrinterConfig::create(
                restaurantId: $command->restaurantId,
                name:         $command->name,
                ip:           $ip,
                port:         $port,
                paperWidth:   $paperWidth,
                enabled:      $command->enabled,
                isDefault:    $command->isDefault,
                zoneUuid:     $command->zoneUuid,
            );
        }

        $this->repository->save($config);

        return new UpsertPrinterConfigResponse(
            uuid:       $config->id()->value(),
            name:       $config->name(),
            ip:         $config->ip()->value(),
            port:       $config->port()->value(),
            paperWidth: $config->paperWidth()->mm(),
            enabled:    $config->isEnabled(),
            isDefault:  $config->isDefault(),
            zoneUuid:   $config->zoneUuid(),
        );
    }
}
