<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetCatalogVersion;

final readonly class GetCatalogVersionResponse
{
    private function __construct(
        public int $version,
    ) {}

    public static function create(int $version): self
    {
        return new self(version: $version);
    }

    public function toArray(): array
    {
        return ['version' => $this->version];
    }
}
