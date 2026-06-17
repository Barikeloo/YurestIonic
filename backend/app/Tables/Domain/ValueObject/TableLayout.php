<?php

declare(strict_types=1);

namespace App\Tables\Domain\ValueObject;

final readonly class TableLayout
{
    private const CANVAS_MAX_X = 1200;
    private const CANVAS_MAX_Y = 800;
    private const MIN_SIZE     = 20;
    private const MAX_SIZE     = 600;
    private const VALID_SHAPES = ['rect', 'circle'];

    private function __construct(
        public int $posX,
        public int $posY,
        public int $width,
        public int $height,
        public string $shape,
    ) {}

    public static function create(int $posX, int $posY, int $width, int $height, string $shape): self
    {
        if ($posX < 0 || $posX > self::CANVAS_MAX_X) {
            throw new \InvalidArgumentException(
                sprintf('pos_x must be between 0 and %d, got %d.', self::CANVAS_MAX_X, $posX)
            );
        }

        if ($posY < 0 || $posY > self::CANVAS_MAX_Y) {
            throw new \InvalidArgumentException(
                sprintf('pos_y must be between 0 and %d, got %d.', self::CANVAS_MAX_Y, $posY)
            );
        }

        if ($width < self::MIN_SIZE || $width > self::MAX_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('width must be between %d and %d, got %d.', self::MIN_SIZE, self::MAX_SIZE, $width)
            );
        }

        if ($height < self::MIN_SIZE || $height > self::MAX_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('height must be between %d and %d, got %d.', self::MIN_SIZE, self::MAX_SIZE, $height)
            );
        }

        if (! in_array($shape, self::VALID_SHAPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('shape must be one of [%s], got "%s".', implode(', ', self::VALID_SHAPES), $shape)
            );
        }

        return new self($posX, $posY, $width, $height, $shape);
    }

    public function toArray(): array
    {
        return [
            'pos_x'  => $this->posX,
            'pos_y'  => $this->posY,
            'width'  => $this->width,
            'height' => $this->height,
            'shape'  => $this->shape,
        ];
    }
}
