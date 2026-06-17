<?php

declare(strict_types=1);

namespace App\Tables\Application\SaveZoneLayout;

final readonly class SaveZoneLayoutTableDto
{
    public function __construct(
        public string $uuid,
        public int    $posX,
        public int    $posY,
        public int    $width,
        public int    $height,
        public string $shape,
    ) {}
}
