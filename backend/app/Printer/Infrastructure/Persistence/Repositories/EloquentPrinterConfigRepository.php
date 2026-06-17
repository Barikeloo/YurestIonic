<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Persistence\Repositories;

use App\Printer\Domain\Entity\PrinterConfig;
use App\Printer\Domain\Interfaces\PrinterConfigRepositoryInterface;
use App\Printer\Infrastructure\Persistence\Models\EloquentPrinterConfig;
use Illuminate\Support\Facades\DB;

final class EloquentPrinterConfigRepository implements PrinterConfigRepositoryInterface
{
    public function findByUuid(string $uuid): ?PrinterConfig
    {
        $model = EloquentPrinterConfig::where('uuid', $uuid)->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByZoneUuid(string $zoneUuid): ?PrinterConfig
    {
        $printerId = DB::table('zones')
            ->where('uuid', $zoneUuid)
            ->whereNull('deleted_at')
            ->value('printer_config_id');

        if ($printerId === null) {
            return null;
        }

        $model = EloquentPrinterConfig::where('id', $printerId)
            ->where('enabled', true)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findDefaultForRestaurant(int $restaurantId): ?PrinterConfig
    {
        $model = EloquentPrinterConfig::where('restaurant_id', $restaurantId)
            ->where('is_default', true)
            ->where('enabled', true)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findAllForRestaurant(int $restaurantId): array
    {
        return EloquentPrinterConfig::where('restaurant_id', $restaurantId)
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function save(PrinterConfig $config): void
    {
        EloquentPrinterConfig::updateOrCreate(
            ['uuid' => $config->id()->value()],
            [
                'restaurant_id' => $config->restaurantId(),
                'name'          => $config->name(),
                'ip'            => $config->ip()->value(),
                'port'          => $config->port()->value(),
                'paper_width'   => $config->paperWidth()->mm(),
                'enabled'       => $config->isEnabled(),
                'is_default'    => $config->isDefault(),
            ],
        );

        $printerId = EloquentPrinterConfig::where('uuid', $config->id()->value())->value('id');

        if ($printerId !== null) {
            // Clear any zone currently pointing to this printer
            DB::table('zones')->where('printer_config_id', $printerId)->update(['printer_config_id' => null]);

            if ($config->zoneUuid() !== null) {
                DB::table('zones')
                    ->where('uuid', $config->zoneUuid())
                    ->whereNull('deleted_at')
                    ->update(['printer_config_id' => $printerId]);
            }
        }
    }

    public function delete(PrinterConfig $config): void
    {
        EloquentPrinterConfig::where('uuid', $config->id()->value())->delete();
    }

    private function toDomain(EloquentPrinterConfig $model): PrinterConfig
    {
        $zoneUuid = DB::table('zones')
            ->where('printer_config_id', $model->id)
            ->whereNull('deleted_at')
            ->value('uuid');

        return PrinterConfig::fromPersistence(
            id:           $model->uuid,
            restaurantId: (int) $model->restaurant_id,
            name:         $model->name,
            ip:           $model->ip,
            port:         (int) $model->port,
            paperWidth:   (int) $model->paper_width,
            enabled:      (bool) $model->enabled,
            isDefault:    (bool) $model->is_default,
            zoneUuid:     $zoneUuid,
        );
    }
}
