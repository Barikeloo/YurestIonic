<?php

declare(strict_types=1);

namespace App\Printer\Domain\ValueObject;

final readonly class PrinterPaperWidth
{
    private const ALLOWED = [58, 80];

    // Characters per line for each paper width
    private const CHAR_WIDTH = [58 => 32, 80 => 48];

    private function __construct(private int $mm) {}

    public static function create(int $mm): self
    {
        if (!in_array($mm, self::ALLOWED, true)) {
            throw new \InvalidArgumentException("Invalid paper width: {$mm}mm. Allowed: 58, 80.");
        }

        return new self($mm);
    }

    public static function mm80(): self
    {
        return new self(80);
    }

    public function mm(): int
    {
        return $this->mm;
    }

    public function charWidth(): int
    {
        return self::CHAR_WIDTH[$this->mm];
    }
}
