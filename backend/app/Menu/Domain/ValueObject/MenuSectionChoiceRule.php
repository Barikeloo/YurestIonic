<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;

/**
 * Regla de elección por sección: cuántos items debe elegir el comensal.
 *
 * Casos típicos:
 *   - min=1, max=1 → "elige UNO" (lo más común: primer plato, segundo plato)
 *   - min=0, max=1 → "opcional, máximo 1" (ej. postre o café)
 *   - min=1, max=2 → "elige 1 o 2" (raros)
 */
final class MenuSectionChoiceRule
{
    private function __construct(
        private int $min,
        private int $max,
    ) {
        if ($min < 0) {
            throw MenuInvalidConfigurationException::invalidChoiceRule('min must be >= 0');
        }
        if ($max < 1) {
            throw MenuInvalidConfigurationException::invalidChoiceRule('max must be >= 1');
        }
        if ($min > $max) {
            throw MenuInvalidConfigurationException::invalidChoiceRule('min cannot exceed max');
        }
    }

    public static function create(int $min, int $max): self
    {
        return new self($min, $max);
    }

    public static function chooseOne(): self
    {
        return new self(1, 1);
    }

    public function min(): int
    {
        return $this->min;
    }

    public function max(): int
    {
        return $this->max;
    }

    public function isExactlyOne(): bool
    {
        return $this->min === 1 && $this->max === 1;
    }
}
