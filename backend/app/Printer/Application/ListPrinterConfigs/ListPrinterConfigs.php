<?php

declare(strict_types=1);

namespace App\Printer\Application\ListPrinterConfigs;

use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;

final class ListPrinterConfigs
{
    public function __construct(
        private readonly PrinterConfigRepositoryInterface $repository,
    ) {}

    public function __invoke(ListPrinterConfigsCommand $command): array
    {
        $configs = $this->repository->findAllForRestaurant($command->restaurantId);

        return array_map(fn ($c) => [
            'uuid'        => $c->id()->value(),
            'name'        => $c->name(),
            'ip'          => $c->ip()->value(),
            'port'        => $c->port()->value(),
            'paper_width' => $c->paperWidth()->mm(),
            'enabled'     => $c->isEnabled(),
            'is_default'  => $c->isDefault(),
            'zone_uuid'   => $c->zoneUuid(),
        ], $configs);
    }
}
