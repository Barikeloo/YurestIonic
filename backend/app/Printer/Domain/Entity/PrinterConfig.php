<?php

declare(strict_types=1);

namespace App\Printer\Domain\Entity;

use App\Printer\Domain\ValueObject\PrinterIp;
use App\Printer\Domain\ValueObject\PrinterPaperWidth;
use App\Printer\Domain\ValueObject\PrinterPort;
use App\Shared\Domain\ValueObject\Uuid;

class PrinterConfig
{
    private function __construct(
        private Uuid              $id,
        private int               $restaurantId,
        private string            $name,
        private PrinterIp         $ip,
        private PrinterPort       $port,
        private PrinterPaperWidth $paperWidth,
        private bool              $enabled,
        private bool              $isDefault,
        private ?string           $zoneUuid,
    ) {}

    public static function dddCreate(
        int               $restaurantId,
        string            $name,
        PrinterIp         $ip,
        PrinterPort       $port,
        PrinterPaperWidth $paperWidth,
        bool              $enabled,
        bool              $isDefault,
        ?string           $zoneUuid,
    ): self {
        return new self(
            id:           Uuid::generate(),
            restaurantId: $restaurantId,
            name:         $name,
            ip:           $ip,
            port:         $port,
            paperWidth:   $paperWidth,
            enabled:      $enabled,
            isDefault:    $isDefault,
            zoneUuid:     $zoneUuid,
        );
    }

    public static function fromPersistence(
        string            $id,
        int               $restaurantId,
        string            $name,
        string            $ip,
        int               $port,
        int               $paperWidth,
        bool              $enabled,
        bool              $isDefault,
        ?string           $zoneUuid,
    ): self {
        return new self(
            id:           Uuid::create($id),
            restaurantId: $restaurantId,
            name:         $name,
            ip:           PrinterIp::create($ip),
            port:         PrinterPort::create($port),
            paperWidth:   PrinterPaperWidth::create($paperWidth),
            enabled:      $enabled,
            isDefault:    $isDefault,
            zoneUuid:     $zoneUuid,
        );
    }

    public function update(
        string            $name,
        PrinterIp         $ip,
        PrinterPort       $port,
        PrinterPaperWidth $paperWidth,
        bool              $enabled,
        bool              $isDefault,
        ?string           $zoneUuid,
    ): void {
        $this->name      = $name;
        $this->ip        = $ip;
        $this->port      = $port;
        $this->paperWidth = $paperWidth;
        $this->enabled   = $enabled;
        $this->isDefault = $isDefault;
        $this->zoneUuid  = $zoneUuid;
    }

    public function id(): Uuid { return $this->id; }
    public function restaurantId(): int { return $this->restaurantId; }
    public function name(): string { return $this->name; }
    public function ip(): PrinterIp { return $this->ip; }
    public function port(): PrinterPort { return $this->port; }
    public function paperWidth(): PrinterPaperWidth { return $this->paperWidth; }
    public function isEnabled(): bool { return $this->enabled; }
    public function isDefault(): bool { return $this->isDefault; }
    public function zoneUuid(): ?string { return $this->zoneUuid; }
}
